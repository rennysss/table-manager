<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Models\FloorplanAmbienteModel;
use App\Models\FloorplanModel;
use App\Models\MesaModel;
use App\Models\SucursalModel;

class FloorplanController extends BaseController
{
    /** Etiquetas legibles para el tipo de floorplan. */
    private const TIPOS_LABEL = [
        'normal'     => 'Normal',
        'festivo'    => 'Festivo',
        'anio_nuevo' => 'Año Nuevo',
        'custom'     => 'Personalizado',
    ];

    public function index(int $sucursalId)
    {
        $sucursal = model(SucursalModel::class)->find($sucursalId);

        if (! $sucursal) {
            return redirect()->to('/admin/sucursales')->with('error', 'Sucursal no encontrada.');
        }

        $floorplans = model(FloorplanModel::class)->porSucursal($sucursalId);

        // Conteo de mesas activas/inactivas por floorplan.
        $ids      = array_column($floorplans, 'id');
        $conteos  = model(MesaModel::class)->conteoPorFloorplans($ids);

        // Nombre de ambiente para etiquetar cada layout.
        $ambientes = [];
        foreach (model(AmbienteModel::class)->paraSucursal($sucursalId) as $a) {
            $ambientes[(int) $a['id']] = $a['descripcion'];
        }

        foreach ($floorplans as &$fp) {
            $idFp                  = (int) $fp['id'];
            $fp['tipo_label']      = self::TIPOS_LABEL[$fp['tipo']] ?? ucfirst((string) $fp['tipo']);
            $fp['ambiente_nombre'] = $fp['ambiente_id'] ? ($ambientes[(int) $fp['ambiente_id']] ?? null) : null;
            $fp['mesas_activas']   = $conteos[$idFp]['activo'] ?? 0;
            $fp['mesas_inactivas'] = $conteos[$idFp]['inactivo'] ?? 0;
            $fp['combinaciones']   = 0;
        }
        unset($fp);

        return view('admin/floorplan/index', [
            'titulo'            => 'FloorPlans',
            'sucursal'          => $sucursal,
            'floorplans'        => $floorplans,
            'ambientesOpciones' => ambientes_opciones(),
        ]);
    }

    public function editor(int $floorplanId)
    {
        $floorplan = model(FloorplanModel::class)->find($floorplanId);

        if (! $floorplan) {
            return redirect()->back()->with('error', 'FloorPlan no encontrado.');
        }

        $sucursal   = model(SucursalModel::class)->find($floorplan['sucursal_id']);
        $ambientes  = $this->ambientesDelFloorplan($floorplan);
        $mesas      = model(MesaModel::class)->porFloorplan($floorplanId);
        $inventario = model(MesaModel::class)->inventarioPorSucursal((int) $floorplan['sucursal_id']);
        $aforo      = model(MesaModel::class)->calcularAforo($floorplanId);

        // Nunca cachear el HTML del editor: así el navegador siempre recibe
        // las etiquetas <script> con la versión (?v=) más reciente del JS.
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
            'aforo'      => $aforo,
            'modo'       => 'admin',
            'autoPrint'  => $this->request->getGet('print') === '1',
        ]);
    }

    /**
     * Duplica un floorplan (layout visual) en su misma sucursal.
     * Crea una copia inactiva con el mismo canvas; el inventario de
     * mesas no se clona para no chocar con los números existentes.
     */
    public function duplicar(int $floorplanId)
    {
        $floorplanModel = model(FloorplanModel::class);
        $original       = $floorplanModel->find($floorplanId);

        if (! $original) {
            return redirect()->back()->with('error', 'FloorPlan no encontrado.');
        }

        $nuevoId = $floorplanModel->insert([
            'sucursal_id' => $original['sucursal_id'],
            'ambiente_id' => $original['ambiente_id'],
            'nombre'      => $this->nombreCopia($original['nombre']),
            'tipo'        => $original['tipo'],
            'activo'      => 0,
            'canvas_json' => $original['canvas_json'] ?: json_encode(['version' => '5.3.0', 'objects' => []]),
        ]);

        $ambienteIds = model(FloorplanAmbienteModel::class)->idsDe($floorplanId);
        model(FloorplanAmbienteModel::class)->sincronizar((int) $nuevoId, $ambienteIds);

        return redirect()->to("/admin/floorplan/{$nuevoId}/editor")
            ->with('success', 'FloorPlan duplicado.');
    }

    /** Genera un nombre tipo "Nombre (copia)" evitando choques simples. */
    private function nombreCopia(string $nombre): string
    {
        return mb_substr($nombre . ' (copia)', 0, 100);
    }

    /**
     * Ambientes que contiene el floorplan. Usa la tabla puente; si está vacía
     * (floorplans antiguos) cae al ambiente_id único o a los activos.
     */
    private function ambientesDelFloorplan(array $floorplan): array
    {
        $ambientes = model(FloorplanAmbienteModel::class)->ambientesDe((int) $floorplan['id']);

        if ($ambientes !== []) {
            return $ambientes;
        }

        if (! empty($floorplan['ambiente_id'])) {
            $uno = model(AmbienteModel::class)
                ->select('id, descripcion')
                ->find((int) $floorplan['ambiente_id']);

            if ($uno) {
                return [$uno];
            }
        }

        return model(AmbienteModel::class)->listarActivos();
    }

    public function crear(int $sucursalId)
    {
        $rules = [
            'nombre' => 'required|max_length[100]',
            'tipo'   => 'required|in_list[normal,festivo,anio_nuevo,custom]',
        ];

        if (! $this->validate($rules)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'errors'  => $this->validator->getErrors(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Uno o varios ambientes; compat con el campo único anterior.
        $ambienteIds = $this->request->getPost('ambiente_ids');
        if (! is_array($ambienteIds)) {
            $unico       = $this->request->getPost('ambiente_id');
            $ambienteIds = $unico ? [$unico] : [];
        }
        $ambienteIds = array_values(array_unique(array_filter(array_map('intval', $ambienteIds))));

        $id = model(FloorplanModel::class)->insert([
            'sucursal_id' => $sucursalId,
            'ambiente_id' => $ambienteIds[0] ?? null,
            'nombre'      => $this->request->getPost('nombre'),
            'tipo'        => $this->request->getPost('tipo'),
            'activo'      => 0,
            'canvas_json' => json_encode(['version' => '5.3.0', 'objects' => []]),
        ]);

        model(FloorplanAmbienteModel::class)->sincronizar((int) $id, $ambienteIds);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success'    => true,
                'message'    => 'FloorPlan creado.',
                'editor_url' => base_url("admin/floorplan/{$id}/editor"),
            ]);
        }

        return redirect()->to("/admin/floorplan/{$id}/editor")->with('success', 'FloorPlan creado.');
    }

    public function guardar(int $floorplanId)
    {
        $floorplan = model(FloorplanModel::class)->find($floorplanId);

        if (! $floorplan) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'No encontrado']);
        }

        $json = $this->request->getJSON(true);

        if (! isset($json['canvas']) || ! is_array($json['mesas'] ?? null)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Datos inválidos']);
        }

        // DEBUG TEMPORAL: registrar qué objetos llegan en el canvas.
        $objs   = $json['canvas']['objects'] ?? [];
        $resumen = [];
        foreach ($objs as $o) {
            $k = ($o['objectType'] ?? ('type:' . ($o['type'] ?? '?')));
            $resumen[$k] = ($resumen[$k] ?? 0) + 1;
        }
        log_message('info', '[FP-DEBUG] guardar fp=' . $floorplanId
            . ' build=' . ($json['build'] ?? 'SIN-BUILD(JS-viejo)')
            . ' total_objs=' . count($objs)
            . ' resumen=' . json_encode($resumen));

        $datosFloorplan = ['canvas_json' => json_encode($json['canvas'])];

        // Renombrado interno desde el editor (opcional).
        if (isset($json['nombre'])) {
            $nombre = trim((string) $json['nombre']);
            if ($nombre !== '') {
                $datosFloorplan['nombre'] = mb_substr($nombre, 0, 100);
            }
        }

        model(FloorplanModel::class)->update($floorplanId, $datosFloorplan);

        $mesaModel   = model(MesaModel::class);
        $mesasNuevas = [];

        foreach ($json['mesas'] as $mesaData) {
            if (! empty($mesaData['id'])) {
                $mesaModel->update($mesaData['id'], [
                    'pos_x'     => $mesaData['pos_x'],
                    'pos_y'     => $mesaData['pos_y'],
                    'ancho'     => $mesaData['ancho'],
                    'alto'      => $mesaData['alto'],
                    'rotacion'  => $mesaData['rotacion'] ?? 0,
                    'fabric_id' => $mesaData['fabric_id'] ?? null,
                    'minimo'    => $mesaData['minimo'],
                    'maximo'    => $mesaData['maximo'],
                    'pax'       => $mesaData['pax'],
                    'forma'     => $mesaData['forma'] ?? 'rectangular',
                    'floorplan_id' => $floorplanId,
                ]);
            } else {
                $fabricId = $mesaData['fabric_id'] ?? ('fab_' . uniqid());
                $nuevoId  = $mesaModel->insert([
                    'ambiente_id'  => $mesaData['ambiente_id'],
                    'sucursal_id'  => $floorplan['sucursal_id'],
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

                // Devuelve el id para que el editor lo vincule y no se duplique
                // en el siguiente autoguardado.
                $mesasNuevas[] = [
                    'id'        => (int) $nuevoId,
                    'numero'    => $mesaData['numero'],
                    'fabric_id' => $fabricId,
                ];
            }
        }

        // Mesas quitadas del layout (panel derecho o tecla Backspace): no se
        // borran, solo se desvinculan del floorplan y vuelven al inventario.
        if (! empty($json['mesas_desvinculadas']) && is_array($json['mesas_desvinculadas'])) {
            foreach ($json['mesas_desvinculadas'] as $mesaId) {
                $mesaId = (int) $mesaId;
                if ($mesaId <= 0) {
                    continue;
                }
                $mesa = $mesaModel->find($mesaId);
                // Solo desvincula mesas de la sucursal de este floorplan.
                if ($mesa && (int) $mesa['sucursal_id'] === (int) $floorplan['sucursal_id']) {
                    $mesaModel->update($mesaId, ['floorplan_id' => null]);
                }
            }
        }

        $aforo = $mesaModel->calcularAforo($floorplanId);

        return $this->response->setJSON([
            'success'      => true,
            'aforo'        => $aforo,
            'mesas_nuevas' => $mesasNuevas,
            'csrf'         => csrf_hash(),
            'message'      => 'FloorPlan guardado correctamente.',
        ]);
    }

    public function activar(int $floorplanId)
    {
        $floorplan = model(FloorplanModel::class)->find($floorplanId);

        if (! $floorplan) {
            return redirect()->back()->with('error', 'FloorPlan no encontrado.');
        }

        model(FloorplanModel::class)->activar(
            $floorplanId,
            (int) $floorplan['sucursal_id'],
            $floorplan['ambiente_id'] ? (int) $floorplan['ambiente_id'] : null
        );

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => 'FloorPlan activado.']);
        }

        return redirect()->back()->with('success', 'FloorPlan activado.');
    }
}
