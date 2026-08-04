# Propuesta: Mesas y ambientes por sucursal + plano para hostess

## Contexto

El editor de mesas tipo SevenRooms se construyó como catálogo global. La
intención real es que **cada sucursal tenga su propio inventario de mesas**.
Los ambientes pueden ser globales (compartidos) o propios de una sucursal.
El floorplan acomoda las mesas y las separa por ambiente, dándoles forma con
los elementos disponibles. La hostess visualiza el plano con el estado de cada
mesa (ocupada / disponible) y puede seleccionarlas.

## Cambios propuestos

1. **Mesas por sucursal**: `tm_mesas` gana `sucursal_id`. El editor vive en
   `admin/sucursales/{id}/mesas` y solo lista/guarda las mesas de esa sucursal.
2. **Ambientes globales o de sucursal**: `tm_ambientes` gana `sucursal_id`
   opcional (NULL = global). En el editor el desplegable muestra los globales
   más los propios de la sucursal, y se puede crear un ambiente de sucursal.
3. **Rotación con pivote al centro**: mesas y estructuras rotan sobre su
   centro; las coordenadas y el ángulo se guardan (ya en `canvas_json` +
   `rotacion`). Se normaliza el origen de las estructuras al centro.
4. **Plano de hostess**: estados con color — ocupada = gris, disponible =
   verde grisáceo, reservada = ámbar; mesas seleccionables.

## Fuera de alcance

- Integración con la BD compartida de reservaciones (sigue pendiente).
