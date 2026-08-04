/**
 * Modal de accesos a sucursales — selects en cascada y tabla editable.
 */
(function ($) {
    'use strict';

    function syncHiddenInputs($form) {
        var $container = $form.find('#accesosSucursalesHidden');
        $container.empty();

        $form.find('#tablaAccesosSucursales tbody tr').each(function () {
            var id = $(this).data('sucursal-id');
            if (id) {
                $container.append(
                    $('<input>', { type: 'hidden', name: 'sucursales[]', value: id })
                );
            }
        });
    }

    function toggleVacio($form) {
        var tieneFilas = $form.find('#tablaAccesosSucursales tbody tr').length > 0;
        $form.find('#accesosSucursalesVacio').toggleClass('d-none', tieneFilas);
    }

    function yaAsignada($form, sucursalId) {
        return $form.find('#tablaAccesosSucursales tbody tr[data-sucursal-id="' + sucursalId + '"]').length > 0;
    }

    var iconoTrash = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">'
        + '<polyline points="3 6 5 6 21 6"/>'
        + '<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>'
        + '</svg>';

    function agregarFila($form, datos) {
        if (yaAsignada($form, datos.id)) {
            return false;
        }

        var $tr = $('<tr>').attr('data-sucursal-id', datos.id);
        $tr.append($('<td>').text(datos.pais_nombre || '—'));
        $tr.append($('<td>').text(datos.ciudad || '—'));
        $tr.append($('<td>').text(datos.nombre || '—'));
        $tr.append(
            $('<td class="text-right text-nowrap">').append(
                $('<button type="button" class="btn btn-sm btn-outline-danger btn-baja-acceso-sucursal app-btn-icon-only">')
                    .attr('title', 'Dar de baja')
                    .attr('aria-label', 'Dar de baja sucursal')
                    .html(iconoTrash)
            )
        );

        $form.find('#tablaAccesosSucursales tbody').append($tr);
        syncHiddenInputs($form);
        toggleVacio($form);
        return true;
    }

    function resetSelect($select, placeholder, disabled) {
        $select.html('<option value="">' + placeholder + '</option>').prop('disabled', !!disabled);
    }

    function initAccesosSucursales() {
        var $form = $('#formUsuarioSucursalesModal');
        if (!$form.length) {
            return;
        }

        var $pais = $form.find('#accesoPais');
        var $ciudad = $form.find('#accesoCiudad');
        var $sucursal = $form.find('#accesoSucursal');
        var $btnAgregar = $form.find('#btnAgregarAccesoSucursal');
        var ciudadesBase = $form.data('ciudades-url');
        var sucursalesUrl = $form.data('sucursales-url');

        syncHiddenInputs($form);
        toggleVacio($form);

        $pais.off('change.accesos').on('change.accesos', function () {
            var paisId = $(this).val();
            resetSelect($ciudad, '— Cargando…', true);
            resetSelect($sucursal, '— Seleccionar ciudad —', true);
            $btnAgregar.prop('disabled', true);

            if (!paisId) {
                resetSelect($ciudad, '— Seleccionar país —', true);
                return;
            }

            fetch(ciudadesBase + '/' + paisId + '/ciudades', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    resetSelect($ciudad, '— Seleccionar —', false);
                    (data.ciudades || []).forEach(function (c) {
                        $ciudad.append($('<option>').val(c).text(c));
                    });
                })
                .catch(function () {
                    resetSelect($ciudad, 'Error al cargar', true);
                });
        });

        $ciudad.off('change.accesos').on('change.accesos', function () {
            var paisId = $pais.val();
            var ciudad = $(this).val();
            resetSelect($sucursal, '— Cargando…', true);
            $btnAgregar.prop('disabled', true);

            if (!paisId || !ciudad) {
                resetSelect($sucursal, '— Seleccionar ciudad —', true);
                return;
            }

            fetch(sucursalesUrl + '?pais_id=' + encodeURIComponent(paisId)
                + '&ciudad=' + encodeURIComponent(ciudad), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    resetSelect($sucursal, '— Seleccionar —', false);
                    (data.sucursales || []).forEach(function (s) {
                        $sucursal.append(
                            $('<option>')
                                .val(s.id)
                                .text(s.nombre)
                                .data('pais', s.pais_nombre)
                                .data('ciudad', s.ciudad)
                                .data('nombre', s.nombre)
                        );
                    });
                })
                .catch(function () {
                    resetSelect($sucursal, 'Error al cargar', true);
                });
        });

        $sucursal.off('change.accesos').on('change.accesos', function () {
            $btnAgregar.prop('disabled', !$(this).val());
        });

        $btnAgregar.off('click.accesos').on('click.accesos', function () {
            var $opt = $sucursal.find('option:selected');
            var id = parseInt($sucursal.val(), 10);
            if (!id) return;

            var agregado = agregarFila($form, {
                id: id,
                pais_nombre: $opt.data('pais') || $pais.find('option:selected').text(),
                ciudad: $opt.data('ciudad') || $ciudad.val(),
                nombre: $opt.data('nombre') || $opt.text()
            });

            if (!agregado) {
                window.alert('Esta sucursal ya está en la lista.');
            }
        });

        $form.off('click.accesos', '.btn-baja-acceso-sucursal')
            .on('click.accesos', '.btn-baja-acceso-sucursal', function () {
                $(this).closest('tr').remove();
                syncHiddenInputs($form);
                toggleVacio($form);
            });

        $form.off('submit.accesos-sync').on('submit.accesos-sync', function () {
            syncHiddenInputs($form);
        });
    }

    window.initAccesosSucursales = initAccesosSucursales;
})(jQuery);
