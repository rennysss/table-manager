<?php /** Modal: floorplans de una sucursal */ ?>
<div class="mb-3">
    <p class="text-muted mb-0 small"><?= esc($sucursal['nombre']) ?></p>
</div>

<div class="card app-card mb-3">
    <div class="card-header py-2">Nuevo FloorPlan</div>
    <div class="card-body">
        <form id="formFloorplanModal" method="post"
              action="<?= base_url("admin/sucursales/{$sucursal['id']}/floorplans") ?>"
              data-ajax-form="floorplan-create"
              data-reload-url="<?= base_url("admin/sucursales/{$sucursal['id']}/modal/floorplans") ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group col-md-4 mb-2">
                    <label class="small mb-1">Nombre</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" required placeholder="Ej. Año Nuevo 2026">
                </div>
                <div class="form-group col-md-3 mb-2">
                    <label class="small mb-1">Tipo</label>
                    <select name="tipo" class="form-control form-control-sm">
                        <option value="normal">Normal</option>
                        <option value="festivo">Festivo</option>
                        <option value="anio_nuevo">Año Nuevo</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                <div class="form-group col-md-3 mb-2">
                    <label class="small mb-1">Ambiente</label>
                    <?= view('partials/select_ambientes', [
                        'ambientesOpciones' => $ambientesOpciones,
                        'class'             => 'form-control form-control-sm',
                    ]) ?>
                </div>
                <div class="form-group col-md-2 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm btn-block">Crear</button>
                </div>
            </div>
            <div class="app-modal-errors alert alert-danger d-none py-2 small" role="alert"></div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Activo</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($floorplans)): ?>
            <tr><td colspan="4" class="text-muted text-center py-3">Sin floorplans registrados.</td></tr>
            <?php else: ?>
            <?php foreach ($floorplans as $fp): ?>
            <tr>
                <td><?= esc($fp['nombre']) ?></td>
                <td><?= esc($fp['tipo']) ?></td>
                <td>
                    <?php if ($fp['activo']): ?>
                    <span class="badge badge-success">Sí</span>
                    <?php else: ?>
                    <span class="badge badge-secondary">No</span>
                    <?php endif; ?>
                </td>
                <td class="text-right text-nowrap">
                    <a href="<?= base_url("admin/floorplan/{$fp['id']}/editor") ?>" class="btn btn-sm btn-secondary">Editor</a>
                    <?php if (! $fp['activo']): ?>
                    <form method="post" action="<?= base_url("admin/floorplan/{$fp['id']}/activar") ?>"
                          class="d-inline form-activar-floorplan"
                          data-reload-url="<?= base_url("admin/sucursales/{$sucursal['id']}/modal/floorplans") ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Activar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p class="text-muted small mt-3 mb-0">El editor de plano abre en pantalla completa para diseñar mesas y estructuras.</p>
