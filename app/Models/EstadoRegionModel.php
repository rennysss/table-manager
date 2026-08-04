<?php

namespace App\Models;

use CodeIgniter\Model;

class EstadoRegionModel extends Model
{
    protected $table            = 'tm_estados_region';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pais_id', 'nombre', 'estatus'];
    protected $useTimestamps    = true;

    public function porPais(int $paisId, bool $soloActivos = true): array
    {
        $builder = $this->where('pais_id', $paisId);

        if ($soloActivos) {
            $builder->where('estatus', 'activo');
        }

        return $builder->orderBy('nombre', 'ASC')->findAll();
    }

    public function listarConPais(): array
    {
        return $this->select('tm_estados_region.*, tm_paises.nombre AS pais_nombre')
            ->join('tm_paises', 'tm_paises.id = tm_estados_region.pais_id')
            ->orderBy('tm_paises.nombre', 'ASC')
            ->orderBy('tm_estados_region.nombre', 'ASC')
            ->findAll();
    }
}
