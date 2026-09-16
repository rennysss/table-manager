<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/hostess.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/hostess.css') ?: time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="hostess-app">
    <header class="hostess-header">
        <div class="hostess-header-filtros">
            <div class="hostess-filtro">
                <label class="hostess-filtro-label" for="filtroPais">País</label>
                <select id="filtroPais" class="form-control hostess-filtro-select">
                    <option value="">Cargando…</option>
                </select>
            </div>
            <div class="hostess-filtro">
                <label class="hostess-filtro-label" for="filtroCiudad">Ciudad</label>
                <select id="filtroCiudad" class="form-control hostess-filtro-select" disabled>
                    <option value="">—</option>
                </select>
            </div>
            <div class="hostess-filtro">
                <label class="hostess-filtro-label" for="filtroEstablecimiento">Establecimiento</label>
                <select id="filtroEstablecimiento" class="form-control hostess-filtro-select" disabled>
                    <option value="">—</option>
                </select>
            </div>
            <div class="hostess-filtro hostess-filtro--fecha">
                <div class="hostess-date-group" role="group" aria-label="Navegación de fecha">
                    <button type="button" class="hostess-date-btn" id="btnFechaAnterior" title="Día anterior" aria-label="Día anterior">&lsaquo;</button>
                    <input type="date" id="fechaReservas" class="form-control hostess-date-input" value="<?= esc($fecha) ?>" aria-label="Fecha de visita">
                    <button type="button" class="hostess-date-btn" id="btnFechaSiguiente" title="Día siguiente" aria-label="Día siguiente">&rsaquo;</button>
                </div>
            </div>
            <div class="hostess-filtro hostess-filtro--accion">
                <button type="button" class="hostess-btn-aplicar" id="btnBuscarReservas" title="Cargar reservas" aria-label="Cargar reservas">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="16.5" y1="16.5" x2="21" y2="21"></line>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div class="hostess-content">
        <section class="hostess-main">
            <div id="hostessApiAlert">
            <?php if (! empty($apiError)): ?>
            <div class="alert alert-warning hostess-api-alert" role="alert"><?= esc($apiError) ?></div>
            <?php elseif (! empty($modoDemo)): ?>
            <div class="alert alert-info hostess-api-alert" role="alert">
                Modo demostración: configure la API Key y el Venue ID en la sucursal para conectar con OneReservations.
            </div>
            <?php endif; ?>
            </div>

            <div class="hostess-card">
                <div class="hostess-card-head">
                    <h2 class="hostess-card-title">Reservas del día</h2>
                    <span class="hostess-card-meta" id="hostessCardMeta">
                        <strong><?= count($reservas) ?></strong> registros · <?= esc(date('d/m/Y', strtotime($fecha))) ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table id="tablaReservas" class="table hostess-table mb-0">
                        <thead>
                            <tr>
                                <th>Reserva</th>
                                <th>Nombre</th>
                                <th>RP</th>
                                <th>Mesa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $r):
                                $asig = $asignaciones[$r['codigo']] ?? null;
                            ?>
                            <tr data-codigo="<?= esc($r['codigo']) ?>" class="hostess-row" role="button" tabindex="0" aria-label="Seleccionar reserva <?= esc($r['codigo']) ?>">
                                <td data-label="Reserva"><span class="hostess-booking"><?= esc($r['codigo']) ?></span></td>
                                <td data-label="Nombre"><?= esc($r['nombre']) ?></td>
                                <td data-label="RP"><?= esc($r['rp']) ?></td>
                                <td class="mesa-cell" data-label="Mesa"><?= $asig && ($asig['mesa_numero'] ?? null) !== null && $asig['mesa_numero'] !== '' ? esc('#' . $asig['mesa_numero']) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Panel detalle reserva (check-in estilo SevenRooms) -->
<div class="hostess-detail-panel" id="panelDetalle" hidden>
    <div class="hostess-detail-header">
        <div>
            <p class="hostess-detail-code mb-0" id="detalleCodigoLabel">RESERVA <span id="detalleCodigo">—</span></p>
            <h5 id="detalleNombre" class="mb-0">—</h5>
        </div>
        <button type="button" class="close app-modal-close" id="cerrarDetalle">&times;</button>
    </div>

    <div class="hostess-status-bar" id="detalleStatusBar">
        <div class="hostess-status-cell">
            <span class="hostess-status-label">Status</span>
            <span class="hostess-status-value" id="detalleStatus">—</span>
        </div>
        <div class="hostess-status-cell">
            <span class="hostess-status-label">Table</span>
            <span class="hostess-status-value" id="detalleMesa"></span>
        </div>
    </div>

    <div class="hostess-detail-columns">
    <div class="hostess-detail-body">
        <div class="hostess-visit-meta">
            <p><strong>Hora:</strong> <span id="detalleHora">—</span></p>
            <p><strong>RP:</strong> <span id="detalleRP">—</span></p>
            <p><strong>Pax reservados:</strong> <span id="detallePax">—</span></p>
            <p><strong>Pax en mesa:</strong> <span id="detallePaxMesa">0</span></p>
        </div>

        <div class="hostess-checkin-actions" id="detalleCheckinActions">
            <button type="button" class="btn btn-primary" id="btnAgregarPax">+ Agregar pax</button>
        </div>

        <div class="hostess-llegadas" id="detalleLlegadasWrap" hidden>
            <label>Llegadas</label>
            <ul class="hostess-llegadas-list" id="detalleLlegadas"></ul>
        </div>

        <!-- Tags de reserva (esta visita) -->
        <button type="button" class="hostess-tags-module" id="btnAbrirTagsReserva" aria-label="Editar tags de la reserva">
            <div class="hostess-tags-module-head">
                <span class="hostess-tags-module-icon" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </span>
                <span class="hostess-tags-module-title">Reservation Tags</span>
            </div>
            <div class="hostess-tags-module-pills" id="detalleTagsReserva">
                <span class="hostess-tags-empty">Toca para agregar tags de reserva</span>
            </div>
        </button>

        <!-- Tags de cliente (perfil por email) -->
        <button type="button" class="hostess-tags-module" id="btnAbrirTagsCliente" aria-label="Editar tags del cliente">
            <div class="hostess-tags-module-head">
                <span class="hostess-tags-module-icon" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <span class="hostess-tags-module-title">Client Tags</span>
            </div>
            <div class="hostess-tags-module-pills" id="detalleTagsCliente">
                <span class="hostess-tags-empty">Toca para agregar tags de cliente</span>
            </div>
        </button>

        <div class="hostess-xetux-block" id="detalleXetuxBlock">
            <div class="form-group mb-2">
                <label for="selectMeseroXetux">Mesero</label>
                <select id="selectMeseroXetux" class="form-control hostess-xetux-select" disabled>
                    <option value="">—</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="selectMesaXetux">Mesa</label>
                <select id="selectMesaXetux" class="form-control hostess-xetux-select" disabled>
                    <option value="">—</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary btn-block mb-2" id="btnSentarXetux">SENTAR</button>
            <div id="detalleXetuxPostSentar" hidden>
                <button type="button" class="btn btn-outline-danger btn-block mb-2" id="btnCancelarXetux">CANCELAR</button>
                <button type="button" class="btn btn-secondary btn-block" id="btnCerrarCuentaXetux">CERRAR CUENTA</button>
            </div>
            <p class="text-muted small mb-0" id="detalleXetuxMeta" hidden></p>
        </div>

        <div class="hostess-finance" id="detalleFinance" hidden>
            <label>Pagos</label>
            <p id="detallePrepagoRow" hidden><strong>Prepago:</strong> <span id="detallePrepago">—</span></p>
            <p id="detalleTotalRow" hidden><strong>Total:</strong> <span id="detalleTotal">—</span></p>
            <p id="detalleBalanceRow" hidden><strong>Saldo:</strong> <span id="detalleBalance">—</span></p>
            <div class="hostess-tarjeta" id="detalleTarjeta" hidden>
                <p id="detalleTarjetaNombreRow" hidden><strong>Titular:</strong> <span id="detalleTarjetaNombre">—</span></p>
                <p id="detalleTarjetaBrandRow" hidden><strong>Brand:</strong> <span id="detalleTarjetaBrand">—</span></p>
                <p id="detalleTarjetaNumeroRow" hidden><strong>Tarjeta:</strong> <span id="detalleTarjetaNumero">—</span></p>
                <p id="detalleTarjetaBancoRow" hidden><strong>Banco:</strong> <span id="detalleTarjetaBanco">—</span></p>
                <p id="detalleTarjetaPaisRow" hidden><strong>País:</strong> <span id="detalleTarjetaPais">—</span></p>
            </div>
        </div>

    </div>

    <aside class="hostess-activity" aria-label="Actividad de la reserva">
        <div class="hostess-activity-head">
            <span class="hostess-activity-title">Toda la actividad</span>
        </div>
        <div class="hostess-activity-feed" id="detalleActividad"></div>
    </aside>
    </div>
</div>

<?php
/** Catálogo de tags en modal hostess */
$renderTagsCatalog = static function (array $categorias, string $opcionClass, string $buscarId, string $catalogId, string $vacioMsg): void {
    ?>
    <input type="search" id="<?= esc($buscarId) ?>" class="form-control hostess-tags-search mb-3"
           placeholder="Buscar tags…" autocomplete="off">
    <div class="hostess-tags-catalog" id="<?= esc($catalogId) ?>">
        <?php foreach ($categorias as $cat): ?>
        <div class="hostess-tags-cat" data-cat="<?= esc($cat['nombre']) ?>">
            <button type="button" class="hostess-tags-cat-toggle" aria-expanded="true">
                <span><?= esc($cat['nombre']) ?></span>
                <span class="hostess-tags-cat-chevron" aria-hidden="true">▾</span>
            </button>
            <div class="hostess-tags-cat-body">
                <?php foreach ($cat['tags'] as $t): ?>
                <?php
                $bg = $t['color'] ?: '#007AFF';
                $fg = \App\Models\TagModel::colorTexto($bg);
                ?>
                <button type="button"
                        class="tag-pill hostess-tag-option <?= esc($opcionClass) ?>"
                        data-tag-id="<?= (int) $t['id'] ?>"
                        data-tag='<?= esc(json_encode($t), 'attr') ?>'
                        data-nombre="<?= esc(mb_strtolower($t['nombre'])) ?>"
                        style="background-color: <?= esc($bg) ?>; color: <?= esc($fg) ?>;">
                    <span><?= esc($t['nombre']) ?></span>
                    <span class="hostess-tag-check" aria-hidden="true"></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if ($categorias === []): ?>
        <p class="text-muted mb-0"><?= esc($vacioMsg) ?></p>
        <?php endif; ?>
    </div>
    <?php
};
?>

<!-- Modal tags de reserva -->
<div class="modal fade" id="modalTagsReserva" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content app-modal-content hostess-tags-modal">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title">Reservation Tags</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body">
                <div class="hostess-tags-selected" id="modalTagsReservaSelected"></div>
                <?php $renderTagsCatalog(
                    $tagCategoriasReserva ?? [],
                    'hostess-tag-option-reserva',
                    'inputBuscarTagsReserva',
                    'modalTagsReservaCatalog',
                    'No hay tags de reserva. Configúralos en Configuraciones → Tags (tipo reserva).'
                ); ?>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary btn-block" id="btnGuardarTagsReserva">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal tags de cliente -->
<div class="modal fade" id="modalTagsCliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content app-modal-content hostess-tags-modal">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title">Client Tags</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body">
                <div class="hostess-tags-selected" id="modalTagsClienteSelected"></div>
                <?php $renderTagsCatalog(
                    $tagCategoriasCliente ?? [],
                    'hostess-tag-option-cliente',
                    'inputBuscarTagsCliente',
                    'modalTagsClienteCatalog',
                    'No hay tags de cliente. Configúralos en Configuraciones → Tags (tipo cliente).'
                ); ?>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary btn-block" id="btnGuardarTagsCliente">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal agregar pax -->
<div class="modal fade" id="modalAgregarPax" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content app-modal-content">
            <div class="modal-header app-modal-header">
                <h5 class="modal-title">Agregar pax</h5>
                <button type="button" class="close app-modal-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body app-modal-body">
                <div class="form-group">
                    <label for="inputPaxDelta">Cantidad de pax que llegan</label>
                    <input type="number" id="inputPaxDelta" class="form-control" min="1" step="1" value="1">
                </div>
                <p class="text-muted small mb-0" id="modalPaxHint"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarPax">Guardar</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/hostess.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/hostess.js') ?: time() ?>"></script>
<script>
    HostessApp.init({
        fecha: '<?= esc($fecha) ?>',
        sucursalId: <?= (int) $sucursalId ?>,
        sucursalPaisId: <?= (int) ($sucursalActiva['pais_id'] ?? 0) ?>,
        sucursalCiudad: <?= json_encode($sucursalActiva['ciudad'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        detalleUrl: '<?= base_url('hostess/reservas') ?>',
        asignarUrl: '<?= base_url('hostess/reservas/asignar') ?>',
        tagsUrl: '<?= base_url('hostess/reservas/tags') ?>',
        llegadaUrl: '<?= base_url('hostess/reservas/llegada') ?>',
        sentarUrl: '<?= base_url('hostess/reservas/sentar') ?>',
        xetuxMeserosUrl: '<?= base_url('hostess/reservas/xetux/meseros') ?>',
        xetuxMesasUrl: '<?= base_url('hostess/reservas/xetux/mesas') ?>',
        xetuxSentarUrl: '<?= base_url('hostess/reservas/xetux/sentar') ?>',
        xetuxCancelarUrl: '<?= base_url('hostess/reservas/xetux/cancelar') ?>',
        xetuxCerrarUrl: '<?= base_url('hostess/reservas/xetux/cerrar-cuenta') ?>',
        liberarUrl: '<?= base_url('hostess/reservas/liberar') ?>',
        actividadUrl: '<?= base_url('hostess/reservas/actividad') ?>',
        apiPaises: '<?= base_url('hostess/reservas/api/paises') ?>',
        apiCiudades: '<?= base_url('hostess/reservas/api/paises') ?>',
        apiSucursales: '<?= base_url('hostess/reservas/api/sucursales') ?>',
        apiBuscar: '<?= base_url('hostess/reservas/api/buscar') ?>',
        filtrosUbicacion: <?= json_encode($filtrosUbicacion ?? ['paises' => [], 'ciudades' => [], 'sucursales' => []], JSON_UNESCAPED_UNICODE) ?>,
        reservasIniciales: <?= json_encode($reservas ?? [], JSON_UNESCAPED_UNICODE) ?>,
        asignacionesIniciales: <?= json_encode($asignaciones ?? [], JSON_UNESCAPED_UNICODE) ?>
    });
</script>
<?= $this->endSection() ?>
