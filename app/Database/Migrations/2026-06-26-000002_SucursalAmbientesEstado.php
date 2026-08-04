<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Estado de ambientes globales por sucursal.
 * Permite desactivar un ambiente global solo para una sucursal específica.
 * (Los ambientes propios de la sucursal usan su propio `estatus`.)
 */
class SucursalAmbientesEstado extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tm_sucursal_ambientes')) {
            return;
        }

        $this->forge->addField([
            'sucursal_id' => ['type' => 'INT', 'unsigned' => true],
            'ambiente_id' => ['type' => 'INT', 'unsigned' => true],
            'activo'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey(['sucursal_id', 'ambiente_id'], true);
        $this->forge->addForeignKey('sucursal_id', 'tm_sucursales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ambiente_id', 'tm_ambientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tm_sucursal_ambientes', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tm_sucursal_ambientes', true);
    }
}
