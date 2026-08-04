<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/floorplan.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/floorplan.css') ?: time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/hostess.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/hostess.css') ?: time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="hostess-plano-view" id="hostessPlanoView">
    <div class="hostess-plano-toolbar">
        <div class="hostess-plano-toolbar-info">
            <p class="hostess-plano-reserva-line mb-0">
                Reserva: <strong id="reservaActiva"><?= esc($reservaCodigo ?: '—') ?></strong>
            </p>
            <p class="hostess-plano-guest mb-0" id="reservaNombreLabel"<?= empty($reservaNombre) ? ' hidden' : '' ?>><?= esc($reservaNombre ?? '') ?></p>
        </div>
        <a href="<?= base_url('hostess/reservas?fecha=' . urlencode($fecha) . (! empty($reservaCodigo) ? '&reserva=' . urlencode($reservaCodigo) : '')) ?>"
           class="hostess-plano-close"
           id="btnCerrarPlano"
           title="Cerrar"
           aria-label="Cerrar plano">&times;</a>
        <span id="planoAforo" hidden>—</span>
    </div>

    <div class="hostess-plano-canvas-wrap">
        <canvas id="hostessPlanoCanvas"></canvas>
    </div>

    <div class="hostess-plano-legend">
        <span class="legend-item legend-libre">Disponible</span>
        <span class="legend-item legend-reservada">Reservada</span>
        <span class="legend-item legend-ocupada">Ocupada</span>
    </div>
</div>

<!-- Modal confirmar asignación (sin Cancelar) -->
<div class="modal fade" id="modalAsignarMesa" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title">Asignar mesa</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body">
                <p class="hostess-plano-confirm-text mb-0" id="textoConfirmarAsignacion">
                    ¿Está seguro que la reserva se asigne a esta mesa?
                </p>
                <p class="hostess-plano-assign-error text-danger small mt-2 mb-0" id="errorAsignarMesa" hidden></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary btn-block" id="btnConfirmarAsignarMesa">Asignar Mesa</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/fabric/fabric.min.js') ?>"></script>
<script src="<?= base_url('assets/js/hostess-plano.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/hostess-plano.js') ?: time() ?>"></script>
<script>
    HostessPlano.init({
        sucursalId: <?= (int) $sucursalId ?>,
        fecha: '<?= esc($fecha) ?>',
        reservaCodigo: <?= json_encode($reservaCodigo ?? '', JSON_UNESCAPED_UNICODE) ?>,
        reservaNombre: <?= json_encode($reservaNombre ?? '', JSON_UNESCAPED_UNICODE) ?>,
        apiUrl: '<?= base_url('api/floorplan/activo') ?>',
        asignarUrl: '<?= base_url('hostess/reservas/asignar') ?>',
        reservasUrl: '<?= base_url('hostess/reservas') ?>'
    });
</script>
<?= $this->endSection() ?>
