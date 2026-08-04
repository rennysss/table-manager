<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <p class="text-muted mb-4">Catálogo de presets de mesas disponibles en el editor de FloorPlan.</p>

    <div class="card app-card mb-4">
        <div class="card-header">Vista previa</div>
        <div class="card-body app-elementos-panel">
            <div class="app-elementos-grid" id="catalogFormasMesas"></div>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Forma</th>
                        <th>Pax mín.</th>
                        <th>Pax máx.</th>
                    </tr>
                </thead>
                <tbody id="tablaFormasMesas"></tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/floorplan-tables.js') ?>"></script>
<script src="<?= base_url('assets/js/elementos-catalog.js') ?>"></script>
<script>window.ElementosCatalog.initFormasMesas();</script>
<?= $this->endSection() ?>
