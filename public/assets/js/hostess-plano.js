/**
 * Vista de plano Hostess — selección de mesa y confirmación de asignación
 */
var HostessPlano = (function () {
    'use strict';

    var canvas, config;
    var mesasData = [];
    var mesaPendiente = null;

    // Estados: disponible = gris, reservada = ámbar, ocupada = verde
    var COLORES = {
        libre: '#8E8E93',
        reservada: '#FF9500',
        ocupada: '#8FAE9D'
    };

    function init(cfg) {
        config = cfg || {};

        canvas = new fabric.Canvas('hostessPlanoCanvas', {
            width: 1200,
            height: 800,
            backgroundColor: '#1e1e1e',
            selection: false,
            hoverCursor: 'pointer'
        });

        $('#btnConfirmarAsignarMesa').on('click', confirmarAsignacion);

        $(window).on('resize', ajustarTamanoCanvas);
        cargarPlano();
    }

    function cargarPlano() {
        if (!config.sucursalId) {
            alert('Seleccione un establecimiento antes de abrir el plano.');
            return;
        }

        var url = config.apiUrl
            + '?sucursal_id=' + encodeURIComponent(config.sucursalId)
            + '&fecha=' + encodeURIComponent(config.fecha || '');

        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || result.data.error) {
                    alert((result.data && result.data.error) || 'No se pudo cargar el plano.');
                    return;
                }

                var data = result.data;
                var aforoEl = document.getElementById('planoAforo');
                if (aforoEl) {
                    aforoEl.textContent = (data.aforo != null ? data.aforo : '—');
                }
                mesasData = Array.isArray(data.mesas) ? data.mesas : [];

                if (data.floorplan && data.floorplan.canvas_json) {
                    var json = typeof data.floorplan.canvas_json === 'string'
                        ? JSON.parse(data.floorplan.canvas_json)
                        : data.floorplan.canvas_json;

                    canvas.loadFromJSON(json, function () {
                        canvas.setBackgroundColor('#1e1e1e', function () {
                            sincronizarMesaData();
                            aplicarEstados();
                            ajustarTamanoCanvas();
                            canvas.renderAll();
                        });
                    });
                } else {
                    canvas.clear();
                    canvas.setBackgroundColor('#1e1e1e', canvas.renderAll.bind(canvas));
                    renderMesasSimple(mesasData);
                    ajustarTamanoCanvas();
                }
            })
            .catch(function () {
                alert('Error de conexión al cargar el plano.');
            });
    }

    /** Enlaza ids reales de BD a los objetos Fabric del canvas. */
    function sincronizarMesaData() {
        canvas.getObjects().forEach(function (obj) {
            if (!obj.mesaData) return;
            var match = encontrarMesa(obj.mesaData);
            if (!match) return;
            obj.mesaData.id = match.id;
            obj.mesaData.numero = match.numero;
            obj.mesaData.minimo = match.minimo;
            obj.mesaData.maximo = match.maximo;
            obj.mesaData.estado = match.estado;
            obj.mesaData.reserva = match.reserva || null;
        });
    }

    function encontrarMesa(ref) {
        if (!ref) return null;
        return mesasData.find(function (m) {
            if (ref.id && parseInt(m.id, 10) === parseInt(ref.id, 10)) return true;
            if (ref.fabric_id && m.fabric_id && String(m.fabric_id) === String(ref.fabric_id)) return true;
            if (ref.numero != null && String(m.numero) === String(ref.numero)) return true;
            return false;
        }) || null;
    }

    function aplicarEstados() {
        limpiarOverlaysEtiqueta();
        canvas.getObjects().forEach(function (obj) {
            if (!obj.mesaData || obj.hostessOverlay) return;

            var mesa = encontrarMesa(obj.mesaData) || obj.mesaData;
            var estado = mesa.estado || 'libre';
            colorearMesa(obj, estado);
            ocultarTextoOriginal(obj);
            colocarEtiquetaHostess(obj, mesa);

            obj.set({ selectable: false, evented: true, hoverCursor: 'pointer' });
            obj.off('mousedown');
            obj.on('mousedown', function () {
                onClickMesa(mesa);
            });
        });
    }

    function limpiarOverlaysEtiqueta() {
        canvas.getObjects()
            .filter(function (o) { return o.hostessOverlay; })
            .forEach(function (o) { canvas.remove(o); });
    }

    /** Oculta la etiqueta antigua (numero + min-max) del JSON del editor. */
    function ocultarTextoOriginal(obj) {
        if (!obj._objects) return;
        obj._objects.forEach(function (o) {
            if (o.type === 'text' || o.type === 'i-text' || o.type === 'textbox') {
                o.set('opacity', 0);
            }
        });
    }

    /**
     * Etiqueta hostess:
     *   #101
     *   [N Pax]  ← pastilla con contorno; N = pax ya ingresados (en mesa)
     *   Booking  ← nombre o código si hay reserva
     */
    function textoBooking(mesa) {
        var r = mesa && mesa.reserva;
        if (!r) return '';
        // En el plano se muestra el código de booking, no el nombre del cliente
        return String(r.reserva_codigo || '').trim();
    }

    function paxIngresados(mesa) {
        var r = mesa && mesa.reserva;
        if (!r) return 0;
        if (r.pax_en_mesa != null && r.pax_en_mesa !== '') {
            return parseInt(r.pax_en_mesa, 10) || 0;
        }
        return 0;
    }

    function crearGrupoEtiqueta(mesa, colorTexto) {
        var numero = mesa.numero != null ? String(mesa.numero) : '?';
        var paxStr = paxIngresados(mesa) + ' Pax';
        var booking = textoBooking(mesa);
        colorTexto = colorTexto || '#1D1D1F';

        var lineaNumero = new fabric.Text('#' + numero, {
            fontSize: 12,
            fontWeight: '700',
            fill: colorTexto,
            originX: 'center',
            originY: 'center',
            top: booking ? -16 : -10,
            fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif'
        });

        var tagPadX = 6;
        var tagH = 15;
        var approxW = Math.max(42, paxStr.length * 5.4 + tagPadX * 2);
        var tagBg = new fabric.Rect({
            width: approxW,
            height: tagH,
            rx: 4,
            ry: 4,
            fill: '#111111',
            stroke: '#111111',
            strokeWidth: 1.25,
            originX: 'center',
            originY: 'center',
            top: booking ? 2 : 6
        });
        var tagTxt = new fabric.Text(paxStr, {
            fontSize: 9,
            fontWeight: '600',
            fill: '#FFFFFF',
            originX: 'center',
            originY: 'center',
            top: booking ? 2 : 6,
            fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif'
        });

        var parts = [lineaNumero, tagBg, tagTxt];
        if (booking) {
            var bookingTxt = booking.length > 18 ? booking.substring(0, 17) + '…' : booking;
            parts.push(new fabric.Text(bookingTxt, {
                fontSize: 9,
                fontWeight: '500',
                fill: colorTexto,
                originX: 'center',
                originY: 'center',
                top: 18,
                fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif'
            }));
        }

        return new fabric.Group(parts, {
            originX: 'center',
            originY: 'center',
            selectable: false,
            evented: false,
            hostessOverlay: true
        });
    }

    function colocarEtiquetaHostess(obj, mesa) {
        var centro = obj.getCenterPoint();
        var etiqueta = crearGrupoEtiqueta(mesa, '#1D1D1F');
        etiqueta.set({
            left: centro.x,
            top: centro.y,
            mesaId: mesa.id || null
        });
        canvas.add(etiqueta);
        etiqueta.bringToFront();
    }

    function renderMesasSimple(mesas) {
        limpiarOverlaysEtiqueta();
        mesas.forEach(function (m) {
            var rect = new fabric.Rect({
                width: parseFloat(m.ancho) || 80,
                height: parseFloat(m.alto) || 60,
                fill: COLORES[m.estado] || COLORES.libre,
                rx: 4,
                ry: 4,
                originX: 'center',
                originY: 'center'
            });
            var group = new fabric.Group([rect], {
                left: parseFloat(m.pos_x) || 100,
                top: parseFloat(m.pos_y) || 100,
                mesaData: m,
                selectable: false,
                evented: true,
                hoverCursor: 'pointer',
                originX: 'center',
                originY: 'center'
            });
            group.on('mousedown', function () {
                onClickMesa(m);
            });
            canvas.add(group);
            colocarEtiquetaHostess(group, m);
        });
        canvas.renderAll();
    }

    function colorearMesa(obj, estado) {
        if (obj.type !== 'group' || !obj._objects) return;
        var color = COLORES[estado] || COLORES.libre;
        // La forma de la mesa es el rect/circle principal (no sillas pequeñas)
        obj._objects.forEach(function (o) {
            if ((o.type === 'rect' || o.type === 'circle') && (o.width >= 40 || o.radius >= 20)) {
                o.set('fill', color);
            }
        });
    }

    function onClickMesa(mesa) {
        if (!config.reservaCodigo) {
            alert('Abre el plano desde una reserva para asignar mesa.');
            return;
        }
        if (!mesa || !mesa.id) {
            alert('No se pudo identificar la mesa seleccionada.');
            return;
        }

        mesaPendiente = mesa;
        var nombre = (config.reservaNombre || '').trim() || 'esta reserva';
        var numero = mesa.numero != null ? mesa.numero : mesa.id;
        $('#errorAsignarMesa').attr('hidden', true).text('');
        $('#textoConfirmarAsignacion').text(
            '¿Está seguro que la reserva de ' + nombre + ', se asigne a la mesa ' + numero + '?'
        );
        $('#modalAsignarMesa').modal('show');
    }

    function confirmarAsignacion() {
        if (!mesaPendiente || !mesaPendiente.id) return;

        var $btn = $('#btnConfirmarAsignarMesa').prop('disabled', true);
        $('#errorAsignarMesa').attr('hidden', true).text('');

        fetch(config.asignarUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                [window.APP.csrfHeader]: window.APP.csrfTokenActual()
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                reserva_codigo: config.reservaCodigo,
                mesa_id: parseInt(mesaPendiente.id, 10),
                fecha: config.fecha,
                cliente_nombre: config.reservaNombre || '',
                pax: null
            })
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data.success) {
                    $btn.prop('disabled', false);
                    var msg = (result.data && result.data.error)
                        || 'No se pudo asignar la mesa.';
                    $('#errorAsignarMesa').text(msg).removeAttr('hidden');
                    return;
                }
                // Sin alert: cierra modal y vuelve a reservas
                mesaPendiente = null;
                $('#modalAsignarMesa').modal('hide');
                var dest = config.reservasUrl
                    + '?fecha=' + encodeURIComponent(config.fecha)
                    + '&sucursal_id=' + encodeURIComponent(config.sucursalId)
                    + '&reserva=' + encodeURIComponent(config.reservaCodigo);
                window.location.replace(dest);
            })
            .catch(function () {
                $btn.prop('disabled', false);
                $('#errorAsignarMesa').text('Error de conexión al asignar.').removeAttr('hidden');
            });
    }

    function ajustarTamanoCanvas() {
        var wrap = document.querySelector('.hostess-plano-canvas-wrap');
        if (!wrap || !canvas) return;

        var w = Math.max(wrap.clientWidth, 320);
        var h = Math.max(wrap.clientHeight, 240);
        canvas.setDimensions({ width: w, height: h });
        canvas.calcOffset();
        canvas.renderAll();
    }

    return { init: init };
})();
