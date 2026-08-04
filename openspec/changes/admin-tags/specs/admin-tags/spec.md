# Spec delta: Admin Tags (UI SevenRooms)

## ADDED Requirements

### Requirement: Tags agrupados por categoría
El admin DEBE ver y gestionar tags en categorías con pastillas de color.

#### Scenario: Layout Reservation Tags
- **GIVEN** categorías activas con tags
- **WHEN** el admin abre `/admin/tags`
- **THEN** cada categoría muestra nombre, subtítulo de alcance/visibilidad, pastillas de tags activos y un botón +

#### Scenario: Agregar tag desde categoría
- **GIVEN** una categoría
- **WHEN** el admin pulsa + y guarda un tag
- **THEN** el tag queda asociado a esa categoría
