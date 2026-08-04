# Propuesta: Módulo Actividad de reserva (log estilo SevenRooms)

## Resumen
Agregar un panel **Actividad** en el detalle de hostess que muestre un historial cronológico de todo lo ocurrido con la reserva (asignación de mesa, tags, llegadas de pax, sentar, liberar, y eventos externos de OneReservations cuando existan).

## Motivación
Hoy no hay bitácora visible: la hostess no puede auditar quién cambió mesa/tags/pax. SevenRooms muestra “ALL ACTIVITY” a la derecha del detalle; se requiere el equivalente con API propia que consolide la información.

## Alcance
1. Tabla `tm_reserva_actividad` + registro automático en acciones hostess.
2. `GET hostess/reservas/actividad?codigo=` — consolida log local + (opcional) eventos de API externa.
3. UI: columna derecha “Toda la actividad” en el panel de detalle (comentarios/mensajes como pestañas deshabilitadas).

## Fuera de alcance
- Comentarios y mensajería editables
- Sync de escritura hacia OneReservations
