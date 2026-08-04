<?php

namespace App\Commands;

use App\Services\ReservasApiService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Prueba real de conexión a OneReservations por sucursal y fecha.
 */
class ProbarReservasApi extends BaseCommand
{
    protected $group       = 'Reservas';
    protected $name        = 'reservas:probar';
    protected $description = 'Prueba el API de reservas con venue_id y API Key de una sucursal.';
    protected $usage       = 'reservas:probar <sucursal_id> [fecha]';
    protected $arguments   = [
        'sucursal_id' => 'ID de la sucursal (tm_sucursales.id)',
        'fecha'       => 'Fecha de visita Y-m-d (opcional, por defecto hoy)',
    ];

    public function run(array $params): void
    {
        $sucursalId = (int) ($params[0] ?? 0);
        $fecha      = $params[1] ?? date('Y-m-d');

        if ($sucursalId <= 0) {
            CLI::error('Indica el ID de sucursal. Ejemplo: php spark reservas:probar 3 2026-07-17');
            return;
        }

        $db  = Database::connect();
        $row = $db->query(
            'SELECT id, nombre, venue_id,
                    IF(reservas_api_key IS NOT NULL AND reservas_api_key != "", "SI", "NO") AS tiene_key,
                    LENGTH(COALESCE(reservas_api_key, "")) AS key_len
             FROM tm_sucursales WHERE id = ? AND deleted_at IS NULL',
            [$sucursalId]
        )->getRowArray();

        if (! $row) {
            CLI::error("Sucursal {$sucursalId} no encontrada.");
            return;
        }

        CLI::write('── Configuración ──', 'yellow');
        CLI::write('Sucursal: ' . $row['nombre'] . ' (id=' . $row['id'] . ')');
        CLI::write('Venue ID: ' . ($row['venue_id'] !== '' && $row['venue_id'] !== null ? $row['venue_id'] : '—'));
        CLI::write('API Key guardada: ' . $row['tiene_key'] . ' (longitud ' . $row['key_len'] . ')');
        CLI::write('Fecha: ' . $fecha);
        CLI::newLine();

        $servicio = new ReservasApiService();
        $reservas = $servicio->obtenerPorFecha($sucursalId, $fecha);

        CLI::write('── Resultado ──', 'yellow');
        CLI::write('Modo demo: ' . ($servicio->esModoDemo() ? 'SÍ' : 'NO'));
        CLI::write('Error API: ' . ($servicio->obtenerUltimoError() ?? 'ninguno'));
        CLI::write('Total reservas: ' . count($reservas));
        CLI::newLine();

        if ($servicio->esModoDemo()) {
            CLI::error('No hay API Key en la sucursal ni en .env → datos ficticios (RSV-001…).');
            CLI::write('Guarda la clave en Admin → Sucursales → Cafe Drama GDL → API Key.', 'light_gray');
            return;
        }

        if ($servicio->obtenerUltimoError()) {
            CLI::error('La petición al API falló. Revisa Venue ID, API Key y fecha.');
            return;
        }

        CLI::write('Primeras reservas:', 'green');
        foreach (array_slice($reservas, 0, 5) as $r) {
            CLI::write(sprintf(
                '  %s | %s | %s | %s',
                $r['hora'] ?? '—',
                $r['codigo'] ?? '—',
                $r['nombre'] ?? '—',
                $r['rp'] ?? '—'
            ));
        }

        if (count($reservas) > 5) {
            CLI::write('  … y ' . (count($reservas) - 5) . ' más', 'light_gray');
        }

        $primera = $reservas[0]['codigo'] ?? '';
        if ($primera === '') {
            return;
        }

        CLI::newLine();
        CLI::write('── Detalle (' . $primera . ') ──', 'yellow');

        $detalle = $servicio->obtenerPorCodigo($primera, $sucursalId);
        if ($detalle) {
            CLI::write('by-booking: OK — ' . ($detalle['nombre'] ?? ''), 'green');
            return;
        }

        CLI::write('by-booking: ' . ($servicio->obtenerUltimoError() ?? 'sin respuesta'), 'light_gray');

        $respaldo = $servicio->buscarEnListadoActivo($primera, $sucursalId, $fecha);
        if ($respaldo) {
            CLI::write('respaldo listado: OK — ' . ($respaldo['nombre'] ?? ''), 'green');
            return;
        }

        CLI::error('Detalle no disponible para ' . $primera);
    }
}
