<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Mapeo de tablas/columnas de la BD compartida del proyecto de reservaciones.
 * Actualizar cuando se entregue el esquema definitivo mañana.
 */
class DatabaseMapping extends BaseConfig
{
    /** Usar tablas locales tm_* mientras no exista BD compartida */
    public bool $useSharedDatabase = false;

    /** Tabla de usuarios del proyecto padre */
    public string $usersTable = 'users';

    public string $usersIdColumn = 'id';
    public string $usersUsernameColumn = 'username';
    public string $usersPasswordColumn = 'password';
    public string $usersNameColumn = 'name';
    public string $usersRoleColumn = 'role';
    public string $usersStatusColumn = 'status';

    /** Tabla local fallback */
    public string $localUsersTable = 'tm_usuarios';

    /** Endpoint externo de reservas */
    public string $reservasApiUrl = '';

    public string $reservasApiToken = '';
}
