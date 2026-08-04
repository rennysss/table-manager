<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Table Manager</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/favicon.png') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="login-page">
    <!-- Zona izquierda: imagen de fondo a pantalla completa -->
    <section class="login-hero" aria-hidden="true"></section>

    <!-- Panel derecho: negro 20% transparente con el formulario -->
    <aside class="login-panel">
        <div class="login-form-wrap">
            <img src="<?= base_url('assets/img/logo-1R.svg') ?>" alt="1R Tables" class="login-panel-logo" width="220" height="105">

            <?php if (session()->getFlashdata('error')): ?>
            <div class="login-alert"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('errors')): ?>
            <div class="login-alert">
                <?php foreach (session()->getFlashdata('errors') as $e): ?>
                <div><?= esc($e) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login') ?>" class="login-form">
                <?= csrf_field() ?>
                <div class="login-field">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Usuario"
                           value="<?= esc(old('usuario')) ?>" required autocomplete="username">
                </div>
                <div class="login-field">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Contraseña"
                           required autocomplete="current-password">
                </div>
                <button type="submit" class="login-submit">Entrar</button>
            </form>
        </div>
    </aside>
</body>
</html>
