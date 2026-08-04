<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent px-0">
            <li class="breadcrumb-item"><a href="<?= base_url('gerente') ?>">Panel</a></li>
            <li class="breadcrumb-item active"><?= esc($sucursal['nombre']) ?> — Ambientes</li>
        </ol>
    </nav>

    <h1 class="h4 mb-4">Ambientes</h1>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Estatus</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ambientes as $a): ?>
                    <tr>
                        <td><?= esc($a['nombre']) ?></td>
                        <td>
                            <span class="badge badge-<?= $a['estatus'] === 'activo' ? 'success' : 'secondary' ?>">
                                <?= esc($a['estatus']) ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="<?= base_url("gerente/ambientes/{$a['id']}/mesas") ?>" class="btn btn-sm btn-outline-primary">
                                Ver mesas
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
