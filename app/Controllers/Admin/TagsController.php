<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TagCategoriaModel;
use App\Models\TagModel;

class TagsController extends BaseController
{
    protected TagModel $model;
    protected TagCategoriaModel $categorias;

    public function __construct()
    {
        $this->model      = model(TagModel::class);
        $this->categorias = model(TagCategoriaModel::class);
    }

    public function index()
    {
        return view('admin/tags/index', [
            'titulo'              => 'Tags',
            'mostrarTituloTopbar' => false,
            'categorias'          => $this->categorias->conTags(),
        ]);
    }

    public function modalCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/tags');
        }

        $categoriaId = (int) ($this->request->getGet('categoria_id') ?? 0);

        return view('admin/tags/_modal_form', [
            'tag'         => null,
            'categoriaId' => $categoriaId,
            'categorias'  => $this->categorias->orderBy('orden')->orderBy('nombre')->findAll(),
        ]);
    }

    public function modalEditar(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/tags');
        }

        $tag = $this->model->find($id);

        if (! $tag) {
            return $this->response->setStatusCode(404)->setBody('Tag no encontrado.');
        }

        return view('admin/tags/_modal_form', [
            'tag'         => $tag,
            'categoriaId' => (int) ($tag['categoria_id'] ?? 0),
            'categorias'  => $this->categorias->orderBy('orden')->orderBy('nombre')->findAll(),
        ]);
    }

    public function modalCategoriaCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/tags');
        }

        $dominio = (string) ($this->request->getGet('dominio') ?? 'reserva');
        if (! in_array($dominio, ['cliente', 'reserva'], true)) {
            $dominio = 'reserva';
        }

        return view('admin/tags/_modal_categoria', [
            'categoria'      => null,
            'dominioDefault' => $dominio,
        ]);
    }

    public function modalCategoriaEditar(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/tags');
        }

        $categoria = $this->categorias->find($id);

        if (! $categoria) {
            return $this->response->setStatusCode(404)->setBody('Categoría no encontrada.');
        }

        return view('admin/tags/_modal_categoria', ['categoria' => $categoria]);
    }

    public function guardar()
    {
        $rules = [
            'categoria_id' => 'required|is_natural_no_zero',
            'nombre'       => 'required|min_length[1]|max_length[50]',
            'color'        => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'estatus'      => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $categoriaId = (int) $this->request->getPost('categoria_id');
        if (! $this->categorias->find($categoriaId)) {
            return $this->respuestaGuardado(false, ['categoria_id' => 'Categoría no válida.']);
        }

        $this->model->insert([
            'categoria_id' => $categoriaId,
            'nombre'       => $this->request->getPost('nombre'),
            'color'        => strtoupper((string) $this->request->getPost('color')),
            'estatus'      => $this->request->getPost('estatus'),
            'orden'        => 0,
        ]);

        return $this->respuestaGuardado(true, null, 'Tag creado correctamente.');
    }

    public function actualizar(int $id)
    {
        $tag = $this->model->find($id);

        if (! $tag) {
            return $this->respuestaGuardado(false, ['general' => 'Tag no encontrado.']);
        }

        $rules = [
            'categoria_id' => 'required|is_natural_no_zero',
            'nombre'       => 'required|min_length[1]|max_length[50]',
            'color'        => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'estatus'      => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $categoriaId = (int) $this->request->getPost('categoria_id');
        if (! $this->categorias->find($categoriaId)) {
            return $this->respuestaGuardado(false, ['categoria_id' => 'Categoría no válida.']);
        }

        $this->model->update($id, [
            'categoria_id' => $categoriaId,
            'nombre'       => $this->request->getPost('nombre'),
            'color'        => strtoupper((string) $this->request->getPost('color')),
            'estatus'      => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Tag actualizado.');
    }

    public function guardarCategoria()
    {
        $rules = [
            'nombre'  => 'required|min_length[2]|max_length[80]',
            'alcance' => 'required|in_list[global,local]',
            'dominio' => 'required|in_list[cliente,reserva]',
            'estatus' => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->categorias->insert([
            'nombre'             => $this->request->getPost('nombre'),
            'alcance'            => $this->request->getPost('alcance'),
            'dominio'            => $this->request->getPost('dominio'),
            'mostrar_en_reserva' => $this->request->getPost('mostrar_en_reserva') ? 1 : 0,
            'mostrar_en_chit'    => $this->request->getPost('mostrar_en_chit') ? 1 : 0,
            'orden'              => (int) ($this->categorias->selectMax('orden')->first()['orden'] ?? 0) + 1,
            'estatus'            => $this->request->getPost('estatus'),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        return $this->respuestaGuardado(true, null, 'Categoría creada correctamente.');
    }

    public function actualizarCategoria(int $id)
    {
        $categoria = $this->categorias->find($id);

        if (! $categoria) {
            return $this->respuestaGuardado(false, ['general' => 'Categoría no encontrada.']);
        }

        $rules = [
            'nombre'  => 'required|min_length[2]|max_length[80]',
            'alcance' => 'required|in_list[global,local]',
            'dominio' => 'required|in_list[cliente,reserva]',
            'estatus' => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $this->categorias->update($id, [
            'nombre'             => $this->request->getPost('nombre'),
            'alcance'            => $this->request->getPost('alcance'),
            'dominio'            => $this->request->getPost('dominio'),
            'mostrar_en_reserva' => $this->request->getPost('mostrar_en_reserva') ? 1 : 0,
            'mostrar_en_chit'    => $this->request->getPost('mostrar_en_chit') ? 1 : 0,
            'estatus'            => $this->request->getPost('estatus'),
        ]);

        return $this->respuestaGuardado(true, null, 'Categoría actualizada.');
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

        return redirect()->to('/admin/tags')->with('success', $message);
    }
}
