<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><?= esc($titulo) ?></h1>
        <button type="button" class="btn btn-primary app-btn-icon btn-modal-usuario"
                data-url="<?= base_url('admin/usuarios/modal/crear') ?>"
                data-title="Nuevo usuario" title="Nuevo usuario" aria-label="Nuevo usuario">+</button>
    </div>

    <div class="card app-card app-card--menu-visible">
        <div class="table-responsive app-table-overflow-visible">
            <table class="table table-hover mb-0" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Estatus</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rolesEtiqueta = ['admin' => 'Administrador', 'gerente' => 'Gerente', 'hostess' => 'Hostess'];
                    foreach ($usuarios as $u):
                    ?>
                    <tr>
                        <td><?= esc($u['usuario']) ?></td>
                        <td><?= esc($u['nombre']) ?></td>
                        <td><?= esc($rolesEtiqueta[$u['rol']] ?? $u['rol']) ?></td>
                        <td>
                            <span class="badge badge-<?= $u['estatus'] === 'activo' ? 'success' : 'secondary' ?>">
                                <?= esc($u['estatus']) ?>
                            </span>
                        </td>
                        <td class="text-right text-nowrap">
                            <div class="dropdown app-table-dropdown">
                                <button type="button" class="btn btn-sm btn-secondary app-btn-kebab"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        title="Acciones" aria-label="Acciones">…</button>
                                <div class="dropdown-menu dropdown-menu-right app-dropdown-menu">
                                    <button type="button" class="dropdown-item btn-modal-usuario-action"
                                            data-url="<?= base_url("admin/usuarios/{$u['id']}/modal/editar") ?>"
                                            data-title="Editar usuario">Editar</button>
                                    <button type="button" class="dropdown-item btn-modal-usuario-sucursales"
                                            data-url="<?= base_url("admin/usuarios/{$u['id']}/modal/sucursales") ?>"
                                            data-title="Accesos a sucursales">Accesos a sucursales</button>
                                    <?php if ((int) $u['id'] !== (int) session()->get('usuario_id')): ?>
                                    <button type="button" class="dropdown-item text-danger btn-eliminar-usuario"
                                            data-url="<?= base_url("admin/usuarios/{$u['id']}/eliminar") ?>"
                                            data-nombre="<?= esc($u['nombre']) ?>">Eliminar</button>
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
<script src="<?= base_url('assets/js/usuarios-accesos.js') ?>"></script>
<script src="<?= base_url('assets/js/usuarios-modals.js') ?>"></script>
<?= $this->endSection() ?>
