<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migración provisional del módulo de gestión de mesas.
 * Se adaptará al esquema compartido cuando se entregue la BD mañana.
 */
class CreateTableManagementTables extends Migration
{
    public function up(): void
    {
        // Sucursales
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'estado'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ciudad'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'estatus'     => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'imagen_png'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('estatus');
        $this->forge->createTable('tm_sucursales', true);

        // Ambientes
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sucursal_id'  => ['type' => 'INT', 'unsigned' => true],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'estatus'      => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sucursal_id', 'tm_sucursales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tm_ambientes', true);

        // Floorplans versionados
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sucursal_id'  => ['type' => 'INT', 'unsigned' => true],
            'ambiente_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'tipo'         => ['type' => 'ENUM', 'constraint' => ['normal', 'festivo', 'anio_nuevo', 'custom'], 'default' => 'normal'],
            'activo'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'canvas_json'  => ['type' => 'LONGTEXT', 'null' => true],
            'zoom_default' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 1.00],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sucursal_id', 'activo']);
        $this->forge->addForeignKey('sucursal_id', 'tm_sucursales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ambiente_id', 'tm_ambientes', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tm_floorplans', true);

        // Mesas
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ambiente_id'   => ['type' => 'INT', 'unsigned' => true],
            'floorplan_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'numero'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'pax'           => ['type' => 'INT', 'unsigned' => true, 'default' => 4],
            'minimo'        => ['type' => 'INT', 'unsigned' => true, 'default' => 2],
            'maximo'        => ['type' => 'INT', 'unsigned' => true, 'default' => 6],
            'estatus'       => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'forma'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'rectangular'],
            'pos_x'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'pos_y'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'ancho'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 80],
            'alto'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 60],
            'rotacion'      => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 0],
            'fabric_id'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['ambiente_id', 'numero']);
        $this->forge->addForeignKey('ambiente_id', 'tm_ambientes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('floorplan_id', 'tm_floorplans', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tm_mesas', true);

        // Elementos estructurales del canvas
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'floorplan_id'  => ['type' => 'INT', 'unsigned' => true],
            'tipo'          => ['type' => 'VARCHAR', 'constraint' => 50],
            'fabric_id'     => ['type' => 'VARCHAR', 'constraint' => 64],
            'props_json'    => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('floorplan_id', 'tm_floorplans', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tm_floorplan_elementos', true);

        // Tags para hostess
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'color'      => ['type' => 'VARCHAR', 'constraint' => 7, 'default' => '#007AFF'],
            'estatus'    => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tm_tags', true);

        // Asignaciones reserva → mesa (trazabilidad comisiones)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reserva_codigo' => ['type' => 'VARCHAR', 'constraint' => 50],
            'mesa_id'        => ['type' => 'INT', 'unsigned' => true],
            'sucursal_id'    => ['type' => 'INT', 'unsigned' => true],
            'rp_codigo'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'cliente_nombre' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'fecha'          => ['type' => 'DATE'],
            'hora'           => ['type' => 'TIME', 'null' => true],
            'estado_mesa'    => ['type' => 'ENUM', 'constraint' => ['reservada', 'ocupada', 'liberada'], 'default' => 'reservada'],
            'tags_json'      => ['type' => 'JSON', 'null' => true],
            'asignado_por'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['reserva_codigo', 'fecha']);
        $this->forge->addKey(['mesa_id', 'fecha']);
        $this->forge->addForeignKey('mesa_id', 'tm_mesas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('sucursal_id', 'tm_sucursales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tm_reserva_asignaciones', true);

        // Usuarios locales (fallback si BD compartida no está lista)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'usuario'    => ['type' => 'VARCHAR', 'constraint' => 80],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'rol'        => ['type' => 'ENUM', 'constraint' => ['admin', 'gerente', 'hostess'], 'default' => 'hostess'],
            'estatus'    => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('usuario');
        $this->forge->createTable('tm_usuarios', true);

        // Relación usuario ↔ sucursales
        $this->forge->addField([
            'usuario_id'   => ['type' => 'INT', 'unsigned' => true],
            'sucursal_id'  => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey(['usuario_id', 'sucursal_id'], true);
        $this->forge->addForeignKey('usuario_id', 'tm_usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('sucursal_id', 'tm_sucursales', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tm_usuario_sucursales', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tm_usuario_sucursales', true);
        $this->forge->dropTable('tm_usuarios', true);
        $this->forge->dropTable('tm_reserva_asignaciones', true);
        $this->forge->dropTable('tm_tags', true);
        $this->forge->dropTable('tm_floorplan_elementos', true);
        $this->forge->dropTable('tm_mesas', true);
        $this->forge->dropTable('tm_floorplans', true);
        $this->forge->dropTable('tm_ambientes', true);
        $this->forge->dropTable('tm_sucursales', true);
    }
}
