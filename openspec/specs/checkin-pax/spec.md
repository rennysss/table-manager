# Spec: Check-in y pax parciales

## Purpose
Operar el check-in de reservas en hostess al estilo SevenRooms: STATUS, TABLE, pax reservados vs en mesa, oleadas de llegada.

## Requirements

### Requirement: Panel de check-in
El detalle de reserva DEBE mostrar STATUS, TABLE, pax reservados y pax en mesa.

#### Scenario: Llegada parcial
- **GIVEN** mesa asignada y 10 pax reservados
- **WHEN** hostess agrega +2 pax
- **THEN** status = Arrived, pax_en_mesa = 2, historial de llegada registrado

#### Scenario: Superar pax de reserva
- **GIVEN** pax_reservados = 10
- **WHEN** hostess agrega pax hasta 12
- **THEN** se permite; si supera maximo de mesa solo se advierte

#### Scenario: Sentar y liberar
- **GIVEN** asignación activa
- **WHEN** hostess pulsa Sentar
- **THEN** estado_mesa = ocupada
- **WHEN** pulsa Liberar
- **THEN** estado_mesa = liberada
