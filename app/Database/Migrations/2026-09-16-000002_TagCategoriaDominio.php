<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Separa catálogo de tags: cliente vs reserva.
 */
class TagCategoriaDominio extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tm_tag_categorias')) {
            return;
        }

        if (! $this->db->fieldExists('dominio', 'tm_tag_categorias')) {
            $this->forge->addColumn('tm_tag_categorias', [
                'dominio' => [
                    'type'       => 'ENUM',
                    'constraint' => ['cliente', 'reserva'],
                    'default'    => 'reserva',
                    'after'      => 'alcance',
                ],
            ]);
        }

        // Tags históricos = reserva
        $this->db->table('tm_tag_categorias')->update(['dominio' => 'reserva']);
    }

    public function down()
    {
        if ($this->db->tableExists('tm_tag_categorias') && $this->db->fieldExists('dominio', 'tm_tag_categorias')) {
            $this->forge->dropColumn('tm_tag_categorias', 'dominio');
        }
    }
}
