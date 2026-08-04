<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AmbienteModel;
use App\Models\MesaModel;
use App\Models\SucursalModel;

class MesasController extends BaseController
{
    /**
     * Editor de inventario de mesas de una sucursal (estilo SevenRooms).
     * Columnas: Mesa / Mini Pax / Max Pax / Ambiente.
     */
    public function index(int $sucursalId)
    {
        $sucursal = model(SucursalModel::class)->find($sucursalId);

        if (! $sucursal) {
            return redirect()->to('/admin/sucursales')->with('error', 'Sucursal no encontrada.');
        }

        return view('admin/mesas/index', [
            'titulo'    => 'Mesas',
            'sucursal'  => $sucursal,
            'mesas'     => model(MesaModel::class)->inventarioPorSucursal($sucursalId),
            'ambientes' => model(AmbienteModel::class)->paraSucursal($sucursalId),
        ]);
    }

    /**
     * Guardado por lote: inserta nuevas, actualiza existentes y elimina
     * (soft delete) las que se quitaron de la tabla, todo dentro de la sucursal.
     * Recibe JSON: { rows: [...], deleted: [ids] }.
     */
    public function guardarLote(int $sucursalId)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to("/admin/sucursales/{$sucursalId}/mesas");
        }

        if (! model(SucursalModel::class)->find($sucursalId)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'errors'  => ['Sucursal no encontrada.'],
            ]);
        }

        $payload = $this->request->getJSON(true) ?? [];
        $rows    = $payload['rows'] ?? [];
        $deleted = $payload['deleted'] ?? [];

        $mesaModel = model(MesaModel::class);

        // Solo ambientes válidos para esta sucursal (globales + propios)
        $ambientes           = model(AmbienteModel::class)->paraSucursal($sucursalId);
        $idsAmbientesValidos = array_map('intval', array_column($ambientes, 'id'));

        $errores = [];
        $limpias = [];

        foreach ($rows as $i => $row) {
            $numero     = trim((string) ($row['numero'] ?? ''));
            $minimo     = (int) ($row['minimo'] ?? 0);
            $maximo     = (int) ($row['maximo'] ?? 0);
            $ambienteId = (int) ($row['ambiente_id'] ?? 0);
            $id         = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;

            $etiqueta = $numero !== '' ? "Mesa {$numero}" : 'Fila ' . ($i + 1);

            if ($numero === '') {
                $errores[] = "{$etiqueta}: el número de mesa es obligatorio.";
            }
            if ($minimo < 1) {
                $errores[] = "{$etiqueta}: el mínimo de pax debe ser mayor a 0.";
            }
            if ($maximo < $minimo) {
                $errores[] = "{$etiqueta}: el máximo de pax no puede ser menor al mínimo.";
            }
            if (! in_array($ambienteId, $idsAmbientesValidos, true)) {
                $errores[] = "{$etiqueta}: selecciona un ambiente válido.";
            }

            $limpias[] = compact('id', 'numero', 'minimo', 'maximo', 'ambienteId');
        }

        // El número de mesa no se puede repetir dentro del mismo ambiente
        $vistos = [];
        foreach ($limpias as $fila) {
            if ($fila['numero'] === '') {
                continue;
            }
            $clave = $fila['ambienteId'] . '|' . mb_strtolower($fila['numero']);
            if (isset($vistos[$clave])) {
                $errores[] = "El número de mesa {$fila['numero']} está repetido en el mismo ambiente.";
            }
            $vistos[$clave] = true;
        }

        if (! empty($errores)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => array_values(array_unique($errores)),
            ]);
        }

        $db = db_connect();
        $db->transStart();

        // Eliminar solo mesas que pertenezcan a esta sucursal
        foreach ($deleted as $delId) {
            $delId = (int) $delId;
            if ($delId <= 0) {
                continue;
            }
            $mesa = $mesaModel->find($delId);
            if ($mesa && (int) $mesa['sucursal_id'] === $sucursalId) {
                $mesaModel->delete($delId);
            }
        }

        foreach ($limpias as $fila) {
            $datos = [
                'numero'      => $fila['numero'],
                'minimo'      => $fila['minimo'],
                'maximo'      => $fila['maximo'],
                'ambiente_id' => $fila['ambienteId'],
                'sucursal_id' => $sucursalId,
            ];

            $existente = $fila['id'] !== null ? $mesaModel->find($fila['id']) : null;

            if ($existente && (int) $existente['sucursal_id'] === $sucursalId) {
                // En mesas existentes no se toca pax (lo usa el floorplan)
                $mesaModel->update($fila['id'], $datos);
            } else {
                // Mesas nuevas: pax inicial igual al mínimo
                $datos['pax'] = $fila['minimo'];
                $mesaModel->insert($datos);
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
            'message' => 'Mesas guardadas correctamente.',
        ]);
    }

    /**
     * Crea un ambiente propio de la sucursal desde el editor de mesas.
     */
    public function crearAmbiente(int $sucursalId)
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to("/admin/sucursales/{$sucursalId}/mesas");
        }

        if (! model(SucursalModel::class)->find($sucursalId)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'errors'  => ['Sucursal no encontrada.'],
            ]);
        }

        $rules = ['descripcion' => 'required|min_length[2]|max_length[150]'];

        if (! $this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $ambienteModel = model(AmbienteModel::class);
        $id = $ambienteModel->insert([
            'sucursal_id' => $sucursalId,
            'descripcion' => $this->request->getPost('descripcion'),
            'estatus'     => 'activo',
        ]);

        return $this->response->setJSON([
            'success'  => true,
            'ambiente' => [
                'id'          => $id,
                'descripcion' => $this->request->getPost('descripcion'),
            ],
        ]);
    }
}
