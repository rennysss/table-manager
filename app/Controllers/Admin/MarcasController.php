<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MarcaModel;

class MarcasController extends BaseController
{
    protected MarcaModel $model;

    public function __construct()
    {
        $this->model = model(MarcaModel::class);
    }

    public function index()
    {
        return view('admin/marcas/index', [
            'titulo'              => 'Marcas',
            'mostrarTituloTopbar' => false,
            'marcas'              => $this->model->orderBy('nombre', 'ASC')->findAll(),
        ]);
    }

    public function modalCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/marcas');
        }

        return view('admin/marcas/_modal_form', ['marca' => null]);
    }

    public function modalEditar(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/marcas');
        }

        $marca = $this->model->find($id);

        if (! $marca) {
            return $this->response->setStatusCode(404)->setBody('Marca no encontrada.');
        }

        return view('admin/marcas/_modal_form', ['marca' => $marca]);
    }

    public function guardar()
    {
        $rules = [
            'nombre'  => 'required|min_length[2]|max_length[120]',
            'estatus' => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->model->insert([
            'nombre'  => $this->request->getPost('nombre'),
            'estatus' => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Marca creada correctamente.');
    }

    public function actualizar(int $id)
    {
        $marca = $this->model->find($id);

        if (! $marca) {
            return $this->respuestaGuardado(false, ['general' => 'Marca no encontrada.']);
        }

        $rules = [
            'nombre'  => 'required|min_length[2]|max_length[120]',
            'estatus' => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->model->update($id, [
            'nombre'  => $this->request->getPost('nombre'),
            'estatus' => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Marca actualizada.');
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

        return redirect()->to('/admin/marcas')->with('success', $message);
    }
}
