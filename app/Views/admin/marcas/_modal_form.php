<?php
$marca = $marca ?? null;
$esEdicion = $marca !== null;
$action = $esEdicion
    ? base_url("admin/marcas/{$marca['id']}")
    : base_url('admin/marcas');
?>
<form id="formMarcaModal" method="post" action="<?= $action ?>" data-ajax-form="marca">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Nombre *</label>
            <input type="text" name="nombre" class="form-control" required
                   value="<?= esc($marca['nombre'] ?? '') ?>">
        </div>
        <div class="form-group col-md-6">
            <label>Estatus</label>
            <select name="estatus" class="form-control">
                <option value="activo" <?= ($marca['estatus'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($marca['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="app-modal-errors alert alert-danger d-none mb-0" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
