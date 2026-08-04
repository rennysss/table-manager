<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <?php if (empty($sucursales)): ?>
    <div class="alert alert-warning">No tienes sucursales asignadas.</div>
    <?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><?= esc($titulo) ?></h1>
        <button type="button" class="btn btn-primary app-btn-icon btn-modal-floorplan"
                data-url="<?= base_url('gerente/floorplan/modal/crear') ?>"
                data-title="Nuevo FloorPlan" title="Nuevo FloorPlan" aria-label="Nuevo FloorPlan">+</button>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
            <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaFloorplans">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Ambiente</th>
                        <th>Estatus</th>
                        <th>Aforo</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($floorplans as $fp): ?>
                    <tr>
                        <td><?= esc($fp['sucursal_nombre']) ?></td>
                        <td><?= esc($fp['nombre']) ?></td>
                        <td><?= esc($fp['tipo_label']) ?></td>
                        <td><?= esc($fp['ambiente_nombre'] ?? '—') ?></td>
                        <td>
                            <span class="badge badge-<?= $fp['activo'] ? 'success' : 'secondary' ?>">
                                <?= $fp['activo'] ? 'activo' : 'inactivo' ?>
                            </span>
                        </td>
                        <td><?= (int) ($fp['aforo'] ?? 0) ?> pax</td>
                        <td class="text-right text-nowrap">
                            <div class="dropdown app-table-dropdown">
                                <button type="button" class="btn btn-sm btn-secondary app-btn-kebab"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        title="Acciones" aria-label="Acciones">…</button>
                                <div class="dropdown-menu dropdown-menu-right app-dropdown-menu">
                                    <a class="dropdown-item"
                                       href="<?= base_url("gerente/floorplan/{$fp['id']}/editor") ?>"
                                       target="_blank"
                                       rel="noopener noreferrer">
                                        Diseñar
                                    </a>
                                    <?php if (! $fp['activo']): ?>
                                    <form method="post"
                                          action="<?= base_url("gerente/floorplan/{$fp['id']}/activar") ?>"
                                          class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dropdown-item">Activar</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (! empty($sucursales)): ?>
<div class="modal fade" id="appModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title" id="appModalTitle">Modal</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body" id="appModalBody">
                <div class="text-center py-4 text-muted">Cargando…</div>
            </div>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('abrir_modal_crear')): ?>
<div id="modalCrearPrecargado" class="d-none">
    <?= view('gerente/floorplan/_modal_crear', [
        'sucursales'        => $sucursales,
        'ambientesOpciones' => $ambientesOpciones,
        'tiposLabel'        => $tiposLabel,
    ]) ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (! empty($sucursales)): ?>
<script>
    window.FLOORPLAN_GERENTE = {
        abrirModalCrear: <?= session()->getFlashdata('abrir_modal_crear') ? 'true' : 'false' ?>,
        modalCrearUrl: '<?= base_url('gerente/floorplan/modal/crear') ?>'
    };
</script>
<script src="<?= base_url('assets/js/floorplan-gerente.js') ?>"></script>
<?php endif; ?>
<?= $this->endSection() ?>
