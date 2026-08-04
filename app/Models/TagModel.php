<?php

namespace App\Models;

use CodeIgniter\Model;

class TagModel extends Model
{
    protected $table            = 'tm_tags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['categoria_id', 'nombre', 'color', 'orden', 'estatus'];
    protected $useTimestamps    = false;

    public function activos(): array
    {
        return $this->where('estatus', 'activo')->orderBy('nombre')->findAll();
    }

    /**
     * Contraste de texto sobre el color de fondo del tag.
     */
    public static function colorTexto(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#111111';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luma < 0.55 ? '#FFFFFF' : '#111111';
    }
}
