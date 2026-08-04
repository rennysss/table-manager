<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservaActividadModel extends Model
{
    protected $table            = 'tm_reserva_actividad';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'reserva_codigo',
        'sucursal_id',
        'tipo',
        'descripcion',
        'origen',
        'usuario_id',
        'usuario_nombre',
        'meta_json',
        'externo_id',
        'created_at',
    ];
    protected $useTimestamps = false;

    /**
     * Eventos locales de una reserva (más reciente primero).
     *
     * @return list<array<string, mixed>>
     */
    public function porCodigo(string $codigo, int $limite = 200): array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return [];
        }

        return $this->where('reserva_codigo', $codigo)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limite);
    }
}
