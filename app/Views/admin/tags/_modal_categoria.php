<?php
$categoria = $categoria ?? null;
$esEdicion = $categoria !== null;
$action = $esEdicion
    ? base_url("admin/tags/categorias/{$categoria['id']}")
    : base_url('admin/tags/categorias');
?>
<form id="formTagCategoriaModal" method="post" action="<?= $action ?>" data-ajax-form="tag-categoria">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group col-md-8">
            <label for="catNombre">Nombre *</label>
            <input type="text" id="catNombre" name="nombre" class="form-control" required maxlength="80"
                   value="<?= esc($categoria['nombre'] ?? '') ?>">
        </div>
        <div class="form-group col-md-4">
            <label for="catAlcance">Alcance</label>
            <select id="catAlcance" name="alcance" class="form-control">
                <option value="global" <?= ($categoria['alcance'] ?? 'global') === 'global' ? 'selected' : '' ?>>Global</option>
                <option value="local" <?= ($categoria['alcance'] ?? '') === 'local' ? 'selected' : '' ?>>Local</option>
            </select>
        </div>
        <div class="form-group col-md-4">
            <label for="catDominio">Tipo de tag</label>
            <select id="catDominio" name="dominio" class="form-control">
                <option value="reserva" <?= ($categoria['dominio'] ?? 'reserva') === 'reserva' ? 'selected' : '' ?>>Tag de reserva</option>
                <option value="cliente" <?= ($categoria['dominio'] ?? '') === 'cliente' ? 'selected' : '' ?>>Tag de cliente</option>
            </select>
        </div>
        <div class="form-group col-md-6">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="catReserva" name="mostrar_en_reserva" value="1"
                    <?= ! isset($categoria) || ! empty($categoria['mostrar_en_reserva']) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="catReserva">Mostrar en reserva</label>
            </div>
        </div>
        <div class="form-group col-md-6">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="catChit" name="mostrar_en_chit" value="1"
                    <?= ! isset($categoria) || ! empty($categoria['mostrar_en_chit']) ? 'checked' : '' ?>>
                <label class="custom-control-label" for="catChit">Mostrar en comanda</label>
            </div>
        </div>
        <div class="form-group col-md-6">
            <label for="catEstatus">Estatus</label>
            <select id="catEstatus" name="estatus" class="form-control">
                <option value="activo" <?= ($categoria['estatus'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($categoria['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="app-modal-errors alert alert-danger d-none mb-0" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
