# Spec: Gestión de Mesas y Reservas

## Autenticación y roles

- Login por usuario/contraseña con sesión segura (Argon2ID, CSRF, RBAC).
- Roles: `admin`, `gerente`, `hostess`.
- Usuarios pueden tener una o varias sucursales asignadas.

## Administrador

- CRUD sucursales (estado, ciudad, estatus, imagen PNG).
- CRUD ambientes y mesas por sucursal.
- FloorPlan editor (Fabric.js) con versiones activables.
- CRUD usuarios multi-sucursal.

## Gerente

- Panel y gestión de floorplans **por sucursal asignada**.
- Crear, editar, activar/desactivar versiones (normal, festivo, año nuevo).
- Editor completo: mesas, elementos estructurales, pax, posición.
- No puede cambiar número de mesa existente; reservas permanecen por `mesa_id`.

## Hostess

- DataTable de reservas (endpoint JSON).
- Escáner QR en tablet/iPad.
- Tags de cliente y asignación reserva → mesa en plano.
- Vista 100% responsiva.

## Aforo

- Suma de `maximo` de mesas activas del floorplan activo por sucursal.

## Integración pendiente

- BD compartida con proyecto de reservaciones (`DatabaseMapping.php`).
- Endpoint externo de reservas (`ReservasApiService`).
