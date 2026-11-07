<?php
/**
 * login.php
 * Formulario público de inicio de sesión.
 * No ejecuta la autenticación: delega en sistema/ajax/login.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

security_headers();
session_boot();

// Si ya existe una sesión válida, no tiene sentido mostrar el login.
if (!empty($_SESSION['usuario_id'])) {
    try {
        if (session_validate(Database::pdo())) {
            header('Location: ' . APP_BASE_PATH . 'sistema/dashboard.php');
            exit;
        }
    } catch (Throwable $e) {
        error_log('[login] ' . $e->getMessage());
    }
}

$csrf  = csrf_token();
$flash = ($_GET['e'] ?? '') === 'sesion'
    ? 'Tu sesión finalizó. Ingresa nuevamente para continuar.'
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0b1220">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="description" content="Acceso seguro al sistema de inventario, ventas y tienda virtual StockiaPro.">
    <title>Iniciar sesión · StockiaPro</title>

    <link rel="icon" href="images/logos/logo.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource-variable/manrope@5.1.0/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page" data-flash="<?= e($flash) ?>">

    <div class="login-bg" aria-hidden="true">
        <span class="login-orb login-orb--1"></span>
        <span class="login-orb login-orb--2"></span>
        <span class="login-grid"></span>
    </div>

    <main class="login-shell">

        <!-- Panel de marca (escritorio) -->
        <aside class="login-showcase">
            <div class="login-showcase__top">
                <a class="login-brand" href="login.php" aria-label="StockiaPro, inicio">
                    <img src="images/logos/logo.svg" alt="" width="44" height="44" class="login-brand__logo">
                    <span class="login-brand__name">Stockia<span>Pro</span></span>
                </a>
                <span class="login-chip">Plataforma SaaS</span>
            </div>

            <div class="login-showcase__body">
                <p class="login-eyebrow">Inventario · Ventas · Tienda</p>
                <h2 class="login-showcase__title">
                    Todo tu negocio,<br>
                    <span>en un solo lugar.</span>
                </h2>
                <p class="login-showcase__lead">
                    Administra inventario, ventas, sucursales y tu tienda virtual
                    desde cualquier dispositivo.
                </p>

                <ul class="login-features">
                    <li>
                        <span class="login-features__icon"><i class="bi bi-buildings"></i></span>
                        <span class="login-features__text">
                            <strong>Multiempresa</strong>
                            <small>Sucursales y almacenes independientes</small>
                        </span>
                    </li>
                    <li>
                        <span class="login-features__icon"><i class="bi bi-shield-lock"></i></span>
                        <span class="login-features__text">
                            <strong>Sesiones protegidas</strong>
                            <small>Control de acceso y cierre automático</small>
                        </span>
                    </li>
                    <li>
                        <span class="login-features__icon"><i class="bi bi-phone"></i></span>
                        <span class="login-features__text">
                            <strong>Diseñado para móviles</strong>
                            <small>La misma experiencia en cualquier pantalla</small>
                        </span>
                    </li>
                </ul>
            </div>

            <footer class="login-showcase__foot">
                <span class="login-status"><span class="login-status__dot"></span> Acceso protegido</span>
                <span>© <?= date('Y') ?> StockiaPro</span>
            </footer>
        </aside>

        <!-- Formulario -->
        <section class="login-panel">
            <div class="login-panel__inner">

                <div class="login-mobile-brand">
                    <img src="images/logos/logo.svg" alt="" width="40" height="40">
                    <span>Stockia<span>Pro</span></span>
                </div>

                <header class="login-head">
                    <p class="login-eyebrow">Acceso seguro</p>
                    <h1>Bienvenido de nuevo</h1>
                    <p>Ingresa tus credenciales para continuar.</p>
                </header>

                <form id="formLogin" class="login-form" method="post" novalidate autocomplete="on">
                    <input type="hidden" name="csrf_token" id="csrfToken" value="<?= e($csrf) ?>">

                    <div class="login-field">
                        <label for="identificador" class="login-label">Usuario o correo</label>
                        <div class="login-control">
                            <i class="bi bi-person login-control__icon" aria-hidden="true"></i>
                            <input
                                type="text"
                                id="identificador"
                                name="identificador"
                                class="login-input"
                                placeholder="tu usuario o correo"
                                autocomplete="username"
                                autocapitalize="none"
                                spellcheck="false"
                                inputmode="email"
                                maxlength="150"
                                required
                                <?= $flash === '' ? 'autofocus' : '' ?>>
                        </div>
                        <small class="login-error" id="errorIdentificador"></small>
                    </div>

                    <div class="login-field">
                        <label for="password" class="login-label">Contraseña</label>
                        <div class="login-control">
                            <i class="bi bi-lock login-control__icon" aria-hidden="true"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="login-input"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                maxlength="200"
                                required>
                            <button type="button" class="login-toggle" id="togglePassword"
                                    aria-label="Mostrar contraseña" aria-pressed="false">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <small class="login-error" id="errorPassword"></small>
                    </div>

                    <div class="login-meta">
                        <label class="login-check" for="recordar">
                            <input type="checkbox" id="recordar" name="recordar" value="1">
                            <span>Recordar mi usuario</span>
                        </label>
                        <span class="login-hint" title="La sesión se cierra automáticamente">
                            <i class="bi bi-clock-history" aria-hidden="true"></i> 4 horas
                        </span>
                    </div>

                    <div class="login-alert" id="loginAlert" role="alert" aria-live="polite" hidden></div>

                    <button type="submit" class="login-btn" id="btnLogin">
                        <span class="login-btn__label">Iniciar sesión</span>
                        <i class="bi bi-arrow-right login-btn__arrow" aria-hidden="true"></i>
                        <span class="login-btn__spinner" hidden aria-hidden="true"></span>
                    </button>
                </form>

                <p class="login-note">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    Sesión protegida. Se cierra automáticamente a las 4 horas.
                </p>

                <footer class="login-foot">
                    © <?= date('Y') ?> StockiaPro · Todos los derechos reservados
                </footer>
            </div>
        </section>
    </main>

    <noscript>
        <div class="login-noscript">
            Activa JavaScript para iniciar sesión en StockiaPro.
        </div>
    </noscript>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"
            integrity="sha384-F8SYeBSrTVPFojwQeAD1UQo0dI5CKJOzc992kU0M/q72tnFcDlxHwbkiw8GLrXd8"
            crossorigin="anonymous"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
