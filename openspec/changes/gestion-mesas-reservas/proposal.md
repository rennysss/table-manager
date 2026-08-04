# Propuesta: WebApp de Gestión de Mesas y Reservas

## Resumen

Desarrollar una webapp complementaria al sistema de reservaciones existente, construida en **PHP 8.3+ / CodeIgniter 4 / MySQL / Bootstrap 4**, con diseño inspirado en las [Human Interface Guidelines de Apple](https://developer.apple.com/design/human-interface-guidelines) y funcionalidad estructural similar a **Sevenrooms**.

La aplicación gestiona planos de mesas (FloorPlan), estados de mesas (libre/ocupada/reservada), asignación de reservas a mesas, control de aforo y comisiones por RP.

## Problema de negocio

- Las hostess necesitan visualizar reservas, escanear QR y asignar mesas sin buscar manualmente.
- Los gerentes requieren ajustar capacidad y ubicación de mesas sin afectar reservas activas.
- Los administradores configuran sucursales, ambientes, planos versionados (normal/festivo/año nuevo) y permisos multi-sucursal.
- El aforo calculado desde mesas activas previene sobrebookeo en el sistema de reservas.

## Alcance por rol

| Rol | Capacidades |
|-----|-------------|
| **Administrador** | CRUD sucursales, ambientes, mesas, floorplans, usuarios y asignación multi-sucursal |
| **Gerente** | Editar floorplan, mover mesas, modificar pax (no número de mesa) en sus sucursales |
| **Hostess** | Listado de reservas (endpoint), escaneo QR, tags, asignación reserva→mesa, vista plano |

## Integración

- **Base de datos compartida** con el proyecto de reservaciones (esquema pendiente de entrega).
- **Endpoint de reservas** consumido por el módulo Hostess (JSON: código, hora, nombre, RP).
- **Endpoint de autenticación/usuarios** alineado con niveles existentes.

## Fuera de alcance (fase inicial)

- Gestión completa de comisiones RP (solo trazabilidad reserva→mesa→RP).
- App móvil nativa (solo web responsiva para tablet/iPad).
- Sincronización bidireccional en tiempo real (WebSockets) — fase 2.

## Criterios de éxito

1. FloorPlan funcional con presets de mesas y elementos estructurales.
2. Múltiples floorplans por sucursal con activación/desactivación.
3. Hostess 100% responsiva con QR y DataTable.
4. Seguridad: CSRF, XSS, SQLi, headers seguros, autenticación por sesión.
5. Aforo calculado automáticamente desde mesas activas del floorplan activo.

## Dependencias

- Esquema MySQL del proyecto de reservaciones (entrega pendiente).
- URL y contrato del endpoint de reservas.
- Imágenes PNG de referencia para presets de mesas y elementos.
