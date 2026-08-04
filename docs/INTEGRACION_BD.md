# Guía de integración — BD compartida y API de reservas

Use este documento mañana al recibir el esquema MySQL del proyecto de reservaciones.

## 1. Configurar conexión

Editar `.env`:

```ini
database.default.hostname = localhost
database.default.database = NOMBRE_BD_COMPARTIDA
database.default.username = root
database.default.password = root
database.default.port = 8889
```

## 2. Mapear tablas de usuarios

Editar `app/Config/DatabaseMapping.php`:

```php
public bool $useSharedDatabase = true;
public string $usersTable = 'users';           // tabla real
public string $usersUsernameColumn = 'username';
public string $usersPasswordColumn = 'password';
public string $usersNameColumn = 'name';
public string $usersRoleColumn = 'role';
public string $usersStatusColumn = 'status';
```

Ajustar `UsuarioModel::mapearRol()` si los nombres de rol difieren.

## 3. Tablas del módulo (`tm_*`)

Ejecutar migraciones en la BD compartida (coexisten con tablas del proyecto padre):

```bash
php spark migrate
php spark db:seed InitialSeeder   # solo en desarrollo
```

## 4. Endpoint de reservas (OneReservations API v1)

En `.env` del servidor (solo backend — nunca en JavaScript del navegador):

```ini
reservas.apiUrl = 'https://one-reservations.com/app-reservas-ws/v1'
reservas.apiKey = 'TU_API_KEY'
```

Autenticación: header `X-API-Key`. Se puede configurar **por sucursal** en Admin → Sucursales (campo API Key) o globalmente en `.env` como respaldo:

```ini
reservas.apiUrl = 'https://one-reservations.com/app-reservas-ws/v1'
reservas.apiKey = 'TU_API_KEY'
```

El **Venue ID** de cada sucursal (`tm_sucursales.venue_id`) se envía como parámetro `disco`.

Endpoints consumidos por `ReservasApiService`:

| Uso | Método | Ruta |
|-----|--------|------|
| Listado del día | GET | `/reservas/activas?disco={venue_id}&fecha_visita={Y-m-d}` |
| Detalle booking | GET | `/reservas/by-booking?booking=1R-xxx` |
| QR / token | GET | `/reservas/by-token?token=...` |
| Actividad (opcional) | GET | `/reservas/actividad?booking=1R-xxx&disco={venue_id}` |

Sin API Key (ni en sucursal ni en `.env`) el módulo Hostess opera en **modo demostración** con datos ficticios.

## 5. Checklist de verificación

- [ ] Login con usuario del proyecto padre
- [ ] Roles admin / gerente / hostess redirigen correctamente
- [ ] Hostess carga reservas reales del endpoint
- [ ] Asignación reserva → mesa persiste en `tm_reserva_asignaciones`
- [ ] Floorplan activo visible en módulo hostess

## 6. Datos que necesitamos del equipo de reservaciones

| Campo | Uso |
|-------|-----|
| Tabla y columnas de usuarios | Auth |
| Tabla usuario ↔ sucursal | Permisos multi-sucursal |
| URL + contrato JSON reservas | Hostess DataTable |
| Campos: código, hora, nombre, RP | Listado y QR |
