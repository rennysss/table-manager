<?= $this->extend('layouts/main') ?>

<?php
// Cache-busting por fecha de modificación: garantiza que el navegador
// cargue la última versión de los assets (MAMP no versiona por defecto).
$assetVer = static function (string $rel): string {
    $abs = FCPATH . $rel;
    $v   = is_file($abs) ? (string) filemtime($abs) : (string) time();
    return base_url($rel) . '?v=' . $v;
};
?>
<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= $assetVer('assets/css/floorplan.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<script>
    // Al abrir el editor de FloorPlan, colapsa el sidebar para ganar espacio.
    // Se aplica solo a la clase (sin tocar localStorage), así al salir del
    // editor el menú vuelve al estado que el usuario tenía antes.
    (function () {
        var shell = document.getElementById('appShell');
        if (shell) { shell.classList.add('sidebar-collapsed'); }
        var btn = document.getElementById('sidebarCollapseBtn');
        if (btn) { btn.setAttribute('aria-expanded', 'false'); }
    })();
</script>
<div class="fp-embed">
    <!-- Barra superior del editor estilo Sevenrooms -->
    <header class="fp-toolbar">
        <div class="fp-toolbar-left">
            <span class="fp-breadcrumb">Layout /
                <input type="text" id="fpNombre" class="fp-name-input"
                       value="<?= esc($floorplan['nombre'], 'attr') ?>"
                       maxlength="100" aria-label="Nombre del floorplan"
                       title="Haz clic para editar el nombre">
            </span>
        </div>
        <div class="fp-toolbar-center">
            <button type="button" class="fp-btn" id="btnAddStructural"><span>+</span> Agregar estructuras</button>
        </div>
        <div class="fp-toolbar-right">
            <button type="button" class="fp-icon-btn" id="btnUndo" title="Undo">&#8624;</button>
            <button type="button" class="fp-icon-btn" id="btnRedo" title="Redo">&#8625;</button>
            <select id="zoomSelect" class="fp-zoom-select">
                <option value="0.25">25%</option>
                <option value="0.28">28%</option>
                <option value="0.5" selected>50%</option>
                <option value="0.75">75%</option>
                <option value="1">100%</option>
            </select>
            <span id="fpSaveStatus" class="fp-save-status" aria-live="polite"></span>
            <a href="<?= $modo === 'admin' ? base_url("admin/sucursales/{$sucursal['id']}/floorplans") : base_url('gerente/floorplan') ?>" class="fp-link">Descartar cambios</a>
            <button type="button" class="fp-btn fp-btn-primary" id="btnSave">Guardar</button>
        </div>
    </header>

    <div class="fp-workspace">
        <!-- Sidebar izquierdo del editor -->
        <aside class="fp-sidebar" id="fpSidebar">
            <div class="fp-sidebar-tabs">
                <button class="fp-tab active" data-panel="tables">Tables</button>
                <button class="fp-tab" data-panel="combinations">Combinations</button>
            </div>
            <div class="fp-panel active" id="panel-tables">
                <div class="fp-panel-header">
                    <span>Tables</span>
                </div>
                <?php if ($modo === 'gerente'): ?>
                <p class="fp-hint fp-hint-gerente">Puedes crear y modificar floorplans de la sucursal. El número de mesas existentes no se puede cambiar; las reservas asignadas se mantienen al moverlas.</p>
                <div class="fp-gerente-edit" id="gerenteMesaEdit" hidden>
                    <h6 class="fp-gerente-title">Mesa seleccionada</h6>
                    <div class="form-group fp-gerente-field">
                        <label>Número</label>
                        <input type="text" id="gerenteNumero" class="fp-input" readonly>
                    </div>
                    <div class="form-row fp-gerente-row">
                        <div class="form-group col-4">
                            <label>Pax</label>
                            <input type="number" id="gerentePax" class="fp-input" min="1">
                        </div>
                        <div class="form-group col-4">
                            <label>Mín</label>
                            <input type="number" id="gerenteMin" class="fp-input" min="1">
                        </div>
                        <div class="form-group col-4">
                            <label>Máx</label>
                            <input type="number" id="gerenteMax" class="fp-input" min="1">
                        </div>
                    </div>
                    <div id="gerenteReservasInfo" class="fp-reservas-info"></div>
                    <button type="button" class="fp-btn fp-btn-block" id="btnApplyPax">Aplicar pax</button>
                </div>
                <?php endif; ?>
                <div class="fp-table-list" id="tableList"></div>
            </div>
            <div class="fp-panel" id="panel-combinations">
                <p class="fp-hint p-3">Combinaciones de mesas — próximamente.</p>
            </div>

            <!-- Panel elementos estructurales -->
            <div class="fp-subpanel" id="subpanel-structural" hidden>
                <div class="fp-subpanel-header">
                    <button type="button" class="fp-back" data-back>&larr;</button>
                    <span>Agregar estructuras</span>
                </div>
                <div class="fp-struct-section">
                    <h6>Etiquetas</h6>
                    <div class="fp-struct-items" id="structLabels"></div>
                </div>
                <div class="fp-struct-section">
                    <h6>Formas</h6>
                    <div class="fp-struct-items" id="structShapes"></div>
                </div>
                <div class="fp-struct-section">
                    <h6>Mobiliario</h6>
                    <div class="fp-struct-items" id="structFurniture"></div>
                </div>
            </div>
        </aside>

        <!-- Canvas -->
        <div class="fp-canvas-wrap">
            <canvas id="floorplanCanvas"></canvas>
        </div>

        <!-- Herramientas inferiores: zoom, mano (H), selector (S) -->
        <div class="fp-tools-bar" role="toolbar" aria-label="Herramientas del lienzo">
            <div class="fp-tools-group">
                <div class="fp-tool-zoom-wrap">
                    <button type="button" class="fp-tool-btn" id="btnToolZoom"
                            title="Zoom" aria-label="Zoom" aria-expanded="false" aria-haspopup="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <line x1="16.5" y1="16.5" x2="21" y2="21"/>
                            <line x1="11" y1="8" x2="11" y2="14"/>
                            <line x1="8" y1="11" x2="14" y2="11"/>
                        </svg>
                    </button>
                    <div class="fp-zoom-popover" id="fpZoomPopover" hidden>
                        <button type="button" class="fp-zoom-step" id="btnZoomOut" title="Alejar" aria-label="Alejar">−</button>
                        <span class="fp-zoom-label" id="fpZoomLabel">50%</span>
                        <button type="button" class="fp-zoom-step" id="btnZoomIn" title="Acercar" aria-label="Acercar">+</button>
                    </div>
                </div>
                <button type="button" class="fp-tool-btn" id="btnToolHand" data-tool="hand"
                        title="Mano (H)" aria-label="Mano (H)">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M7 11V6a2 2 0 0 1 4 0"/>
                        <path d="M11 11V4a2 2 0 0 1 4 0v7"/>
                        <path d="M15 11V7a2 2 0 0 1 4 0v8a5 5 0 0 1-5 5h-2a5 5 0 0 1-5-5v-4"/>
                    </svg>
                </button>
                <button type="button" class="fp-tool-btn active" id="btnToolSelect" data-tool="select"
                        title="Selector (S)" aria-label="Selector (S)">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 4l7 16 2.5-6.5L20 11 4 4z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Toggle ambientes -->
        <div class="fp-ambiente-toggle">
            <?php foreach ($ambientes as $i => $a): ?>
            <button type="button" class="fp-amb-btn <?= $i === 0 ? 'active' : '' ?>" data-ambiente="<?= $a['id'] ?>"><?= esc(strtoupper($a['descripcion'])) ?></button>
            <?php endforeach; ?>
            <button type="button" class="fp-amb-btn fp-amb-add">+</button>
        </div>

        <div class="fp-aforo-badge">Aforo: <strong id="aforoValue"><?= (int) $aforo ?></strong></div>

        <!-- Panel lateral derecho: detalles de la mesa seleccionada -->
        <aside class="fp-details" id="fpDetails" hidden>
            <div class="fp-details-head">
                <span>DETALLES DE LA SELECCIÓN</span>
                <button type="button" class="fp-details-close" id="fpDetailsClose" aria-label="Cerrar">&times;</button>
            </div>
            <div class="fp-details-body">
                <h4 class="fp-details-section">Ajustes generales</h4>

                <div class="fp-details-field">
                    <label for="detName">Nombre de la mesa</label>
                    <input type="text" id="detName" class="fp-details-input" maxlength="20" <?= $modo === 'gerente' ? 'readonly' : '' ?>>
                </div>

                <div class="fp-details-field">
                    <label for="detAmbiente">Área / Ambiente</label>
                    <select id="detAmbiente" class="fp-details-input">
                        <?php foreach ($ambientes as $a): ?>
                        <option value="<?= (int) $a['id'] ?>"><?= esc(strtoupper($a['descripcion'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="fp-details-row">
                    <div class="fp-details-field">
                        <label for="detMin">Cover Mín</label>
                        <input type="number" id="detMin" class="fp-details-input" min="1">
                    </div>
                    <div class="fp-details-field">
                        <label for="detMax">Cover Máx</label>
                        <input type="number" id="detMax" class="fp-details-input" min="1">
                    </div>
                </div>

                <h4 class="fp-details-section">Ajustes del plano</h4>

                <div class="fp-details-field">
                    <label>Forma</label>
                    <div class="fp-shape-group" id="detShapeGroup">
                        <button type="button" class="fp-shape-btn" data-shape="square" title="Cuadrada" aria-label="Cuadrada">
                            <svg viewBox="0 0 24 24" width="22" height="22"><rect x="5" y="5" width="14" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                        <button type="button" class="fp-shape-btn" data-shape="circle" title="Redonda" aria-label="Redonda">
                            <svg viewBox="0 0 24 24" width="22" height="22"><circle cx="12" cy="12" r="7.5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                        <button type="button" class="fp-shape-btn" data-shape="rect" title="Rectangular" aria-label="Rectangular">
                            <svg viewBox="0 0 24 24" width="22" height="22"><rect x="3" y="7" width="18" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                    </div>
                </div>

                <div class="fp-details-field">
                    <label for="detSize">Tamaño</label>
                    <select id="detSize" class="fp-details-input">
                        <option value="xs">Extra Small</option>
                        <option value="s">Small</option>
                        <option value="m" selected>Medium</option>
                        <option value="l">Large</option>
                        <option value="xl">Extra Large</option>
                        <option value="xxl">Extra Extra Large</option>
                    </select>
                </div>

                <?php if ($modo === 'gerente'): ?>
                <div id="detReservas" class="fp-details-reservas"></div>
                <?php endif; ?>

                <?php if ($modo !== 'gerente'): ?>
                <button type="button" class="fp-details-delete" id="detEliminar" title="Quitar del plano (Backspace)">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                        <path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6m4 5v6m4-6v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Quitar del plano
                </button>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    window.FLOORPLAN = {
        id: <?= (int) $floorplan['id'] ?>,
        modo: '<?= esc($modo) ?>',
        saveUrl: '<?= $modo === 'gerente' ? base_url("gerente/floorplan/{$floorplan['id']}/guardar") : base_url("admin/floorplan/{$floorplan['id']}/guardar") ?>',
        canvasData: <?= $floorplan['canvas_json'] ?: '{"version":"5.3.0","objects":[]}' ?>,
        mesas: <?= json_encode($mesas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        inventario: <?= json_encode($inventario ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        ambientes: <?= json_encode($ambientes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        csrfToken: '<?= csrf_hash() ?>',
        csrfHeader: '<?= config('Security')->headerName ?>',
        gerenteMode: <?= $modo === 'gerente' ? 'true' : 'false' ?>,
        reservasMesaUrl: '<?= base_url('gerente/floorplan/mesas') ?>',
        autoPrint: <?= ! empty($autoPrint) ? 'true' : 'false' ?>
    };
</script>
<script src="<?= base_url('assets/vendor/fabric/fabric.min.js') ?>"></script>
<script src="<?= $assetVer('assets/js/floorplan-tables.js') ?>"></script>
<script src="<?= $assetVer('assets/js/floorplan-structures.js') ?>"></script>
<script src="<?= $assetVer('assets/js/floorplan-editor.js') ?>"></script>
<?php if (! empty($autoPrint)): ?>
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 900);
    });
</script>
<?php endif; ?>
<?= $this->endSection() ?>
