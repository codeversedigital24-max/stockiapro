<?php
/**
 * dashboard.php
 * Panel principal. Página protegida y compacta.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

security_headers();
require_login();

require_once __DIR__ . '/includes/layout.php';

$pdo      = Database::pdo();
$nivel    = usuario_nivel($pdo);
$usuario  = $_SESSION['usuario'] ?? [];
$empresa  = empresa_config();
$logo     = layout_company_logo();
$inicio   = date('H:i', (int) $_SESSION['login_at']);
$expiraEn = date('H:i', (int) $_SESSION['login_at'] + (SESSION_LIFETIME_HOURS * 3600));
$nombre   = trim((string) ($usuario['nombre_completo'] ?? 'Usuario'));
$primer   = explode(' ', $nombre)[0] ?: 'Usuario';
$licencia = ($empresa['tipo_licencia'] ?? 'suscripcion') === 'licencia_unica'
    ? 'Licencia única'
    : 'Suscripción';
$formatoFecha = (string) empresa_config('formato_fecha', 'd/m/Y');

layout_start('Panel');
?>

<section class="sp-hero">
    <div class="sp-hero__content">
        <span class="sp-hero__badge">
            <i class="bi bi-stars" aria-hidden="true"></i>
            Panel principal
        </span>

        <h1>Hola, <?= e($primer) ?> <span class="sp-wave" aria-hidden="true">👋</span></h1>

        <p>
            Todo listo en <strong><?= e($empresa['nombre_comercial'] ?? 'tu empresa') ?></strong>.
            Tu sesión está protegida y la configuración de la empresa se cargó correctamente.
        </p>

        <div class="sp-hero__actions">
            <?php if ($nivel >= 90): ?>
                <a class="sp-btn sp-btn--light" href="<?= e(APP_BASE_PATH) ?>sistema/perfil_empresa.php">
                    <i class="bi bi-sliders2" aria-hidden="true"></i>
                    <span>Configurar empresa</span>
                </a>
            <?php endif; ?>

            <span class="sp-hero__status">
                <span class="sp-status-dot" aria-hidden="true"></span>
                Sesión activa · <?= e($inicio) ?> – <?= e($expiraEn) ?>
            </span>
        </div>
    </div>

    <div class="sp-hero__visual" aria-hidden="true">
        <div class="sp-hero__ring">
            <img src="<?= e($logo) ?>" alt="">
        </div>
        <span class="sp-hero__chip sp-hero__chip--1"><i class="bi bi-shield-check"></i> Sesión 4 h</span>
        <span class="sp-hero__chip sp-hero__chip--2"><i class="bi bi-lock"></i> CSRF</span>
        <span class="sp-hero__chip sp-hero__chip--3"><i class="bi bi-fingerprint"></i> Argon2id</span>
    </div>
</section>

<section class="sp-grid sp-grid--kpi sp-mt-14" aria-label="Resumen general">

    <article class="sp-kpi">
        <span class="sp-kpi__icon sp-icon--blue"><i class="bi bi-buildings" aria-hidden="true"></i></span>
        <div class="sp-kpi__body">
            <span class="sp-kpi__label">Empresa</span>
            <span class="sp-kpi__value"><?= e($empresa['nombre_comercial'] ?? '—') ?></span>
            <span class="sp-kpi__meta"><?= e($licencia) ?></span>
        </div>
    </article>

    <article class="sp-kpi">
        <span class="sp-kpi__icon sp-icon--violet"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
        <div class="sp-kpi__body">
            <span class="sp-kpi__label">Usuario</span>
            <span class="sp-kpi__value"><?= e($nombre) ?></span>
            <span class="sp-kpi__meta"><?= e((string) ($usuario['rol_nombre'] ?? 'Sin rol')) ?></span>
        </div>
    </article>

    <article class="sp-kpi">
        <span class="sp-kpi__icon sp-icon--amber"><i class="bi bi-currency-exchange" aria-hidden="true"></i></span>
        <div class="sp-kpi__body">
            <span class="sp-kpi__label">Moneda</span>
            <span class="sp-kpi__value">
                <?= e((string) ($empresa['simbolo'] ?? '$')) ?> · <?= e((string) ($empresa['codigo_moneda'] ?? 'USD')) ?>
            </span>
            <span class="sp-kpi__meta"><?= (int) ($empresa['decimales'] ?? 2) ?> decimales</span>
        </div>
    </article>

    <article class="sp-kpi">
        <span class="sp-kpi__icon sp-icon--green"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
        <div class="sp-kpi__body">
            <span class="sp-kpi__label">Sesión</span>
            <span class="sp-kpi__value">Activa</span>
            <span class="sp-kpi__meta"><?= e($inicio) ?> – <?= e($expiraEn) ?> (4 h)</span>
        </div>
    </article>

</section>

<section class="sp-grid sp-grid--2 sp-mt-14">

    <article class="sp-card">
        <div class="sp-card__head">
            <span class="sp-card__head-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
            <div class="sp-card__head-text">
                <h2>Resumen de la empresa</h2>
                <p>Datos visibles en el sistema</p>
            </div>

            <?php if ($nivel >= 90): ?>
                <a class="sp-btn sp-btn--ghost sp-btn--sm" href="<?= e(APP_BASE_PATH) ?>sistema/perfil_empresa.php">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                    <span>Editar</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="sp-card__body">

            <div class="sp-company-banner">
                <div class="sp-company-banner__logo">
                    <img src="<?= e($logo) ?>" alt="Logotipo de la empresa">
                </div>
                <div class="sp-company-banner__text">
                    <strong><?= e($empresa['nombre_comercial'] ?? '—') ?></strong>
                    <span><?= e($licencia) ?></span>
                </div>
                <span class="sp-badge <?= ($empresa['estado'] ?? '') === 'activa' ? 'sp-badge--success' : 'sp-badge--info' ?>">
                    <?= e(ucfirst((string) ($empresa['estado'] ?? '—'))) ?>
                </span>
            </div>

            <div class="sp-kv">
                <div class="sp-kv__item">
                    <span class="sp-kv__label"><i class="bi bi-card-text" aria-hidden="true"></i> Identificación fiscal</span>
                    <span class="sp-kv__value"><?= e((string) ($empresa['identificacion_fiscal'] ?? '—')) ?></span>
                </div>
                <div class="sp-kv__item">
                    <span class="sp-kv__label"><i class="bi bi-envelope" aria-hidden="true"></i> Correo</span>
                    <span class="sp-kv__value"><?= e((string) ($empresa['email'] ?? '—')) ?></span>
                </div>
                <div class="sp-kv__item">
                    <span class="sp-kv__label"><i class="bi bi-telephone" aria-hidden="true"></i> Teléfono</span>
                    <span class="sp-kv__value"><?= e((string) ($empresa['telefono'] ?? '—')) ?></span>
                </div>
                <div class="sp-kv__item">
                    <span class="sp-kv__label"><i class="bi bi-geo-alt" aria-hidden="true"></i> Ciudad / País</span>
                    <span class="sp-kv__value">
                        <?= e(trim(($empresa['ciudad'] ?? '') . ' / ' . ($empresa['pais'] ?? ''), ' /')) ?: '—' ?>
                    </span>
                </div>
                <div class="sp-kv__item">
                    <span class="sp-kv__label"><i class="bi bi-globe-americas" aria-hidden="true"></i> Zona horaria</span>
                    <span class="sp-kv__value"><?= e((string) ($empresa['zona_horaria'] ?? '—')) ?></span>
                </div>
                <div class="sp-kv__item">
                    <span class="sp-kv__label"><i class="bi bi-percent" aria-hidden="true"></i> Impuesto</span>
                    <span class="sp-kv__value">
                        <?= e((string) ($empresa['impuesto'] ?? 'IVA')) ?>
                        · <?= e(number_format((float) ($empresa['impuesto_porcentaje'] ?? 0), 2)) ?>%
                    </span>
                </div>
            </div>

            <div class="sp-company-note">
                <i class="bi bi-stars" aria-hidden="true"></i>
                <span>Estos datos se usan en comprobantes, reportes y la tienda virtual.</span>
            </div>
        </div>
    </article>

    <article class="sp-card">
        <div class="sp-card__head">
            <span class="sp-card__head-icon"><i class="bi bi-activity" aria-hidden="true"></i></span>
            <div class="sp-card__head-text">
                <h2>Estado del sistema</h2>
                <p>Controles activos</p>
            </div>
            <span class="sp-badge sp-badge--success">
                <i class="bi bi-check2" aria-hidden="true"></i> Operativo
            </span>
        </div>

        <div class="sp-card__body">
            <ul class="sp-status-list">
                <li>
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    <span>Sesión validada en el servidor<small>Token SHA-256 · 4 horas</small></span>
                </li>
                <li>
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    <span>Suscripción activa<small><?= e($licencia) ?></small></span>
                </li>
                <li>
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    <span>Configuración de empresa cargada<small><?= e((string) ($empresa['zona_horaria'] ?? '—')) ?></small></span>
                </li>
                <li>
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    <span>Protección CSRF y cookies HttpOnly<small>SameSite Lax · Secure bajo HTTPS</small></span>
                </li>
            </ul>

            <?php if ($nivel >= 90): ?>
                <div class="sp-quick sp-mt-14">
                    <a class="sp-quick__item" href="<?= e(APP_BASE_PATH) ?>sistema/perfil_empresa.php">
                        <i class="bi bi-building-gear" aria-hidden="true"></i>
                        <span class="sp-quick__text">
                            <strong>Configuración de empresa</strong>
                            <small>Identidad, moneda, impuestos y tienda</small>
                        </span>
                        <i class="bi bi-chevron-right sp-caret" aria-hidden="true"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </article>

</section>

<section class="sp-card sp-mt-14">
    <div class="sp-card__head">
        <span class="sp-card__head-icon"><i class="bi bi-rocket-takeoff" aria-hidden="true"></i></span>
        <div class="sp-card__head-text">
            <h2>Próximos módulos</h2>
            <p>La base ya está lista para crecer</p>
        </div>
        <span class="sp-badge sp-badge--cyan">Roadmap</span>
    </div>

    <div class="sp-card__body">
        <div class="sp-roadmap">
            <span class="sp-roadmap__item"><i class="bi bi-box-seam"></i> Inventario</span>
            <span class="sp-roadmap__item"><i class="bi bi-cart3"></i> Ventas</span>
            <span class="sp-roadmap__item"><i class="bi bi-bag-check"></i> Compras</span>
            <span class="sp-roadmap__item"><i class="bi bi-cash-stack"></i> Caja</span>
            <span class="sp-roadmap__item"><i class="bi bi-shop-window"></i> Tienda virtual</span>
            <span class="sp-roadmap__item"><i class="bi bi-graph-up-arrow"></i> Reportes</span>
        </div>

        <div class="sp-note sp-mt-14">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>
                Fecha del panel: <?= e(date($formatoFecha)) ?>.
                Los módulos se incorporarán sobre la misma base de seguridad y diseño.
            </span>
        </div>
    </div>
</section>

<?php
layout_end();
