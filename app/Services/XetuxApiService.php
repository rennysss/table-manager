<?php

namespace App\Services;

use App\Models\SucursalModel;
use CodeIgniter\HTTP\CURLRequest;

/**
 * Cliente HTTP para integración POS Xetux (meseros, mesas, órdenes).
 */
class XetuxApiService
{
    private ?string $ultimoError = null;

    /** @var array<string, mixed>|null */
    private ?array $ultimaRespuesta = null;

    public function obtenerUltimoError(): ?string
    {
        return $this->ultimoError;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerUltimaRespuesta(): ?array
    {
        return $this->ultimaRespuesta;
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function meserosDisponibles(int $sucursalId): array
    {
        $datos = $this->getJson($sucursalId, '/xspos/api/integration/order/availableWaiters');
        if ($datos === null) {
            return [];
        }

        return $this->normalizarListaId($datos, static function (array $fila): string {
            $nombre = trim((string) ($fila['name'] ?? $fila['nombre'] ?? $fila['waiterName'] ?? ''));

            return $nombre !== '' ? $nombre : ('Mesero #' . ($fila['id'] ?? ''));
        });
    }

    /**
     * Mesas POS; la etiqueta incluye ambiente para distinguir números repetidos.
     *
     * @return list<array{id: int, label: string, space_number: ?string, environment: ?string}>
     */
    public function mesasDisponibles(int $sucursalId): array
    {
        $datos = $this->getJson($sucursalId, '/xspos/api/integration/order/availableSpaces');
        if ($datos === null) {
            return [];
        }

        $lista = [];
        foreach ($this->extraerFilas($datos) as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $id = $this->extraerId($fila);
            if ($id === null) {
                continue;
            }
            $numero = trim((string) ($fila['spaceNumber'] ?? $fila['number'] ?? $fila['mesa'] ?? $fila['name'] ?? ''));
            $ambiente = trim((string) ($fila['environment'] ?? $fila['ambiente'] ?? $fila['area'] ?? ''));
            $label = 'Mesa: ' . ($numero !== '' ? $numero : (string) $id);
            if ($ambiente !== '') {
                $label .= ' AMBIENTE: ' . $ambiente;
            }
            $lista[] = [
                'id'            => $id,
                'label'         => $label,
                'space_number'  => $numero !== '' ? $numero : null,
                'environment'   => $ambiente !== '' ? $ambiente : null,
            ];
        }

        return $lista;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    public function crearOrden(int $sucursalId, array $payload): ?array
    {
        return $this->postJson($sucursalId, '/xspos/api/integration/order/create', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    public function agregarPago(int $sucursalId, array $payload): ?array
    {
        return $this->postJson($sucursalId, '/xspos/api/integration/order/addPayment', $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function infoOrden(int $sucursalId, int $suborderId, bool $detailProductAmounts = true): ?array
    {
        $query = http_build_query([
            'suborderId'             => $suborderId,
            'detailProductAmounts'   => $detailProductAmounts ? 'true' : 'false',
        ]);

        return $this->getJson($sucursalId, '/xspos/api/integration/order/info?' . $query);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getJson(int $sucursalId, string $pathConQuery): ?array
    {
        $this->ultimoError = null;
        $this->ultimaRespuesta = null;

        $cfg = $this->configuracion($sucursalId);
        if ($cfg === null) {
            return null;
        }

        $url = $cfg['base'] . $pathConQuery;

        try {
            /** @var CURLRequest $client */
            $client = service('curlrequest', ['timeout' => 30]);
            $response = $client->get($url, [
                'headers' => $this->headers($cfg['api_key']),
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            $this->ultimoError = 'No se pudo conectar con Xetux: ' . $e->getMessage();
            log_message('error', 'Xetux GET ' . $url . ' — ' . $e->getMessage());

            return null;
        }

        return $this->interpretarRespuesta($response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    private function postJson(int $sucursalId, string $path, array $payload): ?array
    {
        $this->ultimoError = null;
        $this->ultimaRespuesta = null;

        $cfg = $this->configuracion($sucursalId);
        if ($cfg === null) {
            return null;
        }

        $url = $cfg['base'] . $path;

        try {
            /** @var CURLRequest $client */
            $client = service('curlrequest', ['timeout' => 45]);
            $response = $client->post($url, [
                'headers' => array_merge($this->headers($cfg['api_key']), [
                    'Content-Type' => 'application/json',
                ]),
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            $this->ultimoError = 'No se pudo conectar con Xetux: ' . $e->getMessage();
            log_message('error', 'Xetux POST ' . $url . ' — ' . $e->getMessage());

            return null;
        }

        return $this->interpretarRespuesta($response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @return array{base: string, api_key: string, station_code: string, payform_id: ?int}|null
     */
    public function configuracion(int $sucursalId): ?array
    {
        $sucursal = model(SucursalModel::class)->find($sucursalId);
        if (! $sucursal) {
            $this->ultimoError = 'Sucursal no encontrada.';

            return null;
        }

        $base = rtrim(trim((string) ($sucursal['xetux_base_url'] ?? '')), '/');
        $apiKey = trim((string) ($sucursal['xetux_api_key'] ?? ''));
        $station = trim((string) ($sucursal['xetux_station_code'] ?? 'RES01'));

        if ($base === '') {
            $this->ultimoError = 'Configure la URL base de Xetux en la sucursal.';

            return null;
        }
        if ($apiKey === '') {
            $this->ultimoError = 'Configure la API Key de Xetux en la sucursal.';

            return null;
        }

        $payform = $sucursal['xetux_payform_id'] ?? null;

        return [
            'base'          => $base,
            'api_key'       => $apiKey,
            'station_code'  => $station !== '' ? $station : 'RES01',
            'payform_id'    => $payform !== null && $payform !== '' ? (int) $payform : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $apiKey): array
    {
        return [
            'Authorization' => $apiKey,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function interpretarRespuesta(int $status, string $body): ?array
    {
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            $this->ultimoError = 'Respuesta inválida de Xetux (HTTP ' . $status . ').';
            log_message('error', 'Xetux body no JSON: ' . substr($body, 0, 500));

            return null;
        }

        $this->ultimaRespuesta = $decoded;

        if ($status < 200 || $status >= 300) {
            $msg = (string) ($decoded['message'] ?? $decoded['error'] ?? $decoded['mensaje'] ?? '');
            $this->ultimoError = $msg !== '' ? $msg : ('Xetux respondió HTTP ' . $status);

            return null;
        }

        if (isset($decoded['success']) && $decoded['success'] === false) {
            $msg = (string) ($decoded['message'] ?? $decoded['error'] ?? 'Operación rechazada por Xetux');
            $this->ultimoError = $msg;

            return null;
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $decoded
     *
     * @return list<mixed>
     */
    private function extraerFilas(array $decoded): array
    {
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            return array_values($decoded['data']);
        }
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            return array_values($decoded['items']);
        }
        if ($this->esLista($decoded)) {
            return array_values($decoded);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $decoded
     * @param callable(array<string, mixed>): string $etiqueta
     *
     * @return list<array{id: int, label: string}>
     */
    private function normalizarListaId(array $decoded, callable $etiqueta): array
    {
        $lista = [];
        foreach ($this->extraerFilas($decoded) as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $id = $this->extraerId($fila);
            if ($id === null) {
                continue;
            }
            $lista[] = [
                'id'    => $id,
                'label' => $etiqueta($fila),
            ];
        }

        return $lista;
    }

    /**
     * @param array<string, mixed> $fila
     */
    private function extraerId(array $fila): ?int
    {
        foreach (['id', 'Id', 'ID', 'waiterId', 'spaceId', 'userId'] as $clave) {
            if (isset($fila[$clave]) && is_numeric($fila[$clave])) {
                return (int) $fila[$clave];
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $arr
     */
    private function esLista(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Separa nombre completo en nombre y apellido(s).
     *
     * @return array{0: string, 1: string}
     */
    public static function partirNombre(string $nombreCompleto): array
    {
        $partes = preg_split('/\s+/u', trim($nombreCompleto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($partes === []) {
            return ['Invitado', ''];
        }
        if (count($partes) === 1) {
            return [$partes[0], ''];
        }

        return [$partes[0], implode(' ', array_slice($partes, 1))];
    }
}
