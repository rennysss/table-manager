<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marcas, catálogo geográfico (país/estado) y relación con sucursales.
 */
class MarcasYGeografia extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 120],
            'estatus'    => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tm_marcas', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'codigo'     => ['type' => 'VARCHAR', 'constraint' => 10],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 80],
            'estatus'    => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo');
        $this->forge->createTable('tm_paises', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pais_id'    => ['type' => 'INT', 'unsigned' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 120],
            'estatus'    => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['pais_id', 'nombre']);
        $this->forge->addForeignKey('pais_id', 'tm_paises', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tm_estados_region', true);

        $this->forge->addColumn('tm_sucursales', [
            'marca_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'nombre',
            ],
            'pais_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'marca_id',
            ],
            'estado_region_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'pais_id',
            ],
        ]);

    }

    public function down(): void
    {
        $this->forge->dropColumn('tm_sucursales', ['marca_id', 'pais_id', 'estado_region_id']);
        $this->forge->dropTable('tm_estados_region', true);
        $this->forge->dropTable('tm_paises', true);
        $this->forge->dropTable('tm_marcas', true);
    }
}
