# Spec: Tags de cliente por email

## Purpose
La hostess asigna tags del catálogo (Configuraciones → Tags). El perfil del huésped se identifica por email para recordar tags en visitas futuras.

## Requirements

### Requirement: Primera visita sin tags
#### Scenario: Reserva nueva sin historial
- **GIVEN** una reserva cuyo email no existe en `tm_clientes` (o viene vacío)
- **WHEN** la hostess abre el detalle
- **THEN** ningún tag aparece seleccionado; la hostess puede agregar tags del catálogo

### Requirement: Cliente recurrente por email
#### Scenario: Mismo correo en otra reserva
- **GIVEN** un cliente con tags guardados en su perfil por email
- **WHEN** llega una nueva reserva con el mismo email
- **THEN** el detalle precarga esos tags para que la hostess los confirme o ajuste

### Requirement: Persistencia al togglear
#### Scenario: Guardar tags sin mesa asignada
- **GIVEN** una reserva con email válido y sin asignación de mesa
- **WHEN** la hostess selecciona o quita tags
- **THEN** se actualiza el perfil en `tm_clientes` y, si hay asignación, también `tags_json`
