<?php

namespace App\Models;

use CodeIgniter\Model;

class PaisModel extends Model
{
    protected $table            = 'tm_paises';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['codigo', 'nombre', 'estatus'];
    protected $useTimestamps    = true;

    public function activos(): array
    {
        return $this->where('estatus', 'activo')->orderBy('nombre', 'ASC')->findAll();
    }
}
