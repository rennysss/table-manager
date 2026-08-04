<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * API Key de OneReservations por sucursal (header X-API-Key).
 */
class SucursalReservasApiKey extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('tm_sucursales')) {
            return;
        }

        if (! $this->db->fieldExists('reservas_api_key', 'tm_sucursales')) {
            $this->forge->addColumn('tm_sucursales', [
                'reservas_api_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'xetux_id',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('tm_sucursales') && $this->db->fieldExists('reservas_api_key', 'tm_sucursales')) {
            $this->forge->dropColumn('tm_sucursales', 'reservas_api_key');
        }
    }
}
