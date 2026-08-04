# Spec delta: Check-in y pax parciales

## ADDED Requirements

### Requirement: Check-in con estado y mesa
El panel de detalle de hostess DEBE mostrar STATUS y TABLE de la reserva asignada, al estilo SevenRooms.

#### Scenario: Reserva asignada llega parcialmente
- **GIVEN** una reserva con mesa asignada y pax_reservados = 10
- **WHEN** la hostess registra +2 pax
- **THEN** pax_en_mesa = 2, estado = arrived, y queda un registro de llegada con timestamp

#### Scenario: Segunda oleada
- **GIVEN** la misma reserva con pax_en_mesa = 2
- **WHEN** la hostess agrega +4 pax
- **THEN** pax_en_mesa = 6 y existe historial de ambas oleadas

#### Scenario: Pax puede superar la reserva
- **GIVEN** pax_reservados = 10
- **WHEN** la hostess agrega pax hasta 12
- **THEN** el sistema lo permite

#### Scenario: Sentar y liberar
- **GIVEN** una reserva arrived u ocupada
- **WHEN** la hostess pulsa Sentar
- **THEN** estado_mesa/reserva = ocupada en el plano
- **WHEN** pulsa Liberar
- **THEN** la mesa vuelve a libre
