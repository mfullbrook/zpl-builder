<?php

namespace Mfullbrook\ZplBuilder;

use Illuminate\Support\Arr;

class ZplBuilder
{
    protected array $commands;

    protected $dpmm;
    protected $units;
    protected $labelHomeX;
    protected $labelHomeY;

    public $maxPrintWidth;
    public $borderThickness = 2;

    protected $originX = 0;
    protected $originY = 0;
    public $x = 0;
    public $y = 0;

    public function configure(
        $dpmm, $units, $maxPrintWidth = null,
        $width = null, $length = null,
        $labelHomeX = null, $labelHomeY = null,
        $originX = 0, $originY = 0
    )
    {
        $this->dpmm = $dpmm;
        $this->units = $units;
        $this->maxPrintWidth = $maxPrintWidth;
        if ($width) {
            $this->cmd('PW', $this->dots($width));
        }
        if ($length) {
            $this->cmd('JL', $this->dots($length));
        }
        $this->labelHomeX = $labelHomeX;
        $this->labelHomeY = $labelHomeY;
        $this->setOrigin($originX, $originY);

        return $this;
    }

    public function spawnNestedBuilder($originX, $originY): self
    {
        return (new static)->configure(
            $this->dpmm, $this->units, $this->maxPrintWidth,
            null, null, null, null, $originX, $originY
        );
    }

    public function start()
    {
        // Start the label
        $this->cmd('XA');

        // Set the label home
        if (!is_null($this->labelHomeX) && !is_null($this->labelHomeY)) {
           $this->cmd('LH', $this->dots($this->labelHomeX), $this->dots($this->labelHomeY));
        }

        // Set the label default font and size
        $this->cmd('CF', 0, 3 * 8);

        return $this;
    }

    /**
     * Set the print, slew and backfeed speeds 
     */
    public function printRate($print, $slew = null, $backfeed = null)
    {
        return $this->cmd('PR', $print, $slew, $backfeed);
    }

    public function end()
    {
        $this->cmd('XZ');

        return $this;
    }

    public function move($x, $y)
    {
        $this->x += $x;
        $this->y += $y;

        return $this;
    }

    public function moveDown($y)
    {
        $this->y += $y;

        return $this;
    }

    public function moveTo($x, $y)
    {
        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    public function origin()
    {
        $this->x = 0;
        $this->y = 0;

        return $this;
    }

    public function setOrigin($originX, $originY)
    {
        $this->originX = $originX;
        $this->originY = $originY;

        return $this;
    }

    public function moveOrigin($x, $y)
    {
        $this->originX += $x;
        $this->originY += $y;

        return $this;
    }

    /**
     * @param $x float X position (units)
     * @param $y float Y position (units)
     * @param $width float width (units)
     * @param $height float height (units)
     * @param null $thickness box thicknes (dots!)
     * @param string $colour
     * @param int $roundness
     * @return $this
     */
    public function rect($x, $y, $width, $height, $thickness = null, $colour = 'B', $roundness = 0)
    {
        if ($width === 'full') {
            $width = $this->maxPrintWidth - $x - $this->x;
        }

        if ($thickness === 'fill') {
            $thickness = $this->dots(min($width, $height));
        }

        $this->field($x, $y, [
            ['GB', $this->dots($width), $this->dots($height), $thickness ?? $this->borderThickness, $colour, $roundness],
            ['FS']
        ]);

        return $this;
    }

    public function font($font, $fontHeight = null, $fontWidth = null)
    {
        $this->cmd('A', $font, $this->dots($fontHeight), $this->dots($fontWidth));

        return $this;
    }

    public function text(string $message, $font = null, $x = 0, $y = 0)
    {
        $this->field($x, $y, [
            $this->convertFontArgumentToCommand($font),
            ['FD', $message]
        ]);

        return $this;
    }

    public function reversedText(string $message, $font = null, $x = 0, $y = 0)
    {
        $this->field($x, $y, [
            $this->convertFontArgumentToCommand($font),
            ['FR'],
            ['FD', $message]
        ]);

        return $this;
    }

    public function textBlock(string $message, $width, $lines = 1, $align = 'L', $font = null)
    {
        if ($width === 'full') {
            $width = $this->maxPrintWidth - $this->x;
        }

        $this->field(0, 0, [
            ['FB', $this->dots($width), $lines, 0, $align, 0],
            $this->convertFontArgumentToCommand($font),
            ['FD', $message]
        ]);
        
        return $this;
    }

    protected function convertFontArgumentToCommand($font)
    {
        if ($font) {
            if (strpos($font, ',') !== false) {
                list($font, $fontHeight, $fontWidth) = array_pad(explode(',', $font, 3), 3, null);
            } elseif (is_numeric($font)) {
                $fontHeight = $font;
                $fontWidth = null;
                $font = 0;
            } else {
                $fontHeight = $fontWidth = null;
            }
        }

        return $font === null ? null : ['A', $font, $this->dots($fontHeight), $this->dots($fontWidth)];
    }

    public function group($x, $y, $width, $height, $options, $closure = null)
    {
        if ($closure === null) {
            $closure = $options;
            $options = [];
        } else {
            $options = $this->parseOptions($options);
        }

        $z = $this->spawnNestedBuilder($x, $y);
        $z->maxPrintWidth = $this->maxPrintWidth - $x;


        if (isset($options['border'])) {
            $thickness = $options['border'][0] ?? null;
            $z->rect(0, 0, $width, $height, $thickness);
        }

        $closure($z);

        if ($z->hasCommands()) {
            $this->commands = array_merge($this->commands, $z->getCommands());
        }

        return $this;
    }

    protected function parseOptions($options)
    {
        if (!is_array($options)) {
            if (strpos($options, '|') !== false) {
                $options = explode('|', $options);
            }
        }

        return collect($options)->mapWithKeys(
            function ($option) {
                $parameters = [];
                if (strpos($option, ':') !== false) {
                    [$option, $parameters] = explode(':', $option, 2);
                    $parameters = str_getcsv($parameters);
                }
                return [strtolower(trim($option)) => $parameters];
            }
        )->all();
    }

    public function dots($v)
    {
        if (is_null($v)) {
            return null;
        }
        if ($this->units === 'mm') {
            return round($this->dpmm * $v);
        } else {
            return round($v);
        }
    }

    public function field($x, $y, $commands)
    {
        $field = $this->makeCommand('FO', $this->dots($this->originX + $this->x + $x), $this->dots($this->originY + $this->y + $y));

        foreach (array_filter($commands) as $definition) {
            if (is_string($definition)) {
                $field .= $definition;
            } else {
                $field .= $this->makeCommand($definition[0], ...array_slice($definition, 1));
            }
        }
        $this->commands[] = $field . '^FS';

        return $this;
    }

    public function cmd($name, ...$arguments)
    {
        $this->commands[] = $this->makeCommand($name, ...$arguments);

        return $this;
    }

    protected function makeCommand($name, ...$arguments): string
    {
        $arguments = array_filter($arguments, fn($a) => !is_null($a));

        return "^$name" . (count($arguments) ? implode(',', $arguments) : '');
    }

    public function build(): string
    {
        return implode(PHP_EOL, $this->commands);
    }

    public function hasCommands(): bool
    {
        return isset($this->commands);
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}
