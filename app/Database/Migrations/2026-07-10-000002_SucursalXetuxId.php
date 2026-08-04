<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Identificador externo de Xetux para consultar mesas vía API.
 */
class SucursalXetuxId extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('tm_sucursales')) {
            return;
        }

        if (! $this->db->fieldExists('xetux_id', 'tm_sucursales')) {
            $this->forge->addColumn('tm_sucursales', [
                'xetux_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'venue_id',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('tm_sucursales') && $this->db->fieldExists('xetux_id', 'tm_sucursales')) {
            $this->forge->dropColumn('tm_sucursales', 'xetux_id');
        }
    }
}
