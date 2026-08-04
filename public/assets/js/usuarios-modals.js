/**
 * Modales AJAX — usuarios (CRUD)
 */
(function ($) {
    'use strict';

    var $modal = $('#appModal');
    var $body = $('#appModalBody');
    var $title = $('#appModalTitle');

    function csrfHeaders() {
        return {
            'X-Requested-With': 'XMLHttpRequest',
            [window.APP.csrfHeader]: window.APP.csrfToken
        };
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
                if (typeof window.initAccesosSucursales === 'function') {
                    window.initAccesosSucursales();
                }
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
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
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

    $(document).on('click', '.btn-modal-usuario, .btn-modal-usuario-action, .btn-modal-usuario-sucursales', function () {
        loadModal($(this).data('url'), $(this).data('title'));
    });

    $(document).on('submit', '#appModalBody form[data-ajax-form="usuario"]', function (e) {
        e.preventDefault();
        submitAjaxForm($(this));
    });

    $(document).on('submit', '#appModalBody form[data-ajax-form="usuario-sucursales"]', function (e) {
        e.preventDefault();
        submitAjaxForm($(this));
    });

    $(document).on('click', '.btn-eliminar-usuario', function () {
        var url = $(this).data('url');
        var nombre = $(this).data('nombre') || 'este usuario';

        if (!window.confirm('¿Eliminar a ' + nombre + '? Esta acción no se puede deshacer.')) {
            return;
        }

        var fd = new FormData();

        fetch(url, {
            method: 'POST',
            headers: csrfHeaders(),
            body: fd
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    window.alert(result.data.message || 'No se pudo eliminar el usuario.');
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                window.alert('Error de conexión.');
            });
    });

    $modal.on('hidden.bs.modal', function () {
        $body.empty();
    });
})(jQuery);
