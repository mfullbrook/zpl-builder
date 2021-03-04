<?php

use Mfullbrook\ZplBuilder\ZplBuilder;

require_once(__DIR__ . '/../src/ZplBuilder.php');

$z = new ZplBuilder();
$z->configure(8, 'mm', 100, 102, 130, 2);
$z->start();

$defaultSizes = [3, 5, 8];
$sampleText  = 'Hello';
$fonts = [
    ['0', [4,5,6]],  // 32, 40, 48
    ['0', [3.5, 4.5, 5.5]],
    'A',
    'B',
    'D',
    ['E', [0.75,1,1.5,3]],
    'e24',
    'F',
    'G',
    'J',
    'P',
    'Q',
    ['R', ['3,3', '5,5', '8,8']],
    'S',
    'T',
    'U',
    'V',
];

$y = 0;
foreach ($fonts as $font) {
    if (is_array($font)) {
        [$font, $sizes] = $font;
    } else {
        $sizes = $defaultSizes;
    }
    $z->text($font, null, 0, $y);

    foreach ($sizes as $i => $size) {
        $z->text($sampleText, $font.','.$size, 8 + $i * 30, $y);
    }

    $y += 10;
}

$z->end();

echo $z->build();
