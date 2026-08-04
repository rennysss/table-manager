<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Categorías de tags (estilo SevenRooms) y vínculo en tm_tags.
 */
class TagCategorias extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('tm_tag_categorias')) {
            $this->forge->addField([
                'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'nombre'             => ['type' => 'VARCHAR', 'constraint' => 80],
                'alcance'            => ['type' => 'ENUM', 'constraint' => ['global', 'local'], 'default' => 'global'],
                'mostrar_en_reserva' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'mostrar_en_chit'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'orden'              => ['type' => 'INT', 'default' => 0],
                'estatus'            => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
                'created_at'         => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('tm_tag_categorias', true);
        }

        if ($this->db->tableExists('tm_tags')) {
            if (! $this->db->fieldExists('categoria_id', 'tm_tags')) {
                $this->forge->addColumn('tm_tags', [
                    'categoria_id' => [
                        'type'       => 'INT',
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'id',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('orden', 'tm_tags')) {
                $this->forge->addColumn('tm_tags', [
                    'orden' => [
                        'type'    => 'INT',
                        'default' => 0,
                        'after'   => 'color',
                    ],
                ]);
            }
        }

        // Categoría por defecto y asignación de tags existentes
        $existeGeneral = $this->db->table('tm_tag_categorias')->where('nombre', 'General')->countAllResults();
        if ($existeGeneral === 0) {
            $this->db->table('tm_tag_categorias')->insert([
                'nombre'             => 'General',
                'alcance'            => 'global',
                'mostrar_en_reserva' => 1,
                'mostrar_en_chit'    => 1,
                'orden'              => 0,
                'estatus'            => 'activo',
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
        }

        $generalId = (int) ($this->db->table('tm_tag_categorias')->where('nombre', 'General')->get()->getRowArray()['id'] ?? 0);
        if ($generalId > 0 && $this->db->tableExists('tm_tags')) {
            $this->db->table('tm_tags')
                ->where('categoria_id', null)
                ->orWhere('categoria_id', 0)
                ->set(['categoria_id' => $generalId])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('tm_tags')) {
            if ($this->db->fieldExists('categoria_id', 'tm_tags')) {
                $this->forge->dropColumn('tm_tags', 'categoria_id');
            }
            if ($this->db->fieldExists('orden', 'tm_tags')) {
                $this->forge->dropColumn('tm_tags', 'orden');
            }
        }

        if ($this->db->tableExists('tm_tag_categorias')) {
            $this->forge->dropTable('tm_tag_categorias', true);
        }
    }
}
