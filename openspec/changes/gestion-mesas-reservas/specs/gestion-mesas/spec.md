# Spec Delta: Gestión de Mesas

## ADDED Requirements

### REQ-AUTH-001: Autenticación segura
**GIVEN** un usuario con credenciales válidas en la BD compartida  
**WHEN** envía usuario y contraseña en el formulario de login  
**THEN** se crea sesión regenerada con rol y sucursales asignadas  
**AND** redirige al dashboard según rol (admin/gerente/hostess)

### REQ-AUTH-002: Control de acceso por rol
**GIVEN** un usuario autenticado con rol hostess  
**WHEN** intenta acceder a `/admin/sucursales`  
**THEN** recibe HTTP 403 y mensaje de acceso denegado

### REQ-FLOOR-001: Editor de plano
**GIVEN** un administrador en el editor de floorplan  
**WHEN** arrastra un preset de mesa al canvas  
**THEN** la mesa aparece en el grid con número, pax min-max editable  
**AND** se persiste en `canvas_json` al guardar

### REQ-FLOOR-002: Versiones de plano
**GIVEN** una sucursal con floorplans "Normal" y "Año Nuevo"  
**WHEN** el admin activa "Año Nuevo"  
**THEN** solo ese plano cuenta para aforo y visualización hostess  
**AND** el plano "Normal" queda inactivo

### REQ-HOST-001: Listado de reservas
**GIVEN** una hostess autenticada con sucursal asignada  
**WHEN** carga la vista de reservas del día  
**THEN** DataTable muestra columnas: Reserva, Hora, Nombre, RP desde endpoint  
**AND** la vista es 100% responsiva en tablet/iPad

### REQ-HOST-002: Escaneo QR
**GIVEN** una reserva con código QR  
**WHEN** la hostess activa la cámara y escanea el código  
**THEN** se carga automáticamente el detalle de la reserva  
**AND** muestra botón para abrir plano y asignar mesa

### REQ-HOST-003: Tags de reserva
**GIVEN** una reserva seleccionada  
**WHEN** la hostess agrega tags (VIP, Cumpleaños, etc.)  
**THEN** los tags se guardan y visualizan en el listado

### REQ-MESA-001: Estados de mesa
**GIVEN** el plano activo de una sucursal  
**WHEN** una mesa tiene reserva asignada para la fecha actual  
**THEN** se muestra como "reservada" (color distintivo)  
**WHEN** el cliente está sentado  
**THEN** estado "ocupada"  
**OTHERWISE** estado "libre"

### REQ-AFORO-001: Cálculo de aforo
**GIVEN** un floorplan activo  
**WHEN** se consulta aforo de la sucursal  
**THEN** retorna suma de `pax_max` de mesas activas en el plano

### REQ-GER-001: Edición limitada gerente
**GIVEN** un gerente editando una mesa en el floorplan  
**WHEN** modifica pax o posición  
**THEN** el cambio se guarda  
**AND** la reserva asignada a esa mesa no se elimina ni desvincula  
**AND** no puede cambiar el número de mesa

### REQ-SEC-001: Protección XSS/CSRF
**GIVEN** cualquier formulario POST  
**WHEN** falta token CSRF válido  
**THEN** la petición es rechazada  
**AND** toda salida HTML usa escape automático
