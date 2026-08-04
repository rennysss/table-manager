# Spec permanente: Actividad de reserva

El detalle de hostess incluye un feed de actividad consolidado.

## API
- `GET hostess/reservas/actividad?codigo={booking}` → lista de eventos

## Eventos locales
Se registran al asignar/cambiar mesa, registrar pax, sentar, liberar y actualizar tags.

## Eventos externos
Si OneReservations expone actividad, se normaliza y se fusiona (sin duplicar por `externo_id`).
