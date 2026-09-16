<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservaXetuxOrdenModel extends Model
{
    protected $table            = 'tm_reserva_xetux_ordenes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'reserva_codigo',
        'sucursal_id',
        'order_id',
        'suborder_id',
        'waiter_id',
        'space_id',
        'estatus',
        'payload_create_json',
        'payload_response_json',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;

    public function activaPorCodigo(string $codigo, int $sucursalId): ?array
    {
        return $this->where('reserva_codigo', $codigo)
            ->where('sucursal_id', $sucursalId)
            ->where('estatus', 'activa')
            ->orderBy('id', 'DESC')
            ->first();
    }
}
