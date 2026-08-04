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
                <strong id="sucursalActiva"><?= esc($sucursalNombre !== '' ? $sucursalNombre : '—') ?></strong>
            </p>
            <?php if (! empty($reservaCodigo)): ?>
            <p class="hostess-plano-guest mb-0">
                Asignando:
                <strong id="reservaActiva"><?= esc($reservaCodigo) ?></strong>
                <?php if (! empty($reservaNombre)): ?>
                · <span id="reservaNombreLabel"><?= esc($reservaNombre) ?></span>
                <?php endif; ?>
            </p>
            <?php else: ?>
            <p class="hostess-plano-guest mb-0 text-muted" id="planoModoWalkin">
                Plano operativo · clic en mesa libre para <strong>OCUPAR</strong>
            </p>
            <?php endif; ?>
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

    <div class="hostess-plano-legend" aria-label="Leyenda de estados">
        <span class="legend-item legend-libre">
            <span class="legend-swatch" style="background:#8E8E93" aria-hidden="true"></span>
            Disponible
        </span>
        <span class="legend-item legend-reservada">
            <span class="legend-swatch" style="background:#FF9500" aria-hidden="true"></span>
            Reservada
        </span>
        <span class="legend-item legend-ocupada">
            <span class="legend-swatch" style="background:#8FAE9D" aria-hidden="true"></span>
            Ocupada (reserva)
        </span>
        <span class="legend-item legend-walkin">
            <span class="legend-swatch" style="background:#8B5CF6" aria-hidden="true"></span>
            Ocupada (invitado)
        </span>
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

<!-- Modal OCUPAR mesa (walk-in sin reserva) -->
<div class="modal fade" id="modalOcuparWalkin" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title">OCUPAR</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body">
                <p class="hostess-plano-confirm-text mb-3" id="textoOcuparWalkin">
                    Mesa libre para invitados sin reserva.
                </p>
                <div class="form-group mb-3">
                    <label class="small text-muted mb-1" for="walkinNombre">Nombre del invitado</label>
                    <input type="text" class="form-control" id="walkinNombre" maxlength="150"
                           placeholder="Invitado" autocomplete="name">
                </div>
                <div class="form-group mb-2">
                    <label class="small text-muted mb-1" for="walkinPax">Pax <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="walkinPax" min="1" max="99" value="2" required>
                </div>
                <p class="text-muted small mb-0">
                    Mesa que se usará para invitado sin reserva
                </p>
                <p class="hostess-plano-assign-error text-danger small mt-2 mb-0" id="errorOcuparWalkin" hidden></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary btn-block" id="btnConfirmarOcuparWalkin">OCUPAR</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal info reserva en mesa ocupada -->
<div class="modal fade" id="modalInfoMesaOcupada" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title" id="infoMesaTitulo">Mesa ocupada</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body">
                <dl class="hostess-plano-info-list mb-0" id="infoMesaLista">
                    <div class="hostess-plano-info-row">
                        <dt>Estado</dt>
                        <dd id="infoMesaEstado">—</dd>
                    </div>
                    <div class="hostess-plano-info-row">
                        <dt>Reserva</dt>
                        <dd id="infoMesaCodigo">—</dd>
                    </div>
                    <div class="hostess-plano-info-row">
                        <dt>Cliente</dt>
                        <dd id="infoMesaCliente">—</dd>
                    </div>
                    <div class="hostess-plano-info-row">
                        <dt>Hora</dt>
                        <dd id="infoMesaHora">—</dd>
                    </div>
                    <div class="hostess-plano-info-row">
                        <dt>Pax reservados</dt>
                        <dd id="infoMesaPaxRes">—</dd>
                    </div>
                    <div class="hostess-plano-info-row">
                        <dt>Pax en mesa</dt>
                        <dd id="infoMesaPaxMesa">—</dd>
                    </div>
                    <div class="hostess-plano-info-row" id="infoMesaTagsRow" hidden>
                        <dt>Tags</dt>
                        <dd id="infoMesaTags" class="hostess-plano-info-tags">—</dd>
                    </div>
                    <div class="hostess-plano-info-row" id="infoMesaSeatedRow" hidden>
                        <dt>Sentados</dt>
                        <dd id="infoMesaSeated">—</dd>
                    </div>
                </dl>
                <p class="text-muted small mt-3 mb-0" id="infoMesaSinReserva" hidden>
                    Esta mesa está marcada como ocupada, pero no hay datos de reserva asociados.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex flex-column flex-sm-row" style="gap:8px;">
                <button type="button" class="btn btn-secondary flex-fill" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary flex-fill" id="btnAbrirDetalleReserva" hidden>Ver detalle</a>
                <button type="button" class="btn btn-outline-warning flex-fill" id="btnLiberarMesaPlano" hidden>
                    LIBERAR
                </button>
            </div>
            <p class="hostess-plano-assign-error text-danger small text-center mt-2 mb-0 px-3" id="errorLiberarMesa" hidden></p>
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
        sucursalNombre: <?= json_encode($sucursalNombre ?? '', JSON_UNESCAPED_UNICODE) ?>,
        fecha: '<?= esc($fecha) ?>',
        reservaCodigo: <?= json_encode($reservaCodigo ?? '', JSON_UNESCAPED_UNICODE) ?>,
        reservaNombre: <?= json_encode($reservaNombre ?? '', JSON_UNESCAPED_UNICODE) ?>,
        apiUrl: '<?= base_url('api/floorplan/activo') ?>',
        asignarUrl: '<?= base_url('hostess/reservas/asignar') ?>',
        ocuparWalkinUrl: '<?= base_url('hostess/reservas/ocupar-walkin') ?>',
        liberarUrl: '<?= base_url('hostess/reservas/liberar') ?>',
        reservasUrl: '<?= base_url('hostess/reservas') ?>'
    });
</script>
<?= $this->endSection() ?>
