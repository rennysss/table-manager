<?php

namespace App\Controllers\Gerente;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Models\MesaModel;
use App\Traits\GerenteAccessTrait;

class MesasController extends BaseController
{
    use GerenteAccessTrait;

    public function index(int $ambienteId)
    {
        if (! $this->ambientePermitido($ambienteId)) {
            return $this->denegarAcceso();
        }

        $ambiente = model(AmbienteModel::class)->find($ambienteId);

        return view('gerente/mesas/index', [
            'titulo'   => 'Mesas',
            'ambiente' => $ambiente,
            'mesas'    => model(MesaModel::class)->porAmbiente($ambienteId),
        ]);
    }

    /**
     * Gerente puede modificar pax/min/max/estatus, nunca el número de mesa.
     */
    public function actualizar(int $mesaId)
    {
        if (! $this->mesaPermitida($mesaId)) {
            return redirect()->back()->with('error', 'Acceso denegado.');
        }

        $mesa = model(MesaModel::class)->find($mesaId);

        $rules = [
            'pax'     => 'required|integer|greater_than[0]',
            'minimo'  => 'required|integer|greater_than[0]',
            'maximo'  => 'required|integer|greater_than_equal_to[{minimo}]',
            'estatus' => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        model(MesaModel::class)->update($mesaId, [
            'pax'     => (int) $this->request->getPost('pax'),
            'minimo'  => (int) $this->request->getPost('minimo'),
            'maximo'  => (int) $this->request->getPost('maximo'),
            'estatus' => $this->request->getPost('estatus'),
        ]);

        return redirect()->to("/gerente/ambientes/{$mesa['ambiente_id']}/mesas")
            ->with('success', 'Mesa ' . $mesa['numero'] . ' actualizada. Las reservas asignadas no se modificaron.');
    }
}
