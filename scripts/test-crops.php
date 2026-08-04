<?php
declare(strict_types=1);

$src = imagecreatefromjpeg(dirname(__DIR__) . '/public/assets/img/structures/_ref/sevenrooms-panel.jpg');
$out = dirname(__DIR__) . '/public/assets/img/structures/debug';

function crop($src, $x, $y, $w, $h, $name) {
    global $out;
    $tile = imagecreatetruecolor($w, $h);
    imagecopy($tile, $src, 0, 0, $x, $y, $w, $h);
    imagejpeg($tile, "$out/$name", 95);
    imagedestroy($tile);
}

foreach ([
    ['label-exit', 108, 10, 78, 80],
    ['shape-square', 10, 172, 115, 42],
    ['shape-circle', 273, 172, 115, 42],
    ['fur-r0c1', 138, 328, 122, 68],
    ['fur-r1c0', 8, 396, 122, 62],
] as [$name, $x, $y, $w, $h]) {
    crop($src, $x, $y, $w, $h, "v5-$name.jpg");
}

echo "ok\n";
