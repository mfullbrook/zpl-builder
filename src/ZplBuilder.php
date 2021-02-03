<?php

namespace Mfullbrook\ZplBuilder;

class ZplBuilder
{
    protected array $commands;

    protected $dpmm;
    protected $units;
    protected $labelHomeX;
    protected $labelHomeY;

    public $maxPrintWidth;
    public $borderThickness = 2;

    public $x = 0;
    public $y = 0;

    public function configure($dpmm, $units, $maxPrintWidth = null, $width = null, $length = null, $labelHomeX = null, $labelHomeY = null)
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
    }

    public function spawnNestedBuilder(): self
    {
        $z = new self();
        $z->configure($this->dpmm, $this->units, $this->maxPrintWidth);

        return $z;
    }

    public function start()
    {
        // Start the label
        $this->cmd('XA');

        // Set the label home
        if (!is_null($this->labelHomeX) && !is_null($this->labelHomeY)) {
           $this->cmd('LH', $this->dots($this->labelHomeX), $this->dots($this->labelHomeY));
        }

        return $this;
    }

    public function end()
    {
        $this->cmd('XZ');

        return $this;
    }

    public function move($x, $y)
    {
        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    public function rect($x, $y, $width, $height, $thickness = null, $colour = 'B', $roundness = 0)
    {
        if ($width === 'full') {
            $width = $this->maxPrintWidth - $x;
        }

        $this->field($x, $y, [
            ['GB', $this->dots($width), $this->dots($height), $thickness ?? $this->borderThickness, $colour, $roundness],
            ['FS']
        ]);

        return $this;
    }

    public function font()
    {
        $this->cmd('CF');

        return $this;
    }

    public function text(string $message, $fontHeight = null, $x = 0, $y = 0, $font = 0)
    {
        $this->field($x, $y, [
            ['A', $font, $this->dots($fontHeight)],
            ['FD', $message]
        ]);

        return $this;
    }

    public function group($x, $y, $width, $height, $border, $closure)
    {
        $z = $this->spawnNestedBuilder();
        $z->x = $x;
        $z->y = $y;
        $z->maxPrintWidth = $this->maxPrintWidth - $x;

        if ($border) {
            $z->rect(0, 0, $width, $height, $border === true ? null : $border);
        }

        $closure($z);

        $this->commands = array_merge($this->commands, $z->getCommands());

        return $this;
    }

    public function dots($v)
    {
        if (is_null($v)) {
            return null;
        }
        if ($this->units === 'mm') {
            return $this->dpmm * $v;
        } else {
            return $v;
        }
    }

    public function field($x, $y, $commands)
    {
        $field = $this->makeCommand('FO', $this->dots($this->x + $x), $this->dots($this->y + $y));

        foreach ($commands as $definition) {
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

    public function getCommands(): array
    {
        return $this->commands;
    }
}
