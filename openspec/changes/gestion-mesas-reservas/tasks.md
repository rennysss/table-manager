# Tareas de implementación

> **Estado:** Plan aprobado por el cliente (2026-06-23). Fases 1–4 implementadas en código. Fase 6 pendiente de esquema BD.

## Fase 1 — Fundamentos ✅

- [x] Instalar CodeIgniter 4.7
- [x] Crear propuesta OpenSpec
- [x] Configurar `.env` y conexión MySQL (ajustar credenciales MAMP local)
- [x] Migraciones provisionales `tm_*`
- [x] Filtros de seguridad (CSRF, Auth, Role)
- [x] Layouts Bootstrap 4 + CSS Apple HIG
- [x] Login / Logout con sesión segura

## Fase 2 — Administrador ✅

- [x] CRUD Sucursales (filtros estado/ciudad/estatus, upload PNG)
- [x] CRUD Ambientes por sucursal
- [x] CRUD Mesas por ambiente
- [x] CRUD Usuarios + asignación multi-sucursal
- [x] FloorPlan editor completo (Fabric.js)
- [x] Versiones de floorplan (activar/desactivar)
- [x] Cálculo de aforo

## Fase 3 — Hostess ✅

- [x] Vista responsiva estilo Sevenrooms
- [x] DataTable con reservas desde API (demo sin endpoint)
- [x] Escáner QR (html5-qrcode)
- [x] Panel detalle reserva + tags
- [x] Modal/vista plano con asignación mesa
- [x] Estados mesa: libre / ocupada / reservada

## Fase 4 — Gerente ✅

- [x] Panel y gestión floorplans por sucursal
- [x] Crear, editar, activar versiones de floorplan
- [x] Edición pax y posición (bloqueo número mesa existente)
- [x] Preservar reservas al mover mesa

## Fase 5 — API e integración (parcial)

- [x] Endpoints REST base
- [ ] Adaptador BD compartida (post-entrega esquema)
- [x] Seguridad CSRF, XSS, auth, RBAC
- [x] Specs permanentes en `openspec/specs/`

## Fase 6 — Ajustes post-esquema BD ⏳

- [ ] Mapear tablas usuarios/roles del proyecto padre → ver `docs/INTEGRACION_BD.md`
- [ ] Conectar endpoint real de reservas
- [ ] Ejecutar `php spark migrate && php spark db:seed InitialSeeder`
- [ ] Deploy MAMP / producción
