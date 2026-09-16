/**
 * Módulo Hostess — DataTable, tags, detalle reserva
 */
var HostessApp = (function () {
    'use strict';

    var config = {};
    var reservaSeleccionada = null;
    var tagsReservaSeleccionados = [];
    var tagsClienteSeleccionados = [];
    var tagsReservaBorrador = [];
    var tagsClienteBorrador = [];
    var xetuxOrdenActiva = null;
    var xetuxCatalogoCargado = false;
    var tablaReservas = null;
    var cacheReservas = {};
    var cacheAsignaciones = {};
    var ultimasReservasTabla = [];
    var inicializandoFiltros = false;

    var dtIdioma = {
        lengthMenu: 'Mostrar _MENU_ entradas',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ entradas',
        infoEmpty: 'Sin registros',
        infoFiltered: '(filtrado de _MAX_ entradas)',
        emptyTable: 'No hay reservas para esta fecha.',
        zeroRecords: 'No hay coincidencias',
        paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
    };

    function opcionesDataTable(totalFilas) {
        return {
            order: [[0, 'asc']],
            pageLength: 25,
            dom: totalFilas > 25 ? '<"hostess-dt-top"l>rt<"hostess-dt-bottom"ip>' : 'rt',
            language: dtIdioma,
            searching: false,
            deferRender: true,
            autoWidth: false,
            columns: [
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true }
            ],
            createdRow: function (row, data, dataIndex) {
                var reserva = ultimasReservasTabla[dataIndex];
                if (!reserva && data && data.DT_RowAttr && data.DT_RowAttr['data-codigo']) {
                    return;
                }
                var codigo = $(row).attr('data-codigo') || (reserva && reserva.codigo);
                if (!codigo && reserva) codigo = reserva.codigo;
                if (!reserva && !codigo) return;

                if (reserva) {
                    $(row)
                        .attr('data-codigo', reserva.codigo)
                        .addClass('hostess-row')
                        .attr('role', 'button')
                        .attr('tabindex', '0')
                        .attr('aria-label', 'Seleccionar reserva ' + reserva.codigo);
                }
                $('td', row).eq(0).attr('data-label', 'Reserva');
                $('td', row).eq(1).attr('data-label', 'Nombre');
                $('td', row).eq(2).attr('data-label', 'RP');
                $('td', row).eq(3).addClass('mesa-cell').attr('data-label', 'Mesa');
            }
        };
    }

    function init(cfg) {
        config = cfg;

        sincronizarCache(cfg.reservasIniciales || [], cfg.asignacionesIniciales || {});

        // Si quedó una instancia previa (navegación/cache), la destruye
        if ($.fn.DataTable.isDataTable('#tablaReservas')) {
            $('#tablaReservas').DataTable().clear().destroy();
        }
        tablaReservas = $('#tablaReservas').DataTable(opcionesDataTable(ultimasReservasTabla.length));

        $('#btnFechaAnterior').on('click', function () {
            $('#fechaReservas').val(sumarDias($('#fechaReservas').val(), -1));
        });

        $('#btnFechaSiguiente').on('click', function () {
            $('#fechaReservas').val(sumarDias($('#fechaReservas').val(), 1));
        });

        $('#btnBuscarReservas').on('click', aplicarFiltros);

        $('#cerrarDetalle').on('click', cerrarDetalle);

        $('#btnAgregarPax').on('click', function () {
            if (!reservaSeleccionada) return;
            var asig = cacheAsignaciones[reservaSeleccionada.codigo];
            if (asig && asig.estado_mesa === 'liberada') {
                alert('La reserva ya fue liberada.');
                return;
            }
            $('#inputPaxDelta').val(1);
            var reservados = (asig && asig.pax_reservados != null)
                ? asig.pax_reservados
                : (reservaSeleccionada.pax != null ? reservaSeleccionada.pax : '—');
            var enMesa = asig ? (asig.pax_en_mesa || 0) : 0;
            var hint = 'Reservados: ' + reservados + ' · En mesa ahora: ' + enMesa;
            if (!asig || !asig.mesa_id) {
                hint += ' · Puedes registrar llegada sin mesa (status Arrived)';
            }
            $('#modalPaxHint').text(hint);
            $('#modalAgregarPax').modal('show');
        });

        $('#btnConfirmarPax').on('click', function () {
            var delta = parseInt($('#inputPaxDelta').val(), 10);
            if (!delta || delta < 1) {
                alert('Indica al menos 1 pax.');
                return;
            }
            var body = {
                reserva_codigo: reservaSeleccionada.codigo,
                pax_delta: delta,
                fecha: config.fecha || '',
                pax: reservaSeleccionada.pax || null,
                cliente_nombre: reservaSeleccionada.nombre || ''
            };
            postCheckin(config.llegadaUrl, body, function () {
                $('#modalAgregarPax').modal('hide');
            });
        });

        $(document).on('click', '.hostess-row', function () {
            cargarReserva($(this).data('codigo'));
        });

        $(document).on('keydown', '.hostess-row', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                cargarReserva($(this).data('codigo'));
            }
        });

        $('#btnAbrirTagsReserva').on('click', abrirModalTagsReserva);
        $('#btnAbrirTagsCliente').on('click', abrirModalTagsCliente);

        $(document).on('click', '.hostess-tag-option-reserva', function () {
            toggleTagBorrador(JSON.parse($(this).attr('data-tag')), 'reserva');
        });
        $(document).on('click', '.hostess-tag-option-cliente', function () {
            toggleTagBorrador(JSON.parse($(this).attr('data-tag')), 'cliente');
        });

        $(document).on('click', '.hostess-tag-selected-x', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id = parseInt($(this).data('tag-id'), 10);
            var modo = $(this).data('modo') || 'reserva';
            if (modo === 'cliente') {
                tagsClienteBorrador = tagsClienteBorrador.filter(function (t) { return parseInt(t.id, 10) !== id; });
            } else {
                tagsReservaBorrador = tagsReservaBorrador.filter(function (t) { return parseInt(t.id, 10) !== id; });
            }
            sincronizarModalTags(modo);
        });

        $(document).on('click', '.hostess-tags-cat-toggle', function () {
            var $cat = $(this).closest('.hostess-tags-cat');
            var abierto = $cat.hasClass('is-collapsed');
            $cat.toggleClass('is-collapsed', !abierto);
            $(this).attr('aria-expanded', abierto ? 'true' : 'false');
        });

        $('#inputBuscarTagsReserva').on('input', function () {
            filtrarCatalogoTags($(this).val(), '#modalTagsReservaCatalog');
        });
        $('#inputBuscarTagsCliente').on('input', function () {
            filtrarCatalogoTags($(this).val(), '#modalTagsClienteCatalog');
        });

        $('#btnGuardarTagsReserva').on('click', function () {
            tagsReservaSeleccionados = tagsReservaBorrador.slice();
            renderTagsEnContenedor('#detalleTagsReserva', tagsReservaSeleccionados, 'Toca para agregar tags de reserva');
            guardarTags('reserva');
            $('#modalTagsReserva').modal('hide');
            if (reservaSeleccionada) {
                setTimeout(function () { cargarActividad(reservaSeleccionada.codigo); }, 300);
            }
        });
        $('#btnGuardarTagsCliente').on('click', function () {
            tagsClienteSeleccionados = tagsClienteBorrador.slice();
            renderTagsEnContenedor('#detalleTagsCliente', tagsClienteSeleccionados, 'Toca para agregar tags de cliente');
            guardarTags('cliente');
            $('#modalTagsCliente').modal('hide');
            if (reservaSeleccionada) {
                setTimeout(function () { cargarActividad(reservaSeleccionada.codigo); }, 300);
            }
        });

        $('#btnSentarXetux').on('click', sentarEnXetux);
        $('#btnCancelarXetux').on('click', cancelarXetux);
        $('#btnCerrarCuentaXetux').on('click', cerrarCuentaXetux);

        initFiltrosUbicacion();
        abrirReservaDesdeQuery();
    }

    /** Si vuelve del plano con ?reserva=, abre el detalle. */
    function abrirReservaDesdeQuery() {
        try {
            var params = new URLSearchParams(window.location.search);
            var codigo = params.get('reserva');
            if (codigo) {
                cargarReserva(codigo);
            }
        } catch (e) { /* ignore */ }
    }

    function initFiltrosUbicacion() {
        var precargados = config.filtrosUbicacion;

        if (precargados && precargados.paises && precargados.paises.length) {
            poblarSelectPaises(precargados.paises);

            if (config.sucursalPaisId) {
                $('#filtroPais').val(String(config.sucursalPaisId));
                poblarSelectCiudades(precargados.ciudades || []);

                if (config.sucursalCiudad) {
                    $('#filtroCiudad').val(config.sucursalCiudad);
                }

                poblarSelectEstablecimientos(precargados.sucursales || []);

                if (config.sucursalId) {
                    $('#filtroEstablecimiento').val(String(config.sucursalId));
                }
            }

            enlazarEventosFiltros();
            return;
        }

        if (!config.apiPaises) return;

        inicializandoFiltros = true;

        cargarPaisesFiltro().then(function () {
            var paisId = config.sucursalPaisId;
            if (!paisId) {
                inicializandoFiltros = false;
                return;
            }

            $('#filtroPais').val(String(paisId));

            return cargarCiudadesFiltro(paisId).then(function () {
                if (config.sucursalCiudad) {
                    $('#filtroCiudad').val(config.sucursalCiudad);
                }

                return cargarEstablecimientosFiltro(paisId, $('#filtroCiudad').val()).then(function () {
                    if (config.sucursalId) {
                        $('#filtroEstablecimiento').val(String(config.sucursalId));
                    }
                    inicializandoFiltros = false;
                });
            });
        }).catch(function () {
            inicializandoFiltros = false;
        });

        enlazarEventosFiltros();
    }

    function enlazarEventosFiltros() {
        $('#filtroPais').off('change.hostess').on('change.hostess', function () {
            var paisId = $(this).val();
            resetSelect($('#filtroCiudad'), 'Seleccione país');
            resetSelect($('#filtroEstablecimiento'), 'Seleccione ciudad');

            if (!paisId) return;

            cargarCiudadesFiltro(paisId);
        });

        $('#filtroCiudad').off('change.hostess').on('change.hostess', function () {
            var paisId = $('#filtroPais').val();
            var ciudad = $(this).val();
            resetSelect($('#filtroEstablecimiento'), 'Seleccione ciudad');

            if (!paisId || !ciudad) return;

            cargarEstablecimientosFiltro(paisId, ciudad);
        });
    }

    function poblarSelectPaises(paises) {
        var $sel = $('#filtroPais');
        $sel.empty().append($('<option value=""></option>').text('Seleccionar'));
        paises.forEach(function (p) {
            $sel.append($('<option></option>').val(p.id).text(p.nombre));
        });
    }

    function poblarSelectCiudades(ciudades) {
        var $sel = $('#filtroCiudad');
        $sel.empty().append($('<option value=""></option>').text('Seleccionar'));
        ciudades.forEach(function (c) {
            $sel.append($('<option></option>').val(c).text(c));
        });
        $sel.prop('disabled', !ciudades.length);
    }

    function poblarSelectEstablecimientos(sucursales) {
        var $sel = $('#filtroEstablecimiento');
        $sel.empty().append($('<option value=""></option>').text('Seleccionar'));
        sucursales.forEach(function (s) {
            $sel.append($('<option></option>').val(s.id).text(s.nombre));
        });
        $sel.prop('disabled', !sucursales.length);
    }

    function sincronizarCache(reservas, asignaciones) {
        cacheReservas = {};
        cacheAsignaciones = asignaciones || {};
        ultimasReservasTabla = [];

        (reservas || []).forEach(function (r) {
            if (r && r.codigo) {
                cacheReservas[r.codigo] = r;
                ultimasReservasTabla.push(r);
            }
        });
    }

    function resetSelect($select, placeholder) {
        $select.empty().append($('<option value=""></option>').text(placeholder)).prop('disabled', true);
    }

    function cargarPaisesFiltro() {
        return fetch(config.apiPaises, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var $sel = $('#filtroPais');
                $sel.empty().append($('<option value=""></option>').text('Seleccionar'));

                (data.paises || []).forEach(function (p) {
                    $sel.append($('<option></option>').val(p.id).text(p.nombre));
                });
            });
    }

    function cargarCiudadesFiltro(paisId) {
        var $sel = $('#filtroCiudad');
        $sel.prop('disabled', true).html('<option value="">Cargando…</option>');

        return fetch(config.apiCiudades + '/' + paisId + '/ciudades', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                $sel.empty().append($('<option value=""></option>').text('Seleccionar'));

                (data.ciudades || []).forEach(function (c) {
                    $sel.append($('<option></option>').val(c).text(c));
                });

                $sel.prop('disabled', !(data.ciudades && data.ciudades.length));
            });
    }

    function cargarEstablecimientosFiltro(paisId, ciudad) {
        var $sel = $('#filtroEstablecimiento');
        $sel.prop('disabled', true).html('<option value="">Cargando…</option>');

        var url = config.apiSucursales + '?pais_id=' + encodeURIComponent(paisId) +
            '&ciudad=' + encodeURIComponent(ciudad);

        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                $sel.empty().append($('<option value=""></option>').text('Seleccionar'));

                (data.sucursales || []).forEach(function (s) {
                    $sel.append($('<option></option>').val(s.id).text(s.nombre));
                });

                $sel.prop('disabled', !(data.sucursales && data.sucursales.length));
            });
    }

    function cargarReserva(codigo) {
        var identificador = normalizarCodigoQR(codigo);
        var reservaLocal = cacheReservas[identificador];

        if (reservaLocal) {
            reservaSeleccionada = reservaLocal;
            mostrarDetalle(reservaLocal, cacheAsignaciones[identificador] || null);
            refrescarXetuxOrden(identificador);
            return;
        }

        var url = config.detalleUrl + '/' + encodeURIComponent(identificador) + '/detalle';

        if (config.sucursalId) {
            url += '?sucursal_id=' + encodeURIComponent(config.sucursalId);
        }
        if (config.fecha) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'fecha=' + encodeURIComponent(config.fecha);
        }

        fetch(url, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) {
                alert(data.error);
                return;
            }
            reservaSeleccionada = data.reserva;
            mostrarDetalle(data.reserva, data.asignacion, data.actividad || null, data.xetux_orden || null);
        })
        .catch(function () { alert('Error al cargar reserva'); });
    }

    function mostrarDetalle(reserva, asignacion, actividadPrecargada, xetuxOrden) {
        reservaSeleccionada = reserva;
        if (asignacion) {
            cacheAsignaciones[reserva.codigo] = asignacion;
        }

        $('#detalleNombre').text(reserva.nombre || '—');
        $('#detalleCodigo').text(reserva.codigo || '—');
        $('#detalleHora').text(reserva.hora || '—');
        $('#detalleRP').text(reserva.rp || '—');

        var paxReservados = (asignacion && asignacion.pax_reservados != null)
            ? asignacion.pax_reservados
            : (reserva.pax != null ? reserva.pax : '—');
        var paxMesa = asignacion ? (asignacion.pax_en_mesa || 0) : 0;
        $('#detallePax').text(paxReservados);
        $('#detallePaxMesa').text(paxMesa);

        renderFinance(reserva);

        var status = asignacion ? (asignacion.status_label || 'Asignada') : 'Sin asignar';
        // Table en blanco hasta que haya mesa asignada
        var mesaNum = '';
        if (asignacion && asignacion.mesa_id && asignacion.mesa_numero != null && asignacion.mesa_numero !== '') {
            mesaNum = String(asignacion.mesa_numero);
        }
        $('#detalleStatus').text(status).attr('data-status', status);
        $('#detalleMesa').text(mesaNum);

        renderLlegadas(asignacion && asignacion.llegadas ? asignacion.llegadas : []);
        actualizarBotonesCheckin(asignacion);

        $('.hostess-row').removeClass('selected');
        $('[data-codigo="' + reserva.codigo + '"]').addClass('selected');

        var tagsReserva = tagsDesdeAsignacion(asignacion);
        if (tagsReserva === null) {
            tagsReserva = [];
        }
        tagsReservaSeleccionados = normalizarListaTags(tagsReserva);
        tagsClienteSeleccionados = normalizarListaTags(
            Array.isArray(reserva.cliente_tags) ? reserva.cliente_tags : []
        );
        renderTagsEnContenedor('#detalleTagsReserva', tagsReservaSeleccionados, 'Toca para agregar tags de reserva');
        renderTagsEnContenedor('#detalleTagsCliente', tagsClienteSeleccionados, 'Toca para agregar tags de cliente');

        xetuxOrdenActiva = xetuxOrden || null;
        aplicarEstadoXetux();
        if (!xetuxCatalogoCargado) {
            cargarCatalogosXetux();
        }

        document.getElementById('panelDetalle').hidden = false;
        if (Array.isArray(actividadPrecargada)) {
            renderActividad(actividadPrecargada);
        } else {
            cargarActividad(reserva.codigo);
        }
    }

    function refrescarXetuxOrden(codigo) {
        var url = config.detalleUrl + '/' + encodeURIComponent(codigo) + '/detalle';
        if (config.sucursalId) {
            url += '?sucursal_id=' + encodeURIComponent(config.sucursalId);
        }
        if (config.fecha) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'fecha=' + encodeURIComponent(config.fecha);
        }
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.error) return;
                xetuxOrdenActiva = data.xetux_orden || null;
                aplicarEstadoXetux();
            })
            .catch(function () { /* ignore */ });
    }

    function cargarActividad(codigo) {
        var $feed = $('#detalleActividad');
        if (!codigo || !config.actividadUrl) {
            $feed.empty();
            return;
        }
        fetch(config.actividadUrl + '?codigo=' + encodeURIComponent(codigo), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    return { ok: r.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    $feed.empty();
                    return;
                }
                renderActividad(result.data.eventos || []);
            })
            .catch(function () {
                $feed.empty();
            });
    }

    function renderActividad(eventos) {
        var $feed = $('#detalleActividad').empty();
        if (!eventos.length) {
            return;
        }

        var grupos = {};
        var ordenDias = [];
        eventos.forEach(function (ev) {
            var clave = claveDiaActividad(ev.created_at);
            if (!grupos[clave]) {
                grupos[clave] = [];
                ordenDias.push(clave);
            }
            grupos[clave].push(ev);
        });

        ordenDias.forEach(function (clave) {
            $feed.append($('<div class="hostess-activity-day"></div>').text(etiquetaDiaActividad(clave)));
            grupos[clave].forEach(function (ev) {
                var $item = $('<div class="hostess-activity-item"></div>');
                $item.append('<span class="hostess-activity-dot" aria-hidden="true"></span>');
                var $body = $('<div class="hostess-activity-body"></div>');
                var meta = (ev.usuario_nombre || 'Sistema');
                if (ev.origen) meta += ' · ' + ev.origen;
                meta += ' · ' + formatearHoraActividad(ev.created_at);
                $body.append($('<div class="hostess-activity-meta"></div>').text(meta));
                $body.append($('<div class="hostess-activity-desc"></div>').text(ev.descripcion || ''));
                $item.append($body);
                $feed.append($item);
            });
        });
    }

    function claveDiaActividad(fechaIso) {
        if (!fechaIso) return 'sin-fecha';
        var d = new Date(String(fechaIso).replace(' ', 'T'));
        if (isNaN(d.getTime())) return String(fechaIso).substring(0, 10) || 'sin-fecha';
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function etiquetaDiaActividad(clave) {
        if (clave === 'sin-fecha') return 'SIN FECHA';
        var partes = clave.split('-');
        if (partes.length !== 3) return clave;
        var meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
        var mes = meses[parseInt(partes[1], 10) - 1] || partes[1];
        return mes + ' ' + parseInt(partes[2], 10) + ', ' + partes[0];
    }

    function formatearHoraActividad(fechaIso) {
        if (!fechaIso) return '—';
        var d = new Date(String(fechaIso).replace(' ', 'T'));
        if (isNaN(d.getTime())) return String(fechaIso);
        return d.toLocaleString('es-MX', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function normalizarListaTags(lista) {
        if (!Array.isArray(lista)) return [];
        return lista.map(function (t) {
            if (typeof t === 'string') return { nombre: t };
            return t;
        }).filter(function (t) { return t && t.nombre; });
    }

    function renderTagsEnContenedor(selector, tags, vacioTexto) {
        var $box = $(selector).empty();
        if (!tags || !tags.length) {
            $box.append('<span class="hostess-tags-empty">' + vacioTexto + '</span>');
            return;
        }
        tags.forEach(function (t) {
            var bg = t.color || '#007AFF';
            var fg = colorTextoTag(bg);
            $box.append(
                $('<span class="tag-pill hostess-tag-view"></span>')
                    .text(t.nombre)
                    .css({ backgroundColor: bg, color: fg })
            );
        });
    }

    function colorTextoTag(hex) {
        var h = String(hex || '').replace('#', '');
        if (h.length !== 6) return '#111111';
        var r = parseInt(h.substr(0, 2), 16);
        var g = parseInt(h.substr(2, 2), 16);
        var b = parseInt(h.substr(4, 2), 16);
        var luma = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luma < 0.55 ? '#FFFFFF' : '#111111';
    }

    function abrirModalTagsReserva() {
        if (!reservaSeleccionada) return;
        tagsReservaBorrador = tagsReservaSeleccionados.slice();
        $('#inputBuscarTagsReserva').val('');
        filtrarCatalogoTags('', '#modalTagsReservaCatalog');
        sincronizarModalTags('reserva');
        $('#modalTagsReserva').modal('show');
    }

    function abrirModalTagsCliente() {
        if (!reservaSeleccionada) return;
        tagsClienteBorrador = tagsClienteSeleccionados.slice();
        $('#inputBuscarTagsCliente').val('');
        filtrarCatalogoTags('', '#modalTagsClienteCatalog');
        sincronizarModalTags('cliente');
        $('#modalTagsCliente').modal('show');
    }

    function toggleTagBorrador(tag, modo) {
        var lista = modo === 'cliente' ? tagsClienteBorrador : tagsReservaBorrador;
        var id = parseInt(tag.id, 10);
        var idx = -1;
        lista.forEach(function (t, i) {
            if (parseInt(t.id, 10) === id || t.nombre === tag.nombre) idx = i;
        });
        if (idx >= 0) {
            lista.splice(idx, 1);
        } else {
            lista.push(tag);
        }
        if (modo === 'cliente') {
            tagsClienteBorrador = lista;
        } else {
            tagsReservaBorrador = lista;
        }
        sincronizarModalTags(modo);
    }

    function sincronizarModalTags(modo) {
        var borrador = modo === 'cliente' ? tagsClienteBorrador : tagsReservaBorrador;
        var $sel = modo === 'cliente' ? $('#modalTagsClienteSelected') : $('#modalTagsReservaSelected');
        var opcionClass = modo === 'cliente' ? '.hostess-tag-option-cliente' : '.hostess-tag-option-reserva';
        var $catalog = modo === 'cliente' ? $('#modalTagsClienteCatalog') : $('#modalTagsReservaCatalog');

        $sel.empty();
        borrador.forEach(function (t) {
            var bg = t.color || '#007AFF';
            var fg = colorTextoTag(bg);
            var $pill = $('<span class="tag-pill hostess-tag-selected"></span>')
                .css({ backgroundColor: bg, color: fg });
            $pill.append($('<span></span>').text(t.nombre));
            $pill.append(
                $('<button type="button" class="hostess-tag-selected-x" aria-label="Quitar tag">&times;</button>')
                    .attr('data-tag-id', t.id)
                    .attr('data-modo', modo)
            );
            $sel.append($pill);
        });

        $catalog.find(opcionClass).each(function () {
            var tag = JSON.parse($(this).attr('data-tag'));
            var on = borrador.some(function (t) {
                return parseInt(t.id, 10) === parseInt(tag.id, 10) || t.nombre === tag.nombre;
            });
            $(this).toggleClass('is-on', on);
        });
    }

    function filtrarCatalogoTags(q, catalogSelector) {
        q = String(q || '').trim().toLowerCase();
        $(catalogSelector).find('.hostess-tags-cat').each(function () {
            var $cat = $(this);
            var visibles = 0;
            $cat.find('.hostess-tag-option').each(function () {
                var nombre = $(this).attr('data-nombre') || '';
                var ok = !q || nombre.indexOf(q) !== -1;
                $(this).toggle(ok);
                if (ok) visibles++;
            });
            $cat.toggle(visibles > 0 || !q);
            if (q) $cat.removeClass('is-collapsed');
        });
    }

    function cargarCatalogosXetux() {
        if (!config.xetuxMeserosUrl || !config.xetuxMesasUrl) return;

        fetch(config.xetuxMeserosUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var $sel = $('#selectMeseroXetux').empty().append('<option value="">— Seleccionar —</option>');
                if (data.success && data.items) {
                    data.items.forEach(function (it) {
                        $sel.append($('<option></option>').val(it.id).text(it.label));
                    });
                }
                $sel.prop('disabled', false);
            })
            .catch(function () {
                $('#selectMeseroXetux').prop('disabled', false);
            });

        fetch(config.xetuxMesasUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var $sel = $('#selectMesaXetux').empty().append('<option value="">— Seleccionar —</option>');
                if (data.success && data.items) {
                    data.items.forEach(function (it) {
                        $sel.append($('<option></option>').val(it.id).text(it.label));
                    });
                }
                $sel.prop('disabled', false);
            })
            .catch(function () {
                $('#selectMesaXetux').prop('disabled', false);
            });

        xetuxCatalogoCargado = true;
    }

    function aplicarEstadoXetux() {
        var activa = xetuxOrdenActiva && xetuxOrdenActiva.estatus === 'activa';
        $('#btnSentarXetux').prop('hidden', !!activa);
        $('#detalleXetuxPostSentar').prop('hidden', !activa);
        $('#selectMeseroXetux, #selectMesaXetux').prop('disabled', !!activa);

        if (activa) {
            var meta = 'Orden Xetux: ' + (xetuxOrdenActiva.order_id || '—') +
                ' · Suborden: ' + (xetuxOrdenActiva.suborder_id || '—');
            $('#detalleXetuxMeta').text(meta).removeAttr('hidden');
            if (xetuxOrdenActiva.waiter_id) {
                $('#selectMeseroXetux').val(String(xetuxOrdenActiva.waiter_id));
            }
            if (xetuxOrdenActiva.space_id) {
                $('#selectMesaXetux').val(String(xetuxOrdenActiva.space_id));
            }
        } else {
            $('#detalleXetuxMeta').attr('hidden', true).text('');
        }
    }

    function sentarEnXetux() {
        if (!reservaSeleccionada || !config.xetuxSentarUrl) return;
        var waiterId = parseInt($('#selectMeseroXetux').val(), 10);
        var spaceId = parseInt($('#selectMesaXetux').val(), 10);
        if (!waiterId || !spaceId) {
            alert('Selecciona mesero y mesa.');
            return;
        }
        var body = {
            reserva_codigo: reservaSeleccionada.codigo,
            waiter_id: waiterId,
            space_id: spaceId,
            fecha: config.fecha || '',
            stripe_reference: reservaSeleccionada.stripe_reference || ''
        };
        fetch(config.xetuxSentarUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                [window.APP.csrfHeader]: window.APP.csrfTokenActual()
            },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    alert((result.data && result.data.error) || 'No se pudo sentar en Xetux.');
                    return;
                }
                if (result.data.prepago_warning) {
                    alert('Orden creada, pero prepago: ' + result.data.prepago_warning);
                }
                xetuxOrdenActiva = result.data.xetux_orden || null;
                aplicarEstadoXetux();
                if (result.data.asignacion && reservaSeleccionada) {
                    cacheAsignaciones[reservaSeleccionada.codigo] = result.data.asignacion;
                    mostrarDetalle(reservaSeleccionada, result.data.asignacion, null, xetuxOrdenActiva);
                }
                cargarActividad(reservaSeleccionada.codigo);
            })
            .catch(function () {
                alert('Error de conexión con Xetux.');
            });
    }

    function cancelarXetux() {
        if (!reservaSeleccionada || !config.xetuxCancelarUrl) return;
        if (!confirm('¿Cancelar la orden en Xetux y liberar la reserva?')) return;
        postCheckin(config.xetuxCancelarUrl, { reserva_codigo: reservaSeleccionada.codigo }, function () {
            xetuxOrdenActiva = null;
            aplicarEstadoXetux();
        });
    }

    function cerrarCuentaXetux() {
        if (!reservaSeleccionada || !config.xetuxCerrarUrl) return;
        fetch(config.xetuxCerrarUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                [window.APP.csrfHeader]: window.APP.csrfTokenActual()
            },
            credentials: 'same-origin',
            body: JSON.stringify({ reserva_codigo: reservaSeleccionada.codigo })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    alert((result.data && result.data.error) || 'No se pudo consultar el cierre.');
                    return;
                }
                xetuxOrdenActiva = result.data.xetux_orden || null;
                aplicarEstadoXetux();
                console.info('Xetux order/info', result.data.order_info);
                alert('Totales obtenidos de Xetux. El envío al destino final se configurará en el siguiente paso.');
                cargarActividad(reservaSeleccionada.codigo);
            })
            .catch(function () {
                alert('Error de conexión.');
            });
    }

    function renderFinance(reserva) {
        var moneda = reserva.moneda || '';
        var prepago = reserva.prepago;
        var total = reserva.total;
        var balance = reserva.balance;
        var tarjeta = reserva.tarjeta || null;
        var hayTarjeta = !!(tarjeta && (tarjeta.numero || tarjeta.banco || tarjeta.pais || tarjeta.brand || tarjeta.nombre));
        var hayAlgo = prepago != null || total != null || balance != null || hayTarjeta;

        if (!hayAlgo) {
            $('#detalleFinance').attr('hidden', true);
            return;
        }

        function fmt(valor) {
            if (valor == null || valor === '') return '—';
            var n = Number(valor);
            if (isNaN(n)) return String(valor);
            var txt = n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return moneda ? (txt + ' ' + moneda) : txt;
        }

        function filaTexto(rowId, spanId, valor) {
            if (valor) {
                $(spanId).text(valor);
                $(rowId).removeAttr('hidden');
                return true;
            }
            $(rowId).attr('hidden', true);
            return false;
        }

        if (prepago != null) {
            $('#detallePrepago').text(fmt(prepago));
            $('#detallePrepagoRow').removeAttr('hidden');
        } else {
            $('#detallePrepagoRow').attr('hidden', true);
        }

        if (total != null) {
            $('#detalleTotal').text(fmt(total));
            $('#detalleTotalRow').removeAttr('hidden');
        } else {
            $('#detalleTotalRow').attr('hidden', true);
        }

        if (balance != null) {
            $('#detalleBalance').text(fmt(balance));
            $('#detalleBalanceRow').removeAttr('hidden');
        } else {
            $('#detalleBalanceRow').attr('hidden', true);
        }

        if (hayTarjeta) {
            filaTexto('#detalleTarjetaNombreRow', '#detalleTarjetaNombre', tarjeta.nombre);
            filaTexto('#detalleTarjetaBrandRow', '#detalleTarjetaBrand', tarjeta.brand);
            filaTexto('#detalleTarjetaNumeroRow', '#detalleTarjetaNumero', tarjeta.numero);
            filaTexto('#detalleTarjetaBancoRow', '#detalleTarjetaBanco', tarjeta.banco);
            filaTexto('#detalleTarjetaPaisRow', '#detalleTarjetaPais', tarjeta.pais);
            $('#detalleTarjeta').removeAttr('hidden');
        } else {
            $('#detalleTarjeta').attr('hidden', true);
        }

        $('#detalleFinance').removeAttr('hidden');
    }

    function renderLlegadas(llegadas) {
        var $wrap = $('#detalleLlegadasWrap');
        var $list = $('#detalleLlegadas').empty();
        if (!llegadas || !llegadas.length) {
            $wrap.attr('hidden', true);
            return;
        }
        llegadas.forEach(function (l) {
            var hora = (l.created_at || '').substring(11, 16) || '—';
            var signo = l.pax_delta > 0 ? '+' : '';
            $list.append(
                '<li><span class="hostess-llegada-hora">' + hora + '</span> ' +
                signo + l.pax_delta + ' pax → ' + l.pax_resultante + ' en mesa</li>'
            );
        });
        $wrap.removeAttr('hidden');
    }

    function actualizarBotonesCheckin(asignacion) {
        var liberada = asignacion && asignacion.estado_mesa === 'liberada';
        // Agregar pax funciona con o sin mesa (check-in / Arrived)
        $('#btnAgregarPax').prop('disabled', !!liberada);
    }

    function postCheckin(url, body, onSuccess) {
        if (!url || !reservaSeleccionada) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                [window.APP.csrfHeader]: window.APP.csrfTokenActual()
            },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                }).catch(function () {
                    return { ok: false, status: res.status, data: null };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    var msg = (result.data && result.data.error)
                        || (result.status === 403
                            ? 'Sesión de seguridad expirada. Recarga la página e intenta de nuevo.'
                            : 'No se pudo completar la acción.');
                    alert(msg);
                    return;
                }
                if (result.data.warning) {
                    alert(result.data.warning);
                }
                if (result.data.asignacion) {
                    cacheAsignaciones[reservaSeleccionada.codigo] = result.data.asignacion;
                    mostrarDetalle(reservaSeleccionada, result.data.asignacion);
                } else if (reservaSeleccionada) {
                    cargarActividad(reservaSeleccionada.codigo);
                }
                if (typeof onSuccess === 'function') onSuccess(result.data);
            })
            .catch(function () {
                alert('Error de conexión.');
            });
    }

    /** null = sin tags definidos en la asignación; array = tags de esta visita (puede estar vacío). */
    function tagsDesdeAsignacion(asignacion) {
        if (!asignacion || asignacion.tags_json == null || asignacion.tags_json === '') {
            return null;
        }
        try {
            var tags = typeof asignacion.tags_json === 'string'
                ? JSON.parse(asignacion.tags_json)
                : asignacion.tags_json;
            return Array.isArray(tags) ? tags : null;
        } catch (e) {
            return null;
        }
    }

    function cerrarDetalle() {
        document.getElementById('panelDetalle').hidden = true;
        reservaSeleccionada = null;
    }

    function guardarTags(modo) {
        if (!reservaSeleccionada) return;

        if (modo === 'cliente') {
            reservaSeleccionada.cliente_tags = tagsClienteSeleccionados;
            if (cacheReservas[reservaSeleccionada.codigo]) {
                cacheReservas[reservaSeleccionada.codigo].cliente_tags = tagsClienteSeleccionados;
            }
        } else {
            if (cacheAsignaciones[reservaSeleccionada.codigo]) {
                cacheAsignaciones[reservaSeleccionada.codigo].tags_json = JSON.stringify(tagsReservaSeleccionados);
            }
        }

        fetch(config.tagsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                [window.APP.csrfHeader]: window.APP.csrfTokenActual()
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                reserva_codigo: reservaSeleccionada.codigo,
                fecha: config.fecha || '',
                email: reservaSeleccionada.email || '',
                cliente_nombre: reservaSeleccionada.nombre || '',
                telefono: reservaSeleccionada.telefono || '',
                tags_cliente: modo === 'cliente' ? tagsClienteSeleccionados : undefined,
                tags_reserva: modo === 'reserva' ? tagsReservaSeleccionados : undefined
            })
        }).catch(function () {});
    }

    function aplicarFiltros() {
        var sucursalId = parseInt($('#filtroEstablecimiento').val(), 10);
        var fecha = $('#fechaReservas').val();

        if (!sucursalId) {
            alert('Seleccione un establecimiento.');
            return;
        }

        if (!fecha) {
            alert('Seleccione una fecha de visita.');
            return;
        }

        if (!config.apiBuscar) {
            window.location.href = urlReservas({ sucursalId: sucursalId, fecha: fecha });
            return;
        }

        var $btn = $('#btnBuscarReservas');
        $btn.prop('disabled', true).addClass('is-loading');

        var url = config.apiBuscar +
            '?sucursal_id=' + encodeURIComponent(sucursalId) +
            '&fecha=' + encodeURIComponent(fecha);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    alert(result.data.error || 'No se pudieron cargar las reservas.');
                    return;
                }

                var data = result.data;
                config.sucursalId = data.sucursal_id;
                config.fecha = data.fecha;

                sincronizarCache(data.reservas, data.asignaciones || {});
                actualizarAlertaApi(data);
                actualizarMetaReservas(data);
                actualizarTablaReservas(data.reservas, data.asignaciones || {});
                cerrarDetalle();

                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', urlReservas({
                        sucursalId: data.sucursal_id,
                        fecha: data.fecha
                    }));
                }
            })
            .catch(function () {
                alert('Error de conexión al cargar reservas.');
            })
            .finally(function () {
                $btn.prop('disabled', false).removeClass('is-loading');
            });
    }

    function actualizarAlertaApi(data) {
        var $cont = $('#hostessApiAlert');
        var html = '';

        if (data.api_error) {
            html = '<div class="alert alert-warning hostess-api-alert" role="alert">' +
                escapeHtml(data.api_error) + '</div>';
        } else if (data.modo_demo) {
            html = '<div class="alert alert-info hostess-api-alert" role="alert">' +
                'Modo demostración: configure la API Key y el Venue ID en la sucursal para conectar con OneReservations.' +
                '</div>';
        }

        $cont.html(html);
    }

    function actualizarMetaReservas(data) {
        var fechaFmt = formatearFechaVista(data.fecha);
        $('#hostessCardMeta').html(
            '<strong>' + (data.total || 0) + '</strong> registros · ' + escapeHtml(fechaFmt)
        );
    }

    function actualizarTablaReservas(reservas, asignaciones) {
        var html = '';
        var total = reservas ? reservas.length : 0;
        // Siempre 4 <td> por fila (DataTables tn/18: no usar colspan en tbody)
        if (total > 0) {
            reservas.forEach(function (r) {
                var asig = asignaciones[r.codigo] || null;
                var codigo = escapeHtml(r.codigo || '');

                html += '<tr data-codigo="' + codigo + '" class="hostess-row" role="button" tabindex="0" aria-label="Seleccionar reserva ' + codigo + '">';
                html += '<td data-label="Reserva"><span class="hostess-booking">' + codigo + '</span></td>';
                html += '<td data-label="Nombre">' + escapeHtml(r.nombre || '—') + '</td>';
                html += '<td data-label="RP">' + escapeHtml(r.rp || '—') + '</td>';
                var mesaLabel = '—';
                if (asig && asig.mesa_numero != null && asig.mesa_numero !== '') {
                    mesaLabel = '#' + escapeHtml(String(asig.mesa_numero));
                }
                html += '<td class="mesa-cell" data-label="Mesa">' + mesaLabel + '</td>';
                html += '</tr>';
            });
        }

        if (tablaReservas) {
            tablaReservas.clear();
            tablaReservas.destroy();
            tablaReservas = null;
        }

        // Limpia clase residual de una instancia previa
        var $tabla = $('#tablaReservas');
        $tabla.find('tbody').html(html);
        $tabla.removeClass('dataTable no-footer');
        $tabla.find('thead th').removeAttr('style');

        tablaReservas = $tabla.DataTable(opcionesDataTable(total));
    }

    function formatearFechaVista(fechaIso) {
        var partes = (fechaIso || '').split('-');
        if (partes.length !== 3) return fechaIso || '';
        return partes[2] + '/' + partes[1] + '/' + partes[0];
    }

    function escapeHtml(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function urlReservas(opts) {
        opts = opts || {};
        var fecha = opts.fecha || config.fecha;
        var sucursalId = opts.sucursalId != null ? opts.sucursalId : config.sucursalId;
        var q = '?fecha=' + encodeURIComponent(fecha);
        if (sucursalId) {
            q += '&sucursal_id=' + encodeURIComponent(sucursalId);
        }
        return config.detalleUrl + q;
    }

    function sumarDias(fechaStr, delta) {
        var partes = (fechaStr || '').split('-');
        if (partes.length !== 3) return fechaStr;
        var d = new Date(parseInt(partes[0], 10), parseInt(partes[1], 10) - 1, parseInt(partes[2], 10));
        d.setDate(d.getDate() + delta);
        return formatearFechaLocal(d);
    }

    function formatearFechaLocal(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    /** Extrae booking o token desde URL del QR o texto plano. */
    function normalizarCodigoQR(texto) {
        var t = (texto || '').trim();
        var matchToken = t.match(/[?&]token=([^&]+)/i);
        if (matchToken) {
            return decodeURIComponent(matchToken[1]);
        }
        return t;
    }

    return { init: init };
})();
