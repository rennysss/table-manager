<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        // Tags predeterminados para hostess
        $generalId = $this->db->table('tm_tag_categorias')->where('nombre', 'General')->get()->getRowArray()['id'] ?? null;
        if ($generalId === null && $this->db->tableExists('tm_tag_categorias')) {
            $this->db->table('tm_tag_categorias')->insert([
                'nombre'             => 'General',
                'alcance'            => 'global',
                'mostrar_en_reserva' => 1,
                'mostrar_en_chit'    => 1,
                'orden'              => 0,
                'estatus'            => 'activo',
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
            $generalId = $this->db->insertID();
        }

        $this->db->table('tm_tags')->ignore(true)->insertBatch([
            ['categoria_id' => $generalId, 'nombre' => 'VIP', 'color' => '#FFD60A', 'estatus' => 'activo'],
            ['categoria_id' => $generalId, 'nombre' => 'Cumpleaños', 'color' => '#FF375F', 'estatus' => 'activo'],
            ['categoria_id' => $generalId, 'nombre' => 'Celebrity', 'color' => '#BF5AF2', 'estatus' => 'activo'],
            ['categoria_id' => $generalId, 'nombre' => 'Walk-in', 'color' => '#64D2FF', 'estatus' => 'activo'],
            ['categoria_id' => $generalId, 'nombre' => 'Alta prioridad', 'color' => '#FF453A', 'estatus' => 'activo'],
        ]);

        // Usuario administrador demo (cambiar en producción)
        $password = password_hash('Admin123!', PASSWORD_ARGON2ID);

        $this->db->table('tm_usuarios')->ignore(true)->insert([
            'usuario'    => 'admin',
            'email'      => 'admin@1rtables.local',
            'password'   => $password,
            'nombre'     => 'Administrador',
            'rol'        => 'admin',
            'estatus'    => 'activo',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('tm_usuarios')->ignore(true)->insert([
            'usuario'    => 'gerente',
            'email'      => 'gerente@1rtables.local',
            'password'   => password_hash('Gerente123!', PASSWORD_ARGON2ID),
            'nombre'     => 'Gerente Demo',
            'rol'        => 'gerente',
            'estatus'    => 'activo',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('tm_usuarios')->ignore(true)->insert([
            'usuario'    => 'hostess',
            'email'      => 'hostess@1rtables.local',
            'password'   => password_hash('Hostess123!', PASSWORD_ARGON2ID),
            'nombre'     => 'Hostess Demo',
            'rol'        => 'hostess',
            'estatus'    => 'activo',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Sucursal demo
        $this->db->table('tm_sucursales')->ignore(true)->insert([
            'nombre'     => 'Bonbonniere Marbella',
            'estado'     => 'Málaga',
            'ciudad'     => 'Marbella',
            'estatus'    => 'activo',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $sucursalId = $this->db->insertID() ?: 1;

        $this->db->table('tm_ambientes')->ignore(true)->insertBatch([
            ['descripcion' => 'Nightclub', 'estatus' => 'activo', 'created_at' => date('Y-m-d H:i:s')],
            ['descripcion' => 'Restaurante', 'estatus' => 'activo', 'created_at' => date('Y-m-d H:i:s')],
        ]);

        // Asignar sucursal a usuarios demo (admin=1, gerente=2, hostess=3)
        foreach ([1, 2, 3] as $uid) {
            $this->db->table('tm_usuario_sucursales')->ignore(true)->insert([
                'usuario_id'  => $uid,
                'sucursal_id' => $sucursalId,
            ]);
        }
    }
}
