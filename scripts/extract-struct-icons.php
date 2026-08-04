<?php
/**
 * Extrae iconos del panel Sevenrooms de referencia a PNG individuales.
 * Detecta cada icono de furniture por islas verticales (sin mezclar filas).
 * Ejecutar: php scripts/extract-struct-icons.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$fullPanel = $root . '/public/assets/img/structures/_ref/sevenrooms-panel.jpg';
$outBase = $root . '/public/assets/img/structures';

if (! is_file($fullPanel)) {
    fwrite(STDERR, "No se encontró la imagen de referencia.\n");
    exit(1);
}

/** Umbral: fila con contenido si el píxel más claro de la banda no es blanco puro */
const ROW_CONTENT_LUM = 220;

/** Umbral para incluir el relleno gris del icono al recortar */
const ICON_LUM = 120;

function loadImage(string $path)
{
    $info = @getimagesize($path);
    if ($info === false) {
        return false;
    }

    return match ($info[2]) {
        IMAGETYPE_PNG  => imagecreatefrompng($path),
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_WEBP => imagecreatefromwebp($path),
        default        => false,
    };
}

function pixelLum(int $rgb): float
{
    // Enmascarar canal alpha — imagecolorat puede devolver valores > 24 bits
    $rgb &= 0xFFFFFF;
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;

    return ($r + $g + $b) / 3;
}

function isIconPixel(float $lum): bool
{
    return $lum < ICON_LUM;
}

function rowMinLum($src, int $x1, int $x2, int $y): float
{
    $min = 255.0;
    for ($x = $x1; $x <= $x2; $x++) {
        $min = min($min, pixelLum(imagecolorat($src, $x, $y)));
    }

    return $min;
}

/** Recorta y guarda un tile centrado en canvas cuadrado con fondo transparente */
function saveTile(string $srcPath, int $x, int $y, int $w, int $h, string $destPath, int $size = 96): void
{
    $src = loadImage($srcPath);
    if ($src === false) {
        throw new RuntimeException("No se pudo abrir {$srcPath}");
    }

    $imgW = imagesx($src);
    $imgH = imagesy($src);
    $x = max(0, min($x, $imgW - 1));
    $y = max(0, min($y, $imgH - 1));
    $w = min($w, $imgW - $x);
    $h = min($h, $imgH - $y);

    $tile = imagecreatetruecolor($w, $h);
    imagealphablending($tile, false);
    imagesavealpha($tile, true);
    $trans = imagecolorallocatealpha($tile, 0, 0, 0, 127);
    imagefill($tile, 0, 0, $trans);
    imagecopy($tile, $src, 0, 0, $x, $y, $w, $h);
    imagedestroy($src);

    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, $trans);

    $scale = min(($size - 8) / $w, ($size - 8) / $h);
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $ox = (int) round(($size - $nw) / 2);
    $oy = (int) round(($size - $nh) / 2);

    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $tile, $ox, $oy, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($tile);

    $dir = dirname($destPath);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    imagepng($canvas, $destPath);
    imagedestroy($canvas);
}

/**
 * @return list<array{y1:int,y2:int,cy:int}>
 */
function detectColumnIcons($src, int $x1, int $x2, int $y1, int $y2, int $gap = 3): array
{
    $icons = [];
    $inIcon = false;
    $start = 0;
    $empty = 0;

    for ($y = $y1; $y <= $y2; $y++) {
        $hasContent = rowMinLum($src, $x1, $x2, $y) < ROW_CONTENT_LUM;

        if ($hasContent) {
            if (! $inIcon) {
                $start = $y;
                $inIcon = true;
            }
            $empty = 0;
        } elseif ($inIcon) {
            $empty++;
            if ($empty >= $gap) {
                $end = $y - $empty;
                $icons[] = ['y1' => $start, 'y2' => $end, 'cy' => (int) round(($start + $end) / 2)];
                $inIcon = false;
            }
        }
    }

    if ($inIcon) {
        $icons[] = ['y1' => $start, 'y2' => $y2, 'cy' => (int) round(($start + $y2) / 2)];
    }

    return $icons;
}

/**
 * @return list<array{col:int,x1:int,x2:int,y1:int,y2:int,cy:int}>
 */
function detectFurnitureGrid($src): array
{
    $cols = [
        ['col' => 0, 'x1' => 10, 'x2' => 125],
        ['col' => 1, 'x1' => 138, 'x2' => 265],
        ['col' => 2, 'x1' => 278, 'x2' => 392],
    ];

    $cells = [];
    foreach ($cols as $col) {
        foreach (detectColumnIcons($src, $col['x1'], $col['x2'], 335, 1010) as $icon) {
            $cells[] = [
                'col' => $col['col'],
                'x1'  => $col['x1'],
                'x2'  => $col['x2'],
                'y1'  => $icon['y1'],
                'y2'  => $icon['y2'],
                'cy'  => $icon['cy'],
            ];
        }
    }

    usort($cells, static function (array $a, array $b): int {
        if (abs($a['cy'] - $b['cy']) > 20) {
            return $a['cy'] <=> $b['cy'];
        }

        return $a['col'] <=> $b['col'];
    });

    return $cells;
}

/** Bounding box del icono dentro de una celda */
function iconBoundsInCell($src, int $x1, int $x2, int $y1, int $y2): ?array
{
    $minX = $x2;
    $minY = $y2;
    $maxX = $x1;
    $maxY = $y1;
    $found = false;

    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = $x1; $x <= $x2; $x++) {
            if (isIconPixel(pixelLum(imagecolorat($src, $x, $y)))) {
                $found = true;
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }
    }

    if (! $found) {
        return null;
    }

    $pad = 1;

    return [
        max($x1, $minX - $pad),
        max($y1, $minY - $pad),
        min($x2, $maxX + $pad) - max($x1, $minX - $pad) + 1,
        min($y2, $maxY + $pad) - max($y1, $minY - $pad) + 1,
    ];
}

function saveDetectedIcon(string $srcPath, array $cell, string $destPath): void
{
    $src = loadImage($srcPath);
    if ($src === false) {
        throw new RuntimeException("No se pudo abrir {$srcPath}");
    }

    $bounds = iconBoundsInCell($src, $cell['x1'], $cell['x2'], $cell['y1'], $cell['y2']);
    imagedestroy($src);

    if ($bounds === null) {
        return;
    }

    saveTile($srcPath, $bounds[0], $bounds[1], $bounds[2], $bounds[3], $destPath);
}

/**
 * Detecta las 3 formas básicas de la sección Shapes.
 *
 * @return list<array{id:string,x1:int,x2:int,y1:int,y2:int}>
 */
function detectShapeCells($src): array
{
    $bands = [
        ['id' => 'shape-square', 'x1' => 20, 'x2' => 90],
        ['id' => 'shape-roundrect', 'x1' => 90, 'x2' => 160],
        ['id' => 'shape-circle', 'x1' => 160, 'x2' => 230],
    ];

    $cells = [];
    foreach ($bands as $band) {
        $cells[] = [
            'id'  => $band['id'],
            'x1'  => $band['x1'],
            'x2'  => $band['x2'],
            'y1'  => 170,
            'y2'  => 260,
        ];
    }

    return $cells;
}

// Coordenadas calibradas — labels y shapes
$labels = [
    'label-text'       => [6, 6, 94, 90],
    'label-exit'       => [100, 6, 60, 90],
    'label-headphones' => [154, 6, 50, 90],
    'label-arrow'      => [204, 6, 94, 90],
];

// Orden row-major del panel Sevenrooms (29 iconos detectados)
$furnitureIds = [
    'fur-corner-thick',
    'fur-arc-thin',
    'fur-corner-thin',
    'fur-bench-long',
    'fur-booth-u-wide',
    'fur-sofa-corner-round',
    'fur-sofa-corner-round-2',
    'fur-booth-u-small',
    'fur-chair-round',
    'fur-seat-square',
    'fur-seat-square-2',
    'fur-sofa-rounded-wide',
    'fur-sofa-rounded-wide-2',
    'fur-sofa-l-bl',
    'fur-sofa-l-blocky',
    'fur-corner-sharp',
    'fur-booth-semi',
    'fur-seat-square-3',
    'fur-sofa-wide',
    'fur-sofa-wide-2',
    'fur-sofa-l-tl',
    'fur-seat-round',
    'fur-seat-rect',
    'fur-sofa-armrests',
    'fur-table-rect-4',
    'fur-stairs-straight',
    'fur-stairs-l',
    'fur-piano',
    'fur-table-long',
];

foreach ($labels as $id => $box) {
    saveTile($fullPanel, $box[0], $box[1], $box[2], $box[3], "{$outBase}/labels/{$id}.png");
}

$src = loadImage($fullPanel);
if ($src === false) {
    fwrite(STDERR, "No se pudo cargar el panel.\n");
    exit(1);
}

foreach (detectShapeCells($src) as $shapeCell) {
    saveDetectedIcon($fullPanel, $shapeCell, "{$outBase}/shapes/{$shapeCell['id']}.png");
}

$cells = detectFurnitureGrid($src);
imagedestroy($src);

if (count($cells) !== count($furnitureIds)) {
    fwrite(STDERR, 'Advertencia: detectados ' . count($cells) . ' iconos, esperados ' . count($furnitureIds) . ".\n");
}

$count = min(count($cells), count($furnitureIds));
for ($i = 0; $i < $count; $i++) {
    saveDetectedIcon($fullPanel, $cells[$i], "{$outBase}/furniture/{$furnitureIds[$i]}.png");
}

// Eliminar PNGs huérfanos que ya no están en el catálogo
$valid = array_flip($furnitureIds);
foreach (glob("{$outBase}/furniture/*.png") ?: [] as $file) {
    $id = basename($file, '.png');
    if (! isset($valid[$id])) {
        unlink($file);
    }
}

echo 'Extraídos ' . count($labels) . ' labels, 3 shapes, ' . $count . " furniture.\n";
