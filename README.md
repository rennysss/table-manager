# 1R Tables — Gestión de Mesas y Reservas

WebApp complementaria al sistema de reservaciones. Gestiona planos de mesas (FloorPlan), asignación de reservas, aforo y roles multi-sucursal.

## Stack

- **PHP 8.3+** / **CodeIgniter 4.7**
- **MySQL 8**
- **Bootstrap 4.6** + diseño inspirado en [Apple HIG](https://developer.apple.com/design/human-interface-guidelines)
- **Fabric.js** — editor FloorPlan estilo Sevenrooms
- **DataTables** + **html5-qrcode** — módulo Hostess

## Instalación (MAMP)

```bash
# 1. Clonar / copiar en htdocs
cd /Applications/MAMP/htdocs/1R_tables

# 2. Dependencias (ya instaladas)
composer install

# 3. Configurar .env (puerto MySQL MAMP: 8889)
cp env .env
php spark key:generate

# 4. Crear BD y migrar
mysql -u root -proot -P 8889 -e "CREATE DATABASE IF NOT EXISTS 1r_tables CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php spark migrate
php spark db:seed InitialSeeder

# 5. Permisos uploads
mkdir -p public/uploads/sucursales
chmod 775 public/uploads/sucursales writable/
```

## Acceso demo

| Usuario  | Contraseña    | Rol           |
|----------|---------------|---------------|
| admin    | Admin123!     | Administrador |
| gerente  | Gerente123!   | Gerente       |
| hostess  | Hostess123!   | Hostess       |

URL: `http://localhost:8888/1R_tables/`

## Módulos

### Administrador
- Sucursales (filtros, imagen PNG)
- Ambientes y Mesas
- FloorPlan editor (versiones normal/festivo/año nuevo)
- Usuarios multi-sucursal

### Hostess
- DataTable de reservas (endpoint JSON)
- Escáner QR (tablet/iPad)
- Tags de cliente
- Plano interactivo para asignar mesa

### Gerente
- Panel de sucursales asignadas con resumen de aforo
- **Gestión completa de floorplans** por sucursal: crear versiones (normal, festivo, año nuevo), editar, activar/desactivar
- Consulta de ambientes y mesas de su discoteca
- Edición de pax/min/max en mesas existentes (sin cambiar número de mesa)
- Editor FloorPlan: agregar mesas, mover, elementos estructurales, guardar
- Las reservas asignadas permanecen vinculadas al mover una mesa

## API

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/auth/login` | Autenticación |
| GET | `/api/reservas?fecha=&sucursal_id=` | Listado reservas |
| GET | `/api/reservas/{codigo}` | Detalle reserva |
| GET | `/api/floorplan/activo?sucursal_id=` | Plano activo + estados |

## Integración BD compartida

Editar `app/Config/DatabaseMapping.php` cuando se entregue el esquema del proyecto de reservaciones:

```php
public bool $useSharedDatabase = true;
public string $usersTable = 'users'; // nombre real
```

## Seguridad

- CSRF global + header AJAX
- InvalidChars + SecureHeaders
- Password Argon2ID
- RBAC por rol (admin/gerente/hostess)
- Escape XSS en vistas (`esc()`)
- Validación uploads PNG

## OpenSpec

Documentación de cambio en `openspec/changes/gestion-mesas-reservas/`.
