<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php use App\Models\TagCategoriaModel; use App\Models\TagModel;

$porDominio = ['reserva' => [], 'cliente' => []];
foreach ($categorias as $cat) {
    if (($cat['estatus'] ?? '') === 'inactivo') {
        continue;
    }
    $dom = ($cat['dominio'] ?? 'reserva') === 'cliente' ? 'cliente' : 'reserva';
    $porDominio[$dom][] = $cat;
}
$titulosDominio = [
    'reserva' => 'Tags de reserva',
    'cliente' => 'Tags de cliente',
];
?>
<div class="container-fluid py-4 tags-admin">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap tags-admin-header">
        <h1 class="h4 mb-0"><?= esc($titulo) ?></h1>
    </div>

    <?php foreach ($titulosDominio as $dominio => $tituloSeccion): ?>
    <div class="mb-2 mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h5 text-muted mb-0"><?= esc($tituloSeccion) ?></h2>
        <button type="button" class="btn btn-secondary btn-sm btn-modal-tag-categoria"
                data-url="<?= base_url('admin/tags/categorias/modal/crear?dominio=' . $dominio) ?>"
                data-title="Nueva categoría de <?= $dominio === 'cliente' ? 'cliente' : 'reserva' ?>">+ Categoría</button>
    </div>
    <?php if ($porDominio[$dominio] === []): ?>
    <div class="text-muted py-3">
        No hay categorías de <?= esc(strtolower($tituloSeccion)) ?>. Crea una categoría con tipo «<?= $dominio === 'cliente' ? 'Tag de cliente' : 'Tag de reserva' ?>».
    </div>
    <?php endif; ?>

    <?php foreach ($porDominio[$dominio] as $cat): ?>
    <section class="tags-category">
        <div class="tags-category-head">
            <div>
                <h2 class="tags-category-name">
                    <button type="button" class="tags-category-edit btn-modal-tag-categoria"
                            data-url="<?= base_url("admin/tags/categorias/{$cat['id']}/modal/editar") ?>"
                            data-title="Editar categoría"
                            title="Editar categoría"><?= esc($cat['nombre']) ?></button>
                </h2>
                <p class="tags-category-meta"><?= esc(TagCategoriaModel::subtitulo($cat)) ?></p>
            </div>
        </div>
        <div class="tags-category-pills">
            <?php foreach ($cat['tags'] as $t): ?>
                <?php if (($t['estatus'] ?? '') !== 'activo') continue; ?>
                <?php
                $bg = $t['color'] ?: '#007AFF';
                $fg = TagModel::colorTexto($bg);
                ?>
                <button type="button"
                        class="tag-pill btn-modal-tag"
                        style="background-color: <?= esc($bg) ?>; color: <?= esc($fg) ?>;"
                        data-url="<?= base_url("admin/tags/{$t['id']}/modal/editar") ?>"
                        data-title="Editar tag"
                        title="Editar tag"><?= esc($t['nombre']) ?></button>
            <?php endforeach; ?>
            <button type="button"
                    class="tag-pill-add btn-modal-tag"
                    data-url="<?= base_url('admin/tags/modal/crear?categoria_id=' . (int) $cat['id']) ?>"
                    data-title="Nuevo tag"
                    title="Agregar tag"
                    aria-label="Agregar tag">+</button>
        </div>
    </section>
    <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="appModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title" id="appModalTitle">Modal</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body" id="appModalBody">
                <div class="text-center py-4 text-muted">Cargando…</div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/tags-modals.js') ?>"></script>
<?= $this->endSection() ?>
