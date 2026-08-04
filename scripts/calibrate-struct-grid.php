<?php
declare(strict_types=1);
$src = imagecreatefromjpeg(dirname(__DIR__) . '/public/assets/img/structures/_ref/sevenrooms-panel.jpg');
$out = dirname(__DIR__) . '/public/assets/img/structures/debug';
foreach ([308, 318, 328, 338, 348] as $baseY) {
    for ($col = 0; $col < 3; $col++) {
        $x = 6 + $col * 132;
        $tile = imagecreatetruecolor(128, 68);
        imagecopy($tile, $src, 0, 0, $x, $baseY, 128, 68);
        imagejpeg($tile, "$out/r0c{$col}-base{$baseY}.jpg", 92);
        imagedestroy($tile);
    }
}
echo "ok\n";
