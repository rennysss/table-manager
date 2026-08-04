# Diseño: Check-in y pax parciales

## Modelo de datos

### `tm_reserva_asignaciones` (ampliar)
- `pax_reservados` INT NULL — copia del pax API al asignar/check-in
- `pax_en_mesa` INT NOT NULL DEFAULT 0 — ocupación real actual
- `estado_reserva` ENUM(`asignada`,`arrived`,`ocupada`,`liberada`) — o reutilizar/ampliar `estado_mesa`
- `arrived_at`, `seated_at`, `liberated_at` DATETIME NULL

**Mapeo de colores en plano (existente):**
- sin asignación → libre
- `asignada` / `arrived` → reservada
- `ocupada` → ocupada
- `liberada` → libre (histórico)

### `tm_reserva_llegadas` (nueva)
| Campo | Tipo | Nota |
|-------|------|------|
| id | INT PK | |
| asignacion_id | INT FK | |
| pax_delta | INT | +N llegada / −N ajuste |
| pax_resultante | INT | total tras el evento |
| nota | VARCHAR(120) NULL | opcional |
| registrado_por | INT NULL | usuario_id |
| created_at | DATETIME | hora de la oleada |

## Flujo hostess

```
Reserva (API) pax=10
  → Asignar mesa 107
       estado=asignada, pax_reservados=10, pax_en_mesa=0

  → Marcar llegada / Agregar pax (+2 @ 22:00)
       estado=arrived, pax_en_mesa=2, fila llegada +2

  → Agregar pax (+4 @ 23:00)
       pax_en_mesa=6, fila llegada +4

  → Sentar (opcional si aún arrived)
       estado=ocupada

  → Liberar
       estado=liberada, pax_en_mesa=0 (o se conserva histórico en llegadas)
```

## API hostess (nuevos endpoints)
- `POST hostess/reservas/llegada` — `{ reserva_codigo, pax_delta, nota? }`
- `POST hostess/reservas/sentar` — marca ocupada
- `POST hostess/reservas/liberar` — libera mesa

`agregarTags` / asignar mesa se mantienen; al asignar se setea `pax_reservados` desde API.

## UI panel detalle (inspirado en captura)
```
[ Nombre cliente ]
STATUS: Arrived | TABLE: 107 | …

Pax reservados: 10
Pax en mesa: 6
[ + Agregar pax ]  [ Sentar ]  [ Liberar ]

Tags … (existente)
[ Abrir plano y asignar mesa ]
```

Modal “Agregar pax”: input numérico (default 1), confirma → POST llegada.

## Reglas
1. Sin mesa asignada no se puede registrar llegada (igual que SevenRooms: table + status).
2. `pax_en_mesa` puede ser > `pax_reservados`.
3. Si `pax_en_mesa` > `mesa.maximo` → warning en UI; por defecto **no bloquea** (configurable luego).
4. Primera llegada con delta > 0 pasa a `arrived` si estaba `asignada`.
5. `Sentar` pasa a `ocupada` (plano).
