<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservaAsignacionModel extends Model
{
    protected $table            = 'tm_reserva_asignaciones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'reserva_codigo', 'mesa_id', 'sucursal_id', 'rp_codigo',
        'cliente_nombre', 'fecha', 'hora', 'pax_reservados', 'pax_en_mesa',
        'estado_mesa', 'arrived_at', 'seated_at', 'liberated_at',
        'tags_json', 'asignado_por',
    ];
    protected $useTimestamps = true;

    public function porFecha(int $sucursalId, string $fecha): array
    {
        return $this->where('sucursal_id', $sucursalId)
            ->where('fecha', $fecha)
            ->findAll();
    }

    public function porCodigo(string $codigo): ?array
    {
        return $this->where('reserva_codigo', $codigo)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public function estadosMesas(int $sucursalId, string $fecha): array
    {
        $rows = $this->select('mesa_id, estado_mesa, reserva_codigo, cliente_nombre, tags_json, pax_en_mesa, pax_reservados, hora, arrived_at, seated_at')
            ->where('sucursal_id', $sucursalId)
            ->where('fecha', $fecha)
            ->whereIn('estado_mesa', ['reservada', 'ocupada'])
            ->where('mesa_id IS NOT NULL', null, false)
            ->findAll();

        $mapa = [];
        foreach ($rows as $row) {
            if (empty($row['mesa_id'])) {
                continue;
            }
            $mapa[$row['mesa_id']] = $row;
        }

        return $mapa;
    }

    /**
     * Etiqueta de status estilo SevenRooms a partir de la asignación.
     */
    public static function statusLabel(?array $asignacion): string
    {
        if (! $asignacion) {
            return 'Sin asignar';
        }

        $estado = $asignacion['estado_mesa'] ?? 'reservada';
        $pax    = (int) ($asignacion['pax_en_mesa'] ?? 0);
        $tieneMesa = ! empty($asignacion['mesa_id']);

        return match ($estado) {
            'liberada' => 'Liberada',
            'ocupada'  => 'Ocupada',
            'reservada' => $pax > 0 ? 'Arrived' : ($tieneMesa ? 'Asignada' : 'Check-in'),
            default    => 'Asignada',
        };
    }
}
