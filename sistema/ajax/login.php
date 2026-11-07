<?php
/**
 * sistema/ajax/login.php
 * Endpoint POST del inicio de sesión. Responde siempre en JSON.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/modelos/auth/Auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
security_headers();

/** Respuesta JSON y fin de la petición. */
function responder(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder(['ok' => false, 'codigo' => 'metodo', 'mensaje' => 'Método no permitido.'], 405);
}

session_boot();

// Protección CSRF
$tokenCsrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!csrf_valid(is_string($tokenCsrf) ? $tokenCsrf : null)) {
    responder([
        'ok'      => false,
        'codigo'  => 'csrf',
        'mensaje' => 'Tu sesión expiró. Recarga la página e inténtalo de nuevo.',
    ]);
}

// Límite de peticiones por sesión (además del bloqueo persistente en BD)
if (!throttle('login', 12, 60)) {
    responder([
        'ok'      => false,
        'codigo'  => 'rate',
        'mensaje' => 'Demasiados intentos seguidos. Espera un minuto.',
    ]);
}

$identificador = clean_text((string) ($_POST['identificador'] ?? ''), 150);
$password      = (string) ($_POST['password'] ?? '');

if ($identificador === '' || $password === '') {
    responder([
        'ok'      => false,
        'codigo'  => 'validacion',
        'mensaje' => 'Completa usuario y contraseña.',
    ]);
}

if (mb_strlen($password) > 200) {
    responder([
        'ok'      => false,
        'codigo'  => 'validacion',
        'mensaje' => 'La contraseña supera el tamaño permitido.',
    ]);
}

try {
    $auth = new Auth();
    $resultado = $auth->login($identificador, $password);

    // Los errores de negocio (credenciales, bloqueo, suscripción) se
    // devuelven con HTTP 200 y ok=false: el navegador no los marca como
    // fallo de red y el cliente decide qué mostrar. Solo los fallos de
    // infraestructura usan 5xx.
    responder($resultado);
} catch (Throwable $e) {
    error_log('[login] ' . $e->getMessage());
    responder([
        'ok'      => false,
        'codigo'  => 'servidor',
        'mensaje' => 'Ocurrió un error inesperado. Inténtalo más tarde.',
    ], 500);
}
