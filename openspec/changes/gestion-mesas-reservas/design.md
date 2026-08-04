# Diseño técnico: Gestión de Mesas y Reservas

## Stack

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.3+, CodeIgniter 4.7 |
| BD | MySQL 8 (compartida) |
| Frontend | Bootstrap 4.6, jQuery, DataTables, Fabric.js 5 |
| QR | html5-qrcode |
| Seguridad | CSRF, CSP, SecureHeaders, InvalidChars, esc() |

## Arquitectura

```
public/
  assets/css/     → estilos Apple HIG + dark floorplan
  assets/js/      → floorplan-editor.js, hostess.js, qr-scanner.js
app/
  Controllers/
    Auth/         → Login, Logout
    Admin/        → Sucursales, Ambientes, Mesas, FloorPlan, Usuarios
    Gerente/      → FloorPlan (limitado), Mesas
    Hostess/      → Reservas, Plano, Tags
    Api/          → Reservas, Auth, FloorPlan, Mesas (JSON)
  Filters/        → AuthFilter, RoleFilter
  Models/         → Entidades + UsuarioModel (adaptable a BD compartida)
  Database/Migrations/
  Views/
    layouts/      → admin, hostess, floorplan (dark)
    admin/
    gerente/
    hostess/
```

## Modelo de datos (provisional)

Tablas propias del módulo (prefijo `tm_` = table management):

- `tm_sucursales` — estado, ciudad, estatus, imagen_png
- `tm_ambientes` — sucursal_id, nombre, estatus
- `tm_mesas` — ambiente_id, numero, pax/min/max, estatus, geometría canvas
- `tm_floorplans` — sucursal_id, nombre, tipo (normal|festivo|custom), activo, canvas_json
- `tm_floorplan_elementos` — elementos estructurales no-mesa
- `tm_reserva_asignaciones` — reserva_codigo, mesa_id, rp, tags_json, asignado_por
- `tm_tags` — catálogo de tags para hostess

Tablas compartidas (adaptador cuando llegue esquema):

- `users` / `usuarios` — autenticación
- `roles` / `niveles` — admin, gerente, hostess
- `user_sucursales` — relación N:N

## FloorPlan Editor

- **Canvas:** Fabric.js sobre grid oscuro (#1a1a1a).
- **Toolbar superior:** Add Table, Add Structural Element, Undo/Redo, Zoom, Save/Discard.
- **Sidebar izquierdo:** Tabs Tables / Combinations; presets rectangulares y circulares.
- **Panel Add Structural:** Labels, Shapes, Furniture (SVG icons).
- **Bottom-right:** Toggle ambientes (NIGHTCLUB / RESTAURANTE) + botón agregar.
- **Persistencia:** `canvas_json` (Fabric.toJSON) + metadatos de mesas vinculadas a `tm_mesas`.
- **Versiones:** Múltiples floorplans; solo uno `activo=1` por sucursal/ambiente.

## Seguridad

1. **CSRF** global en formularios y AJAX (header X-CSRF-TOKEN).
2. **XSS:** `esc()` en todas las salidas; CSP restrictiva.
3. **SQLi:** Query Builder / prepared statements exclusivamente.
4. **Auth:** Sesión regenerada post-login; timeout 2h; bcrypt/argon2.
5. **RBAC:** RoleFilter por ruta (`admin/*`, `gerente/*`, `hostess/*`).
6. **API:** Token Bearer o sesión + rate limiting en login.
7. **Uploads:** Validación MIME, extensión whitelist (.png), rename UUID.
8. **Headers:** X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy.

## Diseño UI (Apple HIG)

- Tipografía: `-apple-system, BlinkMacSystemFont, "SF Pro Text", system-ui`
- Espaciado generoso, bordes redondeados (8–12px), sombras sutiles
- Modo claro: Hostess/Admin listados (fondo #F5F5F7)
- Modo oscuro: FloorPlan editor (#000 / #1C1C1E)
- Touch targets ≥ 44px en módulo Hostess (iPad)
- Contraste WCAG AA

## API Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/reservas?fecha=&sucursal_id=` | Lista reservas del día |
| GET | `/api/reservas/{codigo}` | Detalle por QR/código |
| POST | `/api/reservas/{codigo}/asignar` | Asignar mesa |
| POST | `/api/reservas/{codigo}/tags` | Agregar tags |
| GET | `/api/floorplan/activo?sucursal_id=` | Plano activo + estados mesas |
| GET | `/api/mesas/estado?sucursal_id=&fecha=` | Mesas con estado |
| POST | `/api/auth/login` | Autenticación JSON |

## Adaptación BD compartida

`app/Config/DatabaseMapping.php` centraliza nombres de tablas/columnas del proyecto padre. Al recibir el esquema mañana, solo se actualiza este config y `UsuarioModel`.
