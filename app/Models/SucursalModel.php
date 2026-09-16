<?php

namespace App\Models;

use CodeIgniter\Model;

class SucursalModel extends Model
{
    protected $table            = 'tm_sucursales';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'nombre', 'venue_id', 'xetux_id', 'xetux_base_url', 'xetux_api_key', 'xetux_station_code', 'xetux_payform_id',
        'reservas_api_key', 'marca_id', 'pais_id', 'estado_region_id', 'estado', 'ciudad', 'estatus', 'imagen_png',
    ];
    protected $useTimestamps = true;
    protected $deletedField  = 'deleted_at';

    public function listarConRelaciones(): array
    {
        $rows = $this->select([
                'tm_sucursales.*',
                'tm_marcas.nombre AS marca_nombre',
                'tm_paises.nombre AS pais_nombre',
                'tm_estados_region.nombre AS estado_region_nombre',
            ])
            ->join('tm_marcas', 'tm_marcas.id = tm_sucursales.marca_id', 'left')
            ->join('tm_paises', 'tm_paises.id = tm_sucursales.pais_id', 'left')
            ->join('tm_estados_region', 'tm_estados_region.id = tm_sucursales.estado_region_id', 'left')
            ->orderBy('tm_sucursales.nombre', 'ASC')
            ->findAll();

        return array_map(static function (array $fila): array {
            unset($fila['reservas_api_key']);

            return $fila;
        }, $rows);
    }

    /** Ciudades distintas con sucursales activas en un país. */
    public function ciudadesActivasPorPais(int $paisId): array
    {
        $rows = $this->db->table($this->table)
            ->select('ciudad')
            ->distinct()
            ->where('pais_id', $paisId)
            ->where('estatus', 'activo')
            ->where('deleted_at', null)
            ->where('ciudad IS NOT NULL', null, false)
            ->where('ciudad !=', '')
            ->orderBy('ciudad', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_map(static fn ($r) => $r['ciudad'], $rows));
    }

    /** Sucursales activas filtradas por país y ciudad. */
    public function activasPorPaisYCiudad(int $paisId, string $ciudad): array
    {
        return $this->select('tm_sucursales.*, tm_paises.nombre AS pais_nombre')
            ->join('tm_paises', 'tm_paises.id = tm_sucursales.pais_id', 'left')
            ->where('tm_sucursales.pais_id', $paisId)
            ->where('tm_sucursales.ciudad', $ciudad)
            ->where('tm_sucursales.estatus', 'activo')
            ->orderBy('tm_sucursales.nombre', 'ASC')
            ->findAll();
    }

    /** IDs de todas las sucursales activas (alcance global para administradores). */
    public function idsActivas(): array
    {
        $rows = $this->select('id')
            ->where('estatus', 'activo')
            ->findAll();

        return array_values(array_map(static fn ($s) => (int) $s['id'], $rows));
    }

    /** Sucursal activa con nombre de país (mismo formato que la sesión). */
    public function obtenerConPais(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->select('tm_sucursales.*, tm_paises.nombre AS pais_nombre')
            ->join('tm_paises', 'tm_paises.id = tm_sucursales.pais_id', 'left')
            ->where('tm_sucursales.id', $id)
            ->where('tm_sucursales.estatus', 'activo')
            ->first() ?: null;
    }

    /** Países con sucursales activas dentro del alcance del usuario. */
    public function paisesConAcceso(array $sucursalIds): array
    {
        if ($sucursalIds === []) {
            return [];
        }

        return $this->db->table($this->table)
            ->select('tm_paises.id, tm_paises.nombre')
            ->join('tm_paises', 'tm_paises.id = tm_sucursales.pais_id')
            ->whereIn('tm_sucursales.id', $sucursalIds)
            ->where('tm_sucursales.estatus', 'activo')
            ->where('tm_sucursales.deleted_at', null)
            ->where('tm_paises.estatus', 'activo')
            ->groupBy('tm_paises.id, tm_paises.nombre')
            ->orderBy('tm_paises.nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Ciudades con sucursales activas en un país (alcance del usuario). */
    public function ciudadesConAcceso(int $paisId, array $sucursalIds): array
    {
        if ($paisId <= 0 || $sucursalIds === []) {
            return [];
        }

        $rows = $this->db->table($this->table)
            ->select('ciudad')
            ->distinct()
            ->where('pais_id', $paisId)
            ->whereIn('id', $sucursalIds)
            ->where('estatus', 'activo')
            ->where('deleted_at', null)
            ->where('ciudad IS NOT NULL', null, false)
            ->where('ciudad !=', '')
            ->orderBy('ciudad', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_map(static fn ($r) => $r['ciudad'], $rows));
    }

    /** Sucursales activas por país y ciudad (alcance del usuario). */
    public function sucursalesConAcceso(int $paisId, string $ciudad, array $sucursalIds): array
    {
        if ($paisId <= 0 || $ciudad === '' || $sucursalIds === []) {
            return [];
        }

        return $this->select('tm_sucursales.id, tm_sucursales.nombre, tm_sucursales.ciudad, tm_paises.nombre AS pais_nombre')
            ->join('tm_paises', 'tm_paises.id = tm_sucursales.pais_id', 'left')
            ->where('tm_sucursales.pais_id', $paisId)
            ->where('tm_sucursales.ciudad', $ciudad)
            ->whereIn('tm_sucursales.id', $sucursalIds)
            ->where('tm_sucursales.estatus', 'activo')
            ->orderBy('tm_sucursales.nombre', 'ASC')
            ->findAll();
    }
}
