<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="card app-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-4">
                <span class="app-perfil-avatar"><?= esc(mb_strtoupper(mb_substr((string) session()->get('nombre'), 0, 1))) ?></span>
                <div class="ml-3">
                    <h2 class="h5 mb-1"><?= esc(session()->get('nombre')) ?></h2>
                    <p class="text-muted mb-0"><?= esc(ucfirst((string) session()->get('rol'))) ?></p>
                </div>
            </div>

            <dl class="app-perfil-dl mb-0">
                <dt>Usuario</dt>
                <dd><?= esc(session()->get('usuario')) ?></dd>
                <dt>Rol</dt>
                <dd><?= esc(ucfirst((string) session()->get('rol'))) ?></dd>
            </dl>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
