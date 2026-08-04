<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent px-0">
            <li class="breadcrumb-item"><a href="<?= base_url('gerente') ?>">Panel</a></li>
            <li class="breadcrumb-item active">Mesas — <?= esc($ambiente['descripcion']) ?></li>
        </ol>
    </nav>

    <div class="alert alert-info small">
        Como gerente puedes modificar <strong>pax, mínimo y máximo</strong>. El <strong>número de mesa</strong> no se puede cambiar.
        Las reservas asignadas no se ven afectadas.
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Pax</th>
                        <th>Mín – Máx</th>
                        <th>Estatus</th>
                        <th class="text-right">Editar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mesas as $m): ?>
                    <tr>
                        <td><strong><?= esc($m['numero']) ?></strong></td>
                        <td><?= (int) $m['pax'] ?></td>
                        <td><?= (int) $m['minimo'] ?> – <?= (int) $m['maximo'] ?></td>
                        <td><?= esc($m['estatus']) ?></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-toggle="modal" data-target="#modalMesa<?= $m['id'] ?>">
                                Modificar pax
                            </button>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalMesa<?= $m['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="<?= base_url("gerente/mesas/{$m['id']}/actualizar") ?>">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h5 class="modal-title">Mesa <?= esc($m['numero']) ?></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Número de mesa</label>
                                            <input type="text" class="form-control" value="<?= esc($m['numero']) ?>" disabled>
                                            <small class="text-muted">No editable por gerente</small>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-4">
                                                <label>Pax</label>
                                                <input type="number" name="pax" class="form-control"
                                                       value="<?= (int) $m['pax'] ?>" min="1" required>
                                            </div>
                                            <div class="form-group col-4">
                                                <label>Mínimo</label>
                                                <input type="number" name="minimo" class="form-control"
                                                       value="<?= (int) $m['minimo'] ?>" min="1" required>
                                            </div>
                                            <div class="form-group col-4">
                                                <label>Máximo</label>
                                                <input type="number" name="maximo" class="form-control"
                                                       value="<?= (int) $m['maximo'] ?>" min="1" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Estatus</label>
                                            <select name="estatus" class="form-control">
                                                <option value="activo" <?= $m['estatus'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                                <option value="inactivo" <?= $m['estatus'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
