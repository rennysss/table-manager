<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        $rol = session()->get('rol');

        return match ($rol) {
            'admin'   => redirect()->to('/admin/sucursales'),
            'gerente' => redirect()->to('/gerente'),
            default   => redirect()->to('/hostess/reservas'),
        };
    }
}
