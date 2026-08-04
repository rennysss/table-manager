/**
 * Sidebar colapsable — persistencia en localStorage
 */
(function () {
    'use strict';

    var STORAGE_KEY = '1r_sidebar_collapsed';
    var shell = document.getElementById('appShell');
    var btn = document.getElementById('sidebarCollapseBtn');
    var mobileBtn = document.getElementById('sidebarMobileToggle');
    var overlay = document.getElementById('sidebarOverlay');

    if (!shell) return;

    function setCollapsed(collapsed) {
        shell.classList.toggle('sidebar-collapsed', collapsed);
        if (btn) {
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (e) {}
    }

    function isCollapsed() {
        return shell.classList.contains('sidebar-collapsed');
    }

    if (btn) {
        btn.addEventListener('click', function () {
            setCollapsed(!isCollapsed());
        });
    }

    if (mobileBtn) {
        mobileBtn.addEventListener('click', function () {
            shell.classList.toggle('sidebar-mobile-open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            shell.classList.remove('sidebar-mobile-open');
        });
    }

    try {
        if (localStorage.getItem(STORAGE_KEY) === '1') {
            setCollapsed(true);
        }
    } catch (e) {}

    /* Submenús colapsables (Configuraciones, etc.) */
    document.querySelectorAll('.sidebar-menu-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var group = toggle.closest('.sidebar-menu-item--group');
            if (!group) return;

            var isOpen = group.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            shell.classList.remove('sidebar-mobile-open');
        }
    });
})();
