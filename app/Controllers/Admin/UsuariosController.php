<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PaisModel;
use App\Models\SucursalModel;
use App\Models\UsuarioModel;

class UsuariosController extends BaseController
{
    protected UsuarioModel $model;

    public function __construct()
    {
        $this->model = model(UsuarioModel::class);
    }

    public function index()
    {
        $usuarios = $this->model->orderBy('nombre', 'ASC')->findAll();

        foreach ($usuarios as &$u) {
            unset($u['password']);
        }

        return view('admin/usuarios/index', [
            'titulo'              => 'Usuarios',
            'mostrarTituloTopbar' => false,
            'usuarios'            => $usuarios,
        ]);
    }

    public function modalCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/usuarios');
        }

        return view('admin/usuarios/_modal_form', $this->datosFormulario(null));
    }

    public function modalEditar(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/usuarios');
        }

        $usuario = $this->model->find($id);

        if (! $usuario) {
            return $this->response->setStatusCode(404)->setBody('Usuario no encontrado.');
        }

        unset($usuario['password']);

        return view('admin/usuarios/_modal_form', $this->datosFormulario($usuario));
    }

    public function modalSucursales(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/usuarios');
        }

        $usuario = $this->model->find($id);

        if (! $usuario) {
            return $this->response->setStatusCode(404)->setBody('Usuario no encontrado.');
        }

        unset($usuario['password']);
        $usuario['sucursales'] = $this->model->obtenerSucursales($id);

        return view('admin/usuarios/_modal_sucursales', [
            'usuario' => $usuario,
            'paises'  => model(PaisModel::class)->activos(),
        ]);
    }

    /** JSON — ciudades con sucursales activas en un país */
    public function ciudadesPorPais(int $paisId)
    {
        $ciudades = model(SucursalModel::class)->ciudadesActivasPorPais($paisId);

        return $this->response->setJSON([
            'success' => true,
            'ciudades' => $ciudades,
        ]);
    }

    /** JSON — sucursales activas por país y ciudad */
    public function sucursalesPorCiudad()
    {
        $paisId = (int) $this->request->getGet('pais_id');
        $ciudad = trim((string) $this->request->getGet('ciudad'));

        if ($paisId <= 0 || $ciudad === '') {
            return $this->response->setJSON(['success' => true, 'sucursales' => []]);
        }

        $sucursales = model(SucursalModel::class)->activasPorPaisYCiudad($paisId, $ciudad);

        return $this->response->setJSON([
            'success' => true,
            'sucursales' => array_map(static fn ($s) => [
                'id'          => (int) $s['id'],
                'nombre'      => $s['nombre'],
                'ciudad'      => $s['ciudad'],
                'pais_nombre' => $s['pais_nombre'] ?? '',
            ], $sucursales),
        ]);
    }

    public function guardarSucursales(int $id)
    {
        $user = $this->model->find($id);

        if (! $user) {
            return $this->respuestaGuardado(false, ['general' => 'Usuario no encontrado.']);
        }

        $this->asignarSucursales($id);

        return $this->respuestaGuardado(true, null, 'Accesos a sucursales actualizados.');
    }

    public function guardar()
    {
        $rules = [
            'usuario'  => 'required|min_length[3]|max_length[80]|is_unique[tm_usuarios.usuario]',
            'nombre'   => 'required|min_length[2]|max_length[150]',
            'email'    => 'permit_empty|valid_email|max_length[150]',
            'password' => 'required|min_length[8]',
            'rol'      => 'required|in_list[admin,gerente,hostess]',
            'estatus'  => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $id = $this->model->insert([
            'usuario'  => $this->request->getPost('usuario'),
            'email'    => $this->request->getPost('email') ?: null,
            'nombre'   => $this->request->getPost('nombre'),
            'password' => $this->model->hashPassword($this->request->getPost('password')),
            'rol'      => $this->request->getPost('rol'),
            'estatus'  => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Usuario creado correctamente.');
    }

    public function actualizar(int $id)
    {
        $user = $this->model->find($id);

        if (! $user) {
            return $this->respuestaGuardado(false, ['general' => 'Usuario no encontrado.']);
        }

        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[150]',
            'email'    => 'permit_empty|valid_email|max_length[150]',
            'password' => 'permit_empty|min_length[8]',
            'rol'      => 'required|in_list[admin,gerente,hostess]',
            'estatus'  => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $datos = [
            'nombre'  => $this->request->getPost('nombre'),
            'email'   => $this->request->getPost('email') ?: null,
            'rol'     => $this->request->getPost('rol'),
            'estatus' => $this->request->getPost('estatus'),
        ];

        $password = $this->request->getPost('password');
        if ($password) {
            $datos['password'] = $this->model->hashPassword($password);
        }

        $this->model->update($id, $datos);

        return $this->respuestaGuardado(true, null, 'Usuario actualizado.');
    }

    public function eliminar(int $id)
    {
        if ((int) $id === (int) session()->get('usuario_id')) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'No puedes eliminar tu propio usuario.',
            ]);
        }

        $user = $this->model->find($id);

        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ]);
        }

        $this->model->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuario eliminado.',
            ]);
        }

        return redirect()->to('/admin/usuarios')->with('success', 'Usuario eliminado.');
    }

    /** Datos compartidos para el modal crear/editar */
    private function datosFormulario(?array $usuario): array
    {
        return [
            'usuario' => $usuario,
        ];
    }

    private function asignarSucursales(int $usuarioId): void
    {
        $sucursales = $this->request->getPost('sucursales') ?? [];
        $db         = \Config\Database::connect();

        $db->table('tm_usuario_sucursales')->where('usuario_id', $usuarioId)->delete();

        foreach ($sucursales as $sid) {
            $db->table('tm_usuario_sucursales')->insert([
                'usuario_id'  => $usuarioId,
                'sucursal_id' => (int) $sid,
            ]);
        }
    }

    /** Respuesta JSON para formularios AJAX o redirect legacy */
    private function respuestaGuardado(bool $ok, ?array $errors = null, ?string $message = null)
    {
        if ($this->request->isAJAX()) {
            if (! $ok) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'errors'  => $errors,
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
            ]);
        }

        if (! $ok) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        return redirect()->to('/admin/usuarios')->with('success', $message);
    }
}
