<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Identificador externo de venue (SevenRooms u otro proveedor de reservas).
 */
class SucursalVenueId extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('tm_sucursales')) {
            return;
        }

        if (! $this->db->fieldExists('venue_id', 'tm_sucursales')) {
            $this->forge->addColumn('tm_sucursales', [
                'venue_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'nombre',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('tm_sucursales') && $this->db->fieldExists('venue_id', 'tm_sucursales')) {
            $this->forge->dropColumn('tm_sucursales', 'venue_id');
        }
    }
}
