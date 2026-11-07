<?php
/**
 * sistema/includes/layout.php
 * Layout compartido de las páginas autenticadas.
 * Topbar moderno, menú móvil y footer.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

/** URL pública de un archivo de la empresa o un respaldo. */
function layout_media_url(?string $path, string $fallback = 'images/logos/logo.svg'): string
{
    $path = trim((string) $path);

    if ($path === '') {
        return APP_BASE_PATH . ltrim($fallback, '/');
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return APP_BASE_PATH . ltrim($path, '/');
}

/** Logo de la empresa cargado en sesión. */
function layout_company_logo(): string
{
    return layout_media_url((string) empresa_config('logo', ''));
}

/** Iniciales del usuario para el avatar. */
function layout_iniciales(string $nombre): string
{
    $iniciales = '';

    foreach (preg_split('/\s+/', trim($nombre)) ?: [] as $parte) {
        $iniciales .= mb_substr($parte, 0, 1);
    }

    return mb_strtoupper(mb_substr($iniciales, 0, 2));
}

/**
 * Abre la página: head, fondo, topbar, menú móvil y <main>.
 */
function layout_start(string $titulo, string $subtitulo = ''): void
{
    $usuario       = $_SESSION['usuario'] ?? [];
    $csrf          = csrf_token();
    $pagina        = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $empresaNombre = (string) empresa_config('nombre_comercial', APP_NAME);
    $logo          = layout_company_logo();
    $favicon       = layout_media_url((string) empresa_config('favicon', ''));
    $nombre        = trim((string) ($usuario['nombre_completo'] ?? 'Usuario'));
    $rol           = (string) ($usuario['rol_nombre'] ?? 'Sin rol');
    $email         = (string) ($usuario['email'] ?? '');
    $iniciales     = layout_iniciales($nombre);
    $nivel         = usuario_nivel(Database::pdo());
    $verConfig     = $nivel >= 90;
    $esPanel       = $pagina === 'dashboard.php';
    $esPerfil      = $pagina === 'perfil_empresa.php';
    $idioma        = trim((string) empresa_config('idioma', 'es')) ?: 'es';
    ?>
<!DOCTYPE html>
<html lang="<?= e($idioma) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0b1220">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo) ?> · <?= e(APP_NAME) ?></title>

    <link rel="icon" href="<?= e($favicon) ?>">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource-variable/manrope@5.1.0/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(APP_BASE_PATH) ?>assets/css/sistema.css">
</head>
<body class="sp-body">
<a class="sp-skip" href="#sp-main">Saltar al contenido</a>

<div class="sp-bg" aria-hidden="true">
    <span class="sp-bg__orb sp-bg__orb--1"></span>
    <span class="sp-bg__orb sp-bg__orb--2"></span>
    <span class="sp-bg__orb sp-bg__orb--3"></span>
    <span class="sp-bg__grid"></span>
</div>

<header class="sp-topbar">
    <div class="sp-topbar__inner">

        <a class="sp-brand" href="<?= e(APP_BASE_PATH) ?>sistema/dashboard.php" aria-label="<?= e(APP_NAME) ?>">
            <img src="<?= e($logo) ?>" alt="" width="32" height="32" data-empresa-logo>
            <span class="sp-brand__text">Stockia<span>Pro</span></span>
        </a>

        <nav class="sp-nav" aria-label="Navegación principal">
            <a class="sp-nav__link<?= $esPanel ? ' is-active' : '' ?>"
               href="<?= e(APP_BASE_PATH) ?>sistema/dashboard.php"
               <?= $esPanel ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                <span>Panel</span>
            </a>

            <?php if ($verConfig): ?>
                <details class="sp-menu">
                    <summary class="sp-nav__link<?= $esPerfil ? ' is-active' : '' ?>">
                        <i class="bi bi-sliders2" aria-hidden="true"></i>
                        <span>Configuración</span>
                        <i class="bi bi-chevron-down sp-caret" aria-hidden="true"></i>
                    </summary>
                    <div class="sp-menu__panel">
                        <span class="sp-menu__title">Configuración</span>
                        <a class="sp-menu__item<?= $esPerfil ? ' is-active' : '' ?>"
                           href="<?= e(APP_BASE_PATH) ?>sistema/perfil_empresa.php"
                           <?= $esPerfil ? 'aria-current="page"' : '' ?>>
                            <i class="bi bi-building-gear" aria-hidden="true"></i>
                            <span>Perfil de empresa</span>
                        </a>
                    </div>
                </details>
            <?php endif; ?>
        </nav>

        <span class="sp-topbar__spacer"></span>

        <span class="sp-company" data-empresa-nombre title="<?= e($empresaNombre) ?>">
            <i class="bi bi-buildings" aria-hidden="true"></i>
            <?= e($empresaNombre) ?>
        </span>

        <details class="sp-menu sp-menu--right sp-user-menu">
            <summary class="sp-user" title="<?= e($nombre) ?>">
                <span class="sp-avatar" aria-hidden="true"><?= e($iniciales) ?></span>
                <span class="sp-user__meta">
                    <strong><?= e($nombre) ?></strong>
                    <small><?= e($rol) ?></small>
                </span>
                <i class="bi bi-chevron-down sp-caret" aria-hidden="true"></i>
            </summary>

            <div class="sp-menu__panel">
                <div class="sp-menu__head">
                    <strong><?= e($nombre) ?></strong>
                    <small><?= e($email) ?></small>
                </div>

                <a class="sp-menu__item" href="<?= e(APP_BASE_PATH) ?>sistema/dashboard.php">
                    <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                    <span>Panel</span>
                </a>

                <?php if ($verConfig): ?>
                    <a class="sp-menu__item" href="<?= e(APP_BASE_PATH) ?>sistema/perfil_empresa.php">
                        <i class="bi bi-building-gear" aria-hidden="true"></i>
                        <span>Perfil de empresa</span>
                    </a>
                <?php endif; ?>

                <div class="sp-menu__sep"></div>

                <form method="post" action="<?= e(APP_BASE_PATH) ?>sistema/logout.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <button type="submit" class="sp-menu__item sp-logout">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </details>

        <button type="button" class="sp-menu-toggle" id="spMenuToggle"
                aria-controls="spMobileMenu" aria-expanded="false" aria-label="Abrir menú">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

    </div>

    <div class="sp-mobile-menu" id="spMobileMenu" aria-hidden="true">
        <div class="sp-mobile-menu__user">
            <span class="sp-avatar" aria-hidden="true"><?= e($iniciales) ?></span>
            <div>
                <strong><?= e($nombre) ?></strong>
                <small><?= e($rol) ?> · <span data-empresa-nombre><?= e($empresaNombre) ?></span></small>
            </div>
        </div>

        <nav class="sp-mobile-menu__nav" aria-label="Navegación móvil">
            <a class="sp-mobile-menu__link<?= $esPanel ? ' is-active' : '' ?>"
               href="<?= e(APP_BASE_PATH) ?>sistema/dashboard.php">
                <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                <span>Panel</span>
                <i class="bi bi-chevron-right sp-caret" aria-hidden="true"></i>
            </a>

            <?php if ($verConfig): ?>
                <a class="sp-mobile-menu__link<?= $esPerfil ? ' is-active' : '' ?>"
                   href="<?= e(APP_BASE_PATH) ?>sistema/perfil_empresa.php">
                    <i class="bi bi-building-gear" aria-hidden="true"></i>
                    <span>Perfil de empresa</span>
                    <i class="bi bi-chevron-right sp-caret" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
        </nav>

        <form method="post" action="<?= e(APP_BASE_PATH) ?>sistema/logout.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <button type="submit" class="sp-mobile-menu__logout">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span>Cerrar sesión</span>
            </button>
        </form>
    </div>
</header>

<main class="sp-main" id="sp-main">
    <?php
}

/**
 * Cierra la página, pinta el footer e incluye los scripts.
 *
 * @param list<string> $scripts Rutas relativas a la raíz del proyecto.
 */
function layout_end(array $scripts = [], bool $conSweetAlert = false): void
{
    $logo = layout_company_logo();
    ?>
</main>

<footer class="sp-footer">
    <div class="sp-footer__inner">
        <div class="sp-footer__brand">
            <img src="<?= e($logo) ?>" alt="" data-empresa-logo>
            <div>
                <strong><?= e(APP_NAME) ?></strong>
                <span>Inventario, ventas y tienda virtual</span>
            </div>
        </div>

        <div class="sp-footer__meta">
            <span class="sp-footer__status">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Sesión protegida
            </span>
            <span>© <?= date('Y') ?> <?= e(APP_NAME) ?> · v<?= e(APP_VERSION) ?></span>
        </div>
    </div>
</footer>

<?php if ($conSweetAlert): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"
            integrity="sha384-F8SYeBSrTVPFojwQeAD1UQo0dI5CKJOzc992kU0M/q72tnFcDlxHwbkiw8GLrXd8"
            crossorigin="anonymous"></script>
<?php endif; ?>

<script src="<?= e(APP_BASE_PATH) ?>assets/js/sistema.js"></script>

<?php foreach ($scripts as $script): ?>
    <script src="<?= e(APP_BASE_PATH . ltrim($script, '/')) ?>"></script>
<?php endforeach; ?>

</body>
</html>
    <?php
}
