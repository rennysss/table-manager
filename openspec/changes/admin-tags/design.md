# Diseño: Admin Tags

## Decisiones
1. **Patrón UI:** Clonar Marcas (listado + modal AJAX + soft status).
2. **Datos:** `tm_tags` (`nombre`, `color` hex `#RRGGBB`, `estatus` activo/inactivo).
3. **Color en listado:** muestra un swatch junto al nombre para lectura rápida (estilo catálogo SevenRooms).
4. **Sin hard delete:** desactivar con `estatus = inactivo` para no romper `tags_json` históricos en asignaciones.

## Integración
- Rutas bajo grupo `admin` con filtro `role:admin`
- Menú en `sidebar.php` dentro de Configuraciones
- Hostess no requiere cambios: tags activos aparecen al recargar
