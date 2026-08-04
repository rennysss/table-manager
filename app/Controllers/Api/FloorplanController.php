<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\FloorplanModel;
use App\Models\MesaModel;
use App\Models\ReservaAsignacionModel;

class FloorplanController extends BaseController
{
    public function activo()
    {
        $sucursalId = (int) ($this->request->getGet('sucursal_id') ?? session()->get('sucursal_id'));
        $ambienteId = $this->request->getGet('ambiente_id') ? (int) $this->request->getGet('ambiente_id') : null;
        $fecha      = $this->request->getGet('fecha') ?? date('Y-m-d');

        $floorplan = model(FloorplanModel::class)->activoPorSucursal($sucursalId, $ambienteId);

        if (! $floorplan) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sin floorplan activo']);
        }

        $mesas   = model(MesaModel::class)->porFloorplan((int) $floorplan['id']);
        $estados = model(ReservaAsignacionModel::class)->estadosMesas($sucursalId, $fecha);
        $aforo   = model(MesaModel::class)->calcularAforo((int) $floorplan['id']);

        foreach ($mesas as &$m) {
            $m['estado']  = $estados[$m['id']]['estado_mesa'] ?? 'libre';
            $m['reserva'] = $estados[$m['id']] ?? null;
        }

        return $this->response->setJSON([
            'floorplan' => $floorplan,
            'mesas'     => $mesas,
            'aforo'     => $aforo,
        ]);
    }
}
