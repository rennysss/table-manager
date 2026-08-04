/**
 * Editor FloorPlan con Fabric.js — estilo Sevenrooms
 */
(function () {
    'use strict';

    var EDITOR_BUILD = 'B22-pan-hand';

    var canvas, history = [], historyIndex = -1;
    var tableCounter = 100;
    var selectedMesaObj = null;
    var editorListo = false;
    var autoSaveTimer = null;
    var guardando = false;
    var guardadoPendiente = false;
    var detallesObj = null;
    var mesasDesvinculadas = [];
    var arrastreActual = null;
    var herramientaActiva = 'select';
    var panActivo = false;
    var panInicio = null;

    // Módulo común de estructuras: todos los SVG usan un viewBox de 48 unidades,
    // así que al escalar 1 unidad = UNIDAD_ESTRUCTURA px todas las piezas
    // comparten el mismo grosor/altura de segmento y empalman en secuencia.
    var UNIDAD_ESTRUCTURA = 3;   // px de lienzo por unidad de SVG
    var GRID_ESTRUCTURA = 12;    // imán de cuadrícula (px de lienzo)
    // Escala constante para los PNG de SevenRooms (px de imagen → px de lienzo).
    // Mantiene las proporciones reales entre todas las piezas.
    var SR_ESCALA = 0.11;

    function init() {
        if (!window.FLOORPLAN) return;

        // Forzar el backend de filtros 2D: el de WebGL recorta imágenes cuyo
        // tamaño no está alineado a potencias de 2 y deja la imagen "a la mitad".
        try {
            if (fabric.Canvas2dFilterBackend) {
                fabric.filterBackend = new fabric.Canvas2dFilterBackend();
            }
            fabric.enableGLFiltering = false;
        } catch (e) {}

        canvas = new fabric.Canvas('floorplanCanvas', {
            width: 2400,
            height: 1600,
            backgroundColor: '#0A0A0A',
            selection: true,
            preserveObjectStacking: true
        });

        loadCanvas();
        bindToolbar();
        renderStructural();
        bindGerentePanel();
        bindDetalles();
        applyZoom(parseFloat(document.getElementById('zoomSelect').value));

        console.log('[FloorPlan] editor build:', EDITOR_BUILD);
        setSaveStatus('build ' + EDITOR_BUILD);
    }

    /**
     * Asientos por tamaño (selector del panel derecho). Al cambiar el tamaño
     * se reconstruye la mesa con esa cantidad de sillas, respetando la forma.
     */
    var SIZE_SEATS = { xs: 1, s: 2, m: 4, l: 8, xl: 10, xxl: 12 };

    function loadCanvas() {
        var data = FLOORPLAN.canvasData;
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) { data = { objects: [] }; }
        }
        canvas.loadFromJSON(data, function () {
            vincularMesasBd();
            // Reaplica el comportamiento a mesas y estructuras cargadas.
            canvas.getObjects().forEach(function (obj) {
                if (obj.mesaData) {
                    prepararMesaGroup(obj);
                } else if (obj.objectType === 'structural') {
                    prepararEstructura(obj);
                    // Las etiquetas son claras: no se invierten (ver addStructural).
                    var esEtiqueta = typeof obj.structuralType === 'string'
                        && obj.structuralType.indexOf('label') === 0;
                    if (!esEtiqueta) { aclararImagenEstructura(obj); }
                }
            });
            canvas.renderAll();
            saveHistory();
            refreshTableList();
            editorListo = true;
        });
    }

    /**
     * Configura un elemento estructural: se puede mover, escalar y rotar
     * (con pivote al centro). Sus coordenadas se guardan en el canvas_json.
     */
    function prepararEstructura(obj) {
        obj.set({
            selectable: true,
            evented: true,
            hasControls: true,
            hasBorders: true,
            centeredRotation: true,
            originX: 'center',
            originY: 'center'
        });
        // Imágenes guardadas con crossOrigin pueden no recargar; lo limpiamos.
        if (obj.type === 'image') {
            if (obj.crossOrigin) { obj.crossOrigin = null; }
            // Escalado uniforme: conserva la proporción del dibujo (no se
            // estira). Se redimensiona solo desde las esquinas.
            obj.set({ lockUniScaling: true });
            if (typeof obj.setControlsVisibility === 'function') {
                obj.setControlsVisibility({
                    mt: false, mb: false, ml: false, mr: false,
                    tl: true, tr: true, bl: true, br: true, mtr: true
                });
            }
        }
        obj.setCoords();
    }

    /**
     * Configura una mesa para que solo se pueda mover y girar (no escalar),
     * con pivote al centro. Evita que se encoja/desaparezca al arrastrar.
     */
    function prepararMesaGroup(group) {
        group.set({
            lockScalingX: true,
            lockScalingY: true,
            lockSkewingX: true,
            lockSkewingY: true,
            hasControls: true,
            hasBorders: true,
            centeredRotation: true
        });

        if (typeof group.setControlsVisibility === 'function') {
            group.setControlsVisibility({
                mt: false, mb: false, ml: false, mr: false,
                tl: false, tr: false, bl: false, br: false,
                mtr: true
            });
        }

        group.setCoords();
    }

    /** Enlaza objetos del canvas con registros de BD por numero o fabric_id */
    function vincularMesasBd() {
        if (!FLOORPLAN.mesas || !FLOORPLAN.mesas.length) return;

        canvas.getObjects().forEach(function (obj) {
            if (!obj.mesaData) return;

            var match = FLOORPLAN.mesas.find(function (m) {
                return m.fabric_id === obj.mesaData.fabric_id
                    || m.numero === obj.mesaData.numero;
            });

            if (match) {
                obj.mesaData.id = match.id;
                obj.mesaData.numero = match.numero;
                obj.mesaData.pax = match.pax;
                obj.mesaData.minimo = match.minimo;
                obj.mesaData.maximo = match.maximo;
                obj.mesaData.ambiente_id = match.ambiente_id;
                if (!obj.mesaData.fabric_id) {
                    obj.mesaData.fabric_id = match.fabric_id;
                }
                actualizarEtiquetaMesa(obj);
            }
        });
    }

    function vincularMesasNuevas(mesasNuevas) {
        mesasNuevas.forEach(function (nueva) {
            FLOORPLAN.mesas = FLOORPLAN.mesas || [];
            FLOORPLAN.mesas.push(nueva);

            canvas.getObjects().forEach(function (obj) {
                if (!obj.mesaData) return;
                if (obj.mesaData.fabric_id === nueva.fabric_id || obj.mesaData.numero === nueva.numero) {
                    obj.mesaData.id = nueva.id;
                    obj.mesaData.fabric_id = nueva.fabric_id;
                }
            });
        });
    }

    function bindToolbar() {
        document.getElementById('btnAddStructural').addEventListener('click', function () {
            showSubpanel('subpanel-structural');
        });

        document.getElementById('btnSave').addEventListener('click', function () { saveFloorplan(false); });
        document.getElementById('btnUndo').addEventListener('click', undo);
        document.getElementById('btnRedo').addEventListener('click', redo);
        document.getElementById('zoomSelect').addEventListener('change', function (e) {
            applyZoom(parseFloat(e.target.value));
        });

        document.querySelectorAll('[data-back]').forEach(function (btn) {
            btn.addEventListener('click', hideSubpanels);
        });

        document.querySelectorAll('.fp-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.fp-tab').forEach(function (t) { t.classList.remove('active'); });
                document.querySelectorAll('.fp-panel').forEach(function (p) { p.classList.remove('active'); });
                tab.classList.add('active');
                document.getElementById('panel-' + tab.dataset.panel).classList.add('active');
            });
        });

        canvas.on('object:modified', function (e) {
            if (e && e.target && typeof e.target.setCoords === 'function') {
                e.target.setCoords();
            }
            saveHistory();
            refreshTableList();
            autoGuardar();
        });
        // Imán de cuadrícula al mover estructuras → quedan en secuencia ordenada.
        canvas.on('object:moving', function (e) {
            var o = e && e.target;
            if (o && o.objectType === 'structural') {
                ajustarAGrid(o);
            }
        });

        // Con Shift, la rotación de estructuras salta de 90 en 90 grados
        // (0, 90, 180, 270, 360) para alinear paredes y segmentos.
        canvas.on('object:rotating', function (e) {
            var o = e && e.target;
            if (!o || o.objectType !== 'structural') return;
            var evt = e.e || (window.event);
            if (evt && evt.shiftKey) {
                o.set('angle', Math.round(o.angle / 90) * 90);
            }
        });
        canvas.on('object:added', function () { refreshTableList(); autoGuardar(); });
        canvas.on('object:removed', function () { refreshTableList(); cerrarDetalles(); autoGuardar(); });
        canvas.on('selection:created', onSelectionChange);
        canvas.on('selection:updated', onSelectionChange);
        canvas.on('selection:cleared', cerrarDetalles);

        bindDragAndDrop();
        bindWheelZoom();
        bindToolsBar();

        // Atajos de teclado: H = mano, S = selector, Supr/Backspace = eliminar.
        document.addEventListener('keydown', function (e) {
            if (esCampoTexto(e.target)) return;

            if (e.key === 'h' || e.key === 'H') {
                e.preventDefault();
                aplicarHerramienta('hand');
                return;
            }
            if (e.key === 's' || e.key === 'S') {
                e.preventDefault();
                aplicarHerramienta('select');
                return;
            }

            if (e.key !== 'Backspace' && e.key !== 'Delete') return;

            var active = canvas.getActiveObject();
            if (!active || active.isEditing) return;

            e.preventDefault();
            eliminarObjetoSeleccionado();
        });
    }

    function esCampoTexto(el) {
        if (!el) return false;
        var tag = el.tagName ? el.tagName.toUpperCase() : '';
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    }

    /** Barra inferior: zoom, mano (H) y selector (S). */
    function bindToolsBar() {
        var btnHand = document.getElementById('btnToolHand');
        var btnSelect = document.getElementById('btnToolSelect');
        var btnZoom = document.getElementById('btnToolZoom');
        var popover = document.getElementById('fpZoomPopover');
        var btnZoomIn = document.getElementById('btnZoomIn');
        var btnZoomOut = document.getElementById('btnZoomOut');

        if (btnHand) {
            btnHand.addEventListener('click', function () { aplicarHerramienta('hand'); });
        }
        if (btnSelect) {
            btnSelect.addEventListener('click', function () { aplicarHerramienta('select'); });
        }

        if (btnZoom && popover) {
            btnZoom.addEventListener('click', function (e) {
                e.stopPropagation();
                var abierto = !popover.hidden;
                popover.hidden = abierto;
                btnZoom.setAttribute('aria-expanded', abierto ? 'false' : 'true');
            });

            document.addEventListener('click', function () {
                if (!popover.hidden) {
                    popover.hidden = true;
                    btnZoom.setAttribute('aria-expanded', 'false');
                }
            });

            popover.addEventListener('click', function (e) { e.stopPropagation(); });
        }

        if (btnZoomIn) {
            btnZoomIn.addEventListener('click', function () { pasoZoom(1); });
        }
        if (btnZoomOut) {
            btnZoomOut.addEventListener('click', function () { pasoZoom(-1); });
        }

        bindPanTool();
        actualizarEtiquetaZoom();
        aplicarHerramienta('select');
    }

    function aplicarHerramienta(nombre) {
        herramientaActiva = nombre;
        var wrap = document.querySelector('.fp-canvas-wrap');

        document.querySelectorAll('.fp-tool-btn[data-tool]').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.tool === nombre);
        });

        if (nombre === 'hand') {
            canvas.selection = false;
            canvas.skipTargetFind = true;
            canvas.discardActiveObject();
            canvas.renderAll();
            canvas.defaultCursor = 'grab';
            canvas.hoverCursor = 'grab';
            if (canvas.upperCanvasEl) {
                canvas.upperCanvasEl.style.cursor = 'grab';
            }
            if (wrap) wrap.classList.add('fp-canvas-wrap--pan');
            cerrarDetalles();
        } else {
            canvas.selection = true;
            canvas.skipTargetFind = false;
            canvas.defaultCursor = 'default';
            canvas.hoverCursor = 'move';
            if (canvas.upperCanvasEl) {
                canvas.upperCanvasEl.style.cursor = '';
            }
            if (wrap) wrap.classList.remove('fp-canvas-wrap--pan', 'fp-canvas-wrap--panning');
            panActivo = false;
            panInicio = null;
        }
    }

    function iniciarPan(clientX, clientY) {
        panActivo = true;
        panInicio = { x: clientX, y: clientY };
        var wrap = document.querySelector('.fp-canvas-wrap');
        if (wrap) wrap.classList.add('fp-canvas-wrap--panning');
        if (canvas.upperCanvasEl) {
            canvas.upperCanvasEl.style.cursor = 'grabbing';
        }
    }

    function moverPan(clientX, clientY) {
        if (!panActivo || !panInicio) return;
        var dx = clientX - panInicio.x;
        var dy = clientY - panInicio.y;
        panInicio.x = clientX;
        panInicio.y = clientY;

        var vpt = canvas.viewportTransform.slice();
        vpt[4] += dx;
        vpt[5] += dy;
        canvas.setViewportTransform(vpt);
        canvas.requestRenderAll();
    }

    function finalizarPan() {
        if (!panActivo) return;
        panActivo = false;
        panInicio = null;
        var wrap = document.querySelector('.fp-canvas-wrap');
        if (wrap) wrap.classList.remove('fp-canvas-wrap--panning');
        if (herramientaActiva === 'hand' && canvas.upperCanvasEl) {
            canvas.upperCanvasEl.style.cursor = 'grab';
        }
    }

    /**
     * Arrastre del lienzo con la herramienta mano.
     * Los eventos van en Fabric (el canvas tapa el contenedor y captura el mouse).
     */
    function bindPanTool() {
        var wrap = document.querySelector('.fp-canvas-wrap');
        if (!wrap || !canvas) return;

        canvas.on('mouse:down', function (opt) {
            if (herramientaActiva !== 'hand') return;
            var e = opt.e;
            if (!e || e.button !== 0) return;
            iniciarPan(e.clientX, e.clientY);
            e.preventDefault();
        });

        canvas.on('mouse:move', function (opt) {
            if (!panActivo || !panInicio) return;
            var e = opt.e;
            if (!e) return;
            moverPan(e.clientX, e.clientY);
        });

        canvas.on('mouse:up', finalizarPan);

        // Respaldo: arrastre en el área del contenedor fuera del canvas (márgenes).
        wrap.addEventListener('mousedown', function (e) {
            if (herramientaActiva !== 'hand' || e.button !== 0) return;
            var container = canvas.wrapperEl;
            if (container && container.contains(e.target)) return;
            iniciarPan(e.clientX, e.clientY);
            e.preventDefault();
        });

        window.addEventListener('mousemove', function (e) {
            if (!panActivo || !panInicio) return;
            moverPan(e.clientX, e.clientY);
        });

        window.addEventListener('mouseup', finalizarPan);
    }

    /** Acerca o aleja el zoom centrado en el área visible. */
    function pasoZoom(direccion) {
        var wrap = document.querySelector('.fp-canvas-wrap');
        if (!wrap) return;
        var rect = wrap.getBoundingClientRect();
        var z = canvas.getZoom() || 1;
        var paso = 0.1;
        zoomEnPunto(z + direccion * paso, rect.left + rect.width / 2, rect.top + rect.height / 2);
    }

    function actualizarEtiquetaZoom() {
        var el = document.getElementById('fpZoomLabel');
        if (!el || !canvas) return;
        el.textContent = Math.round((canvas.getZoom() || 1) * 100) + '%';
    }

    function bindGerentePanel() {
        if (!FLOORPLAN.gerenteMode) return;

        var btnApply = document.getElementById('btnApplyPax');
        if (btnApply) {
            btnApply.addEventListener('click', aplicarPaxGerente);
        }
    }

    function onSelectionChange() {
        refreshTableList();

        var obj = canvas.getActiveObject();
        if (obj && obj.mesaData && obj.type === 'group') {
            abrirDetalles(obj);
        } else {
            cerrarDetalles();
        }
    }

    function mostrarPanelGerente(obj) {
        selectedMesaObj = obj;
        var d = obj.mesaData;
        var panel = document.getElementById('gerenteMesaEdit');

        if (!panel) return;

        panel.hidden = false;
        document.getElementById('gerenteNumero').value = d.numero || '';
        document.getElementById('gerentePax').value = d.pax || d.maximo || 4;
        document.getElementById('gerenteMin').value = d.minimo || 2;
        document.getElementById('gerenteMax').value = d.maximo || 6;

        if (d.id) {
            cargarReservasMesa(d.id);
        } else {
            document.getElementById('gerenteReservasInfo').innerHTML =
                '<p class="fp-reservas-none">Guarda el plano primero para vincular reservas.</p>';
        }
    }

    function ocultarPanelGerente() {
        selectedMesaObj = null;
        var panel = document.getElementById('gerenteMesaEdit');
        if (panel) panel.hidden = true;
    }

    function cargarReservasMesa(mesaId, targetEl) {
        var info = targetEl || document.getElementById('gerenteReservasInfo');
        if (!info) return;
        var url = (FLOORPLAN.reservasMesaUrl || '') + '/' + mesaId + '/reservas';

        fetch(url + '?fecha=' + new Date().toISOString().slice(0, 10))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.reservas || !data.reservas.length) {
                    info.innerHTML = '<p class="fp-reservas-none">Sin reservas activas hoy en esta mesa.</p>';
                    return;
                }
                var html = '<p class="fp-reservas-title">Reservas activas (se mantienen al mover):</p><ul class="fp-reservas-list">';
                data.reservas.forEach(function (r) {
                    html += '<li>' + (r.cliente_nombre || r.reserva_codigo) + ' — ' + (r.estado_mesa || '') + '</li>';
                });
                html += '</ul>';
                info.innerHTML = html;
            })
            .catch(function () {
                info.innerHTML = '';
            });
    }

    function aplicarPaxGerente() {
        if (!selectedMesaObj || !selectedMesaObj.mesaData) return;

        var min = parseInt(document.getElementById('gerenteMin').value, 10);
        var max = parseInt(document.getElementById('gerenteMax').value, 10);
        var pax = parseInt(document.getElementById('gerentePax').value, 10);

        if (max < min) {
            alert('El máximo no puede ser menor al mínimo.');
            return;
        }

        selectedMesaObj.mesaData.minimo = min;
        selectedMesaObj.mesaData.maximo = max;
        selectedMesaObj.mesaData.pax = pax;
        actualizarEtiquetaMesa(selectedMesaObj);
        canvas.renderAll();
        saveHistory();
        refreshTableList();
    }

    function actualizarEtiquetaMesa(obj) {
        if (obj.type !== 'group' || !obj._objects) return;

        var d = obj.mesaData;
        if (!d) return;

        var labelGroup = obj._objects.find(function (o) {
            return o.objectType === 'mesaLabel' || (o.type === 'group' && o._objects && o._objects.length >= 2);
        });
        var labelText = obj._objects.find(function (o) {
            return o.type === 'text' || o.type === 'i-text';
        });

        var paxStr = (d.minimo || 0) + '-' + (d.maximo || 0) + ' Pax';
        var numStr = '#' + (d.numero || '');

        if (labelGroup && labelGroup._objects) {
            labelGroup._objects.forEach(function (child) {
                if (child.type === 'text' || child.type === 'i-text') {
                    if (String(child.text || '').indexOf('Pax') !== -1 || child.fontSize <= 10) {
                        child.set('text', paxStr);
                    } else if (String(child.text || '').charAt(0) === '#' || child.fontWeight === 'bold' || child.fontWeight === '700') {
                        child.set('text', numStr);
                    }
                }
            });
            return;
        }

        if (labelText) {
            labelText.set('text', numStr + '\n' + paxStr);
        }
    }

    /* ----- Panel derecho: detalles / edición de la mesa ----- */
    function bindDetalles() {
        var close = document.getElementById('fpDetailsClose');
        if (close) {
            close.addEventListener('click', function () {
                canvas.discardActiveObject();
                canvas.renderAll();
                cerrarDetalles();
            });
        }

        var name = document.getElementById('detName');
        if (name) {
            name.addEventListener('input', function () {
                if (!detallesObj) return;
                detallesObj.mesaData.numero = name.value;
                actualizarEtiquetaMesa(detallesObj);
                canvas.renderAll();
                refreshTableList();
                autoGuardar();
            });
        }

        var min = document.getElementById('detMin');
        var max = document.getElementById('detMax');
        function aplicarCovers() {
            if (!detallesObj) return;
            var mn = parseInt(min.value, 10) || 1;
            var mx = parseInt(max.value, 10) || mn;
            if (mx < mn) { mx = mn; max.value = mx; }
            detallesObj.mesaData.minimo = mn;
            detallesObj.mesaData.maximo = mx;
            if (!detallesObj.mesaData.pax || detallesObj.mesaData.pax < mn) {
                detallesObj.mesaData.pax = mx;
            }
            actualizarEtiquetaMesa(detallesObj);
            canvas.renderAll();
            refreshTableList();
            autoGuardar();
        }
        if (min) min.addEventListener('change', aplicarCovers);
        if (max) max.addEventListener('change', aplicarCovers);

        var amb = document.getElementById('detAmbiente');
        if (amb) {
            amb.addEventListener('change', function () {
                if (!detallesObj) return;
                detallesObj.mesaData.ambiente_id = parseInt(amb.value, 10);
                autoGuardar();
            });
        }

        var size = document.getElementById('detSize');
        if (size) {
            size.addEventListener('change', function () {
                if (!detallesObj) return;
                cambiarTamano(detallesObj, size.value);
            });
        }

        document.querySelectorAll('#detShapeGroup .fp-shape-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!detallesObj) return;
                cambiarForma(detallesObj, btn.dataset.shape);
            });
        });

        var del = document.getElementById('detEliminar');
        if (del) {
            del.addEventListener('click', function () {
                eliminarObjetoSeleccionado();
            });
        }
    }

    /**
     * Quita la mesa/estructura seleccionada del lienzo. Las mesas no se borran:
     * se desvinculan del layout y regresan al listado "Mesas de la sucursal".
     */
    function eliminarObjetoSeleccionado() {
        var obj = canvas.getActiveObject() || detallesObj;
        if (!obj) return;

        if (obj.mesaData) {
            var d = obj.mesaData;
            if (d.id) {
                // Devuelve la mesa al inventario disponible (sin floorplan).
                mesasDesvinculadas.push(d.id);
                FLOORPLAN.inventario = FLOORPLAN.inventario || [];
                var yaEnInventario = FLOORPLAN.inventario.some(function (m) {
                    return String(m.id) === String(d.id);
                });
                if (!yaEnInventario) {
                    FLOORPLAN.inventario.push({
                        id: d.id,
                        numero: d.numero,
                        pax: d.pax,
                        minimo: d.minimo,
                        maximo: d.maximo,
                        forma: d.forma,
                        ambiente_id: d.ambiente_id,
                        fabric_id: d.fabric_id
                    });
                }
            }
        }

        canvas.remove(obj);
        canvas.discardActiveObject();
        cerrarDetalles();
        canvas.renderAll();
        saveHistory();
        refreshTableList();
        autoGuardar();
    }

    function abrirDetalles(obj) {
        detallesObj = obj;
        var d = obj.mesaData || {};
        var panel = document.getElementById('fpDetails');
        if (!panel) return;

        var name = document.getElementById('detName');
        var amb  = document.getElementById('detAmbiente');
        var min  = document.getElementById('detMin');
        var max  = document.getElementById('detMax');
        var size = document.getElementById('detSize');

        if (name) name.value = d.numero || '';
        if (min)  min.value  = d.minimo || 2;
        if (max)  max.value  = d.maximo || 6;
        if (amb && d.ambiente_id) amb.value = String(d.ambiente_id);
        if (size) size.value = sizeKeyDe(obj);

        marcarFormaActiva(formaDe(obj));
        panel.hidden = false;

        if (FLOORPLAN.gerenteMode && d.id) {
            var info = document.getElementById('detReservas');
            if (info) cargarReservasMesa(d.id, info);
        }
    }

    function cerrarDetalles() {
        detallesObj = null;
        var panel = document.getElementById('fpDetails');
        if (panel) panel.hidden = true;
    }

    function marcarFormaActiva(shape) {
        document.querySelectorAll('#detShapeGroup .fp-shape-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.shape === shape);
        });
    }

    function formaDe(obj) {
        var d = obj.mesaData || {};
        if (d.forma === 'circle') return 'circle';
        var w = obj.width || 0;
        var h = obj.height || 0;
        return Math.abs(w - h) < Math.max(w, h) * 0.18 ? 'square' : 'rect';
    }

    function sizeKeyDe(obj) {
        var d = obj.mesaData || {};
        var seats = parseInt(d.maximo, 10) || 0;
        var bestKey = 'm';
        var diff = Infinity;
        Object.keys(SIZE_SEATS).forEach(function (k) {
            var dd = Math.abs(SIZE_SEATS[k] - seats);
            if (dd < diff) { diff = dd; bestKey = k; }
        });
        return bestKey;
    }

    /** Cambia el tamaño = número de asientos, reconstruyendo la mesa. */
    function cambiarTamano(obj, key) {
        var seats = SIZE_SEATS[key] || 4;
        reconstruirMesa(obj, formaDe(obj), seats);
    }

    /**
     * Reconstruye la mesa con la forma y el número de asientos indicados,
     * conservando identidad (id, número, ambiente), posición y rotación.
     * Sincroniza Cover Mín/Máx y la etiqueta para que todo sea coherente.
     */
    function reconstruirMesa(obj, shape, seats) {
        var d = obj.mesaData || {};
        var fabricId = d.fabric_id || ('fab_' + Date.now());
        var minimo = Math.min(parseInt(d.minimo, 10) || 1, seats) || 1;

        var group = FP_TABLE_PRESETS.buildBySeats({
            shape: shape,
            seats: seats,
            numero: String(d.numero),
            ambienteId: parseInt(d.ambiente_id, 10) || 1,
            fabricId: fabricId,
            minimo: minimo,
            maximo: seats
        });

        group.mesaData.id          = d.id || null;
        group.mesaData.numero      = String(d.numero);
        group.mesaData.pax         = seats;
        group.mesaData.minimo      = minimo;
        group.mesaData.maximo      = seats;
        group.mesaData.ambiente_id = d.ambiente_id;
        group.mesaData.fabric_id   = fabricId;

        group.set({
            left: obj.left,
            top: obj.top,
            angle: obj.angle || 0
        });

        canvas.remove(obj);
        canvas.add(group);
        prepararMesaGroup(group);
        canvas.setActiveObject(group);
        canvas.renderAll();

        detallesObj = group;

        // Refleja la nueva capacidad en los inputs de Cover Mín/Máx.
        var minInput = document.getElementById('detMin');
        var maxInput = document.getElementById('detMax');
        if (minInput) minInput.value = minimo;
        if (maxInput) maxInput.value = seats;

        saveHistory();
        autoGuardar();
    }

    /** Cambia la forma conservando el número de asientos actual. */
    function cambiarForma(obj, shape) {
        var d = obj.mesaData || {};
        var seats = parseInt(d.maximo, 10) || 2;
        reconstruirMesa(obj, shape, seats);
        marcarFormaActiva(shape);
    }

    function showSubpanel(id) {
        hideSubpanels();
        document.getElementById(id).hidden = false;
    }

    function hideSubpanels() {
        document.querySelectorAll('.fp-subpanel').forEach(function (p) { p.hidden = true; });
    }

    var ZOOM_MIN = 0.1;
    var ZOOM_MAX = 3;

    function applyZoom(scale) {
        scale = Math.min(Math.max(scale, ZOOM_MIN), ZOOM_MAX);
        canvas.setZoom(scale);
        canvas.setWidth(2400 * scale);
        canvas.setHeight(1600 * scale);
        canvas.renderAll();
        sincronizarSelectorZoom(scale);
        actualizarEtiquetaZoom();
    }

    /** Refleja el zoom actual en el <select> (opción más cercana). */
    function sincronizarSelectorZoom(scale) {
        var sel = document.getElementById('zoomSelect');
        if (!sel) return;
        var mejor = null, dif = Infinity;
        Array.prototype.forEach.call(sel.options, function (opt) {
            var d = Math.abs(parseFloat(opt.value) - scale);
            if (d < dif) { dif = d; mejor = opt.value; }
        });
        if (mejor !== null) { sel.value = mejor; }
    }

    /**
     * Zoom con la rueda del mouse manteniendo fijo el punto bajo el cursor.
     * Como el lienzo cambia de tamaño con el zoom, ajustamos el scroll del
     * contenedor para que el punto señalado no se mueva.
     */
    function zoomEnPunto(nuevoZoom, clientX, clientY) {
        var wrap = document.querySelector('.fp-canvas-wrap');
        var lower = canvas && canvas.lowerCanvasEl;
        if (!wrap || !lower) { applyZoom(nuevoZoom); return; }

        nuevoZoom = Math.min(Math.max(nuevoZoom, ZOOM_MIN), ZOOM_MAX);
        var zoomViejo = canvas.getZoom() || 1;

        var rectAntes = lower.getBoundingClientRect();
        // Coordenada del lienzo (sin escalar) bajo el cursor.
        var cx = (clientX - rectAntes.left) / zoomViejo;
        var cy = (clientY - rectAntes.top) / zoomViejo;

        applyZoom(nuevoZoom);

        // Tras redimensionar, alinea el scroll para que (cx,cy) siga bajo el cursor.
        var rectDespues = lower.getBoundingClientRect();
        wrap.scrollLeft += rectDespues.left - (clientX - cx * nuevoZoom);
        wrap.scrollTop  += rectDespues.top  - (clientY - cy * nuevoZoom);
    }

    function bindWheelZoom() {
        var wrap = document.querySelector('.fp-canvas-wrap');
        if (!wrap) return;

        wrap.addEventListener('wheel', function (e) {
            e.preventDefault();
            // Factor multiplicativo suave; el trackpad/pellizco usa ctrlKey.
            var intensidad = e.ctrlKey ? 0.01 : 0.0015;
            var factor = Math.exp(-e.deltaY * intensidad);
            zoomEnPunto((canvas.getZoom() || 1) * factor, e.clientX, e.clientY);
        }, { passive: false });
    }

    function renderPresets() {
        var container = document.getElementById('tablePresets');
        if (!container || !window.FP_TABLE_PRESETS) return;

        container.innerHTML = '';

        FP_TABLE_PRESETS.list.forEach(function (preset) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'fp-preset-btn';
            btn.title = preset.label;
            btn.innerHTML = FP_TABLE_PRESETS.buildIconSvg(preset);
            btn.addEventListener('click', function () {
                addTablePreset(preset);
                hideSubpanels();
            });
            container.appendChild(btn);
        });
    }

    function addTablePreset(preset) {
        tableCounter += 1;
        var numero = String(tableCounter);
        var ambienteId = FLOORPLAN.ambientes[0] ? FLOORPLAN.ambientes[0].id : 1;
        var fabricId = 'fab_' + Date.now() + '_' + tableCounter;

        var group = FP_TABLE_PRESETS.buildFabricGroup(preset, numero, ambienteId, fabricId);
        group.set({
            left: 400 + Math.random() * 200,
            top: 300 + Math.random() * 200
        });

        canvas.add(group);
        prepararMesaGroup(group);
        canvas.setActiveObject(group);
        canvas.renderAll();
        saveHistory();
    }

    function renderStructural() {
        if (!window.FP_STRUCTURES) return;

        fillStructPanel('structLabels', FP_STRUCTURES.labels, 'fp-struct-items--labels');
        fillStructPanel('structShapes', FP_STRUCTURES.shapes, 'fp-struct-items--shapes');
        fillStructPanel('structFurniture', FP_STRUCTURES.furniture, 'fp-struct-items--furniture');
    }

    function fillStructPanel(containerId, items, gridClass) {
        var container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        container.className = 'fp-struct-items ' + gridClass;

        items.forEach(function (item) {
            // div en vez de <button>: los botones (controles de formulario) no
            // inician arrastre HTML5 de forma confiable en varios navegadores.
            var btn = document.createElement('div');
            btn.setAttribute('role', 'button');
            btn.setAttribute('tabindex', '0');
            btn.className = 'fp-struct-btn' + (item.wide ? ' fp-struct-btn--wide' : '');
            btn.title = (item.label || item.id) + ' — clic o arrastra al plano';
            if (item.imgUrl) {
                var clsImg = 'fp-struct-img' + (item.grupo === 'label' ? ' fp-struct-img--label' : '');
                // draggable="false" en la imagen: deja que el botón maneje el
                // arrastre (si no, el navegador arrastra la imagen en sí).
                btn.innerHTML = '<img src="' + item.imgUrl + '" alt="" class="' + clsImg + '" draggable="false" loading="lazy">';
            } else {
                btn.innerHTML = item.icon || item.svg || '';
            }

            // Clic: agrega al centro del área visible. El subpanel permanece
            // abierto para poder seguir colocando estructuras en secuencia.
            btn.addEventListener('click', function () {
                addStructural(item);
            });

            // Arrastrar y soltar: suelta el elemento donde caiga en el lienzo.
            btn.setAttribute('draggable', 'true');
            btn.addEventListener('dragstart', function (e) {
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('text/plain', item.id);
                arrastreActual = item.id;
            });
            btn.addEventListener('dragend', function () {
                arrastreActual = null;
            });

            container.appendChild(btn);
        });
    }

    /**
     * Agrega un elemento estructural al lienzo. `pos` es opcional (coordenadas
     * del canvas {left, top}); si no se pasa, va al centro visible.
     *
     * - Texto: objeto IText editable.
     * - Formas (item.svg): vector nativo (escala sin pixelarse).
     * - Mobiliario/etiquetas (item.imgUrl): imagen oficial de SevenRooms.
     */
    function addStructural(item, pos) {
        if (!item) return;

        if (!pos) {
            var p = puntoDeAparicion();
            pos = { left: p.x, top: p.y };
        }

        // Texto editable.
        if (item.type === 'text') {
            var textObj = new fabric.IText('Texto', {
                left: pos.left,
                top: pos.top,
                originX: 'center',
                originY: 'center',
                centeredRotation: true,
                fontSize: 22,
                fill: '#e6e6e6',
                fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif',
                objectType: 'structural',
                structuralType: item.id
            });
            colocarEstructura(textObj);
            return;
        }

        // Imagen oficial (mobiliario / etiquetas).
        if (item.imgUrl) {
            var esEtiqueta = item.grupo === 'label';
            fabric.Image.fromURL(item.imgUrl, function (imgObj) {
                if (!imgObj) return;

                // Etiquetas: tamaño fijo legible (sus PNG son pequeños).
                // Mobiliario: escala constante que conserva proporciones reales.
                var escala = SR_ESCALA;
                if (esEtiqueta) {
                    var lado = Math.max(imgObj.width || 192, imgObj.height || 192);
                    escala = 46 / lado;
                }

                imgObj.set({
                    left: pos.left,
                    top: pos.top,
                    originX: 'center',
                    originY: 'center',
                    centeredRotation: true,
                    scaleX: escala,
                    scaleY: escala,
                    objectType: 'structural',
                    structuralType: item.id
                });

                // El mobiliario es gris oscuro → se invierte para verse sobre el
                // lienzo oscuro. Las etiquetas ya son claras: NO se invierten
                // (si no, quedan negras e invisibles sobre el fondo oscuro).
                if (!esEtiqueta) {
                    aclararImagenEstructura(imgObj);
                    ajustarAGrid(imgObj);
                }
                colocarEstructura(imgObj);
            });
            return;
        }

        if (!item.svg) return;

        fabric.loadSVGFromString(item.svg, function (objetos, opciones) {
            if (!objetos || !objetos.length) return;

            var obj = fabric.util.groupSVGElements(objetos, opciones);

            // Módulo fijo: 1 unidad de SVG = UNIDAD_ESTRUCTURA px. Así todas las
            // piezas comparten el mismo grosor/altura y se pueden encadenar.
            obj.set({
                left: pos.left,
                top: pos.top,
                originX: 'center',
                originY: 'center',
                centeredRotation: true,
                scaleX: UNIDAD_ESTRUCTURA,
                scaleY: UNIDAD_ESTRUCTURA,
                objectType: 'structural',
                structuralType: item.id
            });

            estilizarEstructuraVector(obj);
            colocarEstructura(obj);
        });
    }

    /**
     * Alinea la esquina superior-izquierda del objeto a la cuadrícula
     * (GRID_ESTRUCTURA). Solo cuando no está rotado, para que las piezas
     * empalmen borde con borde. Trabaja con origen al centro.
     */
    function ajustarAGrid(obj) {
        if (!obj || (obj.angle && obj.angle % 360 !== 0)) return;

        var w = obj.getScaledWidth();
        var h = obj.getScaledHeight();
        var bx = obj.left - w / 2;
        var by = obj.top - h / 2;
        var sx = Math.round(bx / GRID_ESTRUCTURA) * GRID_ESTRUCTURA;
        var sy = Math.round(by / GRID_ESTRUCTURA) * GRID_ESTRUCTURA;

        obj.set({ left: obj.left + (sx - bx), top: obj.top + (sy - by) });
        obj.setCoords();
    }

    /**
     * Estiliza las Formas (cuadrado, rect, círculo) como figuras SÓLIDAS de
     * color gris uniforme, igual que SevenRooms. Recorre subobjetos si es grupo.
     */
    function estilizarEstructuraVector(obj) {
        var RELLENO = '#8a8d91';   // gris sólido visible sobre el lienzo oscuro

        function pinta(o) {
            o.set({ fill: RELLENO, stroke: null, strokeWidth: 0 });
        }

        if (obj._objects && obj._objects.length) {
            obj._objects.forEach(pinta);
        } else {
            pinta(obj);
        }
        obj.dirty = true;
    }

    /**
     * Invierte el color de la imagen estructural para que el mobiliario
     * (PNG gris oscuro) sea visible sobre el lienzo oscuro. El filtro se
     * serializa en el canvas_json, así que se mantiene al recargar.
     */
    function aclararImagenEstructura(img) {
        if (!img || img.type !== 'image') return;
        if (!fabric.Image.filters || !fabric.Image.filters.Invert) return;
        try {
            var yaTiene = (img.filters || []).some(function (f) {
                return f instanceof fabric.Image.filters.Invert;
            });
            if (!yaTiene) {
                img.filters = img.filters || [];
                img.filters.push(new fabric.Image.filters.Invert());
                img.applyFilters();
            }
        } catch (e) {
            // Si el filtro falla (p. ej. imagen no lista), no bloquear el alta.
        }
    }

    /** Agrega un objeto estructural al canvas y dispara el guardado. */
    function colocarEstructura(obj) {
        canvas.add(obj);
        prepararEstructura(obj);
        canvas.setActiveObject(obj);
        canvas.renderAll();
        saveHistory();
        autoGuardar();
    }

    /**
     * Devuelve un punto seguro dentro del lienzo para colocar nuevos elementos.
     * Usa el centro del área visible cuando se puede calcular de forma fiable,
     * con respaldo a una zona central fija del canvas.
     */
    function puntoDeAparicion() {
        var x = 500;
        var y = 400;
        var wrap = document.querySelector('.fp-canvas-wrap');
        var lower = canvas && canvas.lowerCanvasEl;

        if (wrap && lower) {
            // Posición del centro visible relativo a la esquina del canvas (px de pantalla),
            // convertida a coordenadas del canvas dividiendo por el zoom.
            var wrapRect = wrap.getBoundingClientRect();
            var canvasRect = lower.getBoundingClientRect();
            var zoom = canvas.getZoom() || 1;

            var centroVisibleX = wrapRect.left + wrap.clientWidth / 2;
            var centroVisibleY = wrapRect.top + wrap.clientHeight / 2;

            x = (centroVisibleX - canvasRect.left) / zoom;
            y = (centroVisibleY - canvasRect.top) / zoom;
        }

        // Mantén el punto dentro de los límites del lienzo.
        x = Math.min(Math.max(x, 80), 2400 - 80);
        y = Math.min(Math.max(y, 80), 1600 - 80);
        return { x: x, y: y };
    }

    /** Convierte coordenadas de pantalla (evento) a coordenadas del lienzo. */
    function coordsDesdeEvento(e) {
        var lower = canvas && canvas.lowerCanvasEl;
        var zoom  = canvas.getZoom() || 1;
        var rect  = lower.getBoundingClientRect();
        var x = (e.clientX - rect.left) / zoom;
        var y = (e.clientY - rect.top) / zoom;
        x = Math.min(Math.max(x, 40), 2400 - 40);
        y = Math.min(Math.max(y, 40), 1600 - 40);
        return { left: x, top: y };
    }

    /** Permite arrastrar elementos del panel y soltarlos en el lienzo. */
    function bindDragAndDrop() {
        var wrap = document.querySelector('.fp-canvas-wrap');
        if (!wrap) return;

        wrap.addEventListener('dragover', function (e) {
            if (!arrastreActual) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        wrap.addEventListener('drop', function (e) {
            e.preventDefault();
            var id = (e.dataTransfer && e.dataTransfer.getData('text/plain')) || arrastreActual;
            arrastreActual = null;
            if (!id || !window.FP_STRUCTURES) return;

            var item = FP_STRUCTURES.byId(id);
            if (!item) return;

            // El subpanel permanece abierto para seguir arrastrando estructuras.
            addStructural(item, coordsDesdeEvento(e));
        });
    }

    function refreshTableList() {
        var list = document.getElementById('tableList');
        if (!list) return;
        list.innerHTML = '';

        var active = canvas.getActiveObject();
        var colocadas = canvas.getObjects().filter(function (o) { return o.mesaData; });

        // Mesas ya colocadas en este plano.
        colocadas.forEach(function (obj) {
            var d = obj.mesaData || {};
            var div = document.createElement('div');
            div.className = 'fp-table-item' + (active === obj ? ' selected' : '');
            div.innerHTML = '<span>' + (d.numero || '?') + '</span><span class="text-muted">' + (d.minimo || 2) + '–' + (d.maximo || 6) + '</span>';
            div.addEventListener('click', function () {
                canvas.setActiveObject(obj);
                canvas.renderAll();
                onSelectionChange();
            });
            list.appendChild(div);
        });

        // Mesas del inventario de la sucursal que aún no están en el plano.
        var disponibles = mesasInventarioDisponibles(colocadas);

        if (disponibles.length) {
            var header = document.createElement('div');
            header.className = 'fp-table-list-subheader';
            header.textContent = 'Mesas de la sucursal (' + disponibles.length + ')';
            list.appendChild(header);

            disponibles.forEach(function (mesa) {
                var div = document.createElement('div');
                div.className = 'fp-table-item fp-table-item--available';
                div.innerHTML = '<span>' + (mesa.numero || '?') + '</span>'
                    + '<span class="text-muted fp-ti-cap">' + (mesa.minimo || 2) + '–' + (mesa.maximo || 6) + '</span>'
                    + '<span class="fp-table-add" title="Agregar al plano">+</span>';
                div.title = 'Agregar la mesa ' + (mesa.numero || '') + ' al plano';
                div.addEventListener('click', function () {
                    placeInventoryMesa(mesa);
                });
                list.appendChild(div);
            });
        }
    }

    /** Inventario de la sucursal que no está colocado en el canvas. */
    function mesasInventarioDisponibles(colocadas) {
        var inventario = FLOORPLAN.inventario || [];
        if (!inventario.length) return [];

        var idsColocados = {};
        var clavesColocadas = {};
        colocadas.forEach(function (obj) {
            var d = obj.mesaData || {};
            if (d.id) idsColocados[String(d.id)] = true;
            if (d.numero != null) {
                clavesColocadas[String(d.numero) + '|' + (d.ambiente_id || '')] = true;
            }
        });

        return inventario.filter(function (mesa) {
            if (mesa.id && idsColocados[String(mesa.id)]) return false;
            var clave = String(mesa.numero) + '|' + (mesa.ambiente_id || '');
            if (clavesColocadas[clave]) return false;
            return true;
        });
    }

    /** Coloca en el canvas una mesa que ya existe en el inventario. */
    function placeInventoryMesa(mesa) {
        var forma = String(mesa.forma || '').toLowerCase();
        var esCircular = forma.indexOf('circ') === 0 || forma.indexOf('redond') === 0;
        var shape = esCircular ? 'circle' : 'rect';
        var seats = parseInt(mesa.maximo, 10) || 2;
        var minimo = parseInt(mesa.minimo, 10) || 1;
        var ambienteId = parseInt(mesa.ambiente_id, 10)
            || (FLOORPLAN.ambientes[0] ? FLOORPLAN.ambientes[0].id : 1);
        var fabricId = mesa.fabric_id || ('fab_' + Date.now() + '_' + (mesa.id || tableCounter));

        // Construye con el número real de asientos (sillas coherentes con la capacidad).
        var group = FP_TABLE_PRESETS.buildBySeats({
            shape: shape,
            seats: seats,
            numero: String(mesa.numero),
            ambienteId: ambienteId,
            fabricId: fabricId,
            minimo: minimo,
            maximo: seats
        });

        // Conserva los datos reales de la mesa del inventario.
        group.mesaData.id = mesa.id ? parseInt(mesa.id, 10) : null;
        group.mesaData.numero = String(mesa.numero);
        group.mesaData.pax = parseInt(mesa.pax, 10) || seats;
        group.mesaData.minimo = minimo;
        group.mesaData.maximo = seats;
        group.mesaData.forma = esCircular ? 'circle' : 'rect';
        group.mesaData.ambiente_id = ambienteId;
        group.mesaData.fabric_id = fabricId;

        group.set({
            left: 380 + Math.random() * 240,
            top: 280 + Math.random() * 200
        });

        canvas.add(group);
        prepararMesaGroup(group);
        canvas.setActiveObject(group);
        canvas.renderAll();
        saveHistory();
        refreshTableList();
    }

    function collectMesas() {
        return canvas.getObjects()
            .filter(function (o) { return o.mesaData; })
            .map(function (obj) {
                var d = obj.mesaData;
                return {
                    id: d.id || null,
                    numero: d.numero,
                    pax: d.pax,
                    minimo: d.minimo,
                    maximo: d.maximo,
                    forma: d.forma,
                    ambiente_id: d.ambiente_id,
                    pos_x: obj.left,
                    pos_y: obj.top,
                    ancho: obj.width * obj.scaleX,
                    alto: obj.height * obj.scaleY,
                    rotacion: obj.angle || 0,
                    fabric_id: d.fabric_id || ('fab_' + Date.now())
                };
            });
    }

    function setSaveStatus(texto) {
        var el = document.getElementById('fpSaveStatus');
        if (el) el.textContent = texto || '';
    }

    /**
     * Guarda canvas + mesas (coordenadas, ángulo, etc.).
     * @param {boolean} silent  true = autoguardado sin alertas.
     */
    function saveFloorplan(silent) {
        var nombreInput = document.getElementById('fpNombre');
        var nombre = nombreInput ? nombreInput.value.trim() : null;

        if (nombreInput && !nombre) {
            if (!silent) {
                alert('El nombre del floorplan no puede quedar vacío.');
                nombreInput.focus();
            }
            return;
        }

        // Evita guardados solapados (que duplicarían inserciones de mesas nuevas).
        if (guardando) {
            guardadoPendiente = true;
            return;
        }
        guardando = true;
        setSaveStatus('Guardando…');

        var desvinculadasEnviadas = mesasDesvinculadas.slice();
        var payload;
        try {
            payload = {
                canvas: canvas.toJSON(['objectType', 'mesaData', 'structuralType']),
                mesas: collectMesas(),
                mesas_desvinculadas: desvinculadasEnviadas,
                build: EDITOR_BUILD
            };
            if (nombre) payload.nombre = nombre;
        } catch (err) {
            // No dejar el flag atascado: bloquearía todos los guardados siguientes.
            guardando = false;
            setSaveStatus('Error al preparar el guardado');
            return;
        }

        fetch(FLOORPLAN.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                [FLOORPLAN.csrfHeader]: FLOORPLAN.csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            guardando = false;

            // CodeIgniter regenera el token CSRF en cada POST; lo refrescamos
            // para que el siguiente autoguardado no sea rechazado.
            if (data && data.csrf) {
                FLOORPLAN.csrfToken = data.csrf;
            }

            if (data.success) {
                document.getElementById('aforoValue').textContent = data.aforo;
                if (data.mesas_nuevas && data.mesas_nuevas.length) {
                    vincularMesasNuevas(data.mesas_nuevas);
                }
                // Descarta las que ya se desvincularon en el servidor.
                if (desvinculadasEnviadas.length) {
                    mesasDesvinculadas = mesasDesvinculadas.filter(function (id) {
                        return desvinculadasEnviadas.indexOf(id) === -1;
                    });
                }
                setSaveStatus('Guardado');
                if (!silent) {
                    setSaveStatus('Guardado ✓');
                }
            } else {
                setSaveStatus('Error al guardar');
                if (!silent) alert(data.error || 'Error al guardar');
            }

            if (guardadoPendiente) {
                guardadoPendiente = false;
                saveFloorplan(true);
            }
        })
        .catch(function () {
            guardando = false;
            setSaveStatus('Sin conexión');
            if (!silent) alert('Error de conexión');
        });
    }

    /** Autoguardado con rebote: persiste las coordenadas mientras se edita. */
    function autoGuardar() {
        if (!editorListo) return;
        setSaveStatus('Cambios sin guardar…');
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function () {
            saveFloorplan(true);
        }, 700);
    }

    function saveHistory() {
        var json = JSON.stringify(canvas.toJSON(['objectType', 'mesaData', 'structuralType']));
        history = history.slice(0, historyIndex + 1);
        history.push(json);
        historyIndex = history.length - 1;
    }

    function restaurarMesasTrasCarga() {
        canvas.getObjects().forEach(function (obj) {
            if (obj.mesaData) prepararMesaGroup(obj);
        });
    }

    function undo() {
        if (historyIndex <= 0) return;
        historyIndex--;
        canvas.loadFromJSON(history[historyIndex], function () {
            vincularMesasBd();
            restaurarMesasTrasCarga();
            canvas.renderAll();
            refreshTableList();
        });
    }

    function redo() {
        if (historyIndex >= history.length - 1) return;
        historyIndex++;
        canvas.loadFromJSON(history[historyIndex], function () {
            vincularMesasBd();
            restaurarMesasTrasCarga();
            canvas.renderAll();
            refreshTableList();
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
