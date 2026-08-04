<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-header" content="<?= config('Security')->headerName ?>">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/favicon.png') ?>">
    <title><?= esc($titulo ?? '1R Tables') ?> — Gestión de Mesas</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/css/dataTables.bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="app-body">
<?php if (session()->get('usuario_id')): ?>
<div class="app-shell" id="appShell">
    <?= view('layouts/partials/sidebar') ?>
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <div class="app-content-wrap">
        <?= view('layouts/partials/topbar', [
            'titulo' => ($mostrarTituloTopbar ?? true) ? ($titulo ?? '1R Tables') : '',
        ]) ?>

        <main class="app-main">
            <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show mx-3 mx-lg-4 mt-3" role="alert">
                <?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show mx-3 mx-lg-4 mt-3" role="alert">
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>
<?php else: ?>
    <?= $this->renderSection('content') ?>
<?php endif; ?>

    <script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/js/dataTables.bootstrap4.min.js') ?>"></script>
    <script>
        window.APP = {
            baseUrl: '<?= base_url() ?>',
            csrfToken: '<?= csrf_hash() ?>',
            csrfHeader: '<?= config('Security')->headerName ?>',
            csrfCookie: '<?= config('Security')->cookieName ?>',
            csrfTokenActual: function () {
                var name = window.APP.csrfCookie || 'csrf_cookie_name';
                var re = new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)');
                var m = document.cookie.match(re);
                if (m) {
                    window.APP.csrfToken = decodeURIComponent(m[1]);
                }
                return window.APP.csrfToken;
            }
        };
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type)) {
                    xhr.setRequestHeader(window.APP.csrfHeader, window.APP.csrfTokenActual());
                }
            }
        });
    </script>
    <?php if (session()->get('usuario_id')): ?>
    <script src="<?= base_url('assets/js/sidebar.js') ?>"></script>
    <script src="<?= base_url('assets/js/user-menu.js') ?>"></script>
    <?php endif; ?>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
