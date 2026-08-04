/**
 * Vista de plano Hostess — selección de mesa y confirmación de asignación
 */
var HostessPlano = (function () {
    'use strict';

    var canvas, config;
    var mesasData = [];
    var mesaPendiente = null;

    // Estados de color en el plano:
    // libre = gris · reservada = ámbar · ocupada (reserva) = verde · walk-in = morado
    var COLORES = {
        libre: '#8E8E93',
        reservada: '#FF9500',
        ocupada: '#8FAE9D',
        walkin: '#8B5CF6'
    };

    /** ¿La ocupación es walk-in (sin reserva de OneReservations)? */
    function esWalkIn(mesa) {
        var r = mesa && mesa.reserva;
        if (!r) return false;
        var code = String(r.reserva_codigo || '').trim();
        return /^WI-/i.test(code);
    }

    /**
     * Color de relleno según estado y tipo de ocupación.
     * reserva ocupada → verde; walk-in ocupado → morado.
     */
    function colorMesa(mesa) {
        var estado = ((mesa && mesa.estado) || 'libre').toLowerCase();
        if (!estado && mesa && mesa.reserva && mesa.reserva.estado_mesa) {
            estado = String(mesa.reserva.estado_mesa).toLowerCase();
        }
        if (estado === 'ocupada' && esWalkIn(mesa)) {
            return COLORES.walkin;
        }
        return COLORES[estado] || COLORES.libre;
    }

    function init(cfg) {
        config = cfg || {};

        canvas = new fabric.Canvas('hostessPlanoCanvas', {
            width: 1200,
            height: 800,
            backgroundColor: '#1e1e1e',
            selection: false,
            // Solo lectura de diseño: no marcos ni manipulación de objetos
            preserveObjectStacking: true,
            hoverCursor: 'default'
        });

        // Evita que un arrastre mueva estructuras aunque el JSON traiga selectable:true
        canvas.on('object:moving', cancelarTransformacion);
        canvas.on('object:scaling', cancelarTransformacion);
        canvas.on('object:rotating', cancelarTransformacion);
        canvas.on('object:skewing', cancelarTransformacion);
        canvas.on('selection:created', function (e) {
            // Solo mesas responden a clic; cualquier selección residual se descarta
            var obj = e && e.selected && e.selected[0];
            if (!obj || !obj.mesaData) {
                canvas.discardActiveObject();
                canvas.requestRenderAll();
            }
        });

        $('#btnConfirmarAsignarMesa').on('click', confirmarAsignacion);
        $('#btnConfirmarOcuparWalkin').on('click', confirmarOcuparWalkin);
        $('#btnLiberarMesaPlano').on('click', confirmarLiberarMesa);

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
                            bloquearDisenoPlano();
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

    /**
     * Hostess: el plano es de solo lectura en diseño.
     * Mesas: clic para asignar (sin mover). Estructuras y decoración: sin interacción.
     */
    function bloquearDisenoPlano() {
        if (!canvas) return;
        canvas.discardActiveObject();
        canvas.selection = false;

        canvas.getObjects().forEach(function (obj) {
            if (obj.hostessOverlay) {
                obj.set({
                    selectable: false,
                    evented: false,
                    hasControls: false,
                    hasBorders: false,
                    lockMovementX: true,
                    lockMovementY: true,
                    lockRotation: true,
                    lockScalingX: true,
                    lockScalingY: true
                });
                return;
            }

            if (obj.mesaData) {
                obj.set({
                    selectable: false,
                    evented: true,
                    hasControls: false,
                    hasBorders: false,
                    lockMovementX: true,
                    lockMovementY: true,
                    lockRotation: true,
                    lockScalingX: true,
                    lockScalingY: true,
                    lockSkewingX: true,
                    lockSkewingY: true,
                    hoverCursor: 'pointer'
                });
                return;
            }

            // Estructuras (objectType === 'structural') y cualquier otro objeto del canvas
            obj.set({
                selectable: false,
                evented: false,
                hasControls: false,
                hasBorders: false,
                lockMovementX: true,
                lockMovementY: true,
                lockRotation: true,
                lockScalingX: true,
                lockScalingY: true,
                lockSkewingX: true,
                lockSkewingY: true,
                hoverCursor: 'default'
            });
        });
    }

    /** Cancela cualquier intento de transformar un objeto en vista hostess. */
    function cancelarTransformacion(e) {
        var obj = e && e.target;
        if (!obj) return;
        obj.set({
            selectable: false,
            hasControls: false,
            hasBorders: false,
            lockMovementX: true,
            lockMovementY: true,
            lockRotation: true,
            lockScalingX: true,
            lockScalingY: true
        });
        // Las mesas siguen recibiendo clic; las estructuras se apagan del todo
        if (!obj.mesaData) {
            obj.set({ evented: false, hoverCursor: 'default' });
        }
        canvas.discardActiveObject();
        canvas.requestRenderAll();
    }

    function aplicarEstados() {
        limpiarOverlaysEtiqueta();
        bloquearDisenoPlano();

        canvas.getObjects().forEach(function (obj) {
            if (!obj.mesaData || obj.hostessOverlay) return;

            var mesa = encontrarMesa(obj.mesaData) || obj.mesaData;
            var estado = mesa.estado || 'libre';
            colorearMesa(obj, mesa);
            ocultarEtiquetaOriginal(obj);
            colocarEtiquetaHostess(obj, mesa);

            obj.set({
                selectable: false,
                evented: true,
                hasControls: false,
                hasBorders: false,
                lockMovementX: true,
                lockMovementY: true,
                lockRotation: true,
                lockScalingX: true,
                lockScalingY: true,
                hoverCursor: 'pointer'
            });
            obj.off('mousedown');
            obj.on('mousedown', function () {
                onClickMesa(mesa);
            });
        });
    }

    function limpiarOverlaysEtiqueta() {
        canvas.getObjects()
            .filter(function (o) {
                return o.hostessOverlay || o.objectType === 'hostessLabel';
            })
            .forEach(function (o) { canvas.remove(o); });
    }

    /**
     * Oculta la etiqueta del editor (número + pax embebida en el grupo de mesa).
     * El editor guarda un sub-grupo objectType=mesaLabel; no basta con ocultar Text sueltos.
     */
    function ocultarEtiquetaOriginal(mesaGroup) {
        if (!mesaGroup || !mesaGroup._objects) return;

        function ocultarObjeto(o) {
            if (!o) return;
            o.set({ opacity: 0, visible: false });
            if (o._objects && o._objects.length) {
                o._objects.forEach(ocultarObjeto);
            }
        }

        function esGrupoEtiqueta(o) {
            if (!o || o.type !== 'group' || !o._objects || !o._objects.length) {
                return false;
            }
            if (o.objectType === 'mesaLabel') {
                return true;
            }
            // Detección por contenido: solo texto + pastilla pequeña (sin sillas)
            var soloPartesLabel = o._objects.every(function (c) {
                var tipo = c.type;
                if (tipo === 'text' || tipo === 'i-text' || tipo === 'textbox') {
                    return true;
                }
                if (tipo === 'rect') {
                    var w = (c.width || 0) * (c.scaleX || 1);
                    var h = (c.height || 0) * (c.scaleY || 1);
                    return w <= 130 && h <= 36;
                }
                return false;
            });
            var tieneTexto = o._objects.some(function (c) {
                return c.type === 'text' || c.type === 'i-text' || c.type === 'textbox';
            });
            return soloPartesLabel && tieneTexto;
        }

        mesaGroup._objects.forEach(function (o) {
            if (o.type === 'text' || o.type === 'i-text' || o.type === 'textbox') {
                ocultarObjeto(o);
                return;
            }
            if (esGrupoEtiqueta(o)) {
                ocultarObjeto(o);
            }
        });
    }

    /**
     * Etiqueta hostess (una sola capa, apilada con separación clara):
     *   #101
     *   [N Pax]
     *   código (corto)
     */
    function textoBooking(mesa) {
        var r = mesa && mesa.reserva;
        if (!r) return '';
        return String(r.reserva_codigo || '').trim();
    }

    /** Código legible en el plano (evita códigos walk-in largos). */
    function textoBookingCorto(mesa) {
        var code = textoBooking(mesa);
        if (!code) return '';
        if (code.length <= 11) return code;
        if (/^WI-/i.test(code) && code.length > 12) {
            return 'WI-…' + code.slice(-6);
        }
        return code.substring(0, 9) + '…';
    }

    function paxIngresados(mesa) {
        var r = mesa && mesa.reserva;
        if (!r) return 0;
        if (r.pax_en_mesa != null && r.pax_en_mesa !== '') {
            return parseInt(r.pax_en_mesa, 10) || 0;
        }
        return 0;
    }

    function colorEtiquetaPorEstado(estado) {
        if (estado === 'ocupada' || estado === 'reservada') {
            return '#FFFFFF';
        }
        return '#1D1D1F';
    }

    function crearGrupoEtiqueta(mesa, colorTexto) {
        var numero = mesa.numero != null ? String(mesa.numero) : '?';
        var paxStr = paxIngresados(mesa) + ' Pax';
        var booking = textoBookingCorto(mesa);
        colorTexto = colorTexto || '#1D1D1F';

        // Filas con más separación vertical (evita apilado visual)
        var hNumero = 15;
        var hPax = 17;
        var hBooking = booking ? 13 : 0;
        var gap = 5;
        var totalH = hNumero + gap + hPax + (booking ? gap + hBooking : 0);
        var y = -totalH / 2;
        var parts = [];

        parts.push(new fabric.Text('#' + numero, {
            fontSize: 13,
            fontWeight: '700',
            fill: colorTexto,
            originX: 'center',
            originY: 'top',
            left: 0,
            top: y,
            fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif',
            paintFirst: 'fill',
            strokeWidth: 0
        }));
        y += hNumero + gap;

        var approxW = Math.max(44, paxStr.length * 5.8 + 14);
        // Pastilla pax como grupo unitario (fondo + texto alineados)
        var tagBg = new fabric.Rect({
            width: approxW,
            height: hPax,
            rx: 5,
            ry: 5,
            fill: '#111111',
            stroke: 'rgba(255,255,255,0.25)',
            strokeWidth: 1,
            originX: 'center',
            originY: 'center',
            left: 0,
            top: 0
        });
        var tagTxt = new fabric.Text(paxStr, {
            fontSize: 9,
            fontWeight: '600',
            fill: '#FFFFFF',
            originX: 'center',
            originY: 'center',
            left: 0,
            top: 0,
            fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif',
            strokeWidth: 0
        });
        parts.push(new fabric.Group([tagBg, tagTxt], {
            originX: 'center',
            originY: 'top',
            left: 0,
            top: y
        }));
        y += hPax;

        if (booking) {
            y += gap;
            parts.push(new fabric.Text(booking, {
                fontSize: 8,
                fontWeight: '600',
                fill: colorTexto,
                originX: 'center',
                originY: 'top',
                left: 0,
                top: y,
                fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif',
                strokeWidth: 0
            }));
        }

        return new fabric.Group(parts, {
            originX: 'center',
            originY: 'center',
            selectable: false,
            evented: false,
            hostessOverlay: true,
            objectType: 'hostessLabel'
        });
    }

    function colocarEtiquetaHostess(obj, mesa) {
        var centro = obj.getCenterPoint();
        var estado = (mesa.estado || '').toLowerCase();
        if (!estado && mesa.reserva && mesa.reserva.estado_mesa) {
            estado = String(mesa.reserva.estado_mesa).toLowerCase();
        }
        var etiqueta = crearGrupoEtiqueta(mesa, colorEtiquetaPorEstado(estado));
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
                fill: colorMesa(m),
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

    function colorearMesa(obj, mesaOEstado) {
        if (obj.type !== 'group' || !obj._objects) return;
        // Acepta mesa completa (preferido) o string de estado (compat)
        var color = typeof mesaOEstado === 'string'
            ? (COLORES[mesaOEstado] || COLORES.libre)
            : colorMesa(mesaOEstado || {});
        // La forma de la mesa es el rect/circle principal (no sillas pequeñas)
        obj._objects.forEach(function (o) {
            if ((o.type === 'rect' || o.type === 'circle') && (o.width >= 40 || o.radius >= 20)) {
                o.set('fill', color);
            }
        });
    }

    function onClickMesa(mesa) {
        if (!mesa || !mesa.id) {
            alert('No se pudo identificar la mesa seleccionada.');
            return;
        }

        var estado = (mesa.estado || '').toLowerCase();
        if (!estado && mesa.reserva && mesa.reserva.estado_mesa) {
            estado = String(mesa.reserva.estado_mesa).toLowerCase();
        }

        // Mesa ocupada: mostrar datos de la reserva (no reasignar)
        if (estado === 'ocupada') {
            mostrarInfoMesaOcupada(mesa);
            return;
        }

        // Mesa ya asignada a otra reserva
        if (estado === 'reservada' && mesa.reserva) {
            mostrarInfoMesaOcupada(mesa, 'Reservada');
            return;
        }

        // Con reserva en contexto: confirmar asignación
        if (config.reservaCodigo) {
            mesaPendiente = mesa;
            var nombre = (config.reservaNombre || '').trim() || 'esta reserva';
            var numero = mesa.numero != null ? mesa.numero : mesa.id;
            $('#errorAsignarMesa').attr('hidden', true).text('');
            $('#textoConfirmarAsignacion').text(
                '¿Está seguro que la reserva de ' + nombre + ', se asigne a la mesa ' + numero + '?'
            );
            $('#modalAsignarMesa').modal('show');
            return;
        }

        // Sin reserva: walk-in / invitado (OCUPAR)
        abrirModalOcuparWalkin(mesa);
    }

    function abrirModalOcuparWalkin(mesa) {
        mesaPendiente = mesa;
        var numero = mesa.numero != null ? mesa.numero : mesa.id;
        var maxPax = parseInt(mesa.maximo, 10) || parseInt(mesa.pax, 10) || 12;
        var minPax = parseInt(mesa.minimo, 10) || 1;
        var paxDefault = Math.min(Math.max(2, minPax), maxPax);

        $('#textoOcuparWalkin').text(
            'Mesa #' + numero + ' · invitados sin reserva'
        );
        $('#walkinNombre').val('');
        $('#walkinPax').attr('min', minPax).attr('max', maxPax).val(paxDefault);
        $('#errorOcuparWalkin').attr('hidden', true).text('');
        // Focus en nombre al abrir (tras animación del modal)
        $('#modalOcuparWalkin')
            .one('shown.bs.modal', function () {
                var el = document.getElementById('walkinNombre');
                if (el) {
                    el.focus();
                    el.select();
                }
            })
            .modal('show');
    }

    function confirmarOcuparWalkin() {
        if (!mesaPendiente || !mesaPendiente.id) return;
        if (!config.ocuparWalkinUrl) {
            $('#errorOcuparWalkin').text('Endpoint de ocupación no configurado.').removeAttr('hidden');
            return;
        }

        var pax = parseInt($('#walkinPax').val(), 10);
        if (!pax || pax < 1) {
            $('#errorOcuparWalkin').text('Indica cuántos pax van a la mesa.').removeAttr('hidden');
            return;
        }

        var nombre = String($('#walkinNombre').val() || '').trim();
        var $btn = $('#btnConfirmarOcuparWalkin').prop('disabled', true);
        $('#errorOcuparWalkin').attr('hidden', true).text('');

        fetch(config.ocuparWalkinUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                [window.APP.csrfHeader]: window.APP.csrfTokenActual()
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                mesa_id: parseInt(mesaPendiente.id, 10),
                fecha: config.fecha,
                pax: pax,
                cliente_nombre: nombre
            })
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (result) {
                $btn.prop('disabled', false);
                if (!result.ok || !result.data.success) {
                    var msg = (result.data && result.data.error)
                        || 'No se pudo ocupar la mesa.';
                    $('#errorOcuparWalkin').text(msg).removeAttr('hidden');
                    return;
                }
                mesaPendiente = null;
                $('#modalOcuparWalkin').modal('hide');
                // Recarga estado de mesas en el plano
                cargarPlano();
            })
            .catch(function () {
                $btn.prop('disabled', false);
                $('#errorOcuparWalkin').text('Error de conexión al ocupar.').removeAttr('hidden');
            });
    }

    /**
     * Popup con los datos de la reserva sentada / asignada en la mesa.
     * @param {string} [estadoLabel] etiqueta de estado (default Ocupada)
     */
    function mostrarInfoMesaOcupada(mesa, estadoLabel) {
        mesaPendiente = mesa;
        var r = mesa.reserva || {};
        var numero = mesa.numero != null ? mesa.numero : mesa.id;
        var codigo = (r.reserva_codigo || '').trim();
        var cliente = (r.cliente_nombre || '').trim();
        var hora = formatearHora(r.hora);
        var paxRes = r.pax_reservados != null && r.pax_reservados !== ''
            ? String(r.pax_reservados)
            : '—';
        var paxMesa = r.pax_en_mesa != null && r.pax_en_mesa !== ''
            ? String(r.pax_en_mesa)
            : '0';
        var seated = formatearFechaHora(r.seated_at) || formatearFechaHora(r.arrived_at);
        var walkin = esWalkIn(mesa);
        var etiquetaEstado = estadoLabel || (walkin ? 'Ocupada (invitado)' : 'Ocupada');

        $('#infoMesaTitulo').text('Mesa #' + numero);
        $('#infoMesaEstado').text(etiquetaEstado);
        $('#infoMesaCodigo').text(codigo || '—');
        $('#infoMesaCliente').text(cliente || '—');
        $('#infoMesaHora').text(hora || '—');
        $('#infoMesaPaxRes').text(paxRes);
        $('#infoMesaPaxMesa').text(paxMesa);
        $('#errorLiberarMesa').attr('hidden', true).text('');

        if (seated) {
            $('#infoMesaSeated').text(seated);
            $('#infoMesaSeatedRow').removeAttr('hidden');
        } else {
            $('#infoMesaSeatedRow').attr('hidden', true);
        }

        var tags = parseTags(r.tags_json);
        var $tags = $('#infoMesaTags').empty();
        if (tags.length) {
            tags.forEach(function (t) {
                $tags.append(
                    $('<span class="hostess-plano-tag-pill"></span>').text(String(t))
                );
            });
            $('#infoMesaTagsRow').removeAttr('hidden');
        } else {
            $('#infoMesaTagsRow').attr('hidden', true);
        }

        var $lista = $('#infoMesaLista');
        var $aviso = $('#infoMesaSinReserva');
        var $btnDetalle = $('#btnAbrirDetalleReserva');
        var $btnLiberar = $('#btnLiberarMesaPlano');

        if (codigo) {
            $lista.removeAttr('hidden');
            $aviso.attr('hidden', true);
            if (walkin) {
                // Walk-in: no hay ficha en listado de reservas OneR
                $btnDetalle.attr('hidden', true).attr('href', '#');
            } else {
                var dest = (config.reservasUrl || '')
                    + '?fecha=' + encodeURIComponent(config.fecha || '')
                    + '&sucursal_id=' + encodeURIComponent(config.sucursalId || '')
                    + '&reserva=' + encodeURIComponent(codigo);
                $btnDetalle.attr('href', dest).removeAttr('hidden');
            }
        } else {
            $lista.attr('hidden', true);
            $aviso.removeAttr('hidden');
            $btnDetalle.attr('hidden', true).attr('href', '#');
        }

        // LIBERAR solo en mesas moradas (walk-in ocupado)
        if (walkin && codigo) {
            $btnLiberar.removeAttr('hidden').prop('disabled', false);
        } else {
            $btnLiberar.attr('hidden', true);
        }

        $('#modalInfoMesaOcupada').modal('show');
    }

    /** Libera walk-in (mesa morada) y recarga el plano. */
    function confirmarLiberarMesa() {
        var mesa = mesaPendiente;
        if (!mesa) return;
        var r = mesa.reserva || {};
        var codigo = String(r.reserva_codigo || '').trim();
        if (!codigo) {
            $('#errorLiberarMesa').text('No hay código de ocupación para liberar.').removeAttr('hidden');
            return;
        }
        if (!config.liberarUrl) {
            $('#errorLiberarMesa').text('Endpoint de liberar no configurado.').removeAttr('hidden');
            return;
        }

        var $btn = $('#btnLiberarMesaPlano').prop('disabled', true);
        $('#errorLiberarMesa').attr('hidden', true).text('');

        fetch(config.liberarUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                [window.APP.csrfHeader]: window.APP.csrfTokenActual()
            },
            credentials: 'same-origin',
            body: JSON.stringify({ reserva_codigo: codigo })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                $btn.prop('disabled', false);
                if (!result.ok || !result.data.success) {
                    var msg = (result.data && result.data.error) || 'No se pudo liberar la mesa.';
                    $('#errorLiberarMesa').text(msg).removeAttr('hidden');
                    return;
                }
                mesaPendiente = null;
                $('#modalInfoMesaOcupada').modal('hide');
                cargarPlano();
            })
            .catch(function () {
                $btn.prop('disabled', false);
                $('#errorLiberarMesa').text('Error de conexión al liberar.').removeAttr('hidden');
            });
    }

    function parseTags(raw) {
        if (raw == null || raw === '') return [];
        try {
            var tags = typeof raw === 'string' ? JSON.parse(raw) : raw;
            if (!Array.isArray(tags)) return [];
            return tags.map(function (t) {
                if (t == null) return '';
                if (typeof t === 'string') return t;
                return t.nombre || t.name || t.label || t.tag || String(t);
            }).filter(Boolean);
        } catch (e) {
            return [];
        }
    }

    function formatearHora(val) {
        if (val == null || val === '') return '';
        var s = String(val);
        var m = s.match(/^(\d{1,2}):(\d{2})/);
        if (m) {
            var h = m[1].length === 1 ? '0' + m[1] : m[1];
            return h + ':' + m[2];
        }
        return s;
    }

    function formatearFechaHora(val) {
        if (val == null || val === '') return '';
        var s = String(val).replace('T', ' ');
        var m = s.match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
        if (m) return m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5];
        return formatearHora(s) || s;
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
