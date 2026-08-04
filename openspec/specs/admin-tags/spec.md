# Spec: Administración de tags

## Purpose
Permitir al administrador gestionar el catálogo de tags de reserva agrupados por categoría, con UI estilo SevenRooms.

## Requirements

### Requirement: Catálogo de tags por categoría
El sistema DEBE exponer Configuraciones → Tags con categorías y tags en pastillas de color.

#### Scenario: Ver reservation tags
- **GIVEN** un usuario con rol admin
- **WHEN** navega a `/admin/tags`
- **THEN** ve categorías con subtítulo (Global/Local, Show on reservation, Show on chit) y tags activos como pastillas de color, con botón + por categoría

#### Scenario: Crear categoría
- **GIVEN** el admin abre el modal de nueva categoría
- **WHEN** captura nombre, alcance y flags de visualización y guarda
- **THEN** la categoría aparece en el listado

#### Scenario: Crear tag en categoría
- **GIVEN** el admin pulsa + en una categoría
- **WHEN** captura nombre, color y estatus y guarda
- **THEN** el tag aparece como pastilla en esa categoría

#### Scenario: Editar tag
- **GIVEN** un tag activo visible como pastilla
- **WHEN** el admin lo pulsa, edita y guarda
- **THEN** los cambios se reflejan en la pastilla

#### Scenario: Tag inactivo no disponible en hostess
- **GIVEN** un tag con estatus `inactivo`
- **WHEN** hostess carga el selector de tags
- **THEN** ese tag no aparece entre las opciones activas
