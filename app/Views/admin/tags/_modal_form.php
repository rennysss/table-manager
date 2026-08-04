<?php
$tag = $tag ?? null;
$categorias = $categorias ?? [];
$categoriaId = (int) ($categoriaId ?? ($tag['categoria_id'] ?? 0));
$esEdicion = $tag !== null;
$action = $esEdicion
    ? base_url("admin/tags/{$tag['id']}")
    : base_url('admin/tags');
$color = $tag['color'] ?? '#64D2FF';
?>
<form id="formTagModal" method="post" action="<?= $action ?>" data-ajax-form="tag">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label for="tagCategoria">Categoría *</label>
            <select id="tagCategoria" name="categoria_id" class="form-control" required>
                <option value="">Selecciona…</option>
                <?php foreach ($categorias as $c): ?>
                    <?php if (($c['estatus'] ?? '') === 'inactivo' && (int) $c['id'] !== $categoriaId) continue; ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $categoriaId ? 'selected' : '' ?>>
                    <?= esc($c['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-6">
            <label for="tagNombre">Nombre *</label>
            <input type="text" id="tagNombre" name="nombre" class="form-control" required maxlength="50"
                   value="<?= esc($tag['nombre'] ?? '') ?>">
        </div>
        <div class="form-group col-md-3">
            <label for="tagColor">Color *</label>
            <input type="color" id="tagColor" name="color" class="form-control form-control-color"
                   value="<?= esc($color) ?>" required title="Color del tag">
        </div>
        <div class="form-group col-md-3">
            <label for="tagEstatus">Estatus</label>
            <select id="tagEstatus" name="estatus" class="form-control">
                <option value="activo" <?= ($tag['estatus'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($tag['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="app-modal-errors alert alert-danger d-none mb-0" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
