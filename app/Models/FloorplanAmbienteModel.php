<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ambientes asociados a un floorplan (relación muchos-a-muchos).
 */
class FloorplanAmbienteModel extends Model
{
    protected $table            = 'tm_floorplan_ambientes';
    protected $primaryKey       = 'floorplan_id';
    protected $useAutoIncrement = false;
    protected $returnType        = 'array';
    protected $allowedFields     = ['floorplan_id', 'ambiente_id'];
    protected $useTimestamps     = true;

    /**
     * IDs de ambientes asociados al floorplan.
     *
     * @return int[]
     */
    public function idsDe(int $floorplanId): array
    {
        $rows = $this->select('ambiente_id')
            ->where('floorplan_id', $floorplanId)
            ->findAll();

        return array_map(static fn ($r) => (int) $r['ambiente_id'], $rows);
    }

    /**
     * Ambientes (id + descripción) asociados al floorplan.
     */
    public function ambientesDe(int $floorplanId): array
    {
        return $this->select('tm_ambientes.id, tm_ambientes.descripcion')
            ->join('tm_ambientes', 'tm_ambientes.id = tm_floorplan_ambientes.ambiente_id')
            ->where('tm_floorplan_ambientes.floorplan_id', $floorplanId)
            ->orderBy('tm_ambientes.descripcion', 'ASC')
            ->findAll();
    }

    /**
     * Reemplaza el conjunto de ambientes del floorplan por los indicados.
     *
     * @param int[] $ambienteIds
     */
    public function sincronizar(int $floorplanId, array $ambienteIds): void
    {
        $ambienteIds = array_values(array_unique(array_filter(array_map('intval', $ambienteIds))));

        $this->db->transStart();

        $this->builder()->where('floorplan_id', $floorplanId)->delete();

        if ($ambienteIds !== []) {
            $now   = date('Y-m-d H:i:s');
            $filas = [];
            foreach ($ambienteIds as $ambienteId) {
                $filas[] = [
                    'floorplan_id' => $floorplanId,
                    'ambiente_id'  => $ambienteId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
            $this->builder()->insertBatch($filas);
        }

        $this->db->transComplete();
    }
}
