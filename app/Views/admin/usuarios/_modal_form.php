<?php
$usuario    = $usuario ?? null;
$sucursales = $sucursales ?? [];
$esEdicion  = $usuario !== null;
$action = $esEdicion
    ? base_url("admin/usuarios/{$usuario['id']}")
    : base_url('admin/usuarios');

$roles = [
    'admin'   => 'Administrador',
    'gerente' => 'Gerente',
    'hostess' => 'Hostess',
];
?>
<form id="formUsuarioModal" method="post" action="<?= $action ?>" data-ajax-form="usuario">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Usuario *</label>
            <input type="text" name="usuario" class="form-control" required minlength="3" maxlength="80"
                   value="<?= esc($usuario['usuario'] ?? '') ?>"
                   <?= $esEdicion ? 'readonly' : '' ?>
                   autocomplete="off">
            <?php if ($esEdicion): ?>
            <small class="form-text text-muted">El usuario no se puede modificar.</small>
            <?php endif; ?>
        </div>
        <div class="form-group col-md-6">
            <label>Nombre *</label>
            <input type="text" name="nombre" class="form-control" required minlength="2" maxlength="150"
                   value="<?= esc($usuario['nombre'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Email</label>
            <input type="email" name="email" class="form-control" maxlength="150"
                   value="<?= esc($usuario['email'] ?? '') ?>">
        </div>
        <div class="form-group col-md-6">
            <label>Contraseña<?= $esEdicion ? '' : ' *' ?></label>
            <input type="password" name="password" class="form-control"
                   <?= $esEdicion ? '' : 'required' ?> minlength="8" autocomplete="new-password"
                   placeholder="<?= $esEdicion ? 'Dejar vacío para no cambiar' : '' ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label>Rol *</label>
            <select name="rol" class="form-control" required>
                <?php foreach ($roles as $valor => $etiqueta): ?>
                <option value="<?= esc($valor) ?>"
                    <?= ($usuario['rol'] ?? 'hostess') === $valor ? 'selected' : '' ?>>
                    <?= esc($etiqueta) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-6">
            <label>Estatus *</label>
            <select name="estatus" class="form-control" required>
                <option value="activo" <?= ($usuario['estatus'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($usuario['estatus'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
    </div>
    <div class="app-modal-errors alert alert-danger d-none mb-0" role="alert"></div>
    <div class="app-modal-form-footer">
        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
