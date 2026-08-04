<?php

namespace App\Controllers\Hostess;

use App\Controllers\BaseController;
use App\Models\ClienteModel;
use App\Models\MesaModel;
use App\Models\ReservaAsignacionModel;
use App\Models\ReservaLlegadaModel;
use App\Models\SucursalModel;
use App\Models\TagCategoriaModel;
use App\Models\TagModel;
use App\Services\ReservaActividadService;
use App\Services\ReservasApiService;

class ReservasController extends BaseController
{
    public function index()
    {
        $fecha = $this->request->getGet('fecha') ?? date('Y-m-d');

        $solicitada = (int) $this->request->getGet('sucursal_id');
        if ($solicitada > 0) {
            $this->establecerSucursalSiPermitida($solicitada);
        }

        $sucursalId     = (int) (session()->get('sucursal_id') ?? 0);
        $sucursalActiva = $this->sucursalActivaPorId($sucursalId);
        $servicio       = new ReservasApiService();
        $reservas       = $this->enriquecerReservasConTagsCliente(
            $servicio->obtenerPorFecha($sucursalId, $fecha)
        );
        $asignaciones   = model(ReservaAsignacionModel::class)->porFecha($sucursalId, $fecha);

        $asignMap = [];
        foreach ($asignaciones as $a) {
            $asignMap[$a['reserva_codigo']] = $this->enriquecerAsignacion($a);
        }

        return view('hostess/reservas/index', [
            'titulo'         => 'Reservas',
            'fecha'          => $fecha,
            'reservas'       => $reservas,
            'asignaciones'   => $asignMap,
            'tags'           => model(TagModel::class)->activos(),
            'tagCategorias'  => $this->categoriasTagsActivos(),
            'sucursalId'     => $sucursalId,
            'sucursalActiva' => $sucursalActiva,
            'filtrosUbicacion' => $this->filtrosUbicacionIniciales($sucursalActiva),
            'apiError'       => $servicio->obtenerUltimoError(),
            'modoDemo'       => $servicio->esModoDemo(),
        ]);
    }

    /**
     * Catálogo de tags por categoría (solo activos) para el modal hostess.
     *
     * @return list<array<string, mixed>>
     */
    private function categoriasTagsActivos(): array
    {
        $categorias = model(TagCategoriaModel::class)->conTags();
        $resultado  = [];

        foreach ($categorias as $cat) {
            if (($cat['estatus'] ?? '') !== 'activo') {
                continue;
            }
            // Solo categorías visibles en el detalle de reserva (hostess)
            if (empty($cat['mostrar_en_reserva'])) {
                continue;
            }
            $activos = array_values(array_filter(
                $cat['tags'] ?? [],
                static fn (array $t): bool => ($t['estatus'] ?? '') === 'activo'
            ));
            if ($activos === []) {
                continue;
            }
            $cat['tags'] = $activos;
            $resultado[] = $cat;
        }

        return $resultado;
    }

    /** Opciones de filtros precargadas (evita 3 peticiones AJAX al cargar la página). */
    private function filtrosUbicacionIniciales(?array $sucursalActiva): array
    {
        $model = model(SucursalModel::class);
        $ids   = $this->idsSucursalesPermitidas();
        $paisId = (int) ($sucursalActiva['pais_id'] ?? 0);
        $ciudad = trim((string) ($sucursalActiva['ciudad'] ?? ''));

        $sucursales = [];
        if ($paisId > 0 && $ciudad !== '') {
            $sucursales = array_map(static fn ($s) => [
                'id'     => (int) $s['id'],
                'nombre' => $s['nombre'],
                'ciudad' => $s['ciudad'],
            ], $model->sucursalesConAcceso($paisId, $ciudad, $ids));
        }

        return [
            'paises'     => $model->paisesConAcceso($ids),
            'ciudades'   => $paisId > 0 ? $model->ciudadesConAcceso($paisId, $ids) : [],
            'sucursales' => $sucursales,
        ];
    }

    /** JSON — países con sucursales a las que el usuario tiene acceso */
    public function apiPaises()
    {
        $paises = model(SucursalModel::class)->paisesConAcceso($this->idsSucursalesPermitidas());

        return $this->response->setJSON(['success' => true, 'paises' => $paises]);
    }

    /** JSON — ciudades con sucursales en un país (alcance del usuario) */
    public function apiCiudades(int $paisId)
    {
        $ciudades = model(SucursalModel::class)->ciudadesConAcceso($paisId, $this->idsSucursalesPermitidas());

        return $this->response->setJSON(['success' => true, 'ciudades' => $ciudades]);
    }

    /** JSON — sucursales por país y ciudad (alcance del usuario) */
    public function apiSucursales()
    {
        $paisId = (int) $this->request->getGet('pais_id');
        $ciudad = trim((string) $this->request->getGet('ciudad'));

        if ($paisId <= 0 || $ciudad === '') {
            return $this->response->setJSON(['success' => true, 'sucursales' => []]);
        }

        $sucursales = model(SucursalModel::class)->sucursalesConAcceso(
            $paisId,
            $ciudad,
            $this->idsSucursalesPermitidas()
        );

        return $this->response->setJSON([
            'success'    => true,
            'sucursales' => array_map(static fn ($s) => [
                'id'     => (int) $s['id'],
                'nombre' => $s['nombre'],
                'ciudad' => $s['ciudad'],
            ], $sucursales),
        ]);
    }

    /** JSON — reservas por sucursal y fecha (OneReservations vía backend) */
    public function apiBuscar()
    {
        $sucursalId = (int) $this->request->getGet('sucursal_id');
        $fecha      = trim((string) ($this->request->getGet('fecha') ?? ''));

        if ($sucursalId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Seleccione un establecimiento.',
            ]);
        }

        if ($fecha === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Seleccione una fecha de visita.',
            ]);
        }

        if (! in_array($sucursalId, $this->idsSucursalesPermitidas(), true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'No tienes acceso a esta sucursal.',
            ]);
        }

        $this->establecerSucursalSiPermitida($sucursalId);

        $servicio     = new ReservasApiService();
        $reservas     = $this->enriquecerReservasConTagsCliente(
            $servicio->obtenerPorFecha($sucursalId, $fecha)
        );
        $asignaciones = model(ReservaAsignacionModel::class)->porFecha($sucursalId, $fecha);

        $asignMap = [];
        foreach ($asignaciones as $a) {
            $enriched = $this->enriquecerAsignacion($a);
            $asignMap[$a['reserva_codigo']] = [
                'id'             => (int) $a['id'],
                'mesa_id'        => (int) $a['mesa_id'],
                'mesa_numero'    => $enriched['mesa_numero'] ?? null,
                'mesa_maximo'    => $enriched['mesa_maximo'] ?? null,
                'estado_mesa'    => $a['estado_mesa'] ?? 'reservada',
                'status_label'   => $enriched['status_label'] ?? 'Asignada',
                'pax_reservados' => $enriched['pax_reservados'] ?? null,
                'pax_en_mesa'    => $enriched['pax_en_mesa'] ?? 0,
                'tags_json'      => $a['tags_json'] ?? '[]',
                'llegadas'       => $enriched['llegadas'] ?? [],
            ];
        }

        return $this->response->setJSON([
            'success'      => true,
            'fecha'        => $fecha,
            'sucursal_id'  => $sucursalId,
            'reservas'     => $reservas,
            'asignaciones' => $asignMap,
            'modo_demo'    => $servicio->esModoDemo(),
            'api_error'    => $servicio->obtenerUltimoError(),
            'total'        => count($reservas),
        ]);
    }

    public function detalle(string $codigo)
    {
        $solicitada = (int) $this->request->getGet('sucursal_id');
        if ($solicitada > 0) {
            $this->establecerSucursalSiPermitida($solicitada);
        }

        $sucursalId = (int) (session()->get('sucursal_id') ?? 0);
        $fecha      = trim((string) ($this->request->getGet('fecha') ?? date('Y-m-d')));
        $servicio   = new ReservasApiService();

        // Listado en caché primero (1 llamada al API como máximo); by-booking solo para QR/token
        $reserva = $sucursalId > 0
            ? $servicio->buscarEnListadoActivo($codigo, $sucursalId, $fecha)
            : null;

        if (! $reserva) {
            $reserva = $servicio->obtenerPorCodigo($codigo, $sucursalId);
        }

        if (! $reserva) {
            $mensaje = $servicio->obtenerUltimoError() ?? 'Reserva no encontrada';

            return $this->response->setStatusCode(404)->setJSON(['error' => $mensaje]);
        }

        $asignacion = model(ReservaAsignacionModel::class)->porCodigo($codigo);
        $reserva    = $this->enriquecerReservasConTagsCliente([$reserva])[0];
        $eventos    = [];
        try {
            $eventos = (new ReservaActividadService())->listar($codigo, $sucursalId);
        } catch (\Throwable $e) {
            log_message('error', 'Actividad en detalle: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'reserva'    => $reserva,
            'asignacion' => $this->enriquecerAsignacion($asignacion),
            'actividad'  => $eventos,
        ]);
    }

    public function asignarMesa()
    {
        $json = $this->request->getJSON(true);

        $rules = [
            'reserva_codigo' => 'required',
            'mesa_id'        => 'required|integer',
            'rp_codigo'      => 'permit_empty',
            'cliente_nombre' => 'permit_empty',
            'fecha'          => 'required|valid_date[Y-m-d]',
            'hora'           => 'permit_empty',
        ];

        if (! $this->validate($rules, $json)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $sucursalId = (int) session()->get('sucursal_id');
        $model      = model(ReservaAsignacionModel::class);

        $existente = $model->where('reserva_codigo', $json['reserva_codigo'])
            ->where('fecha', $json['fecha'])
            ->first();

        $datos = [
            'reserva_codigo' => $json['reserva_codigo'],
            'mesa_id'        => (int) $json['mesa_id'],
            'sucursal_id'    => $sucursalId,
            'rp_codigo'      => $json['rp_codigo'] ?? null,
            'cliente_nombre' => $json['cliente_nombre'] ?? null,
            'fecha'          => $json['fecha'],
            'hora'           => $json['hora'] ?? null,
            'estado_mesa'    => $existente['estado_mesa'] ?? 'reservada',
            'asignado_por'   => session()->get('usuario_id'),
        ];

        // Conserva tags del perfil por email si la asignación aún no tiene
        $email = ClienteModel::normalizarEmail($json['email'] ?? '');
        $reservaApi = null;
        if ($email === '') {
            $reservaApi = (new ReservasApiService())->buscarEnListadoActivo(
                (string) $json['reserva_codigo'],
                $sucursalId,
                (string) $json['fecha']
            );
            $email = ClienteModel::normalizarEmail($reservaApi['email'] ?? '');
            if ($email !== '' && empty($datos['cliente_nombre']) && ! empty($reservaApi['nombre'])) {
                $datos['cliente_nombre'] = $reservaApi['nombre'];
            }
        }
        if ($reservaApi === null) {
            $reservaApi = (new ReservasApiService())->buscarEnListadoActivo(
                (string) $json['reserva_codigo'],
                $sucursalId,
                (string) $json['fecha']
            );
        }
        if ($existente === null || $existente['pax_reservados'] === null) {
            $datos['pax_reservados'] = (int) ($json['pax'] ?? $reservaApi['pax'] ?? 0);
        }
        if ($existente === null) {
            $datos['pax_en_mesa'] = 0;
        }

        if ($email !== '' && (! $existente || empty($existente['tags_json']))) {
            $cliente = model(ClienteModel::class)->porEmail($email);
            if ($cliente && ! empty($cliente['tags_json'])) {
                $datos['tags_json'] = is_string($cliente['tags_json'])
                    ? $cliente['tags_json']
                    : json_encode($cliente['tags_json'], JSON_UNESCAPED_UNICODE);
            }
        }

        if ($existente) {
            // No degradar ocupada al reasignar mesa
            if (($existente['estado_mesa'] ?? '') === 'liberada') {
                $datos['estado_mesa'] = 'reservada';
                $datos['pax_en_mesa'] = 0;
            }
            $model->update($existente['id'], $datos);
            $asignacionId = (int) $existente['id'];
            $mesaAntes = model(MesaModel::class)->find($existente['mesa_id']);
            $mesaDespues = model(MesaModel::class)->find($datos['mesa_id']);
            $numAntes = $mesaAntes['numero'] ?? $existente['mesa_id'];
            $numDespues = $mesaDespues['numero'] ?? $datos['mesa_id'];
            if ((int) $existente['mesa_id'] !== (int) $datos['mesa_id']) {
                (new ReservaActividadService())->registrar(
                    (string) $json['reserva_codigo'],
                    'mesa_cambiada',
                    "cambió la mesa de #{$numAntes} a #{$numDespues}",
                    ['mesa_antes' => (int) $existente['mesa_id'], 'mesa_despues' => (int) $datos['mesa_id']],
                    $sucursalId
                );
            }
        } else {
            $model->insert($datos);
            $asignacionId = (int) $model->getInsertID();
            $mesa = model(MesaModel::class)->find($datos['mesa_id']);
            $num = $mesa['numero'] ?? $datos['mesa_id'];
            (new ReservaActividadService())->registrar(
                (string) $json['reserva_codigo'],
                'mesa_asignada',
                "asignó la mesa #{$num}",
                ['mesa_id' => (int) $datos['mesa_id']],
                $sucursalId
            );
        }

        return $this->response->setJSON([
            'success'    => true,
            'message'    => 'Mesa asignada correctamente.',
            'asignacion' => $this->enriquecerAsignacion($model->find($asignacionId)),
        ]);
    }

    /**
     * Registra una oleada de pax (+/−). Primera llegada positiva → Arrived.
     * Si aún no hay asignación, crea un check-in sin mesa (walk-in de llegada).
     */
    public function registrarLlegada()
    {
        $json = $this->request->getJSON(true) ?? [];
        $codigo = trim((string) ($json['reserva_codigo'] ?? ''));
        $delta  = (int) ($json['pax_delta'] ?? 0);

        if ($codigo === '' || $delta === 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Indica la reserva y un número de pax distinto de cero.',
            ]);
        }

        $sucursalId = (int) (session()->get('sucursal_id') ?? 0);
        if ($sucursalId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Selecciona un establecimiento.',
            ]);
        }

        $model = model(ReservaAsignacionModel::class);
        $asignacion = $model->porCodigo($codigo);

        // Sin mesa aún: crea registro de check-in para poder sumar pax (Arrived)
        if (! $asignacion) {
            $fecha = trim((string) ($json['fecha'] ?? date('Y-m-d')));
            $reservaApi = (new ReservasApiService())->buscarEnListadoActivo($codigo, $sucursalId, $fecha);
            $paxRes = (int) ($json['pax'] ?? $reservaApi['pax'] ?? 0);
            $nombre = trim((string) ($json['cliente_nombre'] ?? $reservaApi['nombre'] ?? ''));
            $hora   = $reservaApi['hora'] ?? null;

            $model->insert([
                'reserva_codigo' => $codigo,
                'mesa_id'        => null,
                'sucursal_id'    => $sucursalId,
                'cliente_nombre' => $nombre !== '' ? $nombre : null,
                'fecha'          => $fecha,
                'hora'           => $hora,
                'pax_reservados' => $paxRes > 0 ? $paxRes : null,
                'pax_en_mesa'    => 0,
                'estado_mesa'    => 'reservada',
                'asignado_por'   => session()->get('usuario_id'),
            ]);
            $asignacion = $model->find($model->getInsertID());
            if (! $asignacion) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error'   => 'No se pudo iniciar el check-in de la reserva.',
                ]);
            }
        }

        if (($asignacion['estado_mesa'] ?? '') === 'liberada') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'La reserva ya fue liberada.',
            ]);
        }

        $nuevo = max(0, (int) ($asignacion['pax_en_mesa'] ?? 0) + $delta);
        $ahora = date('Y-m-d H:i:s');
        $update = ['pax_en_mesa' => $nuevo];

        if ($nuevo > 0 && empty($asignacion['arrived_at'])) {
            $update['arrived_at'] = $ahora;
        }

        $model->update($asignacion['id'], $update);

        model(ReservaLlegadaModel::class)->insert([
            'asignacion_id'  => (int) $asignacion['id'],
            'pax_delta'      => $delta,
            'pax_resultante' => $nuevo,
            'nota'           => $json['nota'] ?? null,
            'registrado_por' => session()->get('usuario_id'),
            'created_at'     => $ahora,
        ]);

        $signo = $delta > 0 ? '+' : '';
        (new ReservaActividadService())->registrar(
            $codigo,
            'llegada_pax',
            "registró {$signo}{$delta} pax → {$nuevo} en mesa",
            ['pax_delta' => $delta, 'pax_en_mesa' => $nuevo, 'sin_mesa' => empty($asignacion['mesa_id'])],
            $sucursalId
        );

        $warning = null;
        if (! empty($asignacion['mesa_id'])) {
            $mesa = model(MesaModel::class)->find($asignacion['mesa_id']);
            if ($mesa && $nuevo > (int) $mesa['maximo']) {
                $warning = 'Los pax en mesa (' . $nuevo . ') superan el máximo de la mesa (' . (int) $mesa['maximo'] . ').';
            }
        }

        return $this->response->setJSON([
            'success'    => true,
            'warning'    => $warning,
            'asignacion' => $this->enriquecerAsignacion($model->find($asignacion['id'])),
        ]);
    }

    /** Marca la mesa como ocupada (sentados). */
    public function sentar()
    {
        $json = $this->request->getJSON(true) ?? [];
        $codigo = trim((string) ($json['reserva_codigo'] ?? ''));
        $model = model(ReservaAsignacionModel::class);
        $asignacion = $model->porCodigo($codigo);

        if (! $asignacion) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Asigna una mesa antes de sentar.',
            ]);
        }

        $ahora = date('Y-m-d H:i:s');
        $update = [
            'estado_mesa' => 'ocupada',
            'seated_at'   => $ahora,
        ];
        if (empty($asignacion['arrived_at'])) {
            $update['arrived_at'] = $ahora;
        }
        if ((int) ($asignacion['pax_en_mesa'] ?? 0) === 0 && (int) ($asignacion['pax_reservados'] ?? 0) > 0) {
            // Sentar sin oleadas previas: asume pax reservados presentes
            $update['pax_en_mesa'] = (int) $asignacion['pax_reservados'];
            model(ReservaLlegadaModel::class)->insert([
                'asignacion_id'  => (int) $asignacion['id'],
                'pax_delta'      => (int) $asignacion['pax_reservados'],
                'pax_resultante' => (int) $asignacion['pax_reservados'],
                'nota'           => 'Sentar (pax reservados)',
                'registrado_por' => session()->get('usuario_id'),
                'created_at'     => $ahora,
            ]);
        }

        $model->update($asignacion['id'], $update);

        (new ReservaActividadService())->registrar(
            $codigo,
            'sentar',
            'marcó la reserva como sentada (ocupada)',
            ['estado_mesa' => 'ocupada']
        );

        return $this->response->setJSON([
            'success'    => true,
            'asignacion' => $this->enriquecerAsignacion($model->find($asignacion['id'])),
        ]);
    }

    /** Libera la mesa. */
    public function liberar()
    {
        $json = $this->request->getJSON(true) ?? [];
        $codigo = trim((string) ($json['reserva_codigo'] ?? ''));
        $model = model(ReservaAsignacionModel::class);
        $asignacion = $model->porCodigo($codigo);

        if (! $asignacion) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Asignación no encontrada.',
            ]);
        }

        $model->update($asignacion['id'], [
            'estado_mesa'  => 'liberada',
            'liberated_at' => date('Y-m-d H:i:s'),
        ]);

        (new ReservaActividadService())->registrar(
            $codigo,
            'liberar',
            'liberó la mesa de la reserva',
            ['estado_mesa' => 'liberada']
        );

        return $this->response->setJSON([
            'success'    => true,
            'asignacion' => $this->enriquecerAsignacion($model->find($asignacion['id'])),
        ]);
    }

    public function agregarTags()
    {
        $json  = $this->request->getJSON(true) ?? [];
        $tags  = is_array($json['tags'] ?? null) ? $json['tags'] : [];
        $codigo = trim((string) ($json['reserva_codigo'] ?? ''));
        $email  = ClienteModel::normalizarEmail($json['email'] ?? '');

        if ($codigo === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Falta el código de reserva.',
            ]);
        }

        $clienteGuardado = false;
        if ($email !== '') {
            model(ClienteModel::class)->upsertTags($email, $tags, [
                'nombre'   => $json['cliente_nombre'] ?? null,
                'telefono' => $json['telefono'] ?? null,
            ]);
            $clienteGuardado = true;
        }

        $asignacion = model(ReservaAsignacionModel::class)
            ->where('reserva_codigo', $codigo)
            ->orderBy('created_at', 'DESC')
            ->first();

        if ($asignacion) {
            model(ReservaAsignacionModel::class)->update($asignacion['id'], [
                'tags_json' => json_encode(array_values($tags), JSON_UNESCAPED_UNICODE),
            ]);
        }

        if (! $asignacion && ! $clienteGuardado) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Sin email de cliente ni asignación de mesa; no se pueden guardar tags.',
            ]);
        }

        $nombres = [];
        foreach ($tags as $t) {
            if (is_array($t) && ! empty($t['nombre'])) {
                $nombres[] = (string) $t['nombre'];
            } elseif (is_string($t) && $t !== '') {
                $nombres[] = $t;
            }
        }
        $listaTags = $nombres === [] ? 'sin tags' : implode(', ', $nombres);
        (new ReservaActividadService())->registrar(
            $codigo,
            'tags',
            'actualizó los tags de la reserva: ' . $listaTags,
            ['tags' => $tags]
        );

        return $this->response->setJSON([
            'success' => true,
            'cliente' => $clienteGuardado,
        ]);
    }

    /**
     * JSON — historial de actividad consolidado de la reserva.
     */
    public function actividad()
    {
        $codigo = trim((string) ($this->request->getGet('codigo') ?? ''));
        if ($codigo === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Falta el código de reserva.',
            ]);
        }

        $sucursalId = (int) (session()->get('sucursal_id') ?? 0);

        try {
            $eventos = (new ReservaActividadService())->listar($codigo, $sucursalId);
        } catch (\Throwable $e) {
            log_message('error', 'Actividad: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => true,
                'codigo'  => $codigo,
                'eventos' => [],
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'codigo'  => $codigo,
            'eventos' => $eventos,
        ]);
    }

    /**
     * Adjunta cliente_tags desde el perfil por email (primera visita = []).
     *
     * @param  list<array<string, mixed>> $reservas
     * @return list<array<string, mixed>>
     */
    private function enriquecerReservasConTagsCliente(array $reservas): array
    {
        if ($reservas === []) {
            return $reservas;
        }

        $emails = [];
        foreach ($reservas as $reserva) {
            $emails[] = $reserva['email'] ?? '';
        }

        $mapa = model(ClienteModel::class)->tagsPorEmails($emails);

        foreach ($reservas as &$reserva) {
            $email = ClienteModel::normalizarEmail($reserva['email'] ?? '');
            $reserva['cliente_tags'] = $mapa[$email] ?? [];
        }
        unset($reserva);

        return $reservas;
    }

    /**
     * Añade mesa, status label y llegadas a la asignación.
     */
    private function enriquecerAsignacion(?array $asignacion): ?array
    {
        if (! $asignacion) {
            return null;
        }

        $mesa = ! empty($asignacion['mesa_id'])
            ? model(MesaModel::class)->find($asignacion['mesa_id'])
            : null;
        $asignacion['mesa_numero']   = $mesa['numero'] ?? null;
        $asignacion['mesa_maximo']   = isset($mesa['maximo']) ? (int) $mesa['maximo'] : null;
        $asignacion['status_label']  = ReservaAsignacionModel::statusLabel($asignacion);
        $asignacion['pax_reservados'] = $asignacion['pax_reservados'] !== null
            ? (int) $asignacion['pax_reservados']
            : null;
        $asignacion['pax_en_mesa'] = (int) ($asignacion['pax_en_mesa'] ?? 0);
        $asignacion['llegadas'] = model(ReservaLlegadaModel::class)->porAsignacion((int) $asignacion['id']);

        return $asignacion;
    }

    public function plano()
    {
        $fecha = $this->request->getGet('fecha') ?? date('Y-m-d');

        $solicitada = (int) $this->request->getGet('sucursal_id');
        if ($solicitada > 0) {
            $this->establecerSucursalSiPermitida($solicitada);
        }

        $sucursalId = (int) session()->get('sucursal_id');
        $codigo     = trim((string) ($this->request->getGet('reserva') ?? ''));
        $nombre     = trim((string) ($this->request->getGet('nombre') ?? ''));

        if ($codigo !== '' && $nombre === '' && $sucursalId > 0) {
            $reserva = (new ReservasApiService())->buscarEnListadoActivo($codigo, $sucursalId, $fecha);
            $nombre  = trim((string) ($reserva['nombre'] ?? ''));
        }

        $sucursal = $sucursalId > 0
            ? model(SucursalModel::class)->find($sucursalId)
            : null;

        return view('hostess/reservas/plano', [
            'titulo'               => 'Plano de mesas',
            'fecha'                => $fecha,
            'sucursalId'           => $sucursalId,
            'sucursalNombre'       => trim((string) ($sucursal['nombre'] ?? '')),
            'reservaCodigo'        => $codigo,
            'reservaNombre'        => $nombre,
            'mostrarTituloTopbar'  => false,
        ]);
    }

    /**
     * Ocupa una mesa libre para invitados sin reserva (walk-in).
     */
    public function ocuparWalkIn()
    {
        $json = $this->request->getJSON(true) ?? [];

        $rules = [
            'mesa_id'        => 'required|integer',
            'fecha'          => 'required|valid_date[Y-m-d]',
            'pax'            => 'required|integer|greater_than[0]',
            'cliente_nombre' => 'permit_empty|max_length[150]',
        ];

        if (! $this->validateData($json, $rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'errors'  => $this->validator->getErrors(),
                'error'   => 'Datos inválidos para ocupar la mesa.',
            ]);
        }

        $sucursalId = (int) session()->get('sucursal_id');
        if ($sucursalId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'error'   => 'Selecciona un establecimiento.',
            ]);
        }

        $mesaId = (int) $json['mesa_id'];
        $fecha  = (string) $json['fecha'];
        $pax    = (int) $json['pax'];
        $nombre = trim((string) ($json['cliente_nombre'] ?? ''));
        if ($nombre === '') {
            $nombre = 'Invitado';
        }

        $mesa = model(MesaModel::class)->find($mesaId);
        if (! $mesa) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Mesa no encontrada.',
            ]);
        }

        // Evita ocupar si ya hay reserva/sentados en esa mesa hoy
        $model  = model(ReservaAsignacionModel::class);
        $activa = $model->where('mesa_id', $mesaId)
            ->where('sucursal_id', $sucursalId)
            ->where('fecha', $fecha)
            ->whereIn('estado_mesa', ['reservada', 'ocupada'])
            ->first();

        if ($activa) {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'error'   => 'La mesa ya está reservada u ocupada.',
            ]);
        }

        $codigo = $this->generarCodigoWalkIn();
        $ahora  = date('Y-m-d H:i:s');
        $hora   = date('H:i:s');

        $insertOk = $model->insert([
            'reserva_codigo' => $codigo,
            'mesa_id'        => $mesaId,
            'sucursal_id'    => $sucursalId,
            'cliente_nombre' => $nombre,
            'fecha'          => $fecha,
            'hora'           => $hora,
            'pax_reservados' => $pax,
            'pax_en_mesa'    => $pax,
            'estado_mesa'    => 'ocupada',
            'arrived_at'     => $ahora,
            'seated_at'      => $ahora,
            'asignado_por'   => session()->get('usuario_id'),
        ]);

        if (! $insertOk) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'No se pudo ocupar la mesa.',
            ]);
        }

        $id = (int) $model->getInsertID();

        $num = $mesa['numero'] ?? $mesaId;
        (new ReservaActividadService())->registrar(
            $codigo,
            'walk_in',
            "ocupó la mesa #{$num} sin reserva (walk-in, {$pax} pax)",
            [
                'mesa_id' => $mesaId,
                'pax'     => $pax,
                'walk_in' => true,
            ],
            $sucursalId
        );

        model(ReservaLlegadaModel::class)->insert([
            'asignacion_id'  => $id,
            'pax_delta'      => $pax,
            'pax_resultante' => $pax,
            'nota'           => 'Walk-in (sin reserva)',
            'registrado_por' => session()->get('usuario_id'),
            'created_at'     => $ahora,
        ]);

        return $this->response->setJSON([
            'success'    => true,
            'message'    => 'Mesa ocupada para invitado.',
            'asignacion' => $this->enriquecerAsignacion($model->find($id)),
        ]);
    }

    /** Código local único para walk-ins (no viene de OneReservations). */
    private function generarCodigoWalkIn(): string
    {
        return 'WI-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /** El rol administrador puede operar cualquier sucursal activa. */
    private function esAdmin(): bool
    {
        return session()->get('rol') === 'admin';
    }

    /** IDs de sucursales que el usuario puede ver en filtros y reservas */
    private function idsSucursalesPermitidas(): array
    {
        if ($this->esAdmin()) {
            return model(SucursalModel::class)->idsActivas();
        }

        $sucursales = session()->get('sucursales') ?? [];

        return array_values(array_map(static fn ($s) => (int) $s['id'], $sucursales));
    }

    /** Cambia la sucursal activa si el usuario tiene permiso */
    private function establecerSucursalSiPermitida(int $sucursalId): bool
    {
        if ($sucursalId <= 0) {
            return false;
        }

        if ($this->esAdmin()) {
            $sucursal = model(SucursalModel::class)->obtenerConPais($sucursalId);

            if ($sucursal) {
                session()->set('sucursal_id', $sucursalId);

                return true;
            }

            return false;
        }

        foreach (session()->get('sucursales') ?? [] as $sucursal) {
            if ((int) $sucursal['id'] === $sucursalId) {
                session()->set('sucursal_id', $sucursalId);

                return true;
            }
        }

        return false;
    }

    /** Datos de la sucursal activa desde la sesión */
    private function sucursalActivaPorId(int $sucursalId): ?array
    {
        foreach (session()->get('sucursales') ?? [] as $sucursal) {
            if ((int) $sucursal['id'] === $sucursalId) {
                return $sucursal;
            }
        }

        if ($this->esAdmin() && $sucursalId > 0) {
            return model(SucursalModel::class)->obtenerConPais($sucursalId);
        }

        $sucursales = session()->get('sucursales') ?? [];

        return $sucursales[0] ?? null;
    }
}
