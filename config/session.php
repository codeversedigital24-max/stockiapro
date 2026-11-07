<?php
/**
 * config/session.php
 * Sesión PHP endurecida + token server-side en `sesiones_usuarios`.
 * La sesión dura como máximo 4 horas y se valida siempre en el servidor.
 */

declare(strict_types=1);

/**
 * Inicia la sesión con cookies seguras y aplica la expiración absoluta.
 */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_check_lifetime();
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) (SESSION_LIFETIME_HOURS * 3600));

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => APP_BASE_PATH,
        'domain'   => '',
        'secure'   => is_https(),   // solo se envía por HTTPS cuando existe
        'httponly' => true,         // inaccesible desde JavaScript
        'samesite' => 'Lax',
    ]);

    session_start();

    session_check_lifetime();
    csrf_rotate_periodic();
}

/**
 * Cierra la sesión PHP si superó la vida máxima (4 h).
 * La comprobación se hace también contra `sesiones_usuarios` en session_validate().
 */
function session_check_lifetime(): void
{
    if (empty($_SESSION['login_at'])) {
        return;
    }

    if ((time() - (int) $_SESSION['login_at']) > (SESSION_LIFETIME_HOURS * 3600)) {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

/** Rota el CSRF cada 30 minutos sin romper peticiones en curso. */
function csrf_rotate_periodic(): void
{
    $now = time();

    if (empty($_SESSION['csrf_rotated_at'])) {
        $_SESSION['csrf_rotated_at'] = $now;
        return;
    }

    if (($now - (int) $_SESSION['csrf_rotated_at']) > CSRF_ROTATE_SECONDS) {
        csrf_rotate();
    }
}

/**
 * Crea la sesión autenticada:
 *  - regenera el ID (evita Session Fixation)
 *  - guarda un token aleatorio solo como hash SHA-256
 *  - fija expiración absoluta de 4 h
 */
function session_create(array $usuario, array $empresa): void
{
    session_regenerate_id(true);

    $tokenPlano = bin2hex(random_bytes(32));
    $tokenHash  = hash('sha256', $tokenPlano);
    $horas      = (int) SESSION_LIFETIME_HOURS;

    // Una sola sesión activa por usuario.
    Database::run(
        "UPDATE sesiones_usuarios
         SET revocada = 1, motivo_revocacion = 'nueva_sesion'
         WHERE usuario_id = :uid AND revocada = 0",
        [':uid' => (int) $usuario['id']]
    );

    Database::run(
        "INSERT INTO sesiones_usuarios
            (usuario_id, empresa_id, token_hash, ip_address, user_agent, fecha_expiracion)
         VALUES
            (:uid, :eid, :th, :ip, :ua, DATE_ADD(NOW(), INTERVAL {$horas} HOUR))",
        [
            ':uid' => (int) $usuario['id'],
            ':eid' => (int) $usuario['empresa_id'],
            ':th'  => $tokenHash,
            ':ip'  => client_ip(),
            ':ua'  => client_user_agent(),
        ]
    );

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['empresa_id'] = (int) $usuario['empresa_id'];
    $_SESSION['rol_id']     = (int) $usuario['rol_id'];
    $_SESSION['usuario']    = [
        'id'             => (int) $usuario['id'],
        'empresa_id'     => (int) $usuario['empresa_id'],
        'sucursal_id'    => $usuario['sucursal_id'] !== null ? (int) $usuario['sucursal_id'] : null,
        'rol_id'         => (int) $usuario['rol_id'],
        'rol_nombre'     => $usuario['rol_nombre'] ?? null,
        'rol_nivel'      => (int) ($usuario['rol_nivel'] ?? 0),
        'nombre_completo'=> $usuario['nombre_completo'],
        'usuario'        => $usuario['usuario'],
        'email'          => $usuario['email'],
    ];
    $_SESSION['empresa']    = $empresa;
    $_SESSION['token_hash'] = $tokenHash;
    $_SESSION['login_at']   = time();
    $_SESSION['last_touch'] = 0;

    csrf_rotate();
}

/**
 * Valida la sesión contra la base de datos.
 * Comprueba token, expiración, usuario, empresa y suscripción.
 */
function session_validate(PDO $pdo): bool
{
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['token_hash'])) {
        return false;
    }

    if (empty($_SESSION['login_at'])
        || (time() - (int) $_SESSION['login_at']) > (SESSION_LIFETIME_HOURS * 3600)
    ) {
        return false;
    }

    $row = Database::one(
        "SELECT s.id AS sesion_id,
                u.estado  AS usuario_estado,
                u.empresa_id,
                e.estado  AS empresa_estado,
                fn_suscripcion_activa(u.empresa_id) AS suscripcion_activa
         FROM sesiones_usuarios s
         INNER JOIN usuarios u ON u.id = s.usuario_id AND u.deleted_at IS NULL
         INNER JOIN empresas e ON e.id = u.empresa_id AND e.deleted_at IS NULL
         WHERE s.usuario_id = :uid
           AND s.token_hash = :th
           AND s.revocada = 0
           AND s.fecha_expiracion > NOW()
         ORDER BY s.id DESC
         LIMIT 1",
        [
            ':uid' => (int) $_SESSION['usuario_id'],
            ':th'  => (string) $_SESSION['token_hash'],
        ]
    );

    if ($row === null) {
        return false;
    }

    if ($row['usuario_estado'] !== 'activo') {
        return false;
    }

    if (!in_array($row['empresa_estado'], ['activa', 'prueba'], true)) {
        return false;
    }

    if ((int) $row['suscripcion_activa'] !== 1) {
        return false;
    }

    // Actualiza la actividad como máximo una vez por minuto.
    if ((time() - (int) ($_SESSION['last_touch'] ?? 0)) > 60) {
        Database::run(
            "UPDATE sesiones_usuarios SET ultima_actividad = NOW() WHERE id = :sid",
            [':sid' => (int) $row['sesion_id']]
        );
        $_SESSION['last_touch'] = time();
    }

    if (empty($_SESSION['empresa'])) {
        $_SESSION['empresa'] = cargar_config_empresa($pdo, (int) $row['empresa_id']);
    }

    return true;
}

/**
 * Cierra y destruye la sesión actual. Revoca el token en BD.
 */
function session_close(PDO $pdo, string $motivo = 'logout'): void
{
    if (!empty($_SESSION['usuario_id']) && !empty($_SESSION['token_hash'])) {
        try {
            Database::run(
                "UPDATE sesiones_usuarios
                 SET revocada = 1, motivo_revocacion = :motivo
                 WHERE usuario_id = :uid AND token_hash = :th AND revocada = 0",
                [
                    ':motivo' => $motivo,
                    ':uid'    => (int) $_SESSION['usuario_id'],
                    ':th'     => (string) $_SESSION['token_hash'],
                ]
            );

            // Limpia el token actual del usuario (no se guarda el token plano).
            Database::run(
                "UPDATE usuarios
                 SET token_sesion_hash = NULL, token_expira_at = NULL
                 WHERE id = :id AND token_sesion_hash = :th",
                [
                    ':id' => (int) $_SESSION['usuario_id'],
                    ':th' => (string) $_SESSION['token_hash'],
                ]
            );
        } catch (Throwable $e) {
            error_log('[session_close] ' . $e->getMessage());
        }
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Guardián para páginas privadas. Redirige al login si la sesión no es válida.
 */
function require_login(): void
{
    session_boot();

    try {
        $pdo = Database::pdo();
    } catch (Throwable $e) {
        error_log('[require_login] ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Servicio temporalmente no disponible. Inténtalo más tarde.');
    }

    if (!session_validate($pdo)) {
        session_close($pdo, 'validacion_fallida');
        header('Location: ' . APP_BASE_PATH . 'login.php?e=sesion');
        exit;
    }
}

/**
 * Carga la configuración de la empresa una sola vez y la centraliza en sesión.
 */
function cargar_config_empresa(PDO $pdo, int $empresaId): array
{
    $row = Database::one(
        "SELECT e.id, e.nombre_comercial, e.razon_social, e.identificacion_fiscal,
                e.email_contacto, e.telefono, e.estado, e.tipo_licencia,
                c.logo_url, c.favicon_url, c.descripcion_comercial, c.direccion,
                c.ciudad, c.pais, c.sitio_web, c.whatsapp,
                c.moneda_principal, c.codigo_moneda, c.simbolo_moneda,
                c.decimales, c.separador_decimal, c.separador_miles,
                c.impuesto_nombre, c.impuesto_porcentaje, c.precios_con_impuesto,
                c.tienda_nombre, c.tienda_slug, c.tienda_logo_url,
                c.tienda_color_primario, c.tienda_color_secundario,
                c.zona_horaria, c.idioma, c.formato_fecha, c.formato_hora
         FROM empresas e
         LEFT JOIN configuracion_empresa c ON c.empresa_id = e.id
         WHERE e.id = :eid AND e.deleted_at IS NULL
         LIMIT 1",
        [':eid' => $empresaId]
    );

    if ($row === null) {
        return [];
    }

    $timezone = (string) ($row['zona_horaria'] ?? '');
    if ($timezone !== '' && in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
        date_default_timezone_set($timezone);
    }

    return [
        'id'                  => (int) $row['id'],
        'nombre_comercial'    => $row['nombre_comercial'],
        'razon_social'        => $row['razon_social'],
        'identificacion_fiscal' => $row['identificacion_fiscal'],
        'email'               => $row['email_contacto'],
        'telefono'            => $row['telefono'],
        'whatsapp'            => $row['whatsapp'],
        'estado'              => $row['estado'],
        'tipo_licencia'       => $row['tipo_licencia'],
        'logo'                => $row['logo_url'],
        'favicon'             => $row['favicon_url'],
        'descripcion'         => $row['descripcion_comercial'],
        'direccion'           => $row['direccion'],
        'ciudad'              => $row['ciudad'],
        'pais'                => $row['pais'],
        'sitio_web'           => $row['sitio_web'],
        'moneda'              => $row['moneda_principal'],
        'codigo_moneda'       => $row['codigo_moneda'],
        'simbolo'             => $row['simbolo_moneda'],
        'decimales'           => (int) $row['decimales'],
        'sep_decimal'         => $row['separador_decimal'],
        'sep_miles'           => $row['separador_miles'],
        'impuesto'            => $row['impuesto_nombre'],
        'impuesto_porcentaje' => (float) $row['impuesto_porcentaje'],
        'precios_con_impuesto'=> (bool) $row['precios_con_impuesto'],
        'tienda_nombre'       => $row['tienda_nombre'],
        'tienda_slug'         => $row['tienda_slug'],
        'tienda_logo'         => $row['tienda_logo_url'],
        'tienda_color'        => $row['tienda_color_primario'],
        'tienda_color_secundario' => $row['tienda_color_secundario'],
        'zona_horaria'        => $row['zona_horaria'],
        'idioma'              => $row['idioma'],
        'formato_fecha'       => $row['formato_fecha'],
        'formato_hora'        => $row['formato_hora'],
    ];
}

/** Acceso centralizado a la configuración de la empresa en sesión. */
function empresa_config(?string $key = null, mixed $default = null): mixed
{
    $config = $_SESSION['empresa'] ?? [];

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

/**
 * Nivel jerárquico del rol actual (90 = administrador).
 * Se guarda en sesión al iniciar sesión para no repetir la consulta.
 */
function usuario_nivel(PDO $pdo): int
{
    $nivel = (int) ($_SESSION['usuario']['rol_nivel'] ?? 0);

    if ($nivel > 0) {
        return $nivel;
    }

    $rolId = (int) ($_SESSION['rol_id'] ?? 0);

    if ($rolId <= 0) {
        return 0;
    }

    $nivel = (int) Database::value(
        'SELECT nivel FROM roles WHERE id = :id LIMIT 1',
        [':id' => $rolId]
    );

    $_SESSION['usuario']['rol_nivel'] = $nivel;

    return $nivel;
}

/** Formatea un número con la moneda de la empresa. */
function money(float $amount): string
{
    $decimales = (int) empresa_config('decimales', 2);
    $simbolo   = (string) empresa_config('simbolo', '$');
    $sepDec    = (string) empresa_config('sep_decimal', '.');
    $sepMiles  = (string) empresa_config('sep_miles', ',');

    return $simbolo . number_format($amount, $decimales, $sepDec, $sepMiles);
}
