/**
 * Modales AJAX — tags y categorías (estilo Reservation Tags)
 */
(function ($) {
    'use strict';

    var $modal = $('#appModal');
    var $body = $('#appModalBody');
    var $title = $('#appModalTitle');

    function csrfHeaders() {
        var token = typeof window.APP.csrfTokenActual === 'function'
            ? window.APP.csrfTokenActual()
            : window.APP.csrfToken;

        return {
            'X-Requested-With': 'XMLHttpRequest',
            [window.APP.csrfHeader]: token
        };
    }

    function postEliminar(url, nombre, etiqueta) {
        if (!window.confirm('¿Eliminar ' + etiqueta + ' «' + nombre + '»? Dejará de aparecer en nuevas reservas.')) {
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: csrfHeaders(),
            body: new FormData()
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    window.alert((result.data && result.data.message) || 'No se pudo eliminar.');
                    return;
                }
                $modal.modal('hide');
                window.location.reload();
            })
            .catch(function () {
                window.alert('Error de conexión.');
            });
    }

    function showErrors($form, errors) {
        var $box = $form.find('.app-modal-errors').first();
        if (!errors || !Object.keys(errors).length) {
            $box.addClass('d-none').empty();
            return;
        }
        var html = '<ul class="mb-0 pl-3">';
        Object.keys(errors).forEach(function (key) {
            html += '<li>' + errors[key] + '</li>';
        });
        html += '</ul>';
        $box.removeClass('d-none').html(html);
    }

    function loadModal(url, title) {
        $title.text(title || 'Modal');
        $body.html('<div class="text-center py-4 text-muted">Cargando…</div>');
        $modal.modal('show');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) {
                if (!res.ok) throw new Error('No se pudo cargar el contenido.');
                return res.text();
            })
            .then(function (html) {
                $body.html(html);
            })
            .catch(function () {
                $body.html('<div class="alert alert-danger mb-0">Error al cargar. Intenta de nuevo.</div>');
            });
    }

    function submitAjaxForm($form) {
        var $submit = $form.find('[type="submit"]');
        $submit.prop('disabled', true);
        showErrors($form, null);

        fetch($form.attr('action'), {
            method: 'POST',
            headers: csrfHeaders(),
            body: new FormData($form[0])
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) {
                    showErrors($form, result.data.errors || { general: 'Error al guardar.' });
                    return;
                }
                $modal.modal('hide');
                window.location.reload();
            })
            .catch(function () {
                showErrors($form, { general: 'Error de conexión.' });
            })
            .finally(function () {
                $submit.prop('disabled', false);
            });
    }

    $(document).on('click', '.btn-modal-tag, .btn-modal-tag-categoria', function () {
        loadModal($(this).data('url'), $(this).data('title'));
    });

    $(document).on('submit', '#appModalBody form[data-ajax-form="tag"], #appModalBody form[data-ajax-form="tag-categoria"]', function (e) {
        e.preventDefault();
        submitAjaxForm($(this));
    });

    $(document).on('click', '.btn-eliminar-tag', function () {
        postEliminar($(this).data('url'), $(this).data('nombre') || 'tag', 'el tag');
    });

    $(document).on('click', '.btn-eliminar-tag-categoria', function () {
        postEliminar($(this).data('url'), $(this).data('nombre') || 'categoría', 'la categoría');
    });

    $modal.on('hidden.bs.modal', function () {
        $body.empty();
    });
})(jQuery);
