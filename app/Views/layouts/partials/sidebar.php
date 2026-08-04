<?php
/**
 * Sidebar de navegación — estilo dashboard oscuro (referencia Color Admin).
 */
$rol  = session()->get('rol');
$path = uri_string();

$isActive = static function (string $href, bool $exact = false) use ($path): bool {
    if ($exact) {
        return $path === $href || $path === rtrim($href, '/');
    }

    return str_starts_with($path, $href);
};

$isGroupActive = static function (array $children) use ($isActive): bool {
    foreach ($children as $child) {
        if ($isActive($child['href'], ! empty($child['exact']))) {
            return true;
        }
    }

    return false;
};

$menu = [];

if ($rol === 'admin') {
    $menu = [
        ['href' => 'admin/sucursales', 'label' => 'Sucursales', 'icon' => 'building'],
        ['href' => 'gerente/floorplan', 'label' => 'FloorPlans', 'icon' => 'map'],
        ['href' => 'hostess/reservas', 'label' => 'Reservas', 'icon' => 'calendar'],
        [
            'label'    => 'Elementos',
            'icon'     => 'layers',
            'children' => [
                ['href' => 'admin/elementos/formas-mesas', 'label' => 'Formas de Mesas', 'icon' => 'table'],
                ['href' => 'admin/elementos/estructurales', 'label' => 'Elementos Estructurales', 'icon' => 'blocks'],
            ],
        ],
        [
            'label'    => 'Configuraciones',
            'icon'     => 'settings',
            'children' => [
                ['href' => 'admin/marcas', 'label' => 'Marcas', 'icon' => 'tag'],
                ['href' => 'admin/tags', 'label' => 'Tags', 'icon' => 'tag'],
                ['href' => 'admin/ambientes', 'label' => 'Ambientes', 'icon' => 'layout'],
                ['href' => 'admin/usuarios', 'label' => 'Usuarios', 'icon' => 'users'],
                ['href' => 'admin/catalogo-geografico', 'label' => 'Geografía', 'icon' => 'globe'],
            ],
        ],
    ];
} elseif ($rol === 'gerente') {
    $menu = [
        ['href' => 'gerente', 'label' => 'Panel', 'icon' => 'grid', 'exact' => true],
        ['href' => 'gerente/floorplan', 'label' => 'FloorPlans', 'icon' => 'map'],
        ['href' => 'hostess/reservas', 'label' => 'Reservas', 'icon' => 'calendar'],
    ];
} else {
    $menu = [
        ['href' => 'hostess/reservas', 'label' => 'Reservas', 'icon' => 'calendar'],
        ['href' => 'hostess/plano', 'label' => 'Plano', 'icon' => 'layout'],
    ];
}

$inicial = mb_strtoupper(mb_substr((string) session()->get('nombre'), 0, 1));
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Menú principal">
    <?php if (file_exists(FCPATH . 'assets/img/logo-header.png')): ?>
    <div class="sidebar-brand">
        <img src="<?= base_url('assets/img/logo-header.png') ?>" alt="Table Manager" class="sidebar-logo" width="180" height="36">
    </div>
    <?php endif; ?>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= esc($inicial) ?></div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?= esc(session()->get('nombre')) ?></span>
            <span class="sidebar-user-role"><?= esc(ucfirst((string) $rol)) ?></span>
        </div>
        <span class="sidebar-user-chevron" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </span>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-section-title">Navegación</p>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $item):
                if (! empty($item['children'])):
                    $groupOpen = $isGroupActive($item['children']);
            ?>
            <li class="sidebar-menu-item sidebar-menu-item--group<?= $groupOpen ? ' is-open has-active' : '' ?>">
                <button type="button" class="sidebar-menu-link sidebar-menu-toggle"
                        aria-expanded="<?= $groupOpen ? 'true' : 'false' ?>"
                        title="<?= esc($item['label']) ?>">
                    <span class="sidebar-menu-icon"><?= view('layouts/partials/sidebar_icon', ['name' => $item['icon']]) ?></span>
                    <span class="sidebar-menu-label"><?= esc($item['label']) ?></span>
                    <span class="sidebar-menu-chevron" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </span>
                </button>
                <ul class="sidebar-submenu">
                    <?php foreach ($item['children'] as $child):
                        $href   = base_url($child['href']);
                        $active = $isActive($child['href'], ! empty($child['exact']));
                    ?>
                    <li class="sidebar-submenu-item">
                        <a href="<?= $href ?>" class="sidebar-menu-link sidebar-submenu-link<?= $active ? ' active' : '' ?>"
                           title="<?= esc($child['label']) ?>">
                            <span class="sidebar-menu-icon"><?= view('layouts/partials/sidebar_icon', ['name' => $child['icon']]) ?></span>
                            <span class="sidebar-menu-label"><?= esc($child['label']) ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <?php else:
                $href   = base_url($item['href']);
                $active = $isActive($item['href'], ! empty($item['exact']));
            ?>
            <li class="sidebar-menu-item">
                <a href="<?= $href ?>" class="sidebar-menu-link<?= $active ? ' active' : '' ?>"
                   title="<?= esc($item['label']) ?>">
                    <span class="sidebar-menu-icon"><?= view('layouts/partials/sidebar_icon', ['name' => $item['icon']]) ?></span>
                    <span class="sidebar-menu-label"><?= esc($item['label']) ?></span>
                </a>
            </li>
            <?php endif; endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn"
                aria-label="Colapsar menú" aria-expanded="true">
            <span class="sidebar-collapse-icon sidebar-collapse-icon--expanded" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </span>
            <span class="sidebar-collapse-icon sidebar-collapse-icon--collapsed" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </span>
        </button>
    </div>
</aside>
