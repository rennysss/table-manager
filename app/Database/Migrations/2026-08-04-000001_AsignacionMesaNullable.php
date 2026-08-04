<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Permite check-in / pax parciales antes de asignar mesa (mesa_id nullable).
 */
class AsignacionMesaNullable extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('tm_reserva_asignaciones')) {
            return;
        }

        // Quita FK de mesa para poder volver la columna nula
        try {
            $this->forge->dropForeignKey('tm_reserva_asignaciones', 'tm_reserva_asignaciones_mesa_id_foreign');
        } catch (\Throwable $e) {
            // Nombre real de la FK puede variar según MySQL/versión
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->forge->modifyColumn('tm_reserva_asignaciones', [
            'mesa_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);

        // Reinstala FK permitiendo NULL (ON DELETE SET NULL)
        try {
            $this->db->query(
                'ALTER TABLE `tm_reserva_asignaciones`
                 ADD CONSTRAINT `tm_reserva_asignaciones_mesa_id_foreign`
                 FOREIGN KEY (`mesa_id`) REFERENCES `tm_mesas` (`id`)
                 ON DELETE SET NULL ON UPDATE CASCADE'
            );
        } catch (\Throwable $e) {
            log_message('error', 'AsignacionMesaNullable FK: ' . $e->getMessage());
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        if (! $this->db->tableExists('tm_reserva_asignaciones')) {
            return;
        }

        // No se revierten filas con mesa_id NULL automáticamente
        try {
            $this->forge->dropForeignKey('tm_reserva_asignaciones', 'tm_reserva_asignaciones_mesa_id_foreign');
        } catch (\Throwable $e) {
            // ignore
        }

        $this->db->table('tm_reserva_asignaciones')->where('mesa_id', null)->delete();

        $this->forge->modifyColumn('tm_reserva_asignaciones', [
            'mesa_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
        ]);

        try {
            $this->db->query(
                'ALTER TABLE `tm_reserva_asignaciones`
                 ADD CONSTRAINT `tm_reserva_asignaciones_mesa_id_foreign`
                 FOREIGN KEY (`mesa_id`) REFERENCES `tm_mesas` (`id`)
                 ON DELETE CASCADE ON UPDATE CASCADE'
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
