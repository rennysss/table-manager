/**
 * FloorPlans gerente — modal de creación (tabla igual que Sucursales, sin DataTables)
 */
(function ($) {
    'use strict';

    var cfg = window.FLOORPLAN_GERENTE || {};
    var $modal = $('#appModal');
    var $body = $('#appModalBody');
    var $title = $('#appModalTitle');

    function loadModal(url, title) {
        $title.text(title || 'Modal');
        $body.html('<div class="text-center py-4 text-muted">Cargando…</div>');
        $modal.modal('show');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('No se pudo cargar el contenido.');
                }
                return res.text();
            })
            .then(function (html) {
                $body.html(html);
            })
            .catch(function () {
                $body.html('<div class="alert alert-danger mb-0">Error al cargar. Intenta de nuevo.</div>');
            });
    }

    $(document).on('click', '.btn-modal-floorplan', function () {
        loadModal($(this).data('url'), $(this).data('title'));
    });

    // Crear: abre el editor en pestaña nueva y recarga el listado
    $(document).on('submit', '#formCrearFloorplan', function () {
        var $form = $(this);
        $form.attr('target', '_blank');
        $form.find('[type="submit"]').prop('disabled', true);
        setTimeout(function () {
            $modal.modal('hide');
            window.location.reload();
        }, 300);
    });

    $(function () {
        if (cfg.abrirModalCrear) {
            var $precargado = $('#modalCrearPrecargado');
            if ($precargado.length) {
                $title.text('Nuevo FloorPlan');
                $body.html($precargado.html());
                $modal.modal('show');
            } else if (cfg.modalCrearUrl) {
                loadModal(cfg.modalCrearUrl, 'Nuevo FloorPlan');
            }
        }
    });
}(jQuery));
