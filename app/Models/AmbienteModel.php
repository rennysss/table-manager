<?php

namespace App\Models;

use CodeIgniter\Model;

class AmbienteModel extends Model
{
    protected $table            = 'tm_ambientes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['sucursal_id', 'descripcion', 'estatus'];
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    public function listarTodos(): array
    {
        return $this->orderBy('descripcion', 'ASC')->findAll();
    }

    /**
     * Ambientes disponibles para una sucursal: los globales (sucursal_id NULL)
     * más los propios de la sucursal. Excluye los globales desactivados para
     * esa sucursal cuando $soloActivos es true.
     */
    public function paraSucursal(int $sucursalId, bool $soloActivos = true): array
    {
        $builder = $this->groupStart()
                ->where('sucursal_id', null)
                ->orWhere('sucursal_id', $sucursalId)
            ->groupEnd();

        if ($soloActivos) {
            $builder->where('estatus', 'activo');
        }

        $ambientes = $builder->orderBy('sucursal_id', 'ASC')
            ->orderBy('descripcion', 'ASC')
            ->findAll();

        if (! $soloActivos) {
            return $ambientes;
        }

        // Quita los globales desactivados específicamente para esta sucursal
        $desactivados = model(SucursalAmbienteModel::class)->desactivadosPorSucursal($sucursalId);

        if ($desactivados === []) {
            return $ambientes;
        }

        return array_values(array_filter($ambientes, static function ($a) use ($desactivados) {
            $esGlobal = $a['sucursal_id'] === null;
            return ! ($esGlobal && in_array((int) $a['id'], $desactivados, true));
        }));
    }

    /**
     * Ambientes para el editor por sucursal: globales (cualquier estatus) y
     * propios de la sucursal, con su estado aplicado por sucursal.
     */
    public function listadoEditorSucursal(int $sucursalId): array
    {
        $ambientes = $this->groupStart()
                ->where('sucursal_id', null)
                ->orWhere('sucursal_id', $sucursalId)
            ->groupEnd()
            ->orderBy('sucursal_id', 'ASC')
            ->orderBy('descripcion', 'ASC')
            ->findAll();

        $overrides = model(SucursalAmbienteModel::class)->estadoPorSucursal($sucursalId);

        foreach ($ambientes as &$a) {
            $a['es_global'] = $a['sucursal_id'] === null;

            if ($a['es_global']) {
                // Por defecto activo, salvo override de la sucursal
                $a['activo_sucursal'] = array_key_exists((int) $a['id'], $overrides)
                    ? (bool) $overrides[(int) $a['id']]
                    : ($a['estatus'] === 'activo');
            } else {
                $a['activo_sucursal'] = $a['estatus'] === 'activo';
            }
        }

        return $ambientes;
    }

    public function listarActivos(): array
    {
        return $this->where('estatus', 'activo')
            ->orderBy('descripcion', 'ASC')
            ->findAll();
    }

    /**
     * Opciones para selects en formularios.
     */
    public function opcionesSelect(bool $soloActivos = true): array
    {
        $builder = $this->select('id, descripcion, estatus')
            ->orderBy('descripcion', 'ASC');

        if ($soloActivos) {
            $builder->where('estatus', 'activo');
        }

        return $builder->findAll();
    }
}
