<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EstadoRegionModel;
use App\Models\PaisModel;

class CatalogoGeograficoController extends BaseController
{
    protected EstadoRegionModel $model;

    public function __construct()
    {
        $this->model = model(EstadoRegionModel::class);
    }

    public function index()
    {
        return view('admin/catalogo_geografico/index', [
            'titulo'              => 'Geografía',
            'mostrarTituloTopbar' => false,
            'paises'              => model(PaisModel::class)->activos(),
            'estados'             => $this->model->listarConPais(),
        ]);
    }

    /** JSON — estados activos de un país (para select dinámico en sucursales) */
    public function estadosPorPais(int $paisId)
    {
        $estados = $this->model->porPais($paisId);

        return $this->response->setJSON([
            'success' => true,
            'estados' => array_map(static fn ($e) => [
                'id'     => (int) $e['id'],
                'nombre' => $e['nombre'],
            ], $estados),
        ]);
    }

    public function modalCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/catalogo-geografico');
        }

        return view('admin/catalogo_geografico/_modal_form', [
            'estado' => null,
            'paises' => model(PaisModel::class)->activos(),
        ]);
    }

    public function modalEditar(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/catalogo-geografico');
        }

        $estado = $this->model->find($id);

        if (! $estado) {
            return $this->response->setStatusCode(404)->setBody('Estado no encontrado.');
        }

        return view('admin/catalogo_geografico/_modal_form', [
            'estado' => $estado,
            'paises' => model(PaisModel::class)->activos(),
        ]);
    }

    public function guardar()
    {
        $rules = [
            'pais_id' => 'required|is_natural_no_zero',
            'nombre'  => 'required|min_length[2]|max_length[120]',
            'estatus' => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->model->insert([
            'pais_id' => (int) $this->request->getPost('pais_id'),
            'nombre'  => $this->request->getPost('nombre'),
            'estatus' => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Estado registrado correctamente.');
    }

    public function actualizar(int $id)
    {
        $estado = $this->model->find($id);

        if (! $estado) {
            return $this->respuestaGuardado(false, ['general' => 'Estado no encontrado.']);
        }

        $rules = [
            'pais_id' => 'required|is_natural_no_zero',
            'nombre'  => 'required|min_length[2]|max_length[120]',
            'estatus' => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->model->update($id, [
            'pais_id' => (int) $this->request->getPost('pais_id'),
            'nombre'  => $this->request->getPost('nombre'),
            'estatus' => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Estado actualizado.');
    }

    public function eliminar(int $id)
    {
        $this->model->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => 'Estado eliminado.']);
        }

        return redirect()->to('/admin/catalogo-geografico')->with('success', 'Estado eliminado.');
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

        return redirect()->to('/admin/catalogo-geografico')->with('success', $message);
    }
}
