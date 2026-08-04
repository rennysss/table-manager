/**
 * Submenú Reservas en sidebar — país → ciudad → sucursal
 */
(function () {
    'use strict';

    var cfg = window.APP && window.APP.reservasLoc;
    var $root = $('#sidebarReservasUbicaciones');
    var $group = $('.sidebar-menu-item--reservas');

    if (!cfg || !$root.length) {
        return;
    }

    var paisesCargados = false;

    function fechaActual() {
        var params = new URLSearchParams(window.location.search);
        return params.get('fecha') || cfg.fecha || '';
    }

    function urlReservas(sucursalId) {
        var q = '?fecha=' + encodeURIComponent(fechaActual() || cfg.fecha);
        if (sucursalId) {
            q += '&sucursal_id=' + encodeURIComponent(sucursalId);
        }
        return cfg.detalleUrl + q;
    }

    function marcarCargando($lista, texto) {
        $lista.html('<li class="sidebar-submenu-item"><span class="sidebar-submenu-status">' + (texto || 'Cargando…') + '</span></li>');
    }

    function cargarPaises() {
        if (paisesCargados) {
            return Promise.resolve();
        }

        marcarCargando($root);

        return fetch(cfg.apiPaises, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                $root.empty();
                paisesCargados = true;

                if (!data.paises || !data.paises.length) {
                    $root.html('<li class="sidebar-submenu-item"><span class="sidebar-submenu-status">Sin sucursales asignadas</span></li>');
                    return;
                }

                data.paises.forEach(function (pais) {
                    $root.append(crearItemPais(pais));
                });

                if (cfg.sucursalPaisId) {
                    expandirRutaActiva();
                }
            })
            .catch(function () {
                $root.html('<li class="sidebar-submenu-item"><span class="sidebar-submenu-status">Error al cargar</span></li>');
            });
    }

    function crearItemPais(pais) {
        var $li = $('<li class="sidebar-menu-item sidebar-menu-item--group sidebar-submenu-item"></li>');
        var $btn = $('<button type="button" class="sidebar-menu-link sidebar-menu-toggle sidebar-submenu-link sidebar-submenu-link--depth-1"></button>');
        $btn.append('<span class="sidebar-menu-label"></span>').find('.sidebar-menu-label').text(pais.nombre);
        $btn.append(
            '<span class="sidebar-menu-chevron" aria-hidden="true">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<polyline points="9 18 15 12 9 6"/></svg></span>'
        );
        $btn.attr('data-pais-id', pais.id);

        var $sub = $('<ul class="sidebar-submenu sidebar-submenu--nested"></ul>');

        $btn.on('click', function () {
            var $item = $btn.closest('.sidebar-menu-item--group');
            var abierto = $item.hasClass('is-open');

            if (!abierto && !$sub.children().length) {
                cargarCiudades(pais.id, $sub);
            }
        });

        $li.append($btn).append($sub);
        return $li;
    }

    function cargarCiudades(paisId, $lista) {
        marcarCargando($lista);

        fetch(cfg.apiCiudades + '/' + paisId + '/ciudades', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                $lista.empty();

                if (!data.ciudades || !data.ciudades.length) {
                    $lista.html('<li class="sidebar-submenu-item"><span class="sidebar-submenu-status">Sin ciudades</span></li>');
                    return;
                }

                data.ciudades.forEach(function (ciudad) {
                    $lista.append(crearItemCiudad(paisId, ciudad));
                });
            })
            .catch(function () {
                $lista.html('<li class="sidebar-submenu-item"><span class="sidebar-submenu-status">Error al cargar</span></li>');
            });
    }

    function crearItemCiudad(paisId, ciudad) {
        var $li = $('<li class="sidebar-menu-item sidebar-menu-item--group sidebar-submenu-item"></li>');
        var $btn = $('<button type="button" class="sidebar-menu-link sidebar-menu-toggle sidebar-submenu-link sidebar-submenu-link--depth-2"></button>');
        $btn.append('<span class="sidebar-menu-label"></span>').find('.sidebar-menu-label').text(ciudad);
        $btn.append(
            '<span class="sidebar-menu-chevron" aria-hidden="true">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<polyline points="9 18 15 12 9 6"/></svg></span>'
        );

        var $sub = $('<ul class="sidebar-submenu sidebar-submenu--nested"></ul>');

        $btn.on('click', function () {
            var $item = $btn.closest('.sidebar-menu-item--group');
            var abierto = $item.hasClass('is-open');

            if (!abierto && !$sub.children().length) {
                cargarSucursales(paisId, ciudad, $sub);
            }
        });

        $li.append($btn).append($sub);
        return $li;
    }

    function cargarSucursales(paisId, ciudad, $lista) {
        marcarCargando($lista);

        var url = cfg.apiSucursales + '?pais_id=' + encodeURIComponent(paisId) +
            '&ciudad=' + encodeURIComponent(ciudad);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                $lista.empty();

                if (!data.sucursales || !data.sucursales.length) {
                    $lista.html('<li class="sidebar-submenu-item"><span class="sidebar-submenu-status">Sin sucursales</span></li>');
                    return;
                }

                data.sucursales.forEach(function (s) {
                    var activa = parseInt(cfg.sucursalId, 10) === s.id;
                    var $link = $('<a class="sidebar-menu-link sidebar-submenu-link sidebar-submenu-link--depth-3"></a>');
                    $link.attr('href', urlReservas(s.id));
                    $link.append('<span class="sidebar-menu-label"></span>').find('.sidebar-menu-label').text(s.nombre);
                    if (activa) {
                        $link.addClass('active');
                    }
                    $lista.append($('<li class="sidebar-submenu-item"></li>').append($link));
                });
            })
            .catch(function () {
                $lista.html('<li class="sidebar-submenu-item"><span class="sidebar-submenu-status">Error al cargar</span></li>');
            });
    }

    function expandirRutaActiva() {
        var paisId = cfg.sucursalPaisId;
        var ciudad = cfg.sucursalCiudad;
        if (!paisId) {
            return;
        }

        var $btnPais = $root.find('.sidebar-submenu-link--depth-1[data-pais-id="' + paisId + '"]');
        if (!$btnPais.length) {
            return;
        }

        var $itemPais = $btnPais.closest('.sidebar-menu-item--group');
        $itemPais.addClass('is-open');
        $btnPais.attr('aria-expanded', 'true');

        var $subPais = $itemPais.children('.sidebar-submenu');
        if (!$subPais.children().length) {
            cargarCiudades(paisId, $subPais);
        }

        setTimeout(function () {
            $subPais.find('.sidebar-submenu-link--depth-2').each(function () {
                if ($(this).find('.sidebar-menu-label').text() === ciudad) {
                    var $itemCiudad = $(this).closest('.sidebar-menu-item--group');
                    $itemCiudad.addClass('is-open');
                    $(this).attr('aria-expanded', 'true');
                    var $subCiudad = $itemCiudad.children('.sidebar-submenu');
                    if (!$subCiudad.children().length) {
                        cargarSucursales(paisId, ciudad, $subCiudad);
                    }
                }
            });
        }, 350);
    }

    // Cargar al abrir el grupo Reservas
    $group.find('> .sidebar-menu-toggle').on('click', function () {
        setTimeout(function () {
            if ($group.hasClass('is-open')) {
                cargarPaises();
            }
        }, 0);
    });

    // Si ya está abierto (página de reservas), cargar de inmediato
    if ($group.hasClass('is-open')) {
        cargarPaises();
    }
})();
