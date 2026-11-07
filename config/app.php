<?php
/**
 * config/app.php
 * Configuración global de StockiaPro: rutas, base de datos y reglas de negocio.
 * Este archivo no imprime nada ni abre conexiones.
 */

declare(strict_types=1);

/* ------------------------------------------------------------------
 | Entorno
 * ------------------------------------------------------------------ */
define('APP_NAME', 'StockiaPro');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'local');                 // local | production
define('APP_DEBUG', false);                 // nunca mostrar errores internos al usuario

/* ------------------------------------------------------------------
 | Rutas
 | APP_ROOT      -> ruta física de la aplicación
 | APP_BASE_PATH -> ruta web, por ejemplo "/stockiapro/"
 | APP_URL       -> URL absoluta, por ejemplo "http://localhost/stockiapro"
 * ------------------------------------------------------------------ */
define('APP_ROOT', dirname(__DIR__));

$documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
    ? rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'])), '/')
    : '';

$appRoot = rtrim(str_replace('\\', '/', APP_ROOT), '/');
$basePath = '/';

if ($documentRoot !== '' && str_starts_with($appRoot, $documentRoot)) {
    $relative = substr($appRoot, strlen($documentRoot));
    $basePath = $relative === '' ? '/' : rtrim($relative, '/') . '/';
}

define('APP_BASE_PATH', $basePath);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443');

define(
    'APP_URL',
    ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(APP_BASE_PATH, '/')
);

/* ------------------------------------------------------------------
 | Base de datos
 * ------------------------------------------------------------------ */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'stockiapro');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/* ------------------------------------------------------------------
 | Reglas de seguridad y sesión
 * ------------------------------------------------------------------ */
define('SESSION_NAME', 'STOCKIAPRO_SESS');
define('SESSION_LIFETIME_HOURS', 4);        // duración máxima absoluta de la sesión
define('CSRF_ROTATE_SECONDS', 1800);        // rotación del token CSRF cada 30 min

define('LOGIN_MAX_INTENTOS', 5);            // fallos en ventana corta
define('LOGIN_VENTANA_MINUTOS', 15);
define('LOGIN_BLOQUEO_MINUTOS', 15);        // primer bloqueo
define('LOGIN_MAX_INTENTOS_LARGO', 10);     // fallos en 24 h
define('LOGIN_BLOQUEO_LARGO_MINUTOS', 60);  // bloqueo extendido

/* ------------------------------------------------------------------
 | Manejo de errores
 * ------------------------------------------------------------------ */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('America/Mexico_City');
