<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Models\SucursalAmbienteModel;
use App\Models\SucursalModel;

class AmbientesController extends BaseController
{
    protected AmbienteModel $model;

    public function __construct()
    {
        $this->model = model(AmbienteModel::class);
    }

    public function index()
    {
        return view('admin/ambientes/index', [
            'titulo'              => 'Ambientes',
            'mostrarTituloTopbar' => false,
            'ambientes'           => $this->model->listarTodos(),
        ]);
    }

    public function modalCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/ambientes');
        }

        return view('admin/ambientes/_modal_form', ['ambiente' => null]);
    }

    public function modalEditar(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/ambientes');
        }

        $ambiente = $this->model->find($id);

        if (! $ambiente) {
            return $this->response->setStatusCode(404)->setBody('Ambiente no encontrado.');
        }

        return view('admin/ambientes/_modal_form', ['ambiente' => $ambiente]);
    }

    public function guardar()
    {
        $rules = [
            'descripcion' => 'required|min_length[2]|max_length[150]',
            'estatus'     => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->model->insert([
            'descripcion' => $this->request->getPost('descripcion'),
            'estatus'     => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Ambiente creado correctamente.');
    }

    public function actualizar(int $id)
    {
        $ambiente = $this->model->find($id);

        if (! $ambiente) {
            return $this->respuestaGuardado(false, ['general' => 'Ambiente no encontrado.']);
        }

        $rules = [
            'descripcion' => 'required|min_length[2]|max_length[150]',
            'estatus'     => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->model->update($id, [
            'descripcion' => $this->request->getPost('descripcion'),
            'estatus'     => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Ambiente actualizado.');
    }

    /**
     * Editor de ambientes por sucursal (mismo diseño que Mesas).
     * Lista los globales (con su estado por sucursal) y los propios de la sucursal.
     */
    public function porSucursal(int $sucursalId)
    {
        $sucursal = model(SucursalModel::class)->find($sucursalId);

        if (! $sucursal) {
            return redirect()->to('/admin/sucursales')->with('error', 'Sucursal no encontrada.');
        }

        return view('admin/ambientes/por_sucursal', [
            'titulo'    => 'Ambientes',
            'sucursal'  => $sucursal,
            'ambientes' => $this->model->listadoEditorSucursal($sucursalId),
        ]);
    }

    /**
     * Guardado por lote del editor por sucursal:
     * - globales: estado (activo/inactivo) propio de la sucursal.
     * - propios: alta/edición de descripción y estatus; eliminación.
     * Recibe JSON: { globales: [{ambiente_id, activo}], propios: [{id?, descripcion, activo}], deleted: [ids] }.
     */
    public function guardarLoteSucursal(int $sucursalId)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to("/admin/sucursales/{$sucursalId}/ambientes");
        }

        if (! model(SucursalModel::class)->find($sucursalId)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'errors'  => ['Sucursal no encontrada.'],
            ]);
        }

        $payload  = $this->request->getJSON(true) ?? [];
        $globales = $payload['globales'] ?? [];
        $propios  = $payload['propios'] ?? [];
        $deleted  = $payload['deleted'] ?? [];

        // Validación de los ambientes propios (nuevos o editados)
        $errores = [];
        foreach ($propios as $i => $p) {
            $descripcion = trim((string) ($p['descripcion'] ?? ''));
            if (mb_strlen($descripcion) < 2) {
                $errores[] = 'Fila ' . ($i + 1) . ': la descripción es obligatoria (mín. 2 caracteres).';
            }
        }

        if (! empty($errores)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => array_values(array_unique($errores)),
            ]);
        }

        $estadoModel = model(SucursalAmbienteModel::class);

        $db = db_connect();
        $db->transStart();

        // Estado por sucursal de los ambientes globales
        foreach ($globales as $g) {
            $ambienteId = (int) ($g['ambiente_id'] ?? 0);
            if ($ambienteId <= 0) {
                continue;
            }
            $ambiente = $this->model->find($ambienteId);
            if ($ambiente && $ambiente['sucursal_id'] === null) {
                $estadoModel->setActivo($sucursalId, $ambienteId, ! empty($g['activo']));
            }
        }

        // Eliminar ambientes propios de la sucursal
        foreach ($deleted as $delId) {
            $delId = (int) $delId;
            if ($delId <= 0) {
                continue;
            }
            $ambiente = $this->model->find($delId);
            if ($ambiente && (int) $ambiente['sucursal_id'] === $sucursalId) {
                $this->model->delete($delId);
            }
        }

        // Alta / edición de ambientes propios
        foreach ($propios as $p) {
            $id          = isset($p['id']) && $p['id'] !== '' ? (int) $p['id'] : null;
            $descripcion = trim((string) ($p['descripcion'] ?? ''));
            $estatus     = ! empty($p['activo']) ? 'activo' : 'inactivo';

            $datos = [
                'descripcion' => $descripcion,
                'estatus'     => $estatus,
                'sucursal_id' => $sucursalId,
            ];

            $existente = $id !== null ? $this->model->find($id) : null;

            if ($existente && (int) $existente['sucursal_id'] === $sucursalId) {
                $this->model->update($id, $datos);
            } else {
                $this->model->insert($datos);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'errors'  => ['No se pudieron guardar los cambios. Intenta de nuevo.'],
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Ambientes guardados correctamente.',
        ]);
    }

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

        return redirect()->to('/admin/ambientes')->with('success', $message);
    }
}
