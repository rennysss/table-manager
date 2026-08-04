<?php
/**
 * Select de ambientes cargado desde la base de datos.
 *
 * @var array       $ambientesOpciones
 * @var string|null $name
 * @var string|null $id
 * @var string|null $class
 * @var bool|null   $incluirTodos
 * @var mixed|null  $selected
 */
$name             = $name ?? 'ambiente_id';
$id               = $id ?? 'ambienteId';
$class            = $class ?? 'form-control';
$incluirTodos     = $incluirTodos ?? true;
$ambientesOpciones = $ambientesOpciones ?? [];
?>
<select name="<?= esc($name) ?>" id="<?= esc($id) ?>" class="<?= esc($class) ?>">
    <?php if ($incluirTodos): ?>
    <option value="">— Todos —</option>
    <?php endif; ?>
    <?php foreach ($ambientesOpciones as $a): ?>
    <option value="<?= (int) $a['id'] ?>"
        <?= (string) ($selected ?? '') === (string) $a['id'] ? 'selected' : '' ?>>
        <?= esc($a['descripcion']) ?>
    </option>
    <?php endforeach; ?>
</select>
