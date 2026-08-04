<?php

namespace App\Services;

use App\Models\SucursalModel;

/**
 * Cliente HTTP para OneReservations API v1 (reservas externas).
 */
class ReservasApiService
{
    private string $baseUrl;

    /** API Key global de respaldo (.env) */
    private string $apiKeyGlobal;

    /** API Key activa para la petición en curso */
    private string $apiKey = '';

    private ?string $ultimoError = null;

    private bool $modoDemo = false;

    /** Caché en memoria del listado por sucursal + fecha (evita llamadas duplicadas al API). */
    private array $cacheListado = [];

    /** Caché de filas de sucursal por ID dentro de la misma petición. */
    private array $cacheSucursales = [];

    public function __construct()
    {
        $mapping          = config('DatabaseMapping');
        $reservas         = config('Reservas');
        $this->baseUrl    = rtrim($reservas->apiUrl ?: $mapping->reservasApiUrl ?: '', '/');
        $this->apiKeyGlobal = $reservas->apiKey ?: $mapping->reservasApiToken ?: '';
    }

    public function obtenerUltimoError(): ?string
    {
        return $this->ultimoError;
    }

    public function esModoDemo(): bool
    {
        return $this->modoDemo;
    }

    /**
     * Listado de reservas activas por sucursal y fecha de visita.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorFecha(int $sucursalId, string $fecha): array
    {
        $clave = $sucursalId . '|' . $fecha;

        if (isset($this->cacheListado[$clave])) {
            $entrada              = $this->cacheListado[$clave];
            $this->ultimoError    = $entrada['error'];
            $this->modoDemo       = $entrada['modo_demo'];

            return $entrada['lista'];
        }

        $this->ultimoError = null;
        $this->modoDemo    = false;

        if (! $this->validarFecha($fecha)) {
            $this->ultimoError = 'Formato de fecha inválido. Use Y-m-d.';

            return $this->guardarListadoCache($clave, []);
        }

        if (! $this->prepararApiKey($sucursalId)) {
            return $this->guardarListadoCache($clave, $this->datosDemo($fecha));
        }

        $disco = $this->discoDeSucursal($sucursalId);

        if ($disco <= 0) {
            $this->ultimoError = 'La sucursal no tiene Venue ID (One Reservations) configurado.';

            return $this->guardarListadoCache($clave, []);
        }

        $respuesta = $this->peticionGet('/reservas/activas', [
            'disco'        => $disco,
            'fecha_visita' => $fecha,
        ]);

        if ($respuesta === null) {
            return $this->guardarListadoCache($clave, []);
        }

        $lista = [];
        foreach ($respuesta['data'] ?? [] as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $lista[] = $this->normalizarReserva($fila);
        }

        usort($lista, static function (array $a, array $b): int {
            $horaA = $a['hora'] === '—' ? '99:99' : $a['hora'];
            $horaB = $b['hora'] === '—' ? '99:99' : $b['hora'];

            return strcmp($horaA, $horaB);
        });

        return $this->guardarListadoCache($clave, $lista);
    }

    /**
     * @param array<int, array<string, mixed>> $lista
     *
     * @return array<int, array<string, mixed>>
     */
    private function guardarListadoCache(string $clave, array $lista): array
    {
        $this->cacheListado[$clave] = [
            'lista'     => $lista,
            'error'     => $this->ultimoError,
            'modo_demo' => $this->modoDemo,
        ];

        return $lista;
    }

    /**
     * Detalle por código de booking (1R-/MT-) o token cifrado del QR.
     */
    public function obtenerPorCodigo(string $codigo, int $sucursalId = 0): ?array
    {
        $this->ultimoError = null;
        $this->modoDemo    = false;

        $identificador = $this->resolverIdentificador($codigo);

        if ($identificador['valor'] === '') {
            $this->ultimoError = 'Código de reserva vacío.';

            return null;
        }

        if (! $this->prepararApiKey($sucursalId)) {
            foreach ($this->datosDemo(date('Y-m-d')) as $reserva) {
                if ($reserva['codigo'] === $identificador['valor']) {
                    return $reserva;
                }
            }

            $this->ultimoError = 'Reserva no encontrada (modo demostración).';

            return null;
        }

        $ruta  = $identificador['tipo'] === 'token' ? '/reservas/by-token' : '/reservas/by-booking';
        $param = $identificador['tipo'] === 'token' ? 'token' : 'booking';

        $query = [$param => $identificador['valor']];

        if ($identificador['tipo'] === 'booking' && $sucursalId > 0) {
            $disco = $this->discoDeSucursal($sucursalId);

            if ($disco > 0) {
                $query['disco'] = $disco;
            }
        }

        $respuesta = $this->peticionGet($ruta, $query);

        if ($respuesta === null || ! isset($respuesta['data']) || ! is_array($respuesta['data'])) {
            return null;
        }

        return $this->normalizarReserva($respuesta['data']);
    }

    /**
     * Busca una reserva en el listado activo del día (respaldo si by-booking falla).
     */
    public function buscarEnListadoActivo(string $codigo, int $sucursalId, string $fecha): ?array
    {
        $codigo = trim($codigo);

        if ($codigo === '' || $sucursalId <= 0 || ! $this->validarFecha($fecha)) {
            return null;
        }

        foreach ($this->obtenerPorFecha($sucursalId, $fecha) as $reserva) {
            if (($reserva['codigo'] ?? '') === $codigo) {
                return $reserva;
            }
        }

        return null;
    }

    /**
     * Historial de actividad de una reserva (API externa; falla silenciosa).
     *
     * @return list<array<string, mixed>>
     */
    public function obtenerActividad(string $codigo, int $sucursalId = 0): array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return [];
        }

        if (! $this->prepararApiKey($sucursalId)) {
            return $this->actividadDemo($codigo);
        }

        $query = ['booking' => $codigo];
        if ($sucursalId > 0) {
            $disco = $this->discoDeSucursal($sucursalId);
            if ($disco > 0) {
                $query['disco'] = $disco;
            }
        }

        // Endpoint dedicado (si el backend de reservas lo expone); no contaminar ultimoError
        $errorPrevio = $this->ultimoError;
        $respuesta    = $this->peticionGet('/reservas/actividad', $query);
        if ($respuesta !== null && isset($respuesta['data']) && is_array($respuesta['data'])) {
            return $this->normalizarListaActividad($respuesta['data']);
        }
        $this->ultimoError = $errorPrevio;

        return [];
    }

    /**
     * @param list<mixed> $lista
     *
     * @return list<array<string, mixed>>
     */
    private function normalizarListaActividad(array $lista): array
    {
        $out = [];
        foreach ($lista as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $desc = trim((string) (
                $item['descripcion'] ?? $item['description'] ?? $item['mensaje']
                ?? $item['message'] ?? $item['texto'] ?? ''
            ));
            if ($desc === '') {
                continue;
            }
            $out[] = [
                'id'             => 0,
                'tipo'           => (string) ($item['tipo'] ?? $item['type'] ?? 'externo'),
                'descripcion'    => $desc,
                'origen'         => (string) ($item['origen'] ?? $item['source'] ?? $item['platform'] ?? 'OneReservations'),
                'usuario_nombre' => (string) ($item['usuario'] ?? $item['user'] ?? $item['usuario_nombre'] ?? $item['nombre'] ?? 'Sistema'),
                'created_at'     => (string) ($item['created_at'] ?? $item['fecha'] ?? $item['timestamp'] ?? $item['date'] ?? ''),
                'externo_id'     => (string) ($item['id'] ?? $item['externo_id'] ?? ('ext-' . $i)),
                'fuente'         => 'api',
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function actividadDemo(string $codigo): array
    {
        if ($codigo !== 'RSV-001') {
            return [];
        }

        return [
            [
                'id'             => 0,
                'tipo'           => 'externo',
                'descripcion'    => 'creó la reserva para 4 pax con prepago 1,500.00 EUR',
                'origen'         => 'OneReservations',
                'usuario_nombre' => 'RP-JUAN',
                'created_at'     => date('Y-m-d') . ' 18:00:00',
                'externo_id'     => 'demo-book',
                'fuente'         => 'demo',
            ],
            [
                'id'             => 0,
                'tipo'           => 'externo',
                'descripcion'    => 'envió notificación de reserva a Carlos Mendoza',
                'origen'         => 'OneReservations',
                'usuario_nombre' => 'Sistema',
                'created_at'     => date('Y-m-d') . ' 18:01:00',
                'externo_id'     => 'demo-mail',
                'fuente'         => 'demo',
            ],
        ];
    }

    /**
     * @return array{tipo: string, valor: string}
     */
    private function resolverIdentificador(string $entrada): array
    {
        $entrada = trim($entrada);

        if (preg_match('/[?&]token=([^&]+)/i', $entrada, $coincidencia) === 1) {
            return ['tipo' => 'token', 'valor' => urldecode($coincidencia[1])];
        }

        if (preg_match('/^(1R|MT)-/i', $entrada) === 1) {
            return ['tipo' => 'booking', 'valor' => $entrada];
        }

        // Tokens cifrados del QR suelen ser largos (base64url)
        if (strlen($entrada) >= 20 && preg_match('/^[A-Za-z0-9_\-]+$/', $entrada) === 1) {
            return ['tipo' => 'token', 'valor' => $entrada];
        }

        return ['tipo' => 'booking', 'valor' => $entrada];
    }

    /**
     * @param array<string, mixed> $fila
     *
     * @return array<string, mixed>
     */
    private function normalizarReserva(array $fila): array
    {
        $hora = trim((string) ($fila['hora_checkin'] ?? ''));
        if ($hora === '' || strtoupper($hora) === 'N/A') {
            $hora = '—';
        } elseif (strlen($hora) > 5) {
            $hora = substr($hora, 0, 5);
        }

        $total   = $this->aFloat($fila['total'] ?? null);
        $balance = $this->aFloat($fila['balance'] ?? null);
        $prepago = $this->aFloat($fila['prepago'] ?? $fila['prepay'] ?? $fila['pagado'] ?? null);

        // Si no viene prepago explícito: lo pagado = total − saldo pendiente
        if ($prepago === null && $total !== null && $balance !== null) {
            $prepago = max(0, round($total - $balance, 2));
        }

        $moneda  = (string) ($fila['moneda'] ?? $fila['currency'] ?? '');
        $tarjeta = $this->normalizarTarjeta($fila);

        return [
            'codigo'          => (string) ($fila['booking'] ?? ''),
            'hora'            => $hora,
            'nombre'          => (string) ($fila['nombre'] ?? ''),
            'rp'              => (string) ($fila['realizado_por_nombre'] ?? 'Sin Registro'),
            'fecha'           => (string) ($fila['fecha_visita'] ?? ''),
            'pax'             => (int) ($fila['pax'] ?? 0),
            'estatus'         => (int) ($fila['estatus'] ?? 0),
            'checkin'         => (string) ($fila['checkin'] ?? ''),
            'email'           => (string) ($fila['email'] ?? ''),
            'telefono'        => (string) ($fila['telefono'] ?? ''),
            'disco'           => (int) ($fila['disco'] ?? 0),
            'disco_nombre'    => (string) ($fila['disco_nombre'] ?? ''),
            'producto_nombre' => (string) ($fila['producto_nombre'] ?? ''),
            'balance'         => $balance,
            'total'           => $total,
            'prepago'         => $prepago,
            'moneda'          => $moneda,
            'tarjeta'         => $tarjeta,
        ];
    }

    /**
     * Datos de tarjeta del prepago (API puede enviarlos planos o anidados en "tarjeta").
     *
     * @param array<string, mixed> $fila
     *
     * @return array{numero: ?string, banco: ?string, pais: ?string, brand: ?string, nombre: ?string}|null
     */
    private function normalizarTarjeta(array $fila): ?array
    {
        $anidada = is_array($fila['tarjeta'] ?? null) ? $fila['tarjeta'] : null;

        if ($anidada !== null) {
            $numero = $this->aTextoNoVacio(
                $anidada['numero'] ?? $anidada['tarjeta_numero'] ?? $anidada['numero_tarjeta']
                ?? $anidada['card_number'] ?? $anidada['pan'] ?? null
            );
            $banco = $this->aTextoNoVacio(
                $anidada['banco'] ?? $anidada['tarjeta_banco'] ?? $anidada['bank'] ?? null
            );
            $pais = $this->aTextoNoVacio(
                $anidada['pais'] ?? $anidada['tarjeta_pais'] ?? $anidada['country'] ?? null
            );
            $brand = $this->aTextoNoVacio(
                $anidada['brand'] ?? $anidada['tarjeta_brand'] ?? $anidada['card_brand']
                ?? $anidada['marca'] ?? null
            );
            $nombre = $this->aTextoNoVacio(
                $anidada['nombre'] ?? $anidada['tarjeta_nombre'] ?? $anidada['nombre_tarjeta']
                ?? $anidada['card_name'] ?? $anidada['cardholder'] ?? null
            );
        } else {
            // En la raíz solo claves explícitas de tarjeta (evitar chocar con nombre del huésped)
            $numero = $this->aTextoNoVacio(
                $fila['tarjeta_numero'] ?? $fila['numero_tarjeta'] ?? $fila['card_number'] ?? null
            );
            $banco = $this->aTextoNoVacio(
                $fila['tarjeta_banco'] ?? $fila['banco'] ?? $fila['bank'] ?? null
            );
            $pais = $this->aTextoNoVacio(
                $fila['tarjeta_pais'] ?? $fila['card_country'] ?? null
            );
            $brand = $this->aTextoNoVacio(
                $fila['tarjeta_brand'] ?? $fila['card_brand'] ?? $fila['marca_tarjeta'] ?? null
            );
            $nombre = $this->aTextoNoVacio(
                $fila['tarjeta_nombre'] ?? $fila['nombre_tarjeta'] ?? $fila['card_name']
                ?? $fila['cardholder'] ?? null
            );
        }

        if ($numero === null && $banco === null && $pais === null && $brand === null && $nombre === null) {
            return null;
        }

        return [
            'numero' => $numero,
            'banco'  => $banco,
            'pais'   => $pais,
            'brand'  => $brand,
            'nombre' => $nombre,
        ];
    }

    private function aTextoNoVacio(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $txt = trim((string) $valor);

        return $txt === '' ? null : $txt;
    }

    private function aFloat(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_string($valor)) {
            $valor = str_replace([',', ' '], ['.', ''], $valor);
        }
        if (! is_numeric($valor)) {
            return null;
        }

        return (float) $valor;
    }

    /**
     * @param array<string, scalar> $query
     *
     * @return array<string, mixed>|null
     */
    private function peticionGet(string $ruta, array $query = []): ?array
    {
        $cliente = \Config\Services::curlrequest();

        try {
            $respuesta = $cliente->get($this->baseUrl . $ruta, [
                'headers'     => $this->headers(),
                'query'       => $query,
                'timeout'     => 15,
                'http_errors' => false,
            ]);

            $estado = $respuesta->getStatusCode();
            $cuerpo = json_decode($respuesta->getBody(), true);

            if (! is_array($cuerpo)) {
                $this->ultimoError = 'Respuesta inválida del servicio de reservas.';
                log_message('error', "ReservasApiService: JSON inválido en {$ruta}");

                return null;
            }

            if ($estado === 401) {
                $this->ultimoError = (string) ($cuerpo['message'] ?? 'Acceso no autorizado (API Key inválida).');
                log_message('error', 'ReservasApiService 401: ' . $this->ultimoError);

                return null;
            }

            if ($estado >= 400) {
                $this->ultimoError = (string) ($cuerpo['message'] ?? "Error del servicio (HTTP {$estado}).");
                log_message('warning', "ReservasApiService {$estado} {$ruta}: " . $this->ultimoError);

                return null;
            }

            if (($cuerpo['status'] ?? '') !== 'success') {
                $this->ultimoError = (string) ($cuerpo['message'] ?? 'El servicio de reservas no devolvió éxito.');

                return null;
            }

            return $cuerpo;
        } catch (\Throwable $e) {
            $this->ultimoError = 'No se pudo conectar con el servicio de reservas.';
            log_message('error', 'ReservasApiService: ' . $e->getMessage());

            return null;
        }
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'Accept'    => 'application/json',
            'X-API-Key' => $this->apiKey,
        ];
    }

    private function validarFecha(string $fecha): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);

        return $dt !== false && $dt->format('Y-m-d') === $fecha;
    }

    /** Venue ID de la sucursal = parámetro disco del API. */
    private function discoDeSucursal(int $sucursalId): int
    {
        $sucursal = $this->sucursalPorId($sucursalId);
        $venueId  = trim((string) ($sucursal['venue_id'] ?? ''));

        if ($venueId === '' || ! ctype_digit($venueId)) {
            return 0;
        }

        return (int) $venueId;
    }

    /** @return array<string, mixed> */
    private function sucursalPorId(int $sucursalId): array
    {
        if ($sucursalId <= 0) {
            return [];
        }

        if (! isset($this->cacheSucursales[$sucursalId])) {
            $this->cacheSucursales[$sucursalId] = model(SucursalModel::class)->find($sucursalId) ?? [];
        }

        return $this->cacheSucursales[$sucursalId];
    }

    /** Resuelve API Key: primero la de la sucursal, luego la global del .env */
    private function resolverApiKey(int $sucursalId): string
    {
        if ($sucursalId > 0) {
            $sucursal = $this->sucursalPorId($sucursalId);
            $apiKey   = trim((string) ($sucursal['reservas_api_key'] ?? ''));

            if ($apiKey !== '') {
                return $apiKey;
            }
        }

        return $this->apiKeyGlobal;
    }

    /** Carga la API Key para la sucursal; activa modo demo si no hay clave. */
    private function prepararApiKey(int $sucursalId): bool
    {
        $this->apiKey = $this->resolverApiKey($sucursalId);

        if ($this->apiKey === '') {
            $this->modoDemo = true;

            return false;
        }

        return true;
    }

    /** Datos de demostración cuando no hay API Key configurada. */
    private function datosDemo(string $fecha): array
    {
        return [
            $this->normalizarReserva([
                'booking'               => 'RSV-001',
                'hora_checkin'          => '22:00',
                'nombre'                => 'Carlos Mendoza',
                'realizado_por_nombre'  => 'RP-JUAN',
                'fecha_visita'          => $fecha,
                'pax'                   => 4,
                'email'                 => 'carlos.mendoza@demo.local',
                'telefono'              => '555-0101',
                'total'                 => 3000,
                'balance'               => 1500,
                'prepago'               => 1500,
                'moneda'                => 'EUR',
                'tarjeta'               => [
                    'numero' => '**** **** **** 4242',
                    'banco'  => 'BBVA',
                    'pais'   => 'ES',
                    'brand'  => 'Visa',
                    'nombre' => 'CARLOS MENDOZA',
                ],
            ]),
            $this->normalizarReserva([
                'booking'              => 'RSV-002',
                'hora_checkin'         => '22:30',
                'nombre'               => 'Ana García',
                'realizado_por_nombre' => 'RP-MARIA',
                'fecha_visita'         => $fecha,
                'pax'                  => 2,
                'email'                => 'ana.garcia@demo.local',
                'total'                => 800,
                'balance'              => 800,
                'moneda'               => 'EUR',
            ]),
            $this->normalizarReserva([
                'booking'              => 'RSV-003',
                'hora_checkin'         => '23:00',
                'nombre'               => 'Luis Torres',
                'realizado_por_nombre' => 'RP-JUAN',
                'fecha_visita'         => $fecha,
                'pax'                  => 6,
            ]),
        ];
    }
}
