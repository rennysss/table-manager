<?php
$ambiente = $ambiente ?? null;
$esEdicion = $ambiente !== null;
$action = $esEdicion
    ? base_url("admin/ambientes/{$ambiente['id']}")
    : base_url('admin/ambientes');
?>
<form id="formAmbienteModal" method="post" action="<?= $action ?>" data-ajax-form="ambiente">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group col-md-8">
            <label>Descripción *</label>
            <input type="text" name="descripcion" class="form-control" required
                   value="<?= esc($ambiente['descripcion'] ?? old('descripcion') ?? '') ?>"
                   placeholder="Ej. Nightclub, Restaurante, Terraza">
        </div>
        <div class="form-group col-md-4">
            <label>Estatus</label>
            <select name="estatus" class="form-control">
                <option value="activo" <?= ($ambiente['estatus'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($ambiente['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="app-modal-errors alert alert-danger d-none mb-0" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
