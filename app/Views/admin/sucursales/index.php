<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><?= esc($titulo) ?></h1>
        <button type="button" class="btn btn-primary app-btn-icon btn-modal-sucursal"
                data-url="<?= base_url('admin/sucursales/modal/crear') ?>"
                data-title="Nueva sucursal" title="Nueva sucursal" aria-label="Nueva sucursal">+</button>
    </div>

    <div class="card app-card app-card--menu-visible">
        <div class="table-responsive app-table-overflow-visible">
            <table class="table table-hover mb-0" id="tablaSucursales">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>País</th>
                        <th>Estado</th>
                        <th>Ciudad</th>
                        <th>Estatus</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sucursales as $s): ?>
                    <tr>
                        <td><?= esc($s['nombre']) ?></td>
                        <td><?= esc($s['marca_nombre'] ?? '—') ?></td>
                        <td><?= esc($s['pais_nombre'] ?? '—') ?></td>
                        <td><?= esc($s['estado_region_nombre'] ?? $s['estado'] ?? '—') ?></td>
                        <td><?= esc($s['ciudad']) ?></td>
                        <td><span class="badge badge-<?= $s['estatus'] === 'activo' ? 'success' : 'secondary' ?>"><?= esc($s['estatus']) ?></span></td>
                        <td class="text-right text-nowrap">
                            <div class="dropdown app-table-dropdown">
                                <button type="button" class="btn btn-sm btn-secondary app-btn-kebab"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        title="Acciones" aria-label="Acciones">…</button>
                                <div class="dropdown-menu dropdown-menu-right app-dropdown-menu">
                                    <button type="button" class="dropdown-item btn-modal-sucursal-action"
                                            data-url="<?= base_url("admin/sucursales/{$s['id']}/modal/editar") ?>"
                                            data-title="Editar sucursal">Editar</button>
                                    <a class="dropdown-item" href="<?= base_url("admin/sucursales/{$s['id']}/mesas") ?>">Mesas</a>
                                    <a class="dropdown-item" href="<?= base_url("admin/sucursales/{$s['id']}/ambientes") ?>">Ambientes</a>
                                    <a class="dropdown-item" href="<?= base_url("admin/sucursales/{$s['id']}/floorplans") ?>">FloorPlan</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/sucursales-modals.js') ?>"></script>
<?= $this->endSection() ?>
