<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mesas e inventario por sucursal.
 * - tm_ambientes.sucursal_id (NULL = ambiente global compartido).
 * - tm_mesas.sucursal_id (cada mesa pertenece a una sucursal).
 */
class MesasAmbientesPorSucursal extends Migration
{
    public function up(): void
    {
        // Ambientes: sucursal_id opcional (NULL = global)
        if ($this->db->tableExists('tm_ambientes') && ! $this->db->fieldExists('sucursal_id', 'tm_ambientes')) {
            $this->forge->addColumn('tm_ambientes', [
                'sucursal_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'id'],
            ]);

            if ($this->db->tableExists('tm_sucursales')) {
                $this->db->query(
                    'ALTER TABLE `tm_ambientes`
                     ADD CONSTRAINT `tm_ambientes_sucursal_id_foreign`
                     FOREIGN KEY (`sucursal_id`) REFERENCES `tm_sucursales`(`id`)
                     ON DELETE CASCADE ON UPDATE CASCADE'
                );
            }
        }

        // Mesas: sucursal_id (rellenado desde el floorplan donde sea posible)
        if ($this->db->tableExists('tm_mesas') && ! $this->db->fieldExists('sucursal_id', 'tm_mesas')) {
            $this->forge->addColumn('tm_mesas', [
                'sucursal_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'ambiente_id'],
            ]);

            // Backfill: mesas ya colocadas en un floorplan heredan su sucursal
            if ($this->db->tableExists('tm_floorplans')) {
                $this->db->query(
                    'UPDATE `tm_mesas` m
                     INNER JOIN `tm_floorplans` f ON f.`id` = m.`floorplan_id`
                     SET m.`sucursal_id` = f.`sucursal_id`
                     WHERE m.`floorplan_id` IS NOT NULL'
                );
            }

            if ($this->db->tableExists('tm_sucursales')) {
                $this->db->query(
                    'ALTER TABLE `tm_mesas`
                     ADD CONSTRAINT `tm_mesas_sucursal_id_foreign`
                     FOREIGN KEY (`sucursal_id`) REFERENCES `tm_sucursales`(`id`)
                     ON DELETE CASCADE ON UPDATE CASCADE'
                );
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('tm_mesas') && $this->db->fieldExists('sucursal_id', 'tm_mesas')) {
            $this->db->query('ALTER TABLE `tm_mesas` DROP FOREIGN KEY `tm_mesas_sucursal_id_foreign`');
            $this->forge->dropColumn('tm_mesas', 'sucursal_id');
        }

        if ($this->db->tableExists('tm_ambientes') && $this->db->fieldExists('sucursal_id', 'tm_ambientes')) {
            $this->db->query('ALTER TABLE `tm_ambientes` DROP FOREIGN KEY `tm_ambientes_sucursal_id_foreign`');
            $this->forge->dropColumn('tm_ambientes', 'sucursal_id');
        }
    }
}
