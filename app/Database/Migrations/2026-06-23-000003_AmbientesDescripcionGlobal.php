<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ambientes como catálogo global: descripcion + estatus (sin sucursal).
 */
class AmbientesDescripcionGlobal extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tm_ambientes')) {
            return;
        }

        // Quitar relación con sucursal
        if ($this->db->fieldExists('sucursal_id', 'tm_ambientes')) {
            $this->db->query('ALTER TABLE `tm_ambientes` DROP FOREIGN KEY `tm_ambientes_sucursal_id_foreign`');
            $this->forge->dropColumn('tm_ambientes', 'sucursal_id');
        }

        // Renombrar nombre → descripcion
        if ($this->db->fieldExists('nombre', 'tm_ambientes')) {
            $this->db->query('ALTER TABLE `tm_ambientes` CHANGE `nombre` `descripcion` VARCHAR(150) NOT NULL');
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tm_ambientes')) {
            return;
        }

        if ($this->db->fieldExists('descripcion', 'tm_ambientes') && ! $this->db->fieldExists('nombre', 'tm_ambientes')) {
            $this->db->query('ALTER TABLE `tm_ambientes` CHANGE `descripcion` `nombre` VARCHAR(100) NOT NULL');
        }

        if (! $this->db->fieldExists('sucursal_id', 'tm_ambientes') && $this->db->tableExists('tm_sucursales')) {
            $this->forge->addColumn('tm_ambientes', [
                'sucursal_id' => ['type' => 'INT', 'unsigned' => true, 'after' => 'id'],
            ]);
            $this->forge->addForeignKey('sucursal_id', 'tm_sucursales', 'id', 'CASCADE', 'CASCADE', 'tm_ambientes');
        }
    }
}
