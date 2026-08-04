<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><?= esc($titulo) ?></h1>
        <button type="button" class="btn btn-primary app-btn-icon btn-modal-ambiente"
                data-url="<?= base_url('admin/ambientes/modal/crear') ?>"
                data-title="Nuevo ambiente" title="Nuevo ambiente" aria-label="Nuevo ambiente">+</button>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Estatus</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ambientes)): ?>
                    <tr>
                        <td colspan="3" class="text-muted text-center py-4">Sin ambientes registrados.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($ambientes as $a): ?>
                    <tr>
                        <td><?= esc($a['descripcion']) ?></td>
                        <td>
                            <span class="badge badge-<?= $a['estatus'] === 'activo' ? 'success' : 'secondary' ?>">
                                <?= esc($a['estatus']) ?>
                            </span>
                        </td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-secondary btn-modal-ambiente"
                                    data-url="<?= base_url("admin/ambientes/{$a['id']}/modal/editar") ?>"
                                    data-title="Editar ambiente">Editar</button>
                            <a href="<?= base_url('admin/mesas') ?>"
                               class="btn btn-sm btn-outline-secondary">Mesas</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="appModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
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
<script src="<?= base_url('assets/js/ambientes-modals.js') ?>"></script>
<?= $this->endSection() ?>
