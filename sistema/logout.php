<?php
/**
 * logout.php
 * Cierra la sesión actual, revoca el token en la base de datos y vuelve al login.
 * Solo acepta POST con token CSRF válido.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

security_headers();
session_boot();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && csrf_valid($_POST['csrf_token'] ?? null)) {
    session_close(Database::pdo(), 'logout');
    header('Location: ' . APP_BASE_PATH . 'login.php');
    exit;
}

if (empty($_SESSION['usuario_id'])) {
    header('Location: ' . APP_BASE_PATH . 'login.php');
    exit;
}

// Acceso por GET o token inválido: no se cierra la sesión.
header('Location: ' . APP_BASE_PATH . 'sistema/dashboard.php');
exit;
