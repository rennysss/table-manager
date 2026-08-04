<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'tm_usuarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'usuario', 'email', 'password', 'nombre', 'rol', 'estatus',
    ];
    protected $useTimestamps = true;

    /**
     * Autentica contra BD local o compartida según configuración.
     */
    public function autenticar(string $usuario, string $password): ?array
    {
        $mapping = config('DatabaseMapping');

        if ($mapping->useSharedDatabase) {
            return $this->autenticarCompartida($usuario, $password, $mapping);
        }

        $row = $this->where('usuario', $usuario)
            ->where('estatus', 'activo')
            ->first();

        if (! $row || ! password_verify($password, $row['password'])) {
            return null;
        }

        unset($row['password']);

        $row['sucursales'] = $this->obtenerSucursales((int) $row['id']);

        return $row;
    }

    /**
     * Autenticación contra tablas del proyecto de reservaciones.
     */
    private function autenticarCompartida(string $usuario, string $password, $mapping): ?array
    {
        $db = \Config\Database::connect();

        $row = $db->table($mapping->usersTable)
            ->where($mapping->usersUsernameColumn, $usuario)
            ->where($mapping->usersStatusColumn, 'activo')
            ->get()
            ->getRowArray();

        if (! $row || ! password_verify($password, $row[$mapping->usersPasswordColumn])) {
            return null;
        }

        return [
            'id'         => $row[$mapping->usersIdColumn],
            'usuario'    => $row[$mapping->usersUsernameColumn],
            'nombre'     => $row[$mapping->usersNameColumn],
            'rol'        => $this->mapearRol($row[$mapping->usersRoleColumn]),
            'sucursales' => $this->obtenerSucursalesCompartidas((int) $row[$mapping->usersIdColumn]),
        ];
    }

    /**
     * Mapea roles del sistema padre a roles internos.
     */
    private function mapearRol(string $rolExterno): string
    {
        $mapa = [
            'administrador' => 'admin',
            'admin'         => 'admin',
            'gerente'       => 'gerente',
            'hostess'       => 'hostess',
        ];

        return $mapa[strtolower($rolExterno)] ?? 'hostess';
    }

    public function obtenerSucursales(int $usuarioId): array
    {
        return $this->db->table('tm_usuario_sucursales us')
            ->select('s.*, p.nombre AS pais_nombre')
            ->join('tm_sucursales s', 's.id = us.sucursal_id')
            ->join('tm_paises p', 'p.id = s.pais_id', 'left')
            ->where('us.usuario_id', $usuarioId)
            ->where('s.estatus', 'activo')
            ->orderBy('p.nombre', 'ASC')
            ->orderBy('s.ciudad', 'ASC')
            ->orderBy('s.nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function obtenerSucursalesCompartidas(int $usuarioId): array
    {
        // Adaptar cuando se conozca la tabla de relación del proyecto padre
        return $this->obtenerSucursales($usuarioId);
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
}
