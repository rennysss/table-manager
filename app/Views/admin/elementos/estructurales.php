<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <p class="text-muted mb-4">Catálogo de etiquetas, formas y mobiliario para el editor de FloorPlan.</p>

    <div id="catalogStructAccordion">
        <div class="card app-card app-elementos-accordion mb-4">
            <div class="card-header app-elementos-section-title p-0" id="headingStructLabels">
                <button class="app-elementos-accordion-btn" type="button"
                        data-toggle="collapse" data-target="#collapseStructLabels"
                        aria-expanded="true" aria-controls="collapseStructLabels">
                    <span class="app-elementos-accordion-label">Labels</span>
                    <span class="app-elementos-accordion-chevron" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </span>
                </button>
            </div>
            <div id="collapseStructLabels" class="collapse show" aria-labelledby="headingStructLabels">
                <div class="card-body app-elementos-panel">
                    <div class="app-elementos-struct-grid" id="catalogStructLabels"></div>
                </div>
            </div>
        </div>

        <div class="card app-card app-elementos-accordion mb-4">
            <div class="card-header app-elementos-section-title p-0" id="headingStructShapes">
                <button class="app-elementos-accordion-btn" type="button"
                        data-toggle="collapse" data-target="#collapseStructShapes"
                        aria-expanded="true" aria-controls="collapseStructShapes">
                    <span class="app-elementos-accordion-label">Shapes</span>
                    <span class="app-elementos-accordion-chevron" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </span>
                </button>
            </div>
            <div id="collapseStructShapes" class="collapse show" aria-labelledby="headingStructShapes">
                <div class="card-body app-elementos-panel">
                    <div class="app-elementos-struct-grid" id="catalogStructShapes"></div>
                </div>
            </div>
        </div>

        <div class="card app-card app-elementos-accordion">
            <div class="card-header app-elementos-section-title p-0" id="headingStructFurniture">
                <button class="app-elementos-accordion-btn" type="button"
                        data-toggle="collapse" data-target="#collapseStructFurniture"
                        aria-expanded="true" aria-controls="collapseStructFurniture">
                    <span class="app-elementos-accordion-label">Furniture</span>
                    <span class="app-elementos-accordion-chevron" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </span>
                </button>
            </div>
            <div id="collapseStructFurniture" class="collapse show" aria-labelledby="headingStructFurniture">
                <div class="card-body app-elementos-panel">
                    <div class="app-elementos-struct-grid" id="catalogStructFurniture"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/floorplan-structures.js') ?>"></script>
<script src="<?= base_url('assets/js/elementos-catalog.js') ?>"></script>
<script>window.ElementosCatalog.initEstructurales();</script>
<?= $this->endSection() ?>
