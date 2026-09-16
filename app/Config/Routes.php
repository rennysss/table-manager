<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Auth\LoginController::index');
$routes->get('login', 'Auth\LoginController::index');
$routes->post('login', 'Auth\LoginController::authenticate');
$routes->get('logout', 'Auth\LoginController::logout');

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('perfil', 'PerfilController::index');

    // Admin
    $routes->group('admin', ['filter' => 'role:admin'], static function ($routes) {
        $routes->get('sucursales', 'Admin\SucursalesController::index');
        $routes->get('sucursales/modal/crear', 'Admin\SucursalesController::modalCrear');
        $routes->get('sucursales/(:num)/modal/editar', 'Admin\SucursalesController::modalEditar/$1');
        $routes->get('sucursales/(:num)/modal/ambientes', 'Admin\SucursalesController::modalAmbientes/$1');
        $routes->get('sucursales/(:num)/modal/floorplans', 'Admin\SucursalesController::modalFloorplans/$1');
        $routes->get('sucursales/crear', 'Admin\SucursalesController::crear');
        $routes->post('sucursales', 'Admin\SucursalesController::guardar');
        $routes->get('sucursales/(:num)/editar', 'Admin\SucursalesController::editar/$1');
        $routes->post('sucursales/(:num)', 'Admin\SucursalesController::actualizar/$1');
        $routes->post('sucursales/(:num)/eliminar', 'Admin\SucursalesController::eliminar/$1');

        $routes->get('marcas', 'Admin\MarcasController::index');
        $routes->get('marcas/modal/crear', 'Admin\MarcasController::modalCrear');
        $routes->get('marcas/(:num)/modal/editar', 'Admin\MarcasController::modalEditar/$1');
        $routes->post('marcas', 'Admin\MarcasController::guardar');
        $routes->post('marcas/(:num)', 'Admin\MarcasController::actualizar/$1');

        $routes->get('tags', 'Admin\TagsController::index');
        $routes->get('tags/modal/crear', 'Admin\TagsController::modalCrear');
        $routes->get('tags/(:num)/modal/editar', 'Admin\TagsController::modalEditar/$1');
        $routes->post('tags', 'Admin\TagsController::guardar');
        $routes->post('tags/(:num)', 'Admin\TagsController::actualizar/$1');
        $routes->get('tags/categorias/modal/crear', 'Admin\TagsController::modalCategoriaCrear');
        $routes->get('tags/categorias/(:num)/modal/editar', 'Admin\TagsController::modalCategoriaEditar/$1');
        $routes->post('tags/categorias', 'Admin\TagsController::guardarCategoria');
        $routes->post('tags/categorias/(:num)', 'Admin\TagsController::actualizarCategoria/$1');

        $routes->get('catalogo-geografico', 'Admin\CatalogoGeograficoController::index');
        $routes->get('catalogo-geografico/paises/(:num)/estados', 'Admin\CatalogoGeograficoController::estadosPorPais/$1');
        $routes->get('catalogo-geografico/modal/crear', 'Admin\CatalogoGeograficoController::modalCrear');
        $routes->get('catalogo-geografico/(:num)/modal/editar', 'Admin\CatalogoGeograficoController::modalEditar/$1');
        $routes->post('catalogo-geografico', 'Admin\CatalogoGeograficoController::guardar');
        $routes->post('catalogo-geografico/(:num)', 'Admin\CatalogoGeograficoController::actualizar/$1');
        $routes->post('catalogo-geografico/(:num)/eliminar', 'Admin\CatalogoGeograficoController::eliminar/$1');

        $routes->get('elementos/formas-mesas', 'Admin\ElementosController::formasMesas');
        $routes->get('elementos/estructurales', 'Admin\ElementosController::elementosEstructurales');

        $routes->get('ambientes', 'Admin\AmbientesController::index');
        $routes->get('ambientes/modal/crear', 'Admin\AmbientesController::modalCrear');
        $routes->get('ambientes/(:num)/modal/editar', 'Admin\AmbientesController::modalEditar/$1');
        $routes->post('ambientes', 'Admin\AmbientesController::guardar');
        $routes->post('ambientes/(:num)', 'Admin\AmbientesController::actualizar/$1');

        $routes->get('sucursales/(:num)/ambientes', 'Admin\AmbientesController::porSucursal/$1');
        $routes->post('sucursales/(:num)/ambientes/guardar-lote', 'Admin\AmbientesController::guardarLoteSucursal/$1');

        $routes->get('sucursales/(:num)/mesas', 'Admin\MesasController::index/$1');
        $routes->post('sucursales/(:num)/mesas/guardar-lote', 'Admin\MesasController::guardarLote/$1');
        $routes->post('sucursales/(:num)/mesas/ambiente', 'Admin\MesasController::crearAmbiente/$1');

        $routes->get('sucursales/(:num)/floorplans', 'Admin\FloorplanController::index/$1');
        $routes->post('sucursales/(:num)/floorplans', 'Admin\FloorplanController::crear/$1');
        $routes->get('floorplan/(:num)/editor', 'Admin\FloorplanController::editor/$1');
        $routes->post('floorplan/(:num)/guardar', 'Admin\FloorplanController::guardar/$1');
        $routes->post('floorplan/(:num)/activar', 'Admin\FloorplanController::activar/$1');
        $routes->post('floorplan/(:num)/desactivar', 'Admin\FloorplanController::desactivar/$1');
        $routes->post('floorplan/(:num)/duplicar', 'Admin\FloorplanController::duplicar/$1');

        $routes->get('usuarios', 'Admin\UsuariosController::index');
        $routes->get('usuarios/modal/crear', 'Admin\UsuariosController::modalCrear');
        $routes->get('usuarios/(:num)/modal/editar', 'Admin\UsuariosController::modalEditar/$1');
        $routes->get('usuarios/(:num)/modal/sucursales', 'Admin\UsuariosController::modalSucursales/$1');
        $routes->get('usuarios/api/paises/(:num)/ciudades', 'Admin\UsuariosController::ciudadesPorPais/$1');
        $routes->get('usuarios/api/sucursales', 'Admin\UsuariosController::sucursalesPorCiudad');
        $routes->post('usuarios', 'Admin\UsuariosController::guardar');
        $routes->post('usuarios/(:num)', 'Admin\UsuariosController::actualizar/$1');
        $routes->post('usuarios/(:num)/sucursales', 'Admin\UsuariosController::guardarSucursales/$1');
        $routes->post('usuarios/(:num)/eliminar', 'Admin\UsuariosController::eliminar/$1');
    });

    // Gerente
    $routes->group('gerente', ['filter' => 'role:gerente,admin'], static function ($routes) {
        $routes->get('/', 'Gerente\DashboardController::index');
        $routes->get('sucursales/(:num)/ambientes', 'Gerente\AmbientesController::index/$1');
        $routes->get('ambientes/(:num)/mesas', 'Gerente\MesasController::index/$1');
        $routes->post('mesas/(:num)/actualizar', 'Gerente\MesasController::actualizar/$1');

        $routes->get('floorplan', 'Gerente\FloorplanController::index');
        $routes->get('floorplan/modal/crear', 'Gerente\FloorplanController::modalCrear');
        $routes->post('floorplan/crear', 'Gerente\FloorplanController::crearDesdeModal');
        $routes->get('sucursales/(:num)/floorplans', 'Gerente\FloorplanController::porSucursal/$1');
        $routes->post('sucursales/(:num)/floorplans', 'Gerente\FloorplanController::crear/$1');
        $routes->get('floorplan/(:num)/editor', 'Gerente\FloorplanController::editor/$1');
        $routes->post('floorplan/(:num)/guardar', 'Gerente\FloorplanController::guardar/$1');
        $routes->post('floorplan/(:num)/activar', 'Gerente\FloorplanController::activar/$1');
        $routes->post('floorplan/(:num)/desactivar', 'Gerente\FloorplanController::desactivar/$1');
        $routes->post('floorplan/mesas/(:num)', 'Gerente\FloorplanController::actualizarMesa/$1');
        $routes->get('floorplan/mesas/(:num)/reservas', 'Gerente\FloorplanController::reservasMesa/$1');
    });

    // Hostess
    $routes->group('hostess', ['filter' => 'role:hostess,gerente,admin'], static function ($routes) {
        $routes->get('reservas', 'Hostess\ReservasController::index');
        $routes->get('reservas/api/paises', 'Hostess\ReservasController::apiPaises');
        $routes->get('reservas/api/paises/(:num)/ciudades', 'Hostess\ReservasController::apiCiudades/$1');
        $routes->get('reservas/api/sucursales', 'Hostess\ReservasController::apiSucursales');
        $routes->get('reservas/api/buscar', 'Hostess\ReservasController::apiBuscar');
        $routes->get('reservas/actividad', 'Hostess\ReservasController::actividad');
        $routes->get('reservas/(:segment)/detalle', 'Hostess\ReservasController::detalle/$1');
        $routes->post('reservas/asignar', 'Hostess\ReservasController::asignarMesa');
        $routes->post('reservas/ocupar-walkin', 'Hostess\ReservasController::ocuparWalkIn');
        $routes->post('reservas/tags', 'Hostess\ReservasController::agregarTags');
        $routes->post('reservas/llegada', 'Hostess\ReservasController::registrarLlegada');
        $routes->post('reservas/sentar', 'Hostess\ReservasController::sentar');
        $routes->get('reservas/xetux/meseros', 'Hostess\ReservasController::xetuxMeseros');
        $routes->get('reservas/xetux/mesas', 'Hostess\ReservasController::xetuxMesas');
        $routes->post('reservas/xetux/sentar', 'Hostess\ReservasController::sentarXetux');
        $routes->post('reservas/xetux/cancelar', 'Hostess\ReservasController::cancelarXetux');
        $routes->post('reservas/xetux/cerrar-cuenta', 'Hostess\ReservasController::cerrarCuentaXetux');
        $routes->post('reservas/liberar', 'Hostess\ReservasController::liberar');
        $routes->get('plano', 'Hostess\ReservasController::plano');
    });

    // API interna (requiere sesión)
    $routes->group('api', static function ($routes) {
        $routes->get('reservas', 'Api\ReservasController::index');
        $routes->get('reservas/(:segment)', 'Api\ReservasController::show/$1');
        $routes->get('floorplan/activo', 'Api\FloorplanController::activo');
    });
});

// API pública de login
$routes->post('api/auth/login', 'Api\AuthController::login');
