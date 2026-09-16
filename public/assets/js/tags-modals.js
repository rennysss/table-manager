/**
 * Modales AJAX — tags y categorías (estilo Reservation Tags)
 */
(function ($) {
    'use strict';

    var $modal = $('#appModal');
    var $body = $('#appModalBody');
    var $title = $('#appModalTitle');
    var CSRF_FIELD = 'csrf_test_name';

    function csrfToken() {
        if (typeof window.APP.csrfTokenActual === 'function') {
            return window.APP.csrfTokenActual();
        }
        return window.APP.csrfToken;
    }

    function csrfHeaders() {
        return {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            [window.APP.csrfHeader]: csrfToken()
        };
    }

    function formDataConCsrf() {
        var fd = new FormData();
        var $csrf = $body.find('input[name="' + CSRF_FIELD + '"]').first();
        if ($csrf.length) {
            fd.append(CSRF_FIELD, $csrf.val());
        } else {
            fd.append(CSRF_FIELD, csrfToken());
        }
        return fd;
    }

    function postEliminar(url, nombre, etiqueta) {
        if (!url) {
            window.alert('No se encontró la URL de eliminación.');
            return;
        }

        if (!window.confirm('¿Eliminar ' + etiqueta + ' «' + nombre + '»? Dejará de aparecer en nuevas reservas.')) {
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: csrfHeaders(),
            credentials: 'same-origin',
            body: formDataConCsrf()
        })
            .then(function (res) {
                var ct = res.headers.get('content-type') || '';
                if (ct.indexOf('application/json') === -1) {
                    return { ok: false, data: { message: 'Respuesta inválida del servidor (¿sesión expirada?). Recarga la página.' } };
                }
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
            credentials: 'same-origin',
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

    $(document).on('click', '.btn-eliminar-tag', function (e) {
        e.preventDefault();
        e.stopPropagation();
        postEliminar($(this).data('url'), $(this).data('nombre') || 'tag', 'el tag');
    });

    $(document).on('click', '.btn-eliminar-tag-categoria', function (e) {
        e.preventDefault();
        e.stopPropagation();
        postEliminar($(this).data('url'), $(this).data('nombre') || 'categoría', 'la categoría');
    });

    $modal.on('hidden.bs.modal', function () {
        $body.empty();
    });
})(jQuery);
