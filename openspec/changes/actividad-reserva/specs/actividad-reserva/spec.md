# Spec: Actividad de reserva

## Requisitos

### R1 — Registro local
GIVEN una hostess asigna mesa, agrega pax, sienta, libera o guarda tags
WHEN la acción tiene éxito
THEN se inserta un evento en `tm_reserva_actividad` con usuario, descripción y timestamp

### R2 — API de consulta
GIVEN un código de reserva
WHEN se llama `GET hostess/reservas/actividad?codigo=`
THEN se responde `{ success, eventos: [...] }` ordenados del más reciente al más antiguo
AND cada evento incluye `tipo`, `descripcion`, `usuario_nombre`, `origen`, `created_at`

### R3 — UI
GIVEN el panel de detalle abierto
WHEN carga la reserva
THEN se muestra la columna Actividad con el feed agrupado por fecha
