<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class GeoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('tm_marcas')->ignore(true)->insertBatch([
            ['nombre' => 'Bonbonniere', 'estatus' => 'activo', 'created_at' => $now],
            ['nombre' => 'Mandala', 'estatus' => 'activo', 'created_at' => $now],
        ]);

        $paises = [
            ['codigo' => 'MX', 'nombre' => 'Mexico', 'estatus' => 'activo', 'created_at' => $now],
            ['codigo' => 'ES', 'nombre' => 'España', 'estatus' => 'activo', 'created_at' => $now],
            ['codigo' => 'US', 'nombre' => 'USA', 'estatus' => 'activo', 'created_at' => $now],
        ];
        $this->db->table('tm_paises')->ignore(true)->insertBatch($paises);

        $paisMx = $this->db->table('tm_paises')->where('codigo', 'MX')->get()->getRowArray();
        $paisEs = $this->db->table('tm_paises')->where('codigo', 'ES')->get()->getRowArray();
        $paisUs = $this->db->table('tm_paises')->where('codigo', 'US')->get()->getRowArray();

        $estadosMx = [
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
            'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Guanajuato',
            'Guerrero', 'Hidalgo', 'Jalisco', 'México', 'Michoacán', 'Morelos', 'Nayarit',
            'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
            'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
        ];

        $batch = [];
        foreach ($estadosMx as $nombre) {
            $batch[] = ['pais_id' => $paisMx['id'], 'nombre' => $nombre, 'estatus' => 'activo', 'created_at' => $now];
        }
        foreach (['Madrid', 'Marbella'] as $nombre) {
            $batch[] = ['pais_id' => $paisEs['id'], 'nombre' => $nombre, 'estatus' => 'activo', 'created_at' => $now];
        }
        $batch[] = ['pais_id' => $paisUs['id'], 'nombre' => 'Miami', 'estatus' => 'activo', 'created_at' => $now];

        $this->db->table('tm_estados_region')->ignore(true)->insertBatch($batch);

        $marca = $this->db->table('tm_marcas')->where('nombre', 'Bonbonniere')->get()->getRowArray();
        $estadoMarbella = $this->db->table('tm_estados_region')
            ->where('pais_id', $paisEs['id'])
            ->where('nombre', 'Marbella')
            ->get()->getRowArray();

        $this->db->table('tm_sucursales')->where('nombre', 'Bonbonniere Marbella')->update([
            'marca_id'         => $marca['id'] ?? null,
            'pais_id'          => $paisEs['id'] ?? null,
            'estado_region_id' => $estadoMarbella['id'] ?? null,
            'updated_at'       => $now,
        ]);
    }
}
