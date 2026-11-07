<?php
/**
 * config/security.php
 * Helpers de seguridad sin clases: escape, cabeceras, CSRF, IP y rate limit.
 */

declare(strict_types=1);

/** Escapa cualquier valor para HTML (previene XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** ¿La petición actual llega por HTTPS? */
function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');
}

/**
 * IP del cliente.
 * No se confía en X-Forwarded-For para evitar suplantación; si algún día
 * se publica detrás de un proxy de confianza, debe configurarse aquí.
 */
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/** User-Agent recortado al tamaño de la columna. */
function client_user_agent(): string
{
    return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

/** Cabeceras de seguridad para todas las respuestas HTML/JSON. */
function security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');

    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' https://cdn.jsdelivr.net; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        . "font-src 'self' https://cdn.jsdelivr.net data:; "
        . "img-src 'self' data: blob:; "
        . "connect-src 'self' https://cdn.jsdelivr.net; "
        . "frame-ancestors 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'"
    );

    header('X-Robots-Tag: noindex, nofollow, noarchive');

    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/** Token CSRF de la sesión (se crea si no existe). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Comparación en tiempo constante del token CSRF. */
function csrf_valid(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || $token === null || $token === '') {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/** Invalida el token actual; se genera uno nuevo en la siguiente petición. */
function csrf_rotate(): void
{
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_rotated_at'] = time();
    csrf_token();
}

/**
 * Rate limit sencillo por sesión (complementa el bloqueo persistente en BD).
 * Devuelve true si la acción está permitida.
 */
function throttle(string $key, int $maxIntentos = 10, int $segundos = 60): bool
{
    $now = time();
    $bucket = $_SESSION['_throttle'][$key] ?? ['inicio' => $now, 'intentos' => 0];

    if (($now - (int) $bucket['inicio']) > $segundos) {
        $bucket = ['inicio' => $now, 'intentos' => 0];
    }

    $bucket['intentos']++;
    $_SESSION['_throttle'][$key] = $bucket;

    return $bucket['intentos'] <= $maxIntentos;
}

/**
 * Limpia un texto de entrada eliminando caracteres de control
 * y recortando la longitud máxima.
 */
function clean_text(string $value, int $maxLength = 150): string
{
    $value = trim($value);
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';

    return mb_substr($value, 0, $maxLength);
}
