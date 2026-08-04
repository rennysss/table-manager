/**
 * Modales AJAX — sucursales, ambientes y floorplans
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

    function cargarEstadosPorPais($pais, $estado, selectedId) {
        var paisId = $pais.val();
        var baseUrl = $pais.data('estados-url') || '';

        $estado.prop('disabled', true).html('<option value="">Cargando…</option>');

        if (!paisId) {
            $estado.html('<option value="">— Seleccionar país primero —</option>').prop('disabled', true);
            return;
        }

        fetch(baseUrl + '/' + paisId + '/estados', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var html = '<option value="">— Seleccionar —</option>';
                (data.estados || []).forEach(function (e) {
                    var sel = String(selectedId) === String(e.id) ? ' selected' : '';
                    html += '<option value="' + e.id + '"' + sel + '>' + e.nombre + '</option>';
                });
                $estado.html(html).prop('disabled', false);
            })
            .catch(function () {
                $estado.html('<option value="">Error al cargar estados</option>').prop('disabled', true);
            });
    }

    function initFormSucursal() {
        var $pais = $body.find('#sucursalPais');
        var $estado = $body.find('#sucursalEstado');

        if (!$pais.length || !$estado.length) {
            return;
        }

        $pais.on('change', function () {
            cargarEstadosPorPais($pais, $estado, '');
        });
    }

    function initModalContent() {
        initFormSucursal();
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
                initModalContent();
            })
            .catch(function () {
                $body.html('<div class="alert alert-danger mb-0">Error al cargar. Intenta de nuevo.</div>');
            });
    }

    function reloadModalFromForm($form) {
        var reloadUrl = $form.data('reload-url');
        if (reloadUrl) {
            loadModal(reloadUrl, $title.text());
            return true;
        }
        return false;
    }

    function submitAjaxForm($form, options) {
        options = options || {};
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

                if (options.onSuccess) {
                    options.onSuccess(result.data);
                    return;
                }

                if (reloadModalFromForm($form)) {
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

    $(document).on('click', '.btn-modal-sucursal', function () {
        loadModal($(this).data('url'), $(this).data('title'));
    });

    $(document).on('click', '.btn-modal-sucursal-action', function () {
        loadModal($(this).data('url'), $(this).data('title'));
    });

    $(document).on('submit', '#appModalBody form[data-ajax-form="sucursal"]', function (e) {
        e.preventDefault();
        submitAjaxForm($(this));
    });

    $(document).on('submit', '#appModalBody form[data-ajax-form="ambiente"]', function (e) {
        e.preventDefault();
        submitAjaxForm($(this));
    });

    $(document).on('submit', '#appModalBody form[data-ajax-form="ambiente-edit"]', function (e) {
        e.preventDefault();
        submitAjaxForm($(this));
    });

    $(document).on('submit', '#appModalBody form[data-ajax-form="floorplan-create"]', function (e) {
        e.preventDefault();
        var $form = $(this);
        submitAjaxForm($form, {
            onSuccess: function (data) {
                if (data.editor_url) {
                    window.location.href = data.editor_url;
                    return;
                }
                reloadModalFromForm($form);
            }
        });
    });

    $(document).on('submit', '#appModalBody form.form-activar-floorplan', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $submit = $form.find('[type="submit"]');
        $submit.prop('disabled', true);

        fetch($form.attr('action'), {
            method: 'POST',
            headers: csrfHeaders(),
            body: new FormData($form[0])
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) return;
                reloadModalFromForm($form);
            })
            .finally(function () {
                $submit.prop('disabled', false);
            });
    });

    $modal.on('hidden.bs.modal', function () {
        $body.empty();
    });
})(jQuery);
