<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Vínculo reserva OneReservations ↔ orden Xetux.
 */
class ReservaXetuxOrden extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tm_reserva_xetux_ordenes')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'reserva_codigo' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'sucursal_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'order_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'suborder_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'waiter_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'space_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'estatus' => [
                'type'       => 'ENUM',
                'constraint' => ['activa', 'cancelada', 'cerrada'],
                'default'    => 'activa',
            ],
            'payload_create_json' => ['type' => 'JSON', 'null' => true],
            'payload_response_json' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('reserva_codigo');
        $this->forge->addKey(['sucursal_id', 'reserva_codigo']);
        $this->forge->createTable('tm_reserva_xetux_ordenes', true);
    }

    public function down()
    {
        if ($this->db->tableExists('tm_reserva_xetux_ordenes')) {
            $this->forge->dropTable('tm_reserva_xetux_ordenes', true);
        }
    }
}
