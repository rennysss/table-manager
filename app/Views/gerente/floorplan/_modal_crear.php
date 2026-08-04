<?php /** Modal: crear floorplan y abrir editor */ ?>
<p class="text-muted small mb-3">
    Al crear el plano se abrirá el editor en una nueva pestaña para diseñar mesas y elementos estructurales.
</p>

<form method="post" action="<?= base_url('gerente/floorplan/crear') ?>" id="formCrearFloorplan">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="fpSucursalId">Sucursal</label>
        <select name="sucursal_id" id="fpSucursalId" class="form-control" required>
            <?php if (count($sucursales) > 1): ?>
            <option value="">— Selecciona —</option>
            <?php endif; ?>
            <?php foreach ($sucursales as $s): ?>
            <option value="<?= (int) $s['id'] ?>"
                <?= (string) old('sucursal_id') === (string) $s['id'] || count($sucursales) === 1 ? 'selected' : '' ?>>
                <?= esc($s['nombre']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="fpNombre">Nombre</label>
        <input type="text" name="nombre" id="fpNombre" class="form-control" required
               value="<?= esc(old('nombre') ?? '') ?>"
               placeholder="Ej. Año Nuevo 2026">
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="fpTipo">Tipo</label>
            <select name="tipo" id="fpTipo" class="form-control">
                <?php foreach ($tiposLabel as $valor => $etiqueta): ?>
                <option value="<?= esc($valor) ?>" <?= old('tipo', 'normal') === $valor ? 'selected' : '' ?>>
                    <?= esc($etiqueta) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-6">
            <label for="fpAmbienteId">Ambiente</label>
            <?= view('partials/select_ambientes', [
                'ambientesOpciones' => $ambientesOpciones,
                'id'                => 'fpAmbienteId',
            ]) ?>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear y diseñar</button>
    </div>
</form>
