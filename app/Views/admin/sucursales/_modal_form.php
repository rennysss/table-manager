<?php
$sucursal = $sucursal ?? null;
$marcas   = $marcas ?? [];
$paises   = $paises ?? [];
$estados  = $estados ?? [];
$esEdicion = $sucursal !== null;
$action = $esEdicion
    ? base_url("admin/sucursales/{$sucursal['id']}")
    : base_url('admin/sucursales');
$paisSeleccionado = $sucursal['pais_id'] ?? '';
$estadoSeleccionado = $sucursal['estado_region_id'] ?? '';
?>
<form id="formSucursalModal" method="post" action="<?= $action ?>" data-ajax-form="sucursal">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Marca *</label>
            <select name="marca_id" class="form-control" required>
                <option value="">— Seleccionar —</option>
                <?php foreach ($marcas as $m): ?>
                <option value="<?= (int) $m['id'] ?>"
                    <?= (string) ($sucursal['marca_id'] ?? '') === (string) $m['id'] ? 'selected' : '' ?>>
                    <?= esc($m['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-6">
            <label>Nombre *</label>
            <input type="text" name="nombre" class="form-control" required
                   value="<?= esc($sucursal['nombre'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>País *</label>
            <select name="pais_id" id="sucursalPais" class="form-control" required
                    data-estados-url="<?= base_url('admin/catalogo-geografico/paises') ?>">
                <option value="">— Seleccionar —</option>
                <?php foreach ($paises as $p): ?>
                <option value="<?= (int) $p['id'] ?>"
                    <?= (string) $paisSeleccionado === (string) $p['id'] ? 'selected' : '' ?>>
                    <?= esc($p['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-6">
            <label>Estado *</label>
            <select name="estado_region_id" id="sucursalEstado" class="form-control" required
                    data-selected="<?= esc((string) $estadoSeleccionado) ?>">
                <option value="">— Seleccionar país primero —</option>
                <?php foreach ($estados as $e): ?>
                <option value="<?= (int) $e['id'] ?>"
                    <?= (string) $estadoSeleccionado === (string) $e['id'] ? 'selected' : '' ?>>
                    <?= esc($e['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Ciudad</label>
            <input type="text" name="ciudad" class="form-control"
                   value="<?= esc($sucursal['ciudad'] ?? '') ?>">
        </div>
        <div class="form-group col-md-6">
            <label>Estatus</label>
            <select name="estatus" class="form-control">
                <option value="activo" <?= ($sucursal['estatus'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($sucursal['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Venue ID (One Reservations)</label>
            <input type="text" name="venue_id" class="form-control"
                   value="<?= esc($sucursal['venue_id'] ?? '') ?>"
                   placeholder="ID del venue en One Reservations"
                   autocomplete="off">
            <small class="form-text text-muted">Para consultar reservas desde One Reservations.</small>
        </div>
        <div class="form-group col-md-6">
            <label>Xetux ID</label>
            <input type="text" name="xetux_id" class="form-control"
                   value="<?= esc($sucursal['xetux_id'] ?? '') ?>"
                   placeholder="Identificador interno (opcional)"
                   autocomplete="off">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>URL base Xetux</label>
            <input type="url" name="xetux_base_url" class="form-control"
                   value="<?= esc($sucursal['xetux_base_url'] ?? '') ?>"
                   placeholder="https://servidor-xetux.example.com"
                   autocomplete="off">
        </div>
        <div class="form-group col-md-3">
            <label>Station code</label>
            <input type="text" name="xetux_station_code" class="form-control"
                   value="<?= esc($sucursal['xetux_station_code'] ?? 'RES01') ?>"
                   placeholder="RES01"
                   autocomplete="off">
        </div>
        <div class="form-group col-md-3">
            <label>Payform ID (prepago)</label>
            <input type="number" name="xetux_payform_id" class="form-control" min="0" step="1"
                   value="<?= esc($sucursal['xetux_payform_id'] ?? '') ?>"
                   placeholder="ID forma de pago">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label>API Key Xetux</label>
            <input type="password" name="xetux_api_key" class="form-control"
                   value=""
                   placeholder="<?= $esEdicion && ! empty($sucursal['xetux_api_key']) ? '••••••••••••••••' : 'Clave Authorization para Xetux' ?>"
                   autocomplete="new-password">
            <small class="form-text text-muted">
                Se envía en el header <code>Authorization</code>.
                <?php if ($esEdicion && ! empty($sucursal['xetux_api_key'])): ?>
                <span class="text-success">Clave configurada.</span> Deja el campo vacío para conservarla.
                <?php endif; ?>
            </small>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label>API Key (One Reservations)</label>
            <input type="password" name="reservas_api_key" class="form-control"
                   value=""
                   placeholder="<?= $esEdicion && ! empty($sucursal['reservas_api_key']) ? '••••••••••••••••' : 'Clave X-API-Key del API de reservas' ?>"
                   autocomplete="new-password">
            <small class="form-text text-muted">
                Se guarda en la base de datos y se usa solo en el servidor.
                <?php if ($esEdicion && ! empty($sucursal['reservas_api_key'])): ?>
                <span class="text-success">Clave configurada.</span> Deja el campo vacío para conservarla.
                <?php else: ?>
                Requerida junto con el Venue ID para consultar reservas reales.
                <?php endif; ?>
            </small>
        </div>
    </div>
    <div class="app-modal-errors alert alert-danger d-none mb-0" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
