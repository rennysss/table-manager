<?php
$estado = $estado ?? null;
$paises = $paises ?? [];
$esEdicion = $estado !== null;
$action = $esEdicion
    ? base_url("admin/catalogo-geografico/{$estado['id']}")
    : base_url('admin/catalogo-geografico');
?>
<form id="formEstadoModal" method="post" action="<?= $action ?>" data-ajax-form="estado">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>País *</label>
            <select name="pais_id" class="form-control" required>
                <option value="">— Seleccionar —</option>
                <?php foreach ($paises as $p): ?>
                <option value="<?= (int) $p['id'] ?>"
                    <?= (string) ($estado['pais_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
                    <?= esc($p['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-6">
            <label>Nombre *</label>
            <input type="text" name="nombre" class="form-control" required
                   value="<?= esc($estado['nombre'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Estatus</label>
            <select name="estatus" class="form-control">
                <option value="activo" <?= ($estado['estatus'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($estado['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="app-modal-errors alert alert-danger d-none mb-0" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
