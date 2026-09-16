# Propuesta: Sentar en Xetux + tags cliente/reserva

## Resumen
En el detalle de reserva (hostess), separar tags de cliente (perfil por email) y tags de reserva; agregar selects de mesero y mesa vía API Xetux; botón **SENTAR** que crea orden, registra prepago si aplica, y persiste `orderId`/`suborderId` por código de reserva. Tras sentar: **CANCELAR** (liberar) y **CERRAR CUENTA** (consulta `order/info`).

## Alcance
- Config sucursal: URL Xetux, API Key, stationCode, payformId prepago
- Catálogo Tags: dominio `cliente` | `reserva`
- Backend proxy Xetux (Authorization + API Key)
- UI panel reserva + actividad

## Fuera de alcance (pendiente usuario)
- URL final tras cerrar cuenta con totales de productos
