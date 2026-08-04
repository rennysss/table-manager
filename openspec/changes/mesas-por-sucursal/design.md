# Diseño técnico

## Modelo de datos

- `tm_ambientes.sucursal_id` (INT unsigned, NULL). `NULL` = ambiente global
  compartido por todas las sucursales; con valor = ambiente propio de esa
  sucursal. FK a `tm_sucursales` con `ON DELETE CASCADE` (al borrar sucursal se
  borran sus ambientes propios; los globales no se ven afectados).
- `tm_mesas.sucursal_id` (INT unsigned, NULL temporal para backfill). Cada mesa
  pertenece a una sucursal. Se rellena desde `tm_floorplans.sucursal_id` para
  las mesas ya colocadas en un plano.

## Consultas clave

- Ambientes disponibles para una sucursal:
  `WHERE sucursal_id IS NULL OR sucursal_id = :id`.
- Inventario de mesas de una sucursal:
  `WHERE tm_mesas.sucursal_id = :id` con join a `tm_ambientes` para el nombre.

## Editor (estilo SevenRooms)

Inventario por sucursal: columnas Mesa / Mini Pax / Max Pax / Ambiente, con
eliminar y duplicar (el número no se duplica). Guardado por lote: inserta,
actualiza y elimina (soft delete) en una transacción. Validaciones: número
obligatorio y único dentro del mismo ambiente de la sucursal; mínimo ≥ 1;
máximo ≥ mínimo; ambiente válido (global o de la sucursal).

`floorplan_id` queda en NULL al crear desde el editor: el inventario se define
aquí y el floorplan es donde luego se colocan y se les da forma.

## Rotación y coordenadas

Fabric.js rota con `centeredRotation` (pivote al centro). Las mesas son grupos
con origen centrado; se normaliza el origen de las estructuras a `center` para
que roten igual. La posición (`left`/`top`), tamaño y `angle` persisten en
`canvas_json` y, para mesas, también en `pos_x/pos_y/ancho/alto/rotacion`.

## Estados en el plano de hostess

Colores por estado: `ocupada` = gris (#8E8E93), `libre` = verde grisáceo
(#8FAE9D), `reservada` = ámbar (#FF9500). Las mesas son seleccionables para
asignación de reserva.
