<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;

class AuthController extends BaseController
{
    public function login()
    {
        $json = $this->request->getJSON(true);

        if (empty($json['usuario']) || empty($json['password'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Credenciales requeridas']);
        }

        $user = model(UsuarioModel::class)->autenticar($json['usuario'], $json['password']);

        if (! $user) {
            sleep(1);

            return $this->response->setStatusCode(401)->setJSON(['error' => 'Credenciales inválidas']);
        }

        session()->regenerate(true);
        session()->set([
            'usuario_id'  => $user['id'],
            'usuario'     => $user['usuario'],
            'nombre'      => $user['nombre'],
            'rol'         => $user['rol'],
            'sucursales'  => $user['sucursales'],
            'sucursal_id' => $user['sucursales'][0]['id'] ?? null,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'user'    => [
                'id'         => $user['id'],
                'nombre'     => $user['nombre'],
                'rol'        => $user['rol'],
                'sucursales' => $user['sucursales'],
            ],
        ]);
    }
}
