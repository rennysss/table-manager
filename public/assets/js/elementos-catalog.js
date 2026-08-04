/**
 * Catálogo de elementos del FloorPlan — vistas de administración
 */
(function () {
    'use strict';

    function renderFormasMesas() {
        var grid = document.getElementById('catalogFormasMesas');
        var tbody = document.getElementById('tablaFormasMesas');

        if (!grid || !tbody || !window.FP_TABLE_PRESETS) {
            return;
        }

        grid.innerHTML = '';
        tbody.innerHTML = '';

        window.FP_TABLE_PRESETS.list.forEach(function (preset) {
            var card = document.createElement('div');
            card.className = 'app-elementos-item';
            card.title = preset.label;
            card.innerHTML = window.FP_TABLE_PRESETS.buildIconSvg(preset);

            var caption = document.createElement('span');
            caption.className = 'app-elementos-item-label';
            caption.textContent = preset.label;
            card.appendChild(caption);

            grid.appendChild(card);

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><code>' + preset.id + '</code></td>' +
                '<td>' + preset.label + '</td>' +
                '<td>' + (preset.forma === 'circle' ? 'Redonda' : 'Rectangular') + '</td>' +
                '<td>' + preset.minimo + '</td>' +
                '<td>' + preset.maximo + '</td>';
            tbody.appendChild(tr);
        });
    }

    function renderStructItems(container, items, gridClass) {
        if (!container || !items) {
            return;
        }

        container.innerHTML = '';
        container.className = 'app-elementos-struct-grid' + (gridClass ? ' ' + gridClass : '');

        items.forEach(function (item) {
            var card = document.createElement('div');
            card.className = 'app-elementos-struct-item';
            card.title = item.label || item.id;

            if (item.icon) {
                card.innerHTML = item.icon;
            } else if (item.imgUrl) {
                card.innerHTML = '<img src="' + item.imgUrl + '" alt="" class="fp-struct-img" width="48" height="48" loading="lazy">';
            } else if (item.svg) {
                card.innerHTML = item.svg;
            }

            container.appendChild(card);
        });
    }

    function renderEstructurales() {
        if (!window.FP_STRUCTURES) {
            return;
        }

        renderStructItems(
            document.getElementById('catalogStructLabels'),
            window.FP_STRUCTURES.labels
        );
        renderStructItems(
            document.getElementById('catalogStructShapes'),
            window.FP_STRUCTURES.shapes,
            'app-elementos-struct-grid--shapes'
        );
        renderStructItems(
            document.getElementById('catalogStructFurniture'),
            window.FP_STRUCTURES.furniture,
            'app-elementos-struct-grid--furniture'
        );
    }

    window.ElementosCatalog = {
        initFormasMesas: renderFormasMesas,
        initEstructurales: renderEstructurales
    };
})();
