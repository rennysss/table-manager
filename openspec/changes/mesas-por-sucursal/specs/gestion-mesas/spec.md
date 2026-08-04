# Spec delta: gestión de mesas por sucursal

## MODIFICADO: Mesas

Las mesas pertenecen a una **sucursal** y a un **ambiente**.

### Scenario: inventario por sucursal
- GIVEN un admin en `admin/sucursales/{id}/mesas`
- WHEN agrega, edita, duplica o elimina mesas y guarda
- THEN solo se afectan las mesas de esa sucursal
- AND el número de mesa no se duplica al duplicar una fila
- AND el número es único dentro del mismo ambiente de la sucursal

## MODIFICADO: Ambientes

Un ambiente puede ser global (`sucursal_id` NULL) o propio de una sucursal.

### Scenario: ambientes disponibles en el editor
- GIVEN una sucursal
- WHEN se listan los ambientes para asignar a una mesa
- THEN se muestran los globales más los propios de esa sucursal
- AND se puede crear un ambiente propio de la sucursal desde el editor

## MODIFICADO: FloorPlan

### Scenario: rotación con pivote al centro
- GIVEN una mesa o estructura en el editor
- WHEN se rota
- THEN gira sobre su centro y se guarda su posición y ángulo

## MODIFICADO: Plano de hostess

### Scenario: estados visuales
- GIVEN el plano activo de una sucursal en una fecha
- WHEN la hostess lo visualiza
- THEN las mesas ocupadas se ven en gris y las disponibles en verde grisáceo
- AND puede seleccionar una mesa para asignarla a una reserva
