<?php

namespace App\Services;

use App\Models\ReservaActividadModel;
use App\Models\ReservaAsignacionModel;
use App\Models\ReservaLlegadaModel;

/**
 * Bitácora de actividad de reservas (local + externa).
 */
class ReservaActividadService
{
    public function registrar(
        string $codigo,
        string $tipo,
        string $descripcion,
        array $meta = [],
        ?int $sucursalId = null,
        string $origen = 'Table Manager'
    ): void {
        $codigo = trim($codigo);
        if ($codigo === '' || trim($descripcion) === '') {
            return;
        }

        $usuarioId = session()->get('usuario_id');
        $nombre    = trim((string) (session()->get('nombre') ?? ''));
        if ($nombre === '') {
            $nombre = 'Usuario';
        }

        model(ReservaActividadModel::class)->insert([
            'reserva_codigo' => $codigo,
            'sucursal_id'    => $sucursalId ?? (int) (session()->get('sucursal_id') ?? 0) ?: null,
            'tipo'           => $tipo,
            'descripcion'    => $descripcion,
            'origen'         => $origen,
            'usuario_id'     => $usuarioId ? (int) $usuarioId : null,
            'usuario_nombre' => $nombre,
            'meta_json'      => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
            'externo_id'     => null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Consolida actividad local, respaldo de llegadas y API externa.
     *
     * @return list<array<string, mixed>>
     */
    public function listar(string $codigo, int $sucursalId = 0): array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return [];
        }

        $eventos = [];
        foreach (model(ReservaActividadModel::class)->porCodigo($codigo) as $fila) {
            $eventos[] = $this->normalizarFilaLocal($fila);
        }

        $idsExternos = [];
        foreach ($eventos as $ev) {
            if (! empty($ev['externo_id'])) {
                $idsExternos[$ev['externo_id']] = true;
            }
        }

        foreach ($this->eventosDesdeLlegadas($codigo) as $ev) {
            $eventos[] = $ev;
        }

        $externos = (new ReservasApiService())->obtenerActividad($codigo, $sucursalId);
        foreach ($externos as $ev) {
            $eid = $ev['externo_id'] ?? null;
            if ($eid !== null && isset($idsExternos[$eid])) {
                continue;
            }
            $eventos[] = $ev;
        }

        usort($eventos, static function (array $a, array $b): int {
            $ta = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
            $tb = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
            if ($ta === $tb) {
                return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
            }

            return $tb <=> $ta;
        });

        return $eventos;
    }

    /**
     * @param array<string, mixed> $fila
     *
     * @return array<string, mixed>
     */
    private function normalizarFilaLocal(array $fila): array
    {
        return [
            'id'             => (int) ($fila['id'] ?? 0),
            'tipo'           => (string) ($fila['tipo'] ?? 'otro'),
            'descripcion'    => (string) ($fila['descripcion'] ?? ''),
            'origen'         => (string) ($fila['origen'] ?? 'Table Manager'),
            'usuario_nombre' => (string) ($fila['usuario_nombre'] ?? 'Sistema'),
            'created_at'     => (string) ($fila['created_at'] ?? ''),
            'externo_id'     => $fila['externo_id'] ?? null,
            'fuente'         => 'local',
        ];
    }

    /**
     * Si hubo llegadas antes de existir la bitácora, las muestra como actividad.
     *
     * @return list<array<string, mixed>>
     */
    private function eventosDesdeLlegadas(string $codigo): array
    {
        $asignacion = model(ReservaAsignacionModel::class)->porCodigo($codigo);
        if (! $asignacion) {
            return [];
        }

        // Si ya hay eventos de llegada en la bitácora, no duplicar el respaldo
        $yaHay = model(ReservaActividadModel::class)
            ->where('reserva_codigo', $codigo)
            ->where('tipo', 'llegada_pax')
            ->countAllResults();
        if ($yaHay > 0) {
            return [];
        }

        $out = [];
        foreach (model(ReservaLlegadaModel::class)->porAsignacion((int) $asignacion['id']) as $l) {
            $delta = (int) ($l['pax_delta'] ?? 0);
            $signo = $delta > 0 ? '+' : '';
            $out[] = [
                'id'             => 0,
                'tipo'           => 'llegada_pax',
                'descripcion'    => "registró {$signo}{$delta} pax → {$l['pax_resultante']} en mesa",
                'origen'         => 'Table Manager',
                'usuario_nombre' => 'Hostess',
                'created_at'     => (string) ($l['created_at'] ?? ''),
                'externo_id'     => 'llegada:' . ($l['id'] ?? ''),
                'fuente'         => 'llegadas',
            ];
        }

        return $out;
    }
}
