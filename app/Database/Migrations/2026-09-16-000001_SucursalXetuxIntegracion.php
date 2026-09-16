<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Credenciales y parámetros POS Xetux por sucursal.
 */
class SucursalXetuxIntegracion extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tm_sucursales')) {
            return;
        }

        $campos = [
            'xetux_base_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'xetux_id',
            ],
            'xetux_api_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'xetux_base_url',
            ],
            'xetux_station_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'after'      => 'xetux_api_key',
            ],
            'xetux_payform_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'xetux_station_code',
            ],
        ];

        foreach ($campos as $nombre => $def) {
            if (! $this->db->fieldExists($nombre, 'tm_sucursales')) {
                $this->forge->addColumn('tm_sucursales', [$nombre => $def]);
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tm_sucursales')) {
            return;
        }

        foreach (['xetux_payform_id', 'xetux_station_code', 'xetux_api_key', 'xetux_base_url'] as $col) {
            if ($this->db->fieldExists($col, 'tm_sucursales')) {
                $this->forge->dropColumn('tm_sucursales', $col);
            }
        }
    }
}
