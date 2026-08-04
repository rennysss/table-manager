<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Check-in / pax parciales en asignaciones + historial de llegadas.
 */
class CheckinPaxParciales extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('tm_reserva_asignaciones')) {
            if (! $this->db->fieldExists('pax_reservados', 'tm_reserva_asignaciones')) {
                $this->forge->addColumn('tm_reserva_asignaciones', [
                    'pax_reservados' => [
                        'type'     => 'INT',
                        'unsigned' => true,
                        'null'     => true,
                        'after'    => 'hora',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('pax_en_mesa', 'tm_reserva_asignaciones')) {
                $this->forge->addColumn('tm_reserva_asignaciones', [
                    'pax_en_mesa' => [
                        'type'     => 'INT',
                        'unsigned' => true,
                        'default'  => 0,
                        'after'    => 'pax_reservados',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('arrived_at', 'tm_reserva_asignaciones')) {
                $this->forge->addColumn('tm_reserva_asignaciones', [
                    'arrived_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'estado_mesa',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('seated_at', 'tm_reserva_asignaciones')) {
                $this->forge->addColumn('tm_reserva_asignaciones', [
                    'seated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'arrived_at',
                    ],
                ]);
            }
            if (! $this->db->fieldExists('liberated_at', 'tm_reserva_asignaciones')) {
                $this->forge->addColumn('tm_reserva_asignaciones', [
                    'liberated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'seated_at',
                    ],
                ]);
            }
        }

        if (! $this->db->tableExists('tm_reserva_llegadas')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'asignacion_id'   => ['type' => 'INT', 'unsigned' => true],
                'pax_delta'       => ['type' => 'INT'],
                'pax_resultante'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'nota'            => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'registrado_por'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('asignacion_id');
            $this->forge->addForeignKey('asignacion_id', 'tm_reserva_asignaciones', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('tm_reserva_llegadas', true);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('tm_reserva_llegadas')) {
            $this->forge->dropTable('tm_reserva_llegadas', true);
        }

        if ($this->db->tableExists('tm_reserva_asignaciones')) {
            foreach (['liberated_at', 'seated_at', 'arrived_at', 'pax_en_mesa', 'pax_reservados'] as $col) {
                if ($this->db->fieldExists($col, 'tm_reserva_asignaciones')) {
                    $this->forge->dropColumn('tm_reserva_asignaciones', $col);
                }
            }
        }
    }
}
