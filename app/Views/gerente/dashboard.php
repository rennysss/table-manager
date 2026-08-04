<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1"><?= esc($titulo) ?></h1>
        <p class="text-muted mb-0">Gestiona ambientes, mesas y floorplans de tus sucursales asignadas.</p>
    </div>

    <?php if (empty($resumen)): ?>
    <div class="alert alert-warning">No tienes sucursales asignadas. Contacta al administrador.</div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($resumen as $item): ?>
        <div class="col-lg-6 mb-4">
            <div class="card app-card h-100">
                <div class="card-body">
                    <h2 class="h5 font-weight-semibold mb-3"><?= esc($item['sucursal']['nombre']) ?></h2>
                    <div class="row mb-3">
                        <div class="col-4">
                            <div class="text-muted small">Ambientes</div>
                            <div class="h4 mb-0"><?= count($item['ambientes']) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Mesas activas</div>
                            <div class="h4 mb-0"><?= (int) $item['total_mesas'] ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Aforo</div>
                            <div class="h4 mb-0"><?= (int) $item['aforo'] ?></div>
                        </div>
                    </div>

                    <?php if ($item['floorplan']): ?>
                    <p class="small text-muted mb-3">
                        Plano activo: <strong><?= esc($item['floorplan']['nombre']) ?></strong>
                        (<?= esc($item['floorplan']['tipo']) ?>)
                    </p>
                    <?php else: ?>
                    <p class="small text-warning mb-3">Sin floorplan activo configurado.</p>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= base_url('gerente/sucursales/' . $item['sucursal']['id'] . '/ambientes') ?>"
                           class="btn btn-outline-primary btn-sm mr-2 mb-2">Ambientes</a>
                        <a href="<?= base_url('gerente/floorplan') ?>"
                           class="btn btn-dark btn-sm mr-2 mb-2">FloorPlans</a>
                        <?php if ($item['floorplan']): ?>
                        <a href="<?= base_url('gerente/floorplan/' . $item['floorplan']['id'] . '/editor') ?>"
                           class="btn btn-primary btn-sm mb-2"
                           target="_blank"
                           rel="noopener noreferrer">Editar plano activo</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
