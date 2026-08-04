<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Models\EstadoRegionModel;
use App\Models\FloorplanModel;
use App\Models\MarcaModel;
use App\Models\PaisModel;
use App\Models\SucursalModel;

class SucursalesController extends BaseController
{
    protected SucursalModel $model;

    public function __construct()
    {
        $this->model = model(SucursalModel::class);
    }

    public function index()
    {
        return view('admin/sucursales/index', [
            'titulo'              => 'Sucursales',
            'mostrarTituloTopbar' => false,
            'sucursales'          => $this->model->listarConRelaciones(),
        ]);
    }

    public function modalCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/sucursales');
        }

        return view('admin/sucursales/_modal_form', $this->datosFormulario(null));
    }

    public function modalEditar(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/sucursales');
        }

        $sucursal = $this->model->find($id);

        if (! $sucursal) {
            return $this->response->setStatusCode(404)->setBody('Sucursal no encontrada.');
        }

        return view('admin/sucursales/_modal_form', $this->datosFormulario($sucursal));
    }

    public function modalAmbientes(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/sucursales');
        }

        $sucursal = $this->model->find($id);

        if (! $sucursal) {
            return $this->response->setStatusCode(404)->setBody('Sucursal no encontrada.');
        }

        return view('admin/sucursales/_modal_ambientes', [
            'ambientes' => model(AmbienteModel::class)->listarTodos(),
        ]);
    }

    public function modalFloorplans(int $id)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/admin/sucursales');
        }

        $sucursal = $this->model->find($id);

        if (! $sucursal) {
            return $this->response->setStatusCode(404)->setBody('Sucursal no encontrada.');
        }

        return view('admin/sucursales/_modal_floorplans', [
            'sucursal'          => $sucursal,
            'floorplans'        => model(FloorplanModel::class)->porSucursal($id),
            'ambientesOpciones' => ambientes_opciones(),
        ]);
    }

    public function crear()
    {
        return redirect()->to('/admin/sucursales');
    }

    public function guardar()
    {
        $rules = [
            'marca_id'         => 'required|is_natural_no_zero',
            'nombre'           => 'required|min_length[2]|max_length[150]',
            'venue_id'         => 'permit_empty|max_length[100]',
            'xetux_id'         => 'permit_empty|max_length[100]',
            'reservas_api_key' => 'permit_empty|max_length[255]',
            'pais_id'          => 'required|is_natural_no_zero',
            'estado_region_id' => 'required|is_natural_no_zero',
            'ciudad'           => 'permit_empty|max_length[100]',
            'estatus'          => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $datos = $this->extraerDatosFormulario(false);

        if ($datos === null) {
            return $this->respuestaGuardado(false, ['estado_region_id' => 'El estado no corresponde al país seleccionado.']);
        }

        $this->model->insert($datos);

        return $this->respuestaGuardado(true, null, 'Sucursal creada correctamente.');
    }

    public function editar(int $id)
    {
        return redirect()->to('/admin/sucursales');
    }

    public function actualizar(int $id)
    {
        $sucursal = $this->model->find($id);

        if (! $sucursal) {
            return $this->respuestaGuardado(false, ['general' => 'Sucursal no encontrada.']);
        }

        $rules = [
            'marca_id'         => 'required|is_natural_no_zero',
            'nombre'           => 'required|min_length[2]|max_length[150]',
            'venue_id'         => 'permit_empty|max_length[100]',
            'xetux_id'         => 'permit_empty|max_length[100]',
            'reservas_api_key' => 'permit_empty|max_length[255]',
            'pais_id'          => 'required|is_natural_no_zero',
            'estado_region_id' => 'required|is_natural_no_zero',
            'ciudad'           => 'permit_empty|max_length[100]',
            'estatus'          => 'required|in_list[activo,inactivo]',
        ];

        if (! $this->validate($rules)) {
            return $this->respuestaGuardado(false, $this->validator->getErrors());
        }

        $datos = $this->extraerDatosFormulario(true);

        if ($datos === null) {
            return $this->respuestaGuardado(false, ['estado_region_id' => 'El estado no corresponde al país seleccionado.']);
        }

        if (! $this->model->update($id, $datos)) {
            return $this->respuestaGuardado(false, ['general' => 'No se pudo actualizar la sucursal.']);
        }

        return $this->respuestaGuardado(true, null, 'Sucursal actualizada.');
    }

    public function eliminar(int $id)
    {
        $this->model->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => 'Sucursal eliminada.']);
        }

        return redirect()->to('/admin/sucursales')->with('success', 'Sucursal eliminada.');
    }

    /** Datos compartidos para el modal crear/editar */
    private function datosFormulario(?array $sucursal): array
    {
        $paisId = $sucursal['pais_id'] ?? null;
        $estados = $paisId
            ? model(EstadoRegionModel::class)->porPais((int) $paisId)
            : [];

        return [
            'sucursal' => $sucursal,
            'marcas'   => model(MarcaModel::class)->activas(),
            'paises'   => model(PaisModel::class)->activos(),
            'estados'  => $estados,
        ];
    }

    /** Valida coherencia país/estado y devuelve fila lista para guardar */
    private function extraerDatosFormulario(bool $preservarApiKeySiVacio = false): ?array
    {
        $paisId = (int) $this->request->getPost('pais_id');
        $estadoId = (int) $this->request->getPost('estado_region_id');

        $estado = model(EstadoRegionModel::class)->find($estadoId);

        if (! $estado || (int) $estado['pais_id'] !== $paisId) {
            return null;
        }

        $datos = [
            'marca_id'         => (int) $this->request->getPost('marca_id'),
            'nombre'           => $this->request->getPost('nombre'),
            'venue_id'         => trim((string) $this->request->getPost('venue_id')) ?: null,
            'xetux_id'         => trim((string) $this->request->getPost('xetux_id')) ?: null,
            'pais_id'          => $paisId,
            'estado_region_id' => $estadoId,
            'estado'           => $estado['nombre'],
            'ciudad'           => $this->request->getPost('ciudad'),
            'estatus'          => $this->request->getPost('estatus'),
        ];

        $apiKey = trim((string) $this->request->getPost('reservas_api_key'));
        if ($apiKey !== '') {
            $datos['reservas_api_key'] = $apiKey;
        } elseif (! $preservarApiKeySiVacio) {
            $datos['reservas_api_key'] = null;
        }

        return $datos;
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

        return redirect()->to('/admin/sucursales')->with('success', $message);
    }
}
