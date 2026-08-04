<?php
$usuario = $usuario ?? null;
$paises  = $paises ?? [];

$rolesEtiqueta = ['admin' => 'Administrador', 'gerente' => 'Gerente', 'hostess' => 'Hostess'];
$asignadas     = $usuario['sucursales'] ?? [];
?>
<p class="text-muted mb-3 small">
    Usuario: <strong><?= esc($usuario['nombre'] ?? '') ?></strong>
    (<?= esc($usuario['usuario'] ?? '') ?>)
    · Rol: <?= esc($rolesEtiqueta[$usuario['rol'] ?? ''] ?? $usuario['rol'] ?? '—') ?>
</p>

<form id="formUsuarioSucursalesModal" method="post"
      action="<?= base_url("admin/usuarios/{$usuario['id']}/sucursales") ?>"
      data-ajax-form="usuario-sucursales"
      data-ciudades-url="<?= base_url('admin/usuarios/api/paises') ?>"
      data-sucursales-url="<?= base_url('admin/usuarios/api/sucursales') ?>">
    <?= csrf_field() ?>

    <div class="form-row align-items-end mb-3">
        <div class="form-group col-md-4 mb-md-0">
            <label for="accesoPais">País</label>
            <select id="accesoPais" class="form-control">
                <option value="">— Seleccionar —</option>
                <?php foreach ($paises as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= esc($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-4 mb-md-0">
            <label for="accesoCiudad">Ciudad</label>
            <select id="accesoCiudad" class="form-control" disabled>
                <option value="">— Seleccionar país —</option>
            </select>
        </div>
        <div class="form-group col-md-4 mb-md-0">
            <label for="accesoSucursal">Sucursal</label>
            <select id="accesoSucursal" class="form-control" disabled>
                <option value="">— Seleccionar ciudad —</option>
            </select>
        </div>
    </div>
    <div class="d-flex justify-content-end mb-4">
        <button type="button" class="btn btn-secondary" id="btnAgregarAccesoSucursal" disabled>
            Agregar
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="tablaAccesosSucursales">
            <thead>
                <tr>
                    <th>País</th>
                    <th>Ciudad</th>
                    <th>Sucursal</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asignadas as $s): ?>
                <tr data-sucursal-id="<?= (int) $s['id'] ?>">
                    <td><?= esc($s['pais_nombre'] ?? '—') ?></td>
                    <td><?= esc($s['ciudad'] ?? '—') ?></td>
                    <td><?= esc($s['nombre']) ?></td>
                    <td class="text-right text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-baja-acceso-sucursal app-btn-icon-only"
                                title="Dar de baja" aria-label="Dar de baja sucursal">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p id="accesosSucursalesVacio" class="text-muted small mb-0 mt-2<?= empty($asignadas) ? '' : ' d-none' ?>">
        Sin sucursales asignadas. Usa los selectores de arriba para agregar accesos.
    </p>

    <div id="accesosSucursalesHidden"></div>

    <div class="app-modal-errors alert alert-danger d-none mb-0 mt-3" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar accesos</button>
    </div>
</form>
