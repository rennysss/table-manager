/**
 * Presets de mesas para el FloorPlan (referencia Sevenrooms).
 * Fila 1: cuadradas/rectangulares · Fila 2: circulares.
 */
(function () {
    'use strict';

    var TABLE_FILL = '#ebebef';
    var TABLE_STROKE = '#b8b8bd';
    var CHAIR_FILL = '#8e8e93';

    /** Posiciones de sillas alrededor de mesa rectangular (coords en viewBox 64×64) */
    function rectChairsSvg(w, h, cx, cy, sides) {
        var cw = 7;
        var ch = 4;
        var html = '';
        var gap = 2;

        sides.forEach(function (side) {
            var x = cx;
            var y = cy;
            var rw = cw;
            var rh = ch;

            if (side === 'left') {
                x = cx - w / 2 - cw - gap;
                y = cy - ch / 2;
            } else if (side === 'right') {
                x = cx + w / 2 + gap;
                y = cy - ch / 2;
            } else if (side === 'top') {
                x = cx - cw / 2;
                y = cy - h / 2 - ch - gap;
            } else if (side === 'bottom') {
                x = cx - cw / 2;
                y = cy + h / 2 + gap;
            } else if (side === 'top-left') {
                x = cx - w * 0.22 - cw / 2;
                y = cy - h / 2 - ch - gap;
            } else if (side === 'top-right') {
                x = cx + w * 0.22 - cw / 2;
                y = cy - h / 2 - ch - gap;
            } else if (side === 'bottom-left') {
                x = cx - w * 0.22 - cw / 2;
                y = cy + h / 2 + gap;
            } else if (side === 'bottom-right') {
                x = cx + w * 0.22 - cw / 2;
                y = cy + h / 2 + gap;
            }

            html += '<rect x="' + x + '" y="' + y + '" width="' + rw + '" height="' + rh + '" rx="1" fill="' + CHAIR_FILL + '"/>';
        });

        return html;
    }

    /** Sillas distribuidas en círculo */
    function circleChairsSvg(r, cx, cy, count) {
        var html = '';
        var cw = 7;
        var ch = 4;
        var dist = r + ch + 3;

        for (var i = 0; i < count; i++) {
            var angle = (-Math.PI / 2) + (i * 2 * Math.PI / count);
            var x = cx + Math.cos(angle) * dist - cw / 2;
            var y = cy + Math.sin(angle) * dist - ch / 2;
            var rot = (angle * 180 / Math.PI) + 90;
            html += '<rect x="' + x + '" y="' + y + '" width="' + cw + '" height="' + ch + '" rx="1" fill="' + CHAIR_FILL + '" transform="rotate(' + rot + ' ' + (x + cw / 2) + ' ' + (y + ch / 2) + ')"/>';
        }

        return html;
    }

    function buildIconSvg(preset) {
        var cx = 32;
        var cy = 32;
        var svg = '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">';

        if (preset.forma === 'circle') {
            var ir = preset.iconR || 14;
            svg += '<circle cx="' + cx + '" cy="' + cy + '" r="' + ir + '" fill="' + TABLE_FILL + '" stroke="' + TABLE_STROKE + '" stroke-width="1"/>';
            svg += circleChairsSvg(ir, cx, cy, preset.chairCount);
        } else {
            var iw = preset.iconW || 20;
            var ih = preset.iconH || 20;
            svg += '<rect x="' + (cx - iw / 2) + '" y="' + (cy - ih / 2) + '" width="' + iw + '" height="' + ih + '" rx="4" fill="' + TABLE_FILL + '" stroke="' + TABLE_STROKE + '" stroke-width="1"/>';
            svg += rectChairsSvg(iw, ih, cx, cy, preset.chairSides);
        }

        svg += '</svg>';
        return svg;
    }

    /** Crea sillas en Fabric alrededor de mesa rectangular */
    function createRectChairs(preset, tableW, tableH) {
        var chairs = [];
        var cw = 14;
        var ch = 8;
        var gap = 3;

        preset.chairSides.forEach(function (side) {
            var left = 0;
            var top = 0;
            var angle = 0;

            if (side === 'left') {
                left = -tableW / 2 - cw / 2 - gap;
                top = 0;
                angle = 90;
            } else if (side === 'right') {
                left = tableW / 2 + cw / 2 + gap;
                top = 0;
                angle = 90;
            } else if (side === 'top') {
                left = 0;
                top = -tableH / 2 - ch / 2 - gap;
            } else if (side === 'bottom') {
                left = 0;
                top = tableH / 2 + ch / 2 + gap;
            } else if (side === 'top-left') {
                left = -tableW * 0.22;
                top = -tableH / 2 - ch / 2 - gap;
            } else if (side === 'top-right') {
                left = tableW * 0.22;
                top = -tableH / 2 - ch / 2 - gap;
            } else if (side === 'bottom-left') {
                left = -tableW * 0.22;
                top = tableH / 2 + ch / 2 + gap;
            } else if (side === 'bottom-right') {
                left = tableW * 0.22;
                top = tableH / 2 + ch / 2 + gap;
            }

            chairs.push(new fabric.Rect({
                width: cw,
                height: ch,
                fill: CHAIR_FILL,
                rx: 1,
                ry: 1,
                originX: 'center',
                originY: 'center',
                left: left,
                top: top,
                angle: angle,
                selectable: false,
                evented: false
            }));
        });

        return chairs;
    }

    /** Crea sillas en Fabric alrededor de mesa circular */
    function createCircleChairs(preset, radius) {
        var chairs = [];
        var cw = 14;
        var ch = 8;
        var dist = radius + ch / 2 + 4;
        var count = preset.chairCount;

        for (var i = 0; i < count; i++) {
            var angleRad = (-Math.PI / 2) + (i * 2 * Math.PI / count);
            var left = Math.cos(angleRad) * dist;
            var top = Math.sin(angleRad) * dist;
            var angleDeg = (angleRad * 180 / Math.PI) + 90;

            chairs.push(new fabric.Rect({
                width: cw,
                height: ch,
                fill: CHAIR_FILL,
                rx: 1,
                ry: 1,
                originX: 'center',
                originY: 'center',
                left: left,
                top: top,
                angle: angleDeg,
                selectable: false,
                evented: false
            }));
        }

        return chairs;
    }

    /* ----- Construcción genérica por número de asientos ----- */
    var CHAIR_W = 14;
    var CHAIR_H = 8;
    var CHAIR_GAP = 3;
    var CHAIR_PITCH = 22; // espacio que ocupa cada silla a lo largo de un lado

    /** Reparte N asientos en los lados de una mesa rectangular/cuadrada. */
    function distribuirAsientosRect(seats, isSquare) {
        var d = { top: 0, right: 0, bottom: 0, left: 0 };
        if (seats <= 0) return d;

        if (isSquare) {
            // Cuadrada: se reparte lo más parejo posible en los 4 lados.
            var base = Math.floor(seats / 4);
            var rem = seats % 4;
            d.top = d.right = d.bottom = d.left = base;
            var orden = ['top', 'bottom', 'left', 'right'];
            for (var i = 0; i < rem; i++) { d[orden[i]]++; }
        } else {
            // Rectangular: lados largos (arriba/abajo) llevan la mayoría;
            // las cabeceras (izq/der) reciben 1 silla cuando hay 6 o más.
            var ends = seats >= 6 ? 1 : 0;
            var resto = seats - ends * 2;
            if (resto < 0) { resto = seats; ends = 0; }
            d.top = Math.ceil(resto / 2);
            d.bottom = resto - d.top;
            d.left = ends;
            d.right = ends;
        }
        return d;
    }

    /** Dimensiones de la mesa rectangular según el reparto de asientos. */
    function dimsRect(dist, isSquare) {
        if (isSquare) {
            var maxSide = Math.max(dist.top, dist.bottom, dist.left, dist.right, 1);
            var lado = Math.max(58, maxSide * CHAIR_PITCH + 16);
            return { w: lado, h: lado };
        }
        var maxLargo = Math.max(dist.top, dist.bottom, 1);
        return { w: Math.max(58, maxLargo * CHAIR_PITCH + 16), h: 58 };
    }

    /** Posiciones equidistantes centradas a lo largo de un lado. */
    function offsetsLinea(count, length) {
        var arr = [];
        if (count <= 0) return arr;
        var step = length / count;
        var start = -length / 2 + step / 2;
        for (var i = 0; i < count; i++) { arr.push(start + i * step); }
        return arr;
    }

    function nuevaSilla(left, top, angle) {
        return new fabric.Rect({
            width: CHAIR_W,
            height: CHAIR_H,
            fill: CHAIR_FILL,
            rx: 1,
            ry: 1,
            originX: 'center',
            originY: 'center',
            left: left,
            top: top,
            angle: angle || 0,
            selectable: false,
            evented: false
        });
    }

    /** Sillas Fabric repartidas en los 4 lados según `dist`. */
    function sillasRectDist(dist, w, h) {
        var chairs = [];
        // Inset para que las sillas no sobresalgan en las esquinas.
        var insW = Math.max(w - 18, w * 0.5);
        var insH = Math.max(h - 18, h * 0.5);

        offsetsLinea(dist.top, insW).forEach(function (x) {
            chairs.push(nuevaSilla(x, -h / 2 - CHAIR_H / 2 - CHAIR_GAP, 0));
        });
        offsetsLinea(dist.bottom, insW).forEach(function (x) {
            chairs.push(nuevaSilla(x, h / 2 + CHAIR_H / 2 + CHAIR_GAP, 0));
        });
        offsetsLinea(dist.left, insH).forEach(function (y) {
            chairs.push(nuevaSilla(-w / 2 - CHAIR_W / 2 - CHAIR_GAP, y, 90));
        });
        offsetsLinea(dist.right, insH).forEach(function (y) {
            chairs.push(nuevaSilla(w / 2 + CHAIR_W / 2 + CHAIR_GAP, y, 90));
        });
        return chairs;
    }

    /** Radio de mesa circular acorde al número de asientos. */
    function radioCircular(seats) {
        return Math.max(22, 16 + seats * 4);
    }

    /** Sillas Fabric distribuidas en círculo (cantidad explícita). */
    function sillasCirculo(count, radius) {
        var chairs = [];
        if (count <= 0) return chairs;
        var dist = radius + CHAIR_H / 2 + 4;
        for (var i = 0; i < count; i++) {
            var a = (-Math.PI / 2) + (i * 2 * Math.PI / count);
            chairs.push(nuevaSilla(
                Math.cos(a) * dist,
                Math.sin(a) * dist,
                (a * 180 / Math.PI) + 90
            ));
        }
        return chairs;
    }

    function etiquetaMesa(numero, minimo, maximo) {
        var paxStr = minimo + '-' + maximo + ' Pax';
        var lineaNumero = new fabric.Text('#' + numero, {
            fontSize: 12,
            fontWeight: 'bold',
            fill: '#1D1D1F',
            originX: 'center',
            originY: 'center',
            top: -10,
            textAlign: 'center',
            fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif'
        });
        var tagW = Math.max(42, paxStr.length * 5.4 + 12);
        var tagBg = new fabric.Rect({
            width: tagW,
            height: 15,
            rx: 4,
            ry: 4,
            fill: '#111111',
            stroke: '#111111',
            strokeWidth: 1.25,
            originX: 'center',
            originY: 'center',
            top: 6
        });
        var tagTxt = new fabric.Text(paxStr, {
            fontSize: 9,
            fontWeight: '600',
            fill: '#FFFFFF',
            originX: 'center',
            originY: 'center',
            top: 6,
            fontFamily: '-apple-system, BlinkMacSystemFont, system-ui, sans-serif'
        });
        return new fabric.Group([lineaNumero, tagBg, tagTxt], {
            originX: 'center',
            originY: 'center',
            objectType: 'mesaLabel'
        });
    }

    window.FP_TABLE_PRESETS = {
        list: [
            {
                id: 'sq-1',
                label: 'Cuadrada 1 pax',
                forma: 'rect',
                w: 52, h: 52,
                iconW: 16, iconH: 16,
                minimo: 1, maximo: 1,
                chairSides: ['left']
            },
            {
                id: 'sq-2',
                label: 'Cuadrada 2 pax',
                forma: 'rect',
                w: 60, h: 60,
                iconW: 20, iconH: 20,
                minimo: 2, maximo: 2,
                chairSides: ['left', 'right']
            },
            {
                id: 'sq-4',
                label: 'Cuadrada 4 pax',
                forma: 'rect',
                w: 76, h: 76,
                iconW: 26, iconH: 26,
                minimo: 2, maximo: 4,
                chairSides: ['top', 'bottom', 'left', 'right']
            },
            {
                id: 'rect-4',
                label: 'Rectangular 4 pax',
                forma: 'rect',
                w: 110, h: 58,
                iconW: 34, iconH: 16,
                minimo: 2, maximo: 4,
                chairSides: ['top-left', 'top-right', 'bottom-left', 'bottom-right']
            },
            {
                id: 'rect-6',
                label: 'Rectangular 6 pax',
                forma: 'rect',
                w: 130, h: 58,
                iconW: 38, iconH: 16,
                minimo: 4, maximo: 6,
                chairSides: ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'left', 'right']
            },
            {
                id: 'circ-1',
                label: 'Redonda 1 pax',
                forma: 'circle',
                w: 48, h: 48,
                iconR: 10,
                minimo: 1, maximo: 1,
                chairCount: 1
            },
            {
                id: 'circ-2',
                label: 'Redonda 2 pax',
                forma: 'circle',
                w: 56, h: 56,
                iconR: 12,
                minimo: 2, maximo: 2,
                chairCount: 2
            },
            {
                id: 'circ-3',
                label: 'Redonda 3 pax',
                forma: 'circle',
                w: 68, h: 68,
                iconR: 14,
                minimo: 2, maximo: 3,
                chairCount: 3
            },
            {
                id: 'circ-5',
                label: 'Redonda 5 pax',
                forma: 'circle',
                w: 84, h: 84,
                iconR: 17,
                minimo: 4, maximo: 5,
                chairCount: 5
            },
            {
                id: 'circ-8',
                label: 'Redonda 8 pax',
                forma: 'circle',
                w: 100, h: 100,
                iconR: 20,
                minimo: 6, maximo: 8,
                chairCount: 8
            }
        ],

        buildIconSvg: buildIconSvg,

        /**
         * Construye una mesa (mesa + N sillas + etiqueta) a partir del número
         * de asientos. La forma puede ser 'rect', 'square' o 'circle'. El
         * tamaño de la mesa y el reparto de sillas se calculan para que el
         * resultado sea coherente en cualquier forma.
         */
        buildBySeats: function (opts) {
            var shape = opts.shape || 'rect';
            var seats = Math.max(0, parseInt(opts.seats, 10) || 0);
            var numero = opts.numero;
            var minimo = (opts.minimo != null) ? opts.minimo : seats;
            var maximo = (opts.maximo != null) ? opts.maximo : seats;
            var parts = [];
            var formaData;

            if (shape === 'circle') {
                var r = radioCircular(seats);
                formaData = 'circle';
                parts.push(new fabric.Circle({
                    radius: r,
                    fill: TABLE_FILL,
                    stroke: '#FFFFFF',
                    strokeWidth: 2,
                    originX: 'center',
                    originY: 'center'
                }));
                parts = parts.concat(sillasCirculo(seats, r));
            } else {
                var isSquare = shape === 'square';
                var dist = distribuirAsientosRect(seats, isSquare);
                var dims = dimsRect(dist, isSquare);
                formaData = 'rect';
                parts.push(new fabric.Rect({
                    width: dims.w,
                    height: dims.h,
                    fill: TABLE_FILL,
                    stroke: '#FFFFFF',
                    strokeWidth: 2,
                    rx: 8,
                    ry: 8,
                    originX: 'center',
                    originY: 'center'
                }));
                parts = parts.concat(sillasRectDist(dist, dims.w, dims.h));
            }

            parts.push(etiquetaMesa(numero, minimo, maximo));

            return new fabric.Group(parts, {
                objectType: 'mesa',
                centeredRotation: true,
                mesaData: {
                    numero: numero,
                    pax: maximo,
                    minimo: minimo,
                    maximo: maximo,
                    forma: formaData,
                    ambiente_id: opts.ambienteId,
                    fabric_id: opts.fabricId
                }
            });
        },

        /** Construye objetos Fabric (mesa + sillas + etiqueta) para un preset */
        buildFabricGroup: function (preset, numero, ambienteId, fabricId) {
            var parts = [];
            var tableW = preset.w;
            var tableH = preset.h;

            if (preset.forma === 'circle') {
                var radius = preset.w / 2;
                parts.push(new fabric.Circle({
                    radius: radius,
                    fill: TABLE_FILL,
                    stroke: '#FFFFFF',
                    strokeWidth: 2,
                    originX: 'center',
                    originY: 'center'
                }));
                parts = parts.concat(createCircleChairs(preset, radius));
            } else {
                parts.push(new fabric.Rect({
                    width: tableW,
                    height: tableH,
                    fill: TABLE_FILL,
                    stroke: '#FFFFFF',
                    strokeWidth: 2,
                    rx: 8,
                    ry: 8,
                    originX: 'center',
                    originY: 'center'
                }));
                parts = parts.concat(createRectChairs(preset, tableW, tableH));
            }

            parts.push(etiquetaMesa(numero, preset.minimo, preset.maximo));

            return new fabric.Group(parts, {
                objectType: 'mesa',
                centeredRotation: true,
                mesaData: {
                    numero: numero,
                    pax: preset.maximo,
                    minimo: preset.minimo,
                    maximo: preset.maximo,
                    forma: preset.forma,
                    presetId: preset.id,
                    ambiente_id: ambienteId,
                    fabric_id: fabricId
                }
            });
        }
    };
})();
