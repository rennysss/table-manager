<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ReservasApiService;

class ReservasController extends BaseController
{
    public function index()
    {
        $fecha      = $this->request->getGet('fecha') ?? date('Y-m-d');
        $sucursalId = (int) ($this->request->getGet('sucursal_id') ?? session()->get('sucursal_id'));

        $reservas = (new ReservasApiService())->obtenerPorFecha($sucursalId, $fecha);

        return $this->response->setJSON(['data' => $reservas, 'fecha' => $fecha]);
    }

    public function show(string $codigo)
    {
        $sucursalId = (int) ($this->request->getGet('sucursal_id') ?? session()->get('sucursal_id'));
        $servicio   = new ReservasApiService();
        $reserva    = $servicio->obtenerPorCodigo($codigo, $sucursalId);

        if (! $reserva) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'No encontrada']);
        }

        return $this->response->setJSON($reserva);
    }
}
