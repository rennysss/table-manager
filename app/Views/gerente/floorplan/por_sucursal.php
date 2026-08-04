<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent px-0">
            <li class="breadcrumb-item"><a href="<?= base_url('gerente') ?>">Panel</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('gerente/floorplan') ?>">FloorPlans</a></li>
            <li class="breadcrumb-item active"><?= esc($sucursal['nombre']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><?= esc($titulo) ?></h1>
            <p class="text-muted mb-0">Crea y modifica versiones del plano para días normales, festividades o eventos especiales.</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-5">
            <div class="card app-card">
                <div class="card-header">Nuevo FloorPlan</div>
                <div class="card-body">
                    <form method="post" action="<?= base_url("gerente/sucursales/{$sucursal['id']}/floorplans") ?>">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input name="nombre" class="form-control" required placeholder="Ej. Año Nuevo 2026">
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
                        <div class="form-group">
                            <label>Ambiente</label>
                            <?= view('partials/select_ambientes', [
                                'ambientesOpciones' => $ambientesOpciones ?? ambientes_opciones(),
                            ]) ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Crear y editar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="alert alert-info small mb-0 h-100 d-flex align-items-center">
                <div>
                    <strong>Tip:</strong> Solo un floorplan puede estar activo a la vez por sucursal/ambiente.
                    Activa el plano correspondiente al día (normal vs. festivo vs. año nuevo).
                    Al mover mesas, las reservas ya asignadas <strong>no se pierden</strong>.
                </div>
            </div>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Activo</th>
                        <th>Aforo</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($floorplans)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Sin floorplans. Crea el primero con el formulario de arriba.
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($floorplans as $fp): ?>
                    <tr>
                        <td><strong><?= esc($fp['nombre']) ?></strong></td>
                        <td><?= esc($fp['tipo']) ?></td>
                        <td>
                            <?php if ($fp['activo']): ?>
                            <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) ($fp['aforo'] ?? 0) ?> pax</td>
                        <td class="text-right text-nowrap">
                            <a href="<?= base_url("gerente/floorplan/{$fp['id']}/editor") ?>" class="btn btn-sm btn-dark">
                                Editor
                            </a>
                            <?php if (! $fp['activo']): ?>
                            <form method="post" action="<?= base_url("gerente/floorplan/{$fp['id']}/activar") ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-success">Activar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
