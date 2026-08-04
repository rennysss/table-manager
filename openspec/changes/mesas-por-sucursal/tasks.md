# Tareas

## Esquema y datos
- [ ] Migración: `sucursal_id` (NULL) en `tm_ambientes` (NULL = global).
- [ ] Migración: `sucursal_id` en `tm_mesas` + backfill desde `tm_floorplans`.

## Modelos
- [ ] `AmbienteModel`: `sucursal_id` en allowedFields + `paraSucursal()`.
- [ ] `MesaModel`: `sucursal_id` en allowedFields + `inventarioPorSucursal()`.

## Editor de mesas por sucursal
- [ ] `Admin\MesasController`: `index($sucursalId)`, `guardarLote($sucursalId)`,
      `crearAmbiente($sucursalId)`.
- [ ] Rutas `admin/sucursales/(:num)/mesas[...]`; quitar rutas globales.
- [ ] Vista por sucursal con desplegable de ambientes (global + sucursal) y
      modal para crear ambiente de sucursal.
- [ ] `mesas-editor.js`: alta de ambiente de sucursal en línea.
- [ ] Navegación: quitar entrada global del sidebar; botón "Mesas" en sucursales.

## Floorplan y hostess
- [ ] Estructuras con origen/pivote al centro al rotar.
- [ ] Hostess: colores ocupada = gris, disponible = verde grisáceo.
