<?php

namespace App\Traits;

/**
 * Validación de acceso a sucursales asignadas al gerente.
 */
trait GerenteAccessTrait
{
    protected function sucursalesAsignadas(): array
    {
        return session()->get('sucursales') ?? [];
    }

    protected function sucursalPermitida(int $sucursalId): bool
    {
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
}
