/**
 * Submenú de usuario en la barra superior
 */
(function () {
    'use strict';

    var wrap = document.getElementById('userMenuWrap');
    var toggle = document.getElementById('userMenuToggle');
    var dropdown = document.getElementById('userMenuDropdown');

    if (!wrap || !toggle || !dropdown) return;

    function openMenu() {
        dropdown.hidden = false;
        wrap.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    function closeMenu() {
        dropdown.hidden = true;
        wrap.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    function isOpen() {
        return wrap.classList.contains('is-open');
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (isOpen()) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();
        }
    });
})();
