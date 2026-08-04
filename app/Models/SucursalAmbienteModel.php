<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Estado (activo/inactivo) de los ambientes globales para cada sucursal.
 */
class SucursalAmbienteModel extends Model
{
    protected $table            = 'tm_sucursal_ambientes';
    protected $primaryKey       = 'sucursal_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['sucursal_id', 'ambiente_id', 'activo'];
    protected $useTimestamps    = true;

    /**
     * Devuelve un mapa ambiente_id => activo(0|1) con los overrides de la sucursal.
     */
    public function estadoPorSucursal(int $sucursalId): array
    {
        $rows = $this->where('sucursal_id', $sucursalId)->findAll();

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[(int) $row['ambiente_id']] = (int) $row['activo'];
        }

        return $mapa;
    }

    /**
     * IDs de ambientes globales desactivados para la sucursal.
     */
    public function desactivadosPorSucursal(int $sucursalId): array
    {
        $rows = $this->select('ambiente_id')
            ->where('sucursal_id', $sucursalId)
            ->where('activo', 0)
            ->findAll();

        return array_map(static fn ($r) => (int) $r['ambiente_id'], $rows);
    }

    /**
     * Inserta o actualiza el estado de un ambiente global para una sucursal.
     * Usa el query builder directo por la llave primaria compuesta.
     */
    public function setActivo(int $sucursalId, int $ambienteId, bool $activo): void
    {
        $now    = date('Y-m-d H:i:s');
        $existe = $this->builder()
            ->where('sucursal_id', $sucursalId)
            ->where('ambiente_id', $ambienteId)
            ->countAllResults();

        if ($existe > 0) {
            $this->builder()
                ->where('sucursal_id', $sucursalId)
                ->where('ambiente_id', $ambienteId)
                ->update(['activo' => $activo ? 1 : 0, 'updated_at' => $now]);
            return;
        }

        $this->builder()->insert([
            'sucursal_id' => $sucursalId,
            'ambiente_id' => $ambienteId,
            'activo'      => $activo ? 1 : 0,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }
}
