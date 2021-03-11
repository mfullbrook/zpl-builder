<?php

namespace Mfullbrook\ZplBuilder;

class FontZeroWidthEstimator
{
    protected static $charSizes = [
        32 => 305,
        33 => 287,
        34 => 471,
        35 => 471,
        36 => 471,
        37 => 890,
        38 => 598,
        39 => 287,
        40 => 293,
        41 => 293,
        42 => 471,
        43 => 847,
        44 => 325,
        45 => 1500,
        46 => 293,
        47 => 293,
        48 => 480,
        49 => 480,
        50 => 480,
        51 => 480,
        52 => 480,
        53 => 480,
        54 => 480,
        55 => 480,
        56 => 480,
        57 => 480,
        58 => 293,
        59 => 293,
        60 => 986,
        61 => 890,
        62 => 986,
        63 => 432,
        64 => 890,
        65 => 547,
        66 => 547,
        67 => 527,
        68 => 585,
        69 => 489,
        70 => 515,
        71 => 585,
        72 => 598,
        73 => 267,
        74 => 432,
        75 => 547,
        76 => 471,
        77 => 742,
        78 => 598,
        79 => 560,
        80 => 547,
        81 => 560,
        82 => 585,
        83 => 522,
        84 => 489,
        85 => 598,
        86 => 522,
        87 => 795,
        88 => 547,
        89 => 547,
        90 => 489,
        92 => 293,
        97 => 452,
        98 => 489,
        99 => 432,
        100 => 489,
        101 => 470,
        102 => 274,
        103 => 489,
        104 => 489,
        105 => 254,
        106 => 254,
        107 => 432,
        108 => 254,
        109 => 742,
        110 => 489,
        111 => 471,
        112 => 489,
        113 => 489,
        114 => 325,
        115 => 420,
        116 => 267,
        117 => 489,
        118 => 432,
        119 => 657,
        120 => 432,
        121 => 432,
        122 => 381,
        123 => 489,
        124 => 489,
        125 => 489,
    ];

    /**
     * Function returns the estimated total length of the string in millimeteres
     */
    public static function calculateStringLength(string $text, $fontSizeMm, $dpmm = 8): int
    {
        if ($text === '') {
            return 0;
        }
        $length = 0;

        /**
         * Sizes in $charSizes are of Font Zero at 72 points on a 300dpi printer.
         *
         * 1 inch = 25.4mm
         * 8 dpmm * 25.4 = 203.2 DPI
         *
         * 203.2 / 300 = factor to convert resolution.
         *
         * 72 points = 25.4 mm
         *
         * target font size 5mm
         */

        $resolutionRatio = ($dpmm * 25.4) / 300;
        $fontSizeRatio = $fontSizeMm / 25.4;
        $applyRatios = fn($x) => round($x * $resolutionRatio * $fontSizeRatio);

        for ($i = 0; $i < strlen($text); $i++) {
            $charCode = ord(substr($text, $i, 1));
            if ($charCode >= 32 && $charCode <= 125) {
                $length += $applyRatios(self::$charSizes[$charCode]);
            } else {
                $length += $applyRatios(833);
            }
        }

        return $length / $dpmm * 0.3;
    }

    public static function calculateLines(string $text, $lineLength, $fontSizeMm, $dpmm = 8)
    {
        $mm = static::calculateStringLength($text, $fontSizeMm, $dpmm);

        return ceil($mm / $lineLength);
    }
}


