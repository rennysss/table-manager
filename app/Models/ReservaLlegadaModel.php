<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservaLlegadaModel extends Model
{
    protected $table            = 'tm_reserva_llegadas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'asignacion_id', 'pax_delta', 'pax_resultante', 'nota', 'registrado_por', 'created_at',
    ];
    protected $useTimestamps = false;

    public function porAsignacion(int $asignacionId): array
    {
        return $this->where('asignacion_id', $asignacionId)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
