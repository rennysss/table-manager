<?php
/**
 * Detecta rejilla del panel Sevenrooms analizando bandas grises de sección.
 */
declare(strict_types=1);

$path = dirname(__DIR__) . '/public/assets/img/structures/_ref/sevenrooms-panel.jpg';
$src = imagecreatefromjpeg($path);
$w = imagesx($src);
$h = imagesy($src);

echo "Panel: {$w}x{$h}\n";

/** Promedio de luminancia en una fila */
function rowLum($img, int $y, int $w): float
{
    $sum = 0;
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $sum += ($r + $g + $b) / 3;
    }

    return $sum / $w;
}

/** Busca inicio de banda gris clara (header de sección) */
$bands = [];
$inBand = false;
$bandStart = 0;
for ($y = 0; $y < $h; $y++) {
    $lum = rowLum($src, $y, $w);
    $isGray = $lum > 210 && $lum < 250;
    if ($isGray && ! $inBand) {
        $inBand = true;
        $bandStart = $y;
    } elseif (! $isGray && $inBand) {
        $inBand = false;
        $bands[] = [$bandStart, $y - 1, $y - $bandStart];
    }
}

echo "Bandas grises (headers):\n";
foreach ($bands as $i => [$ys, $ye, $ht]) {
    echo "  #{$i}: y={$ys}..{$ye} (h={$ht})\n";
}

/** Detectar filas del grid furniture por cambios de contenido en columna central */
$colX = (int) ($w / 2);
$contentRows = [];
$prevDark = false;
for ($y = 0; $y < $h; $y++) {
    $rgb = imagecolorat($src, $colX, $y);
    $r = ($rgb >> 16) & 0xFF;
    $lum = ($r + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) / 3;
    $dark = $lum < 200;
    if ($dark && ! $prevDark) {
        $contentRows[] = $y;
    }
    $prevDark = $dark;
}

echo "\nTransiciones a oscuro en x={$colX}: " . count($contentRows) . " puntos\n";
foreach (array_slice($contentRows, 0, 20) as $y) {
    echo "  y={$y}\n";
}

/** Generar overlay de rejilla para inspección visual */
$overlay = imagecreatetruecolor($w, $h);
imagecopy($overlay, $src, 0, 0, 0, 0, $w, $h);
$red = imagecolorallocate($overlay, 255, 0, 0);
$blue = imagecolorallocate($overlay, 0, 0, 255);
$green = imagecolorallocate($overlay, 0, 200, 0);

// 4 columnas labels
$labelW = (int) floor($w / 4);
for ($c = 0; $c < 4; $c++) {
    $x = $c * $labelW;
    imagerectangle($overlay, $x, 0, $x + $labelW - 1, 95, $red);
}

// 3 columnas shapes/furniture
$colW = (int) floor($w / 3);
for ($c = 0; $c < 3; $c++) {
    $x = $c * $colW;
    imagerectangle($overlay, $x, 100, $x + $colW - 1, 200, $blue);
    imagerectangle($overlay, $x, 220, $x + $colW - 1, $h - 1, $green);
}

$outDir = dirname(__DIR__) . '/public/assets/img/structures/debug';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}
imagejpeg($overlay, "$outDir/grid-overlay.jpg", 92);
imagedestroy($overlay);
imagedestroy($src);

echo "\nOverlay guardado en debug/grid-overlay.jpg\n";
