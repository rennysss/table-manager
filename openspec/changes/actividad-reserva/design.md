# Diseño: Actividad de reserva

## Persistencia
Tabla `tm_reserva_actividad`:
- `reserva_codigo`, `sucursal_id`
- `tipo`: mesa_asignada | mesa_cambiada | llegada_pax | sentar | liberar | tags | externo | otro
- `descripcion`: texto legible en español
- `origen`: Table Manager | OneReservations | …
- `usuario_id`, `usuario_nombre` (snapshot)
- `meta_json`: opcional (antes/después)
- `externo_id`: deduplicación de eventos API
- `created_at`

## Escritura
`ReservaActividadService::registrar()` se invoca desde:
- `asignarMesa`, `registrarLlegada`, `sentar`, `liberar`, `agregarTags`

## Lectura
`GET hostess/reservas/actividad?codigo=`
1. Eventos locales por código
2. Intento `ReservasApiService::obtenerActividad()` (`/reservas/actividad` o campo `actividad` en by-booking)
3. Respaldo: sintetizar desde `tm_reserva_llegadas` si aún no hay filas de actividad para esa oleada
4. Orden descendente por `created_at`; agrupación por día en el cliente

## UI
Panel detalle ensanchado (~720px): columna izquierda (detalle actual) + derecha (feed Actividad).
