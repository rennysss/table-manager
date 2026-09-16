<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tags de cliente por visita en la asignación (mismo patrón que tags_json de reserva).
 */
class AsignacionTagsCliente extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tm_reserva_asignaciones')) {
            return;
        }

        if (! $this->db->fieldExists('tags_cliente_json', 'tm_reserva_asignaciones')) {
            $this->forge->addColumn('tm_reserva_asignaciones', [
                'tags_cliente_json' => [
                    'type' => 'JSON',
                    'null' => true,
                    'after' => 'tags_json',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tm_reserva_asignaciones')
            && $this->db->fieldExists('tags_cliente_json', 'tm_reserva_asignaciones')) {
            $this->forge->dropColumn('tm_reserva_asignaciones', 'tags_cliente_json');
        }
    }
}
