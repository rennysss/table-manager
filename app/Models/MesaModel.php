<?php

namespace App\Models;

use CodeIgniter\Model;

class MesaModel extends Model
{
    protected $table            = 'tm_mesas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'ambiente_id', 'sucursal_id', 'floorplan_id', 'numero', 'pax', 'minimo', 'maximo',
        'estatus', 'forma', 'pos_x', 'pos_y', 'ancho', 'alto', 'rotacion', 'fabric_id',
    ];
    protected $useTimestamps = true;
    protected $deletedField  = 'deleted_at';

    public function porAmbiente(int $ambienteId): array
    {
        return $this->where('ambiente_id', $ambienteId)
            ->orderBy('numero', 'ASC')
            ->findAll();
    }

    /**
     * Inventario de mesas de una sucursal con el nombre de su ambiente.
     */
    public function inventarioPorSucursal(int $sucursalId): array
    {
        return $this->select('tm_mesas.*, tm_ambientes.descripcion AS ambiente_nombre')
            ->join('tm_ambientes', 'tm_ambientes.id = tm_mesas.ambiente_id', 'left')
            ->where('tm_mesas.sucursal_id', $sucursalId)
            ->orderBy('tm_mesas.ambiente_id', 'ASC')
            ->orderBy('tm_mesas.numero', 'ASC')
            ->findAll();
    }

    public function porFloorplan(int $floorplanId): array
    {
        return $this->where('floorplan_id', $floorplanId)
            ->where('estatus', 'activo')
            ->findAll();
    }

    /**
     * Conteo de mesas activas/inactivas agrupadas por floorplan.
     * Devuelve [floorplan_id => ['activo' => n, 'inactivo' => n, 'total' => n]].
     */
    public function conteoPorFloorplans(array $floorplanIds): array
    {
        if ($floorplanIds === []) {
            return [];
        }

        $rows = $this->select('floorplan_id, estatus, COUNT(*) AS total')
            ->whereIn('floorplan_id', $floorplanIds)
            ->groupBy(['floorplan_id', 'estatus'])
            ->findAll();

        $mapa = [];

        foreach ($floorplanIds as $id) {
            $mapa[(int) $id] = ['activo' => 0, 'inactivo' => 0, 'total' => 0];
        }

        foreach ($rows as $r) {
            $fpId  = (int) $r['floorplan_id'];
            $clave = $r['estatus'] === 'activo' ? 'activo' : 'inactivo';
            $total = (int) $r['total'];

            $mapa[$fpId][$clave] = ($mapa[$fpId][$clave] ?? 0) + $total;
            $mapa[$fpId]['total'] = ($mapa[$fpId]['total'] ?? 0) + $total;
        }

        return $mapa;
    }

    public function porSucursal(int $sucursalId): array
    {
        return $this->select('tm_mesas.*')
            ->join('tm_floorplans f', 'f.id = tm_mesas.floorplan_id', 'left')
            ->where('f.sucursal_id', $sucursalId)
            ->where('tm_mesas.estatus', 'activo')
            ->findAll();
    }

    /**
     * Calcula aforo máximo del floorplan activo.
     */
    public function calcularAforo(int $floorplanId): int
    {
        $result = $this->selectSum('maximo', 'aforo')
            ->where('floorplan_id', $floorplanId)
            ->where('estatus', 'activo')
            ->first();

        return (int) ($result['aforo'] ?? 0);
    }
}
