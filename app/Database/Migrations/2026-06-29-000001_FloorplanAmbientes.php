<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Relación muchos-a-muchos entre floorplans y ambientes.
 * Un floorplan puede contener uno o varios ambientes (Salón, Terraza, etc.).
 */
class FloorplanAmbientes extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tm_floorplan_ambientes')) {
            return;
        }

        $this->forge->addField([
            'floorplan_id' => ['type' => 'INT', 'unsigned' => true],
            'ambiente_id'  => ['type' => 'INT', 'unsigned' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['floorplan_id', 'ambiente_id'], true);
        $this->forge->addForeignKey('floorplan_id', 'tm_floorplans', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ambiente_id', 'tm_ambientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tm_floorplan_ambientes', true);

        // Respalda los floorplans existentes: su ambiente_id pasa a la tabla puente.
        $existentes = $this->db->table('tm_floorplans')
            ->select('id, ambiente_id')
            ->where('ambiente_id IS NOT NULL')
            ->get()
            ->getResultArray();

        $now  = date('Y-m-d H:i:s');
        $filas = [];
        foreach ($existentes as $fp) {
            $filas[] = [
                'floorplan_id' => (int) $fp['id'],
                'ambiente_id'  => (int) $fp['ambiente_id'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if ($filas !== []) {
            $this->db->table('tm_floorplan_ambientes')->insertBatch($filas);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('tm_floorplan_ambientes', true);
    }
}
