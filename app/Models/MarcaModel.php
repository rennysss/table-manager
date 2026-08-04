<?php

namespace App\Models;

use CodeIgniter\Model;

class MarcaModel extends Model
{
    protected $table            = 'tm_marcas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['nombre', 'estatus'];
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    public function activas(): array
    {
        return $this->where('estatus', 'activo')->orderBy('nombre', 'ASC')->findAll();
    }
}
