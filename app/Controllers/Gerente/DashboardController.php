<?php

namespace App\Controllers\Gerente;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Models\FloorplanModel;
use App\Models\MesaModel;
use App\Traits\GerenteAccessTrait;

class DashboardController extends BaseController
{
    use GerenteAccessTrait;

    public function index()
    {
        $sucursales = $this->sucursalesAsignadas();
        $resumen    = [];

        $ambientesGlobales = model(AmbienteModel::class)->listarActivos();

        foreach ($sucursales as $s) {
            $sid = (int) $s['id'];
            $fp  = model(FloorplanModel::class)->activoPorSucursal($sid);

            $resumen[] = [
                'sucursal'    => $s,
                'ambientes'   => $ambientesGlobales,
                'floorplan'   => $fp,
                'aforo'       => $fp ? model(MesaModel::class)->calcularAforo((int) $fp['id']) : 0,
                'total_mesas' => count(model(MesaModel::class)->porSucursal($sid)),
            ];
        }

        return view('gerente/dashboard', [
            'titulo'  => 'Panel Gerente',
            'resumen' => $resumen,
        ]);
    }
}
