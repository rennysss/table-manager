<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perfil de cliente (huésped) por email para recordar tags entre visitas.
 */
class ClientesPerfilTags extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tm_clientes')) {
            return;
        }

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'tags_json'  => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('tm_clientes', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('tm_clientes')) {
            $this->forge->dropTable('tm_clientes', true);
        }
    }
}
