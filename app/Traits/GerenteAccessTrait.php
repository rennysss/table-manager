<?php

namespace App\Traits;

use App\Models\SucursalModel;

/**
 * Validación de acceso a sucursales asignadas al gerente.
 * Administradores ven y operan todas las sucursales activas.
 */
trait GerenteAccessTrait
{
    protected function sucursalesAsignadas(): array
    {
        if ($this->esAdminGlobal()) {
            return model(SucursalModel::class)
                ->select('tm_sucursales.id, tm_sucursales.nombre, tm_sucursales.pais_id, tm_sucursales.ciudad, tm_paises.nombre AS pais_nombre')
                ->join('tm_paises', 'tm_paises.id = tm_sucursales.pais_id', 'left')
                ->where('tm_sucursales.estatus', 'activo')
                ->orderBy('tm_sucursales.nombre', 'ASC')
                ->findAll();
        }

        return session()->get('sucursales') ?? [];
    }

    protected function sucursalPermitida(int $sucursalId): bool
    {
        if ($this->esAdminGlobal()) {
            return $sucursalId > 0 && (bool) model(SucursalModel::class)
                ->where('id', $sucursalId)
                ->where('estatus', 'activo')
                ->first();
        }

        foreach ($this->sucursalesAsignadas() as $s) {
            if ((int) $s['id'] === $sucursalId) {
                return true;
            }
        }

        return false;
    }

    protected function ambientePermitido(int $ambienteId): bool
    {
        return (bool) model(\App\Models\AmbienteModel::class)->find($ambienteId);
    }

    protected function floorplanPermitido(int $floorplanId): bool
    {
        $fp = model(\App\Models\FloorplanModel::class)->find($floorplanId);

        return $fp && $this->sucursalPermitida((int) $fp['sucursal_id']);
    }

    protected function mesaPermitida(int $mesaId): bool
    {
        $mesa = model(\App\Models\MesaModel::class)->find($mesaId);

        if (! $mesa) {
            return false;
        }

        if ($mesa['floorplan_id']) {
            return $this->floorplanPermitido((int) $mesa['floorplan_id']);
        }

        return $this->ambientePermitido((int) $mesa['ambiente_id']);
    }

    protected function denegarAcceso()
    {
        return redirect()->to('/gerente')->with('error', 'No tienes acceso a este recurso.');
    }

    /** Admin opera con alcance global (todas las sucursales activas). */
    protected function esAdminGlobal(): bool
    {
        return session()->get('rol') === 'admin';
    }
}
