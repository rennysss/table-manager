<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bitácora de actividad por reserva (panel Actividad / estilo SevenRooms).
 */
class ReservaActividad extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tm_reserva_actividad')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'reserva_codigo' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'sucursal_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'descripcion' => [
                'type' => 'TEXT',
            ],
            'origen' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'default'    => 'Table Manager',
            ],
            'usuario_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'usuario_nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'meta_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'externo_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('reserva_codigo');
        $this->forge->addKey(['reserva_codigo', 'created_at']);
        $this->forge->createTable('tm_reserva_actividad', true);
    }

    public function down()
    {
        if ($this->db->tableExists('tm_reserva_actividad')) {
            $this->forge->dropTable('tm_reserva_actividad', true);
        }
    }
}
