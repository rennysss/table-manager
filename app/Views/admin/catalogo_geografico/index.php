<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><?= esc($titulo) ?></h1>
        <button type="button" class="btn btn-primary app-btn-icon btn-modal-estado"
                data-url="<?= base_url('admin/catalogo-geografico/modal/crear') ?>"
                data-title="Nuevo estado" title="Nuevo estado" aria-label="Nuevo estado">+</button>
    </div>

    <p class="text-muted mb-4">Administra los estados o regiones disponibles por país. Los cambios se reflejan en el formulario de sucursales.</p>

    <div class="card app-card mb-4">
        <div class="card-header">Países</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paises as $p): ?>
                    <tr>
                        <td><?= esc($p['codigo']) ?></td>
                        <td><?= esc($p['nombre']) ?></td>
                        <td><span class="badge badge-<?= $p['estatus'] === 'activo' ? 'success' : 'secondary' ?>"><?= esc($p['estatus']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card app-card">
        <div class="card-header">Estados / regiones</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>País</th>
                        <th>Nombre</th>
                        <th>Estatus</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estados as $e): ?>
                    <tr>
                        <td><?= esc($e['pais_nombre']) ?></td>
                        <td><?= esc($e['nombre']) ?></td>
                        <td><span class="badge badge-<?= $e['estatus'] === 'activo' ? 'success' : 'secondary' ?>"><?= esc($e['estatus']) ?></span></td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-secondary btn-modal-estado"
                                    data-url="<?= base_url("admin/catalogo-geografico/{$e['id']}/modal/editar") ?>"
                                    data-title="Editar estado">Editar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
<script src="<?= base_url('assets/js/catalogo-geografico-modals.js') ?>"></script>
<?= $this->endSection() ?>
