<?php

namespace App\Models;

use CodeIgniter\Model;

class FloorplanModel extends Model
{
    protected $table            = 'tm_floorplans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'sucursal_id', 'ambiente_id', 'nombre', 'tipo', 'activo',
        'canvas_json', 'zoom_default',
    ];
    protected $useTimestamps = true;
    protected $deletedField  = 'deleted_at';

    public function activoPorSucursal(int $sucursalId, ?int $ambienteId = null): ?array
    {
        $builder = $this->where('sucursal_id', $sucursalId)->where('activo', 1);

        if ($ambienteId) {
            $builder->where('ambiente_id', $ambienteId);
        }

        return $builder->first();
    }

    public function porSucursal(int $sucursalId): array
    {
        return $this->where('sucursal_id', $sucursalId)
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    /**
     * Lista floorplans de varias sucursales con nombre de sucursal y ambiente.
     */
    public function listarPorSucursales(array $sucursalIds): array
    {
        if ($sucursalIds === []) {
            return [];
        }

        return $this->select('tm_floorplans.*, s.nombre AS sucursal_nombre, a.descripcion AS ambiente_nombre')
            ->join('tm_sucursales s', 's.id = tm_floorplans.sucursal_id')
            ->join('tm_ambientes a', 'a.id = tm_floorplans.ambiente_id', 'left')
            ->whereIn('tm_floorplans.sucursal_id', $sucursalIds)
            ->orderBy('s.nombre', 'ASC')
            ->orderBy('tm_floorplans.nombre', 'ASC')
            ->findAll();
    }

    /**
     * Activa un floorplan y desactiva los demás de la misma sucursal/ambiente.
     */
    public function activar(int $floorplanId, int $sucursalId, ?int $ambienteId): bool
    {
        $this->db->transStart();

        $builder = $this->builder()->where('sucursal_id', $sucursalId);
        if ($ambienteId) {
            $builder->where('ambiente_id', $ambienteId);
        }
        $builder->update(['activo' => 0]);

        $this->update($floorplanId, ['activo' => 1]);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Desactiva un floorplan (sin activar otro automáticamente).
     */
    public function desactivar(int $floorplanId): bool
    {
        return (bool) $this->update($floorplanId, ['activo' => 0]);
    }
}
