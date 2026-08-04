/**
 * Catálogo de elementos estructurales del FloorPlan.
 *
 * El mobiliario y las etiquetas con imagen usan los assets oficiales de
 * SevenRooms (PNG de alta resolución) guardados en
 * /assets/img/structures/sr/. Así se respeta el dibujo exacto. Las "Formas"
 * (cuadrado, rect, círculo) y el "Texto" se mantienen como vectores nativos.
 */
(function () {
    'use strict';

    var BASE = (window.APP && window.APP.baseUrl ? window.APP.baseUrl : '/')
        + 'assets/img/structures/sr/';

    function img(file) { return BASE + file + '.png'; }

    var labels = [
        {
            id: 'label-text',
            label: 'Text',
            type: 'text',
            wide: true,
            icon: '<span class="fp-struct-text-label">Text</span>'
        },
        { id: 'label_exit',       label: 'Salida', grupo: 'label', imgUrl: img('label_exit') },
        { id: 'label_headphones', label: 'DJ',     grupo: 'label', imgUrl: img('label_headphones') },
        { id: 'label_arrow',      label: 'Flecha', grupo: 'label', imgUrl: img('label_arrow') }
    ];

    var shapes = [
        {
            id: 'shape-square',
            label: 'Cuadrado',
            svg: '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="8" width="32" height="32" fill="#4a4a4a"/></svg>',
            fabric: 'rect'
        },
        {
            id: 'shape-roundrect',
            label: 'Rect. redondeado',
            svg: '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="8" width="32" height="32" rx="8" fill="#4a4a4a"/></svg>',
            fabric: 'roundrect'
        },
        {
            id: 'shape-circle',
            label: 'Círculo',
            svg: '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="16" fill="#4a4a4a"/></svg>',
            fabric: 'circle'
        }
    ];

    // Mobiliario: imágenes oficiales de SevenRooms (mismo orden del panel).
    var furnitureNames = [
        ['bar_round_corner',          'Barra esquina curva'],
        ['bar_round',                 'Barra curva'],
        ['bar_square_corner',         'Barra esquina recta'],
        ['bar_square',                'Barra recta'],
        ['sofa_squareback_banquet',   'Banca / Banquet'],
        ['booth_half',                'Booth medio'],
        ['booth_quarter',             'Booth cuarto'],
        ['chair_roundback',           'Silla respaldo redondo'],
        ['chair_squareback',          'Silla respaldo recto'],
        ['section_corner_round',      'Sección esquina curva'],
        ['section_corner_square',     'Sección esquina recta'],
        ['section_end_cap',           'Sección tope'],
        ['section_end_round',         'Sección fin curvo'],
        ['section_end_square',        'Sección fin recto'],
        ['section_middle',            'Sección media'],
        ['sofa_roundback_long',       'Sofá redondo largo'],
        ['sofa_roundback_short',      'Sofá redondo corto'],
        ['sofa_roundback_standard',   'Sofá redondo estándar'],
        ['sofa_squareback_long',      'Sofá recto largo'],
        ['sofa_squareback_short',     'Sofá recto corto'],
        ['sofa_squareback_standard',  'Sofá recto estándar'],
        ['sofa_squareback_sectional', 'Sofá seccional'],
        ['stair_l',                   'Escalera en L'],
        ['stair_straight_short',      'Escalera recta corta'],
        ['stair_straight',            'Escalera recta'],
        ['stair_u',                   'Escalera en U'],
        ['stair_spiral',              'Escalera caracol'],
        ['shape_piano',               'Piano'],
        ['line_quarter',              'Curva']
    ];

    var furniture = furnitureNames.map(function (f) {
        return { id: f[0], label: f[1], imgUrl: img(f[0]) };
    });

    var index = {};
    [labels, shapes, furniture].forEach(function (grupo) {
        grupo.forEach(function (it) { index[it.id] = it; });
    });

    window.FP_STRUCTURES = {
        labels: labels,
        shapes: shapes,
        furniture: furniture,
        byId: function (id) { return index[id] || null; }
    };
})();
