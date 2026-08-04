# Propuesta: Check-in de reserva y pax parciales (estilo SevenRooms)

## Resumen
Enriquecer el panel de detalle de hostess para operar el **check-in** de la reserva como en SevenRooms: estado de llegada, mesa asignada, pax reservados vs pax en mesa, y posibilidad de **agregar pax por oleadas** (llegadas parciales). El total en mesa puede superar el pax de la reserva original.

## Motivación
Hoy hostess solo ve pax (lectura API) y asigna mesa. No hay “Arrived / Seated”, ni registro de cuántos pax ya entraron en distintos horarios. Operación real (ej. 10 pax reservados → 2 a las 22:00 → +4 a las 23:00) no está cubierta.

## Referencia UI (SevenRooms)
Panel de reserva con:
- Cabecera: código de reserva + nombre del cliente
- Barra **STATUS** (ej. Arrived) + **TABLE** (nº mesa)
- Resumen de visita: fecha, hora, **pax**, ambiente/servicio
- Tags de reserva
- Contacto (teléfono / email)
- (Fase 2) Activity feed — fuera del MVP

## Alcance MVP
1. Estados operativos de la asignación: `confirmada` → `arrived` (llegada parcial o total) → `ocupada` (sentados) → `liberada`
2. Campos: `pax_reservados` (snapshot API), `pax_en_mesa` (reales)
3. Historial de oleadas: `tm_reserva_llegadas` (+/− pax, hora, usuario)
4. UI hostess en panel detalle:
   - Mostrar STATUS + TABLE + pax reservados / en mesa
   - Acciones: **Marcar llegada**, **Agregar pax**, **Sentar**, **Liberar**
   - Asignar mesa (ya existe) alimenta TABLE
5. Plano: mesa `ocupada` cuando está sentada; `reservada` cuando asignada/arrived

## Fuera de alcance (MVP)
- Activity feed completo / comentarios / messaging
- Sync de status hacia OneReservations API
- Split de una reserva en varias mesas
- Adjuntos

## Decisiones de producto (confirmadas por el usuario)
- Pax en mesa **puede superar** pax de la reserva
- Hostess es quien registra las oleadas

## Pendiente de confirmar
- Si `pax_en_mesa` supera `mesa.maximo`: ¿solo aviso o bloqueo?
