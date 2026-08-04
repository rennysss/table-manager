<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;

class LoginController extends BaseController
{
    public function index()
    {
        if (session()->get('usuario_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function authenticate()
    {
        $rules = [
            'usuario'  => 'required|min_length[3]|max_length[80]',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $usuario  = trim($this->request->getPost('usuario'));
        $password = $this->request->getPost('password');

        $model = model(UsuarioModel::class);
        $user  = $model->autenticar($usuario, $password);

        if (! $user) {
            // Retraso anti fuerza bruta
            sleep(1);

            return redirect()->back()->withInput()->with('error', 'Usuario o contraseña incorrectos.');
        }

        session()->regenerate(true);
        session()->set([
            'usuario_id'   => $user['id'],
            'usuario'      => $user['usuario'],
            'nombre'       => $user['nombre'],
            'rol'          => $user['rol'],
            'sucursales'   => $user['sucursales'],
            'sucursal_id'  => $user['sucursales'][0]['id'] ?? null,
        ]);

        return redirect()->to('/dashboard')->with('success', 'Bienvenido, ' . esc($user['nombre']));
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Sesión cerrada correctamente.');
    }
}
