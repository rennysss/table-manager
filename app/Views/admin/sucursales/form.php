<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3 mb-4"><?= esc($titulo) ?></h1>

    <div class="card app-card">
        <div class="card-body">
            <form method="post" action="<?= $sucursal ? base_url("admin/sucursales/{$sucursal['id']}") : base_url('admin/sucursales') ?>">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required
                               value="<?= esc($sucursal['nombre'] ?? old('nombre')) ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Estado</label>
                        <input type="text" name="estado" class="form-control"
                               value="<?= esc($sucursal['estado'] ?? old('estado')) ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad" class="form-control"
                               value="<?= esc($sucursal['ciudad'] ?? old('ciudad')) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Estatus</label>
                        <select name="estatus" class="form-control">
                            <option value="activo" <?= ($sucursal['estatus'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($sucursal['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?= base_url('admin/sucursales') ?>" class="btn btn-link ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
