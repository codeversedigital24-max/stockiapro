<?php
/**
 * sistema/ajax/perfil_empresa.php
 * Endpoint POST para guardar el perfil de la empresa.
 * La empresa SIEMPRE se toma de la sesión, nunca de los datos enviados.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__) . '/modulos/perfilEmpresa/PerfilEmpresa.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
security_headers();

function json_out(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_out(['success' => false, 'message' => 'Método no permitido.'], 405);
}

session_boot();

try {
    $pdo = Database::pdo();
} catch (Throwable $e) {
    error_log('[perfil_empresa] ' . $e->getMessage());
    json_out(['success' => false, 'message' => 'Servicio temporalmente no disponible.'], 503);
}

if (!session_validate($pdo)) {
    json_out(['success' => false, 'message' => 'La sesión ya no es válida. Recarga la página.'], 401);
}

if (usuario_nivel($pdo) < 90) {
    json_out(['success' => false, 'message' => 'No tienes permiso para modificar el perfil.'], 403);
}

$csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!csrf_valid(is_string($csrf) ? $csrf : null)) {
    json_out(['success' => false, 'message' => 'Token de seguridad inválido. Recarga la página.'], 403);
}

if (!throttle('perfil_empresa', 20, 60)) {
    json_out(['success' => false, 'message' => 'Demasiados intentos seguidos. Espera un minuto.'], 429);
}

$empresaId = (int) ($_SESSION['empresa_id'] ?? 0);

if ($empresaId <= 0) {
    json_out(['success' => false, 'message' => 'No se pudo identificar la empresa.'], 403);
}

try {
    $perfil = new PerfilEmpresa($pdo);
    $resultado = $perfil->guardar($empresaId, $_POST, $_FILES);

    if (empty($resultado['ok'])) {
        json_out([
            'success' => false,
            'message' => $resultado['mensaje'] ?? 'Revisa los datos e inténtalo de nuevo.',
        ]);
    }

    // Refresca la configuración centralizada para el resto del sistema.
    $_SESSION['empresa'] = cargar_config_empresa($pdo, $empresaId);

    json_out([
        'success' => true,
        'message' => $resultado['mensaje'] ?? 'Perfil actualizado.',
        'data'    => $resultado['data'] ?? [],
    ]);
} catch (Throwable $e) {
    error_log('[perfil_empresa] ' . $e->getMessage());
    json_out(['success' => false, 'message' => 'No se pudo guardar el perfil.'], 500);
}
