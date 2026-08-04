<?php

namespace App\Controllers\Gerente;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Models\FloorplanAmbienteModel;
use App\Models\FloorplanModel;
use App\Models\MesaModel;
use App\Models\ReservaAsignacionModel;
use App\Models\SucursalModel;
use App\Traits\GerenteAccessTrait;

class FloorplanController extends BaseController
{
    use GerenteAccessTrait;

    /** Etiquetas legibles del tipo de floorplan */
    private const TIPOS_LABEL = [
        'normal'     => 'Normal',
        'festivo'    => 'Festivo',
        'anio_nuevo' => 'Año Nuevo',
        'custom'     => 'Personalizado',
    ];

    /**
     * Listado completo de floorplans por sucursal asignada.
     */
    public function index()
    {
        $sucursales = $this->sucursalesAsignadas();
        $ids        = array_map(static fn (array $s): int => (int) $s['id'], $sucursales);
        $floorplans = model(FloorplanModel::class)->listarPorSucursales($ids);
        $mesaModel  = model(MesaModel::class);

        foreach ($floorplans as &$fp) {
            $fp['aforo']       = $mesaModel->calcularAforo((int) $fp['id']);
            $fp['tipo_label']  = self::TIPOS_LABEL[$fp['tipo']] ?? $fp['tipo'];
        }
        unset($fp);

        return view('gerente/floorplan/index', [
            'titulo'              => 'FloorPlans',
            'mostrarTituloTopbar' => false,
            'floorplans'          => $floorplans,
            'sucursales'          => $sucursales,
            'ambientesOpciones'   => ambientes_opciones(),
            'tiposLabel'          => self::TIPOS_LABEL,
        ]);
    }

    /**
     * Modal para crear floorplan (botón +).
     */
    public function modalCrear()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/gerente/floorplan');
        }

        $sucursales = $this->sucursalesAsignadas();
        $ids        = array_map(static fn (array $s): int => (int) $s['id'], $sucursales);

        return view('gerente/floorplan/_modal_crear', [
            'sucursales'        => $sucursales,
            'ambientesOpciones' => ambientes_opciones(),
            'tiposLabel'        => self::TIPOS_LABEL,
        ]);
    }

    /**
     * Crea floorplan desde el modal y abre el editor en página nueva.
     */
    public function crearDesdeModal()
    {
        $sucursalId = (int) $this->request->getPost('sucursal_id');

        if (! $this->sucursalPermitida($sucursalId)) {
            return $this->denegarAcceso();
        }

        return $this->crearFloorplan($sucursalId);
    }

    /**
     * Gestión por sucursal (compatibilidad con enlaces antiguos).
     */
    public function porSucursal(int $sucursalId)
    {
        if (! $this->sucursalPermitida($sucursalId)) {
            return $this->denegarAcceso();
        }

        return redirect()->to('/gerente/floorplan');
    }

    public function crear(int $sucursalId)
    {
        if (! $this->sucursalPermitida($sucursalId)) {
            return $this->denegarAcceso();
        }

        return $this->crearFloorplan($sucursalId);
    }

    private function crearFloorplan(int $sucursalId)
    {
        $rules = [
            'nombre'      => 'required|max_length[100]',
            'tipo'        => 'required|in_list[normal,festivo,anio_nuevo,custom]',
            'ambiente_id' => 'permit_empty|integer',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/gerente/floorplan')
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('abrir_modal_crear', true);
        }

        $id = model(FloorplanModel::class)->insert([
            'sucursal_id' => $sucursalId,
            'ambiente_id' => $this->request->getPost('ambiente_id') ?: null,
            'nombre'      => $this->request->getPost('nombre'),
            'tipo'        => $this->request->getPost('tipo'),
            'activo'      => 0,
            'canvas_json' => json_encode(['version' => '5.3.0', 'objects' => []]),
        ]);

        return redirect()->to("/gerente/floorplan/{$id}/editor")
            ->with('success', 'FloorPlan creado. Configura el layout y guarda los cambios.');
    }

    public function activar(int $floorplanId)
    {
        if (! $this->floorplanPermitido($floorplanId)) {
            return $this->denegarAcceso();
        }

        $floorplan = model(FloorplanModel::class)->find($floorplanId);

        model(FloorplanModel::class)->activar(
            $floorplanId,
            (int) $floorplan['sucursal_id'],
            $floorplan['ambiente_id'] ? (int) $floorplan['ambiente_id'] : null
        );

        return redirect()->back()
            ->with('success', 'FloorPlan "' . $floorplan['nombre'] . '" activado.');
    }

    public function desactivar(int $floorplanId)
    {
        if (! $this->floorplanPermitido($floorplanId)) {
            return $this->denegarAcceso();
        }

        $floorplan = model(FloorplanModel::class)->find($floorplanId);
        model(FloorplanModel::class)->desactivar($floorplanId);

        return redirect()->back()
            ->with('success', 'FloorPlan "' . $floorplan['nombre'] . '" desactivado.');
    }

    public function editor(int $floorplanId)
    {
        if (! $this->floorplanPermitido($floorplanId)) {
            return $this->denegarAcceso();
        }

        $floorplan  = model(FloorplanModel::class)->find($floorplanId);
        $sucursal   = model(SucursalModel::class)->find($floorplan['sucursal_id']);
        $ambientes  = model(FloorplanAmbienteModel::class)->ambientesDe($floorplanId);
        if ($ambientes === []) {
            $ambientes = model(AmbienteModel::class)->listarActivos();
        }
        $mesas      = model(MesaModel::class)->porFloorplan($floorplanId);
        $inventario = model(MesaModel::class)->inventarioPorSucursal((int) $floorplan['sucursal_id']);

        // No cachear el HTML: garantiza scripts con ?v= actualizado.
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');

        return view('admin/floorplan/editor', [
            'titulo'     => 'Editor FloorPlan',
            'floorplan'  => $floorplan,
            'sucursal'   => $sucursal,
            'ambientes'  => $ambientes,
            'mesas'      => $mesas,
            'inventario' => $inventario,
            'aforo'      => model(MesaModel::class)->calcularAforo($floorplanId),
            'modo'       => 'gerente',
        ]);
    }

    /**
     * Guarda canvas completo. Mesas existentes: no cambia número; reservas intactas por mesa_id.
     */
    public function guardar(int $floorplanId)
    {
        if (! $this->floorplanPermitido($floorplanId)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        }

        $json = $this->request->getJSON(true);

        if (! isset($json['canvas']) || ! is_array($json['mesas'] ?? null)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Datos inválidos']);
        }

        $datosFloorplan = ['canvas_json' => json_encode($json['canvas'])];

        // Renombrado interno desde el editor (opcional).
        if (isset($json['nombre'])) {
            $nombre = trim((string) $json['nombre']);
            if ($nombre !== '') {
                $datosFloorplan['nombre'] = mb_substr($nombre, 0, 100);
            }
        }

        model(FloorplanModel::class)->update($floorplanId, $datosFloorplan);

        $mesaModel = model(MesaModel::class);
        $mesasNuevas = [];

        foreach ($json['mesas'] as $mesaData) {
            if (! empty($mesaData['id'])) {
                $existente = $mesaModel->find($mesaData['id']);

                if (! $existente || ! $this->mesaPermitida((int) $mesaData['id'])) {
                    continue;
                }

                $mesaModel->update($mesaData['id'], [
                    'pos_x'        => $mesaData['pos_x'],
                    'pos_y'        => $mesaData['pos_y'],
                    'ancho'        => $mesaData['ancho'],
                    'alto'         => $mesaData['alto'],
                    'rotacion'     => $mesaData['rotacion'] ?? 0,
                    'fabric_id'    => $mesaData['fabric_id'] ?? $existente['fabric_id'],
                    'pax'          => $mesaData['pax'],
                    'minimo'       => $mesaData['minimo'],
                    'maximo'       => $mesaData['maximo'],
                    'forma'        => $mesaData['forma'] ?? $existente['forma'],
                    'floorplan_id' => $floorplanId,
                ]);
            } else {
                if (empty($mesaData['ambiente_id']) || empty($mesaData['numero'])) {
                    continue;
                }

                $fabricId = $mesaData['fabric_id'] ?? ('fab_' . uniqid());
                $nuevoId  = $mesaModel->insert([
                    'ambiente_id'  => $mesaData['ambiente_id'],
                    'floorplan_id' => $floorplanId,
                    'numero'       => $mesaData['numero'],
                    'pax'          => $mesaData['pax'],
                    'minimo'       => $mesaData['minimo'],
                    'maximo'       => $mesaData['maximo'],
                    'estatus'      => 'activo',
                    'forma'        => $mesaData['forma'] ?? 'rectangular',
                    'pos_x'        => $mesaData['pos_x'],
                    'pos_y'        => $mesaData['pos_y'],
                    'ancho'        => $mesaData['ancho'],
                    'alto'         => $mesaData['alto'],
                    'rotacion'     => $mesaData['rotacion'] ?? 0,
                    'fabric_id'    => $fabricId,
                ]);

                $mesasNuevas[] = [
                    'id'        => $nuevoId,
                    'fabric_id' => $fabricId,
                    'numero'    => $mesaData['numero'],
                ];
            }
        }

        // Mesas quitadas del layout: no se borran, solo se desvinculan.
        if (! empty($json['mesas_desvinculadas']) && is_array($json['mesas_desvinculadas'])) {
            foreach ($json['mesas_desvinculadas'] as $mesaId) {
                $mesaId = (int) $mesaId;
                if ($mesaId > 0 && $this->mesaPermitida($mesaId)) {
                    $mesaModel->update($mesaId, ['floorplan_id' => null]);
                }
            }
        }

        $aforo = $mesaModel->calcularAforo($floorplanId);

        return $this->response->setJSON([
            'success'     => true,
            'aforo'       => $aforo,
            'mesas_nuevas'=> $mesasNuevas,
            'csrf'        => csrf_hash(),
            'message'     => 'FloorPlan guardado. Las reservas asignadas se mantienen intactas.',
        ]);
    }

    public function actualizarMesa(int $mesaId)
    {
        if (! $this->mesaPermitida($mesaId)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        }

        $mesa = model(MesaModel::class)->find($mesaId);
        $json = $this->request->getJSON(true);

        model(MesaModel::class)->update($mesaId, [
            'pax'      => (int) ($json['pax'] ?? $mesa['pax']),
            'minimo'   => (int) ($json['minimo'] ?? $mesa['minimo']),
            'maximo'   => (int) ($json['maximo'] ?? $mesa['maximo']),
            'pos_x'    => $json['pos_x'] ?? $mesa['pos_x'],
            'pos_y'    => $json['pos_y'] ?? $mesa['pos_y'],
            'ancho'    => $json['ancho'] ?? $mesa['ancho'],
            'alto'     => $json['alto'] ?? $mesa['alto'],
            'rotacion' => $json['rotacion'] ?? $mesa['rotacion'],
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    public function reservasMesa(int $mesaId)
    {
        if (! $this->mesaPermitida($mesaId)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso denegado']);
        }

        $fecha = $this->request->getGet('fecha') ?? date('Y-m-d');

        $asignaciones = model(ReservaAsignacionModel::class)
            ->where('mesa_id', $mesaId)
            ->where('fecha', $fecha)
            ->whereIn('estado_mesa', ['reservada', 'ocupada'])
            ->findAll();

        return $this->response->setJSON(['reservas' => $asignaciones]);
    }
}
