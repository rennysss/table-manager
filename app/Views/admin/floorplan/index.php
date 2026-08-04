<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb app-breadcrumb bg-transparent px-0">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/sucursales') ?>">Sucursales</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= esc($sucursal['nombre']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap">
        <div class="mb-2 mr-3">
            <h1 class="h4 mb-1">Floorplan Layouts</h1>
            <p class="text-muted mb-0 fp-layout-intro">
                Representación visual del plano de la sucursal &mdash; áreas de comedor, bar y demás
                zonas clave para optimizar el espacio, la experiencia del cliente y la eficiencia del equipo.
            </p>
        </div>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNuevoFloorplan">
            Agregar FloorPlan
        </button>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
            <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0 text-uppercase text-muted">Tus FloorPlans (<?= count($floorplans) ?>)</h2>
    </div>

    <?php if (empty($floorplans)): ?>
    <div class="card app-card">
        <div class="card-body text-center text-muted py-5">
            Aún no hay floorplans. Usa <strong>Agregar FloorPlan</strong> para crear el primero.
        </div>
    </div>
    <?php else: ?>
    <div class="fp-layout-grid">
        <?php foreach ($floorplans as $fp): ?>
        <div class="card app-card fp-layout-card">
            <div class="fp-layout-card-head">
                <div>
                    <span class="fp-layout-eyebrow">Nombre del layout</span>
                    <h3 class="fp-layout-name"><?= esc($fp['nombre']) ?></h3>
                    <p class="fp-layout-edited">
                        Última edición:
                        <?= esc(date('d/m/Y', strtotime($fp['updated_at'] ?? $fp['created_at'] ?? 'now'))) ?>
                    </p>
                </div>
                <div class="dropdown app-table-dropdown">
                    <button type="button" class="btn btn-sm app-btn-kebab fp-layout-kebab"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                            title="Acciones" aria-label="Acciones">&#8942;</button>
                    <div class="dropdown-menu dropdown-menu-right app-dropdown-menu">
                        <a class="dropdown-item" href="<?= base_url("admin/floorplan/{$fp['id']}/editor") ?>">Editar</a>
                        <form method="post" action="<?= base_url("admin/floorplan/{$fp['id']}/duplicar") ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item">Duplicar</button>
                        </form>
                        <a class="dropdown-item" target="_blank" rel="noopener noreferrer"
                           href="<?= base_url("admin/floorplan/{$fp['id']}/editor?print=1") ?>">Imprimir</a>
                    </div>
                </div>
            </div>

            <div class="fp-layout-card-body">
                <span class="fp-layout-eyebrow">Detalles del layout</span>
                <ul class="fp-layout-stats">
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12l2.5 2.5L16 9"/></svg>
                        Mesas activas <strong><?= (int) $fp['mesas_activas'] ?></strong>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
                        Mesas inactivas <strong><?= (int) $fp['mesas_inactivas'] ?></strong>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12a3 3 0 0 1 3-3h2a3 3 0 0 1 0 6h-1"/><path d="M15 12a3 3 0 0 1-3 3h-2a3 3 0 0 1 0-6h1"/></svg>
                        Combinaciones <strong><?= (int) $fp['combinaciones'] ?></strong>
                    </li>
                </ul>
            </div>

            <div class="fp-layout-card-foot">
                <span class="fp-layout-tag"><?= esc($fp['ambiente_nombre'] ?? $fp['tipo_label']) ?></span>
                <span class="badge badge-<?= $fp['activo'] ? 'success' : 'secondary' ?>">
                    <?= $fp['activo'] ? 'Activo' : 'Inactivo' ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal: nuevo floorplan -->
<div class="modal fade" id="modalNuevoFloorplan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title">Nuevo FloorPlan</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="<?= base_url("admin/sucursales/{$sucursal['id']}/floorplans") ?>"
                  id="formNuevoFloorplan">
                <?= csrf_field() ?>
                <div class="modal-body app-modal-body">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej. Año Nuevo 2026">
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="normal">Normal</option>
                            <option value="festivo">Festivo</option>
                            <option value="anio_nuevo">Año Nuevo</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Ambientes</label>
                        <div class="fp-amb-picker">
                            <select id="fpAmbienteSelect" class="form-control">
                                <option value="">— Selecciona un ambiente —</option>
                                <?php foreach ($ambientesOpciones as $a): ?>
                                <option value="<?= (int) $a['id'] ?>"><?= esc($a['descripcion']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="fpAmbienteAdd">Agregar</button>
                        </div>
                        <div id="fpAmbienteChips" class="fp-amb-chips" aria-live="polite"></div>
                        <small class="text-muted d-block mt-1">Puedes agregar uno o varios ambientes a este floorplan.</small>
                        <div class="invalid-feedback d-block" id="fpAmbienteError" hidden>Agrega al menos un ambiente.</div>
                    </div>

                    <div class="app-modal-form-footer">
                        <button type="button" class="btn btn-link text-muted mr-2" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear y editar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var form = document.getElementById('formNuevoFloorplan');
    if (!form) return;

    var select = document.getElementById('fpAmbienteSelect');
    var addBtn = document.getElementById('fpAmbienteAdd');
    var chips  = document.getElementById('fpAmbienteChips');
    var error  = document.getElementById('fpAmbienteError');

    function agregar() {
        var id = select.value;
        if (!id) return;
        var opt = select.options[select.selectedIndex];
        var nombre = opt.textContent.trim();

        var chip = document.createElement('span');
        chip.className = 'fp-amb-chip';
        chip.dataset.id = id;
        chip.innerHTML = '<span>' + nombre + '</span>'
            + '<button type="button" class="fp-amb-chip-x" aria-label="Quitar">&times;</button>'
            + '<input type="hidden" name="ambiente_ids[]" value="' + id + '">';

        chip.querySelector('.fp-amb-chip-x').addEventListener('click', function () {
            chip.remove();
            // Regresa la opción al select.
            var o = document.createElement('option');
            o.value = id;
            o.textContent = nombre;
            select.appendChild(o);
        });

        chips.appendChild(chip);
        opt.remove();
        select.value = '';
        if (error) error.hidden = true;
    }

    addBtn.addEventListener('click', agregar);

    form.addEventListener('submit', function (e) {
        if (!chips.querySelector('.fp-amb-chip')) {
            e.preventDefault();
            if (error) error.hidden = false;
            select.focus();
        }
    });
})();
</script>
<?= $this->endSection() ?>
