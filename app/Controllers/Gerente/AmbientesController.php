<?php

namespace App\Controllers\Gerente;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Traits\GerenteAccessTrait;

class AmbientesController extends BaseController
{
    use GerenteAccessTrait;

    public function index(int $sucursalId)
    {
        if (! $this->sucursalPermitida($sucursalId)) {
            return $this->denegarAcceso();
        }

        return redirect()->to('/admin/ambientes');
    }
}
