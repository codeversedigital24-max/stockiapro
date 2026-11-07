<?php
/**
 * perfil_empresa.php
 * Configuración → Perfil de empresa.
 * Los datos se renderizan en el servidor (una sola consulta).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

security_headers();
require_login();

$pdo = Database::pdo();

if (usuario_nivel($pdo) < 90) {
    header('Location: ' . APP_BASE_PATH . 'sistema/dashboard.php');
    exit;
}

require_once __DIR__ . '/modulos/perfilEmpresa/PerfilEmpresa.php';
require_once __DIR__ . '/includes/layout.php';

$perfil = (new PerfilEmpresa($pdo))->obtener((int) $_SESSION['empresa_id']);
$csrf   = csrf_token();

$v = static fn(string $clave, string $defecto = ''): string =>
    e((string) ($perfil[$clave] ?? $defecto));

$sel = static fn(string $clave, string $valor): string =>
    (string) ($perfil[$clave] ?? '') === $valor ? 'selected' : '';

$checked = static fn(string $clave): string =>
    ((int) ($perfil[$clave] ?? 0)) === 1 ? 'checked' : '';

$logoUrl = layout_media_url((string) ($perfil['logo'] ?? ''));

$vencimiento = !empty($perfil['fecha_vencimiento'])
    ? date('d/m/Y', strtotime((string) $perfil['fecha_vencimiento']))
    : 'Sin vencimiento';

$actualizado = !empty($perfil['actualizado'])
    ? date('d/m/Y H:i', strtotime((string) $perfil['actualizado']))
    : '—';

$licencia = ($perfil['tipo_licencia'] ?? '') === 'licencia_unica'
    ? 'Licencia única'
    : 'Suscripción';

$empresaActiva = in_array((string) ($perfil['empresa_estado'] ?? ''), ['activa', 'prueba'], true);

layout_start('Perfil de empresa');
?>

<form id="formPerfil"
      method="post"
      enctype="multipart/form-data"
      data-endpoint="<?= e(APP_BASE_PATH) ?>sistema/ajax/perfil_empresa.php"
      data-base="<?= e(APP_BASE_PATH) ?>">

    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

    <section class="sp-hero sp-hero--page">
        <div class="sp-hero__content">
            <span class="sp-hero__badge">
                <i class="bi bi-sliders2" aria-hidden="true"></i>
                Configuración
            </span>

            <h1>Perfil de empresa</h1>
            <p>
                Personaliza la identidad, la moneda, los impuestos y la tienda.
                Todos los cambios se validan en el servidor antes de guardarse.
            </p>

            <div class="sp-hero__actions">
                <span class="sp-hero__status">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    Solo administradores
                </span>
            </div>
        </div>

        <div class="sp-hero__visual" aria-hidden="true">
            <div class="sp-hero__icon">
                <i class="bi bi-building-gear"></i>
            </div>
            <span class="sp-hero__chip sp-hero__chip--1"><i class="bi bi-check2-circle"></i> Validado</span>
            <span class="sp-hero__chip sp-hero__chip--2"><i class="bi bi-lock"></i> Seguro</span>
        </div>
    </section>

    <!-- Único botón de guardado: barra fija bajo el topbar -->
    <div class="sp-savebar">
        <span class="sp-savebar__hint">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            Los cambios se aplican al guardar y quedan disponibles en todo el sistema.
        </span>

        <button type="submit" class="sp-btn sp-btn--primary" data-save>
            <i class="bi bi-check2-circle" aria-hidden="true"></i>
            <span>Guardar cambios</span>
        </button>
    </div>

    <div class="sp-tabs" role="tablist" aria-label="Secciones del perfil">
        <button type="button" class="sp-tab is-active" data-tab="empresa" role="tab" aria-selected="true">
            <i class="bi bi-building" aria-hidden="true"></i> Empresa
        </button>
        <button type="button" class="sp-tab" data-tab="moneda" role="tab" aria-selected="false" title="Moneda e impuestos">
            <i class="bi bi-coin" aria-hidden="true"></i> Moneda
        </button>
        <button type="button" class="sp-tab" data-tab="tienda" role="tab" aria-selected="false">
            <i class="bi bi-shop-window" aria-hidden="true"></i> Tienda
        </button>
        <button type="button" class="sp-tab" data-tab="general" role="tab" aria-selected="false">
            <i class="bi bi-sliders2" aria-hidden="true"></i> General
        </button>
    </div>

    <!-- ============ EMPRESA ============ -->
    <section class="sp-tab-panel is-active" data-panel="empresa" role="tabpanel">
        <div class="sp-card">
            <div class="sp-card__head">
                <span class="sp-card__head-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
                <div class="sp-card__head-text">
                    <h2>Identidad de la empresa</h2>
                    <p>Nombre, marca, datos fiscales y contacto.</p>
                </div>
            </div>

            <div class="sp-card__body">
                <div class="sp-form-grid">

                    <div class="sp-field sp-field--full">
                        <span class="sp-label"><i class="bi bi-image" aria-hidden="true"></i> Logotipo de la empresa</span>

                        <div class="sp-logo-upload">
                            <div class="sp-logo-preview">
                                <img id="logoPreview"
                                     src="<?= e($logoUrl) ?>"
                                     data-original="<?= e($logoUrl) ?>"
                                     alt="Logotipo de la empresa">
                            </div>

                            <div class="sp-dropzone" id="logoDropzone" role="button" tabindex="0"
                                 aria-label="Arrastrar o seleccionar el logotipo">
                                <input type="file" id="logo" name="logo" class="sp-hidden"
                                       accept="image/png,image/jpeg,image/webp">

                                <span class="sp-dropzone__icon"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i></span>

                                <div class="sp-dropzone__meta">
                                    <strong>Arrastra y suelta tu logo aquí</strong>
                                    <span class="sp-dropzone__text">
                                        o <span class="sp-dropzone__browse">haz clic para seleccionar</span>
                                    </span>
                                    <span class="sp-dropzone__hint">JPG, PNG o WebP · máximo 2 MB</span>
                                    <span class="sp-dropzone__file" id="logoFileName">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                        <span id="logoFileNameText"></span>
                                    </span>
                                </div>

                                <button type="button" class="sp-logo-reset" data-logo-reset id="logoReset">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    <span>Quitar</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="nombre_comercial">
                            Nombre comercial <span class="req">*</span>
                        </label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-shop sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="nombre_comercial" name="nombre_comercial"
                                   value="<?= $v('nombre_comercial') ?>" maxlength="150" required
                                   placeholder="Ej. StockiaPro S.A." autocomplete="organization">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="razon_social">Razón social</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-briefcase sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="razon_social" name="razon_social"
                                   value="<?= $v('razon_social') ?>" maxlength="200"
                                   placeholder="Ej. StockiaPro Sociedad Anónima">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="identificacion_fiscal">NIT / Identificación fiscal</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-card-text sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="identificacion_fiscal" name="identificacion_fiscal"
                                   value="<?= $v('identificacion_fiscal') ?>" maxlength="50"
                                   placeholder="Ej. 123456789-0">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="email_contacto">
                            Correo de contacto <span class="req">*</span>
                        </label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-envelope sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="email" id="email_contacto" name="email_contacto"
                                   value="<?= $v('email_contacto') ?>" maxlength="150" required
                                   placeholder="contacto@tuempresa.com" autocomplete="email">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="telefono">Teléfono</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-telephone sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="tel" id="telefono" name="telefono"
                                   value="<?= $v('telefono') ?>" maxlength="30"
                                   placeholder="+591 700 00000" autocomplete="tel">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="whatsapp">WhatsApp</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-whatsapp sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="tel" id="whatsapp" name="whatsapp"
                                   value="<?= $v('whatsapp') ?>" maxlength="30"
                                   placeholder="+591 700 00000">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="sitio_web">Sitio web</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-globe2 sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="url" id="sitio_web" name="sitio_web"
                                   value="<?= $v('sitio_web') ?>" maxlength="150"
                                   placeholder="https://tuempresa.com" autocomplete="url">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="ciudad">Ciudad</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-geo-alt sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="ciudad" name="ciudad"
                                   value="<?= $v('ciudad') ?>" maxlength="80"
                                   placeholder="Ej. La Paz" autocomplete="address-level2">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="pais">País</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-flag sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="pais" name="pais"
                                   value="<?= $v('pais') ?>" maxlength="80"
                                   placeholder="Ej. Bolivia" autocomplete="country-name">
                        </div>
                    </div>

                    <div class="sp-field sp-field--full">
                        <label class="sp-label" for="direccion">Dirección</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-geo sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="direccion" name="direccion"
                                   value="<?= $v('direccion') ?>" maxlength="200"
                                   placeholder="Av. Principal #123, Zona Centro" autocomplete="street-address">
                        </div>
                    </div>

                    <div class="sp-field sp-field--full">
                        <label class="sp-label" for="descripcion_comercial">Descripción comercial</label>
                        <div class="sp-input-wrap sp-textarea-wrap">
                            <i class="bi bi-chat-left-text sp-input-icon" aria-hidden="true"></i>
                            <textarea class="sp-textarea" id="descripcion_comercial" name="descripcion_comercial"
                                      maxlength="2000" rows="3"
                                      placeholder="Describe brevemente tu empresa, productos o servicios..."><?= $v('descripcion_comercial') ?></textarea>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ============ MONEDA E IMPUESTOS ============ -->
    <section class="sp-tab-panel" data-panel="moneda" role="tabpanel">
        <div class="sp-card">
            <div class="sp-card__head">
                <span class="sp-card__head-icon"><i class="bi bi-coin" aria-hidden="true"></i></span>
                <div class="sp-card__head-text">
                    <h2>Moneda e impuestos</h2>
                    <p>Formato de precios, impuestos y comprobantes.</p>
                </div>
            </div>

            <div class="sp-card__body">
                <div class="sp-form-grid">

                    <div class="sp-field">
                        <label class="sp-label" for="moneda_principal">Moneda principal</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-currency-exchange sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="moneda_principal" name="moneda_principal"
                                   value="<?= $v('moneda_principal') ?>" maxlength="50"
                                   placeholder="Ej. Boliviano">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="codigo_moneda">
                            Código ISO <span class="req">*</span>
                        </label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-upc-scan sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input sp-input--upper" type="text" id="codigo_moneda" name="codigo_moneda"
                                   value="<?= $v('codigo_moneda') ?>" maxlength="3" minlength="3"
                                   pattern="[A-Za-z]{3}" placeholder="BOB" required>
                        </div>
                        <span class="sp-help">3 letras: USD, BOB, EUR…</span>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="simbolo_moneda">
                            Símbolo <span class="req">*</span>
                        </label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-currency-dollar sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="simbolo_moneda" name="simbolo_moneda"
                                   value="<?= $v('simbolo_moneda') ?>" maxlength="5" placeholder="Bs" required>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="decimales">Decimales</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-123 sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="decimales" name="decimales">
                                <?php for ($i = 0; $i <= 4; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int) ($perfil['decimales'] ?? 2) === $i ? 'selected' : '' ?>>
                                        <?= $i ?> decimales
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="separador_decimal">Separador decimal</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-dot sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="separador_decimal" name="separador_decimal">
                                <option value="." <?= $sel('separador_decimal', '.') ?>>Punto · 1.234,56</option>
                                <option value="," <?= $sel('separador_decimal', ',') ?>>Coma · 1,234.56</option>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="separador_miles">Separador de miles</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-three-dots sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="separador_miles" name="separador_miles">
                                <option value="," <?= $sel('separador_miles', ',') ?>>Coma · 1,234.56</option>
                                <option value="." <?= $sel('separador_miles', '.') ?>>Punto · 1.234,56</option>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="impuesto_nombre">Nombre del impuesto</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-receipt sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="impuesto_nombre" name="impuesto_nombre"
                                   value="<?= $v('impuesto_nombre') ?>" maxlength="50" placeholder="IVA">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="impuesto_porcentaje">Porcentaje</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-percent sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="number" id="impuesto_porcentaje" name="impuesto_porcentaje"
                                   value="<?= e(number_format((float) ($perfil['impuesto_porcentaje'] ?? 0), 2, '.', '')) ?>"
                                   min="0" max="100" step="0.01" inputmode="decimal" placeholder="13.00">
                        </div>
                    </div>

                    <div class="sp-field sp-field--full">
                        <span class="sp-label"><i class="bi bi-tags" aria-hidden="true"></i> Precios con impuesto</span>
                        <label class="sp-switch">
                            <input type="checkbox" name="precios_con_impuesto" value="1" <?= $checked('precios_con_impuesto') ?>>
                            <span class="sp-switch__track" aria-hidden="true"></span>
                            <span class="sp-switch__text">
                                Los precios ya incluyen impuesto
                                <small>Si está desactivado, el impuesto se suma al precio final.</small>
                            </span>
                        </label>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="comprobante_tipo">Tipo de comprobante</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-file-earmark-text sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="comprobante_tipo" name="comprobante_tipo"
                                   value="<?= $v('comprobante_tipo') ?>" maxlength="50"
                                   placeholder="Factura, recibo o nota">
                        </div>
                    </div>

                    <div class="sp-field sp-field--full">
                        <label class="sp-label" for="datos_facturacion">Datos para facturación</label>
                        <div class="sp-input-wrap sp-textarea-wrap">
                            <i class="bi bi-journal-text sp-input-icon" aria-hidden="true"></i>
                            <textarea class="sp-textarea" id="datos_facturacion" name="datos_facturacion"
                                      maxlength="3000" rows="3"
                                      placeholder="Datos que aparecerán en los comprobantes: razón social, NIT, dirección..."><?= $v('datos_facturacion') ?></textarea>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ============ TIENDA ============ -->
    <section class="sp-tab-panel" data-panel="tienda" role="tabpanel">
        <div class="sp-card">
            <div class="sp-card__head">
                <span class="sp-card__head-icon"><i class="bi bi-shop-window" aria-hidden="true"></i></span>
                <div class="sp-card__head-text">
                    <h2>Tienda virtual</h2>
                    <p>Identidad pública y estado de publicación.</p>
                </div>
            </div>

            <div class="sp-card__body">
                <div class="sp-form-grid">

                    <div class="sp-field">
                        <label class="sp-label" for="tienda_nombre">Nombre de la tienda</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-shop sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="tienda_nombre" name="tienda_nombre"
                                   value="<?= $v('tienda_nombre') ?>" maxlength="150"
                                   placeholder="Ej. Mi Tienda Online">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="tienda_slug">Identificador público</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-link-45deg sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="tienda_slug" name="tienda_slug"
                                   value="<?= $v('tienda_slug') ?>" maxlength="100"
                                   pattern="[a-z0-9-]{3,100}" placeholder="mi-tienda">
                        </div>
                        <span class="sp-help">Solo minúsculas, números y guiones.</span>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="tienda_contacto">Contacto de la tienda</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-person-lines-fill sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input" type="text" id="tienda_contacto" name="tienda_contacto"
                                   value="<?= $v('tienda_contacto') ?>" maxlength="255"
                                   placeholder="WhatsApp, correo o teléfono de la tienda">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="tienda_color_primario">Color primario</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-palette sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input sp-input--color" type="color" id="tienda_color_primario"
                                   name="tienda_color_primario" value="<?= $v('tienda_color_primario', '#2563eb') ?>">
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="tienda_color_secundario">Color secundario</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-palette2 sp-input-icon" aria-hidden="true"></i>
                            <input class="sp-input sp-input--color" type="color" id="tienda_color_secundario"
                                   name="tienda_color_secundario" value="<?= $v('tienda_color_secundario', '#6c757d') ?>">
                        </div>
                    </div>

                    <div class="sp-field sp-field--full">
                        <span class="sp-label"><i class="bi bi-broadcast" aria-hidden="true"></i> Estado de publicación</span>
                        <label class="sp-switch">
                            <input type="checkbox" name="tienda_publicada" value="1" <?= $checked('tienda_publicada') ?>>
                            <span class="sp-switch__track" aria-hidden="true"></span>
                            <span class="sp-switch__text">
                                Tienda publicada
                                <small>Haz visible la tienda virtual para tus clientes.</small>
                            </span>
                        </label>
                    </div>

                    <div class="sp-field sp-field--full">
                        <label class="sp-label" for="tienda_descripcion">Descripción de la tienda</label>
                        <div class="sp-input-wrap sp-textarea-wrap">
                            <i class="bi bi-chat-square-text sp-input-icon" aria-hidden="true"></i>
                            <textarea class="sp-textarea" id="tienda_descripcion" name="tienda_descripcion"
                                      maxlength="2000" rows="3"
                                      placeholder="Describe tu tienda, productos y propuesta de valor..."><?= $v('tienda_descripcion') ?></textarea>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ============ GENERAL ============ -->
    <section class="sp-tab-panel" data-panel="general" role="tabpanel">
        <div class="sp-card">
            <div class="sp-card__head">
                <span class="sp-card__head-icon"><i class="bi bi-sliders2" aria-hidden="true"></i></span>
                <div class="sp-card__head-text">
                    <h2>Preferencias generales</h2>
                    <p>Zona horaria, idioma, formatos y estado.</p>
                </div>
            </div>

            <div class="sp-card__body">
                <div class="sp-form-grid">

                    <div class="sp-field">
                        <label class="sp-label" for="zona_horaria">Zona horaria</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-globe-americas sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="zona_horaria" name="zona_horaria">
                                <?php foreach (DateTimeZone::listIdentifiers() as $zona): ?>
                                    <option value="<?= e($zona) ?>" <?= $sel('zona_horaria', $zona) ?>>
                                        <?= e($zona) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="idioma">Idioma</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-translate sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="idioma" name="idioma">
                                <option value="es" <?= $sel('idioma', 'es') ?>>Español</option>
                                <option value="en" <?= $sel('idioma', 'en') ?>>English</option>
                                <option value="pt" <?= $sel('idioma', 'pt') ?>>Português</option>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="formato_fecha">Formato de fecha</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-calendar3 sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="formato_fecha" name="formato_fecha">
                                <option value="d/m/Y" <?= $sel('formato_fecha', 'd/m/Y') ?>>31/12/2026</option>
                                <option value="Y-m-d" <?= $sel('formato_fecha', 'Y-m-d') ?>>2026-12-31</option>
                                <option value="d-m-Y" <?= $sel('formato_fecha', 'd-m-Y') ?>>31-12-2026</option>
                                <option value="m/d/Y" <?= $sel('formato_fecha', 'm/d/Y') ?>>12/31/2026</option>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="formato_hora">Formato de hora</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-clock sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="formato_hora" name="formato_hora">
                                <option value="H:i" <?= $sel('formato_hora', 'H:i') ?>>24 horas · 14:30</option>
                                <option value="h:i A" <?= $sel('formato_hora', 'h:i A') ?>>12 horas · 02:30 PM</option>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="estado">Estado de la configuración</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-toggle-on sp-input-icon" aria-hidden="true"></i>
                            <select class="sp-select" id="estado" name="estado">
                                <option value="activa" <?= $sel('estado', 'activa') ?>>Activa</option>
                                <option value="inactiva" <?= $sel('estado', 'inactiva') ?>>Inactiva</option>
                            </select>
                            <i class="bi bi-chevron-down sp-select-arrow" aria-hidden="true"></i>
                        </div>
                    </div>

                </div>

                <div class="sp-license">
                    <div class="sp-license__item">
                        <span class="sp-license__icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
                        <span>
                            <span class="sp-license__label">Empresa</span>
                            <span class="sp-license__value">
                                <span class="sp-badge <?= $empresaActiva ? 'sp-badge--success' : 'sp-badge--danger' ?>">
                                    <?= e(ucfirst((string) ($perfil['empresa_estado'] ?? ''))) ?>
                                </span>
                            </span>
                        </span>
                    </div>

                    <div class="sp-license__item">
                        <span class="sp-license__icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
                        <span>
                            <span class="sp-license__label">Licencia</span>
                            <span class="sp-license__value"><?= e($licencia) ?></span>
                        </span>
                    </div>

                    <div class="sp-license__item">
                        <span class="sp-license__icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                        <span>
                            <span class="sp-license__label">Vencimiento</span>
                            <span class="sp-license__value"><?= e($vencimiento) ?></span>
                        </span>
                    </div>

                    <div class="sp-license__item">
                        <span class="sp-license__icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                        <span>
                            <span class="sp-license__label">Actualizado</span>
                            <span class="sp-license__value"><?= e($actualizado) ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

</form>

<noscript>
    <div class="sp-note sp-mt-14">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <span>Activa JavaScript para guardar el perfil.</span>
    </div>
</noscript>

<?php
layout_end(['sistema/modulos/perfilEmpresa/perfil_empresa_api.js'], true);
