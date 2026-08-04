<?php
/** Barra superior oscura — notificaciones y usuario. */
$inicial = mb_strtoupper(mb_substr((string) session()->get('nombre'), 0, 1));
?>
<header class="app-topbar">
    <button type="button" class="app-topbar-menu-btn d-lg-none" id="sidebarMobileToggle" aria-label="Abrir menú">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <?php if (! empty($titulo)): ?>
    <h1 class="app-topbar-title"><?= esc($titulo) ?></h1>
    <?php endif; ?>

    <div class="app-topbar-actions">
        <button type="button" class="app-topbar-icon-btn" aria-label="Notificaciones" disabled>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </button>

        <div class="app-topbar-user-wrap" id="userMenuWrap">
            <div class="app-topbar-user">
                <span class="app-topbar-user-avatar"><?= esc($inicial) ?></span>
                <span class="app-topbar-user-name d-none d-sm-inline"><?= esc(session()->get('nombre')) ?></span>
            </div>
            <button type="button" class="app-topbar-user-toggle" id="userMenuToggle"
                    aria-label="Menú de usuario" aria-expanded="false" aria-haspopup="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            <div class="app-topbar-dropdown" id="userMenuDropdown" hidden>
                <a href="<?= base_url('perfil') ?>" class="app-topbar-dropdown-item">Perfil</a>
                <a href="<?= base_url('logout') ?>" class="app-topbar-dropdown-item">Salir</a>
            </div>
        </div>
    </div>
</header>
