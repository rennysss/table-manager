<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class ElementosController extends BaseController
{
    public function formasMesas()
    {
        return view('admin/elementos/formas_mesas', [
            'titulo' => 'Formas de Mesas',
        ]);
    }

    public function elementosEstructurales()
    {
        return view('admin/elementos/estructurales', [
            'titulo' => 'Elementos Estructurales',
        ]);
    }
}
