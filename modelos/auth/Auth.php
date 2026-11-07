<?php
/**
 * modelos/auth/Auth.php
 * Única clase de autenticación de StockiaPro.
 * Concentra credenciales, bloqueo por intentos y creación de sesión.
 *
 * Regla: la empresa y la suscripción SIEMPRE se validan en el servidor.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

final class Auth
{
    /**
     * Hash de relleno para igualar el tiempo de respuesta cuando el
     * usuario no existe (mitiga ataques de enumeración por temporización).
     */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$SzIxNmlDZHZPSGFuT3k4dQ$aqCQH8UcSB+8VhZ1KQpuga0YCX2F1KM/5NfUM2fHyGU';

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * Intenta iniciar sesión.
     *
     * @return array{ok:bool, codigo:string, mensaje:string, redirect?:string, nombre?:string}
     */
    public function login(string $identificador, string $password): array
    {
        $identificador = trim($identificador);
        $ip = client_ip();
        $ua = client_user_agent();

        // 1) Bloqueo por intentos fallidos (identificador o IP)
        $minutos = $this->minutosBloqueo($identificador, $ip);
        if ($minutos > 0) {
            $this->registrarIntento($identificador, $ip, $ua, 'bloqueado', 'demasiados_intentos');
            return $this->fallo(
                'bloqueado',
                "Demasiados intentos fallidos. Inténtalo de nuevo en {$minutos} minuto(s)."
            );
        }

        // 2) Buscar candidatos (usuario o correo). El identificador puede
        //    repetirse entre empresas: se resuelve por contraseña, nunca por orden.
        $candidatos = Database::all(
            "SELECT u.id, u.empresa_id, u.sucursal_id, u.rol_id,
                    u.nombre_completo, u.usuario, u.email, u.password_hash,
                    u.estado, u.intentos_fallidos,
                    TIMESTAMPDIFF(MINUTE, NOW(), u.bloqueado_hasta) AS bloqueo_minutos,
                    r.nombre AS rol_nombre,
                    r.nivel  AS rol_nivel,
                    e.estado AS empresa_estado, e.tipo_licencia,
                    e.fecha_vencimiento_licencia, e.es_saas_owner
             FROM usuarios u
             INNER JOIN empresas e ON e.id = u.empresa_id AND e.deleted_at IS NULL
             LEFT JOIN roles r ON r.id = u.rol_id
             WHERE (u.usuario = :idUsuario OR u.email = :idEmail)
               AND u.deleted_at IS NULL
             ORDER BY u.id ASC
             LIMIT 20",
            [
                ':idUsuario' => $identificador,
                ':idEmail'   => $identificador,
            ]
        );

        // 3) Sin coincidencias: misma respuesta y tiempo similar
        if ($candidatos === []) {
            password_verify($password, self::DUMMY_HASH);
            $this->registrarIntento($identificador, $ip, $ua, 'fallido', 'usuario_inexistente');

            return $this->fallo('credenciales', 'Usuario o contraseña incorrectos.');
        }

        // 4) Verificar la contraseña contra cada candidato.
        //    Exactamente una coincidencia = empresa inequívoca.
        $coincidencias = [];
        foreach ($candidatos as $candidato) {
            if (password_verify($password, (string) $candidato['password_hash'])) {
                $coincidencias[] = $candidato;
            }
        }

        if (count($coincidencias) !== 1) {
            if (count($candidatos) === 1) {
                $this->incrementarFallos((int) $candidatos[0]['id']);
            }

            $this->registrarIntento(
                $identificador,
                $ip,
                $ua,
                'fallido',
                count($coincidencias) > 1 ? 'identificador_ambiguo' : 'password_invalido'
            );

            return $this->fallo('credenciales', 'Usuario o contraseña incorrectos.');
        }

        $usuario = $coincidencias[0];

        // 5) Bloqueo aplicado directamente al usuario
        if ((int) ($usuario['bloqueo_minutos'] ?? 0) > 0) {
            $restantes = (int) $usuario['bloqueo_minutos'];
            $this->registrarIntento(
                $identificador,
                $ip,
                $ua,
                'bloqueado',
                'usuario_bloqueado',
                (int) $usuario['empresa_id']
            );

            return $this->fallo(
                'bloqueado',
                "La cuenta está bloqueada temporalmente. Inténtalo en {$restantes} minuto(s)."
            );
        }

        // 6) Estado del usuario
        if ($usuario['estado'] !== 'activo') {
            $this->registrarIntento(
                $identificador,
                $ip,
                $ua,
                'fallido',
                'usuario_inactivo',
                (int) $usuario['empresa_id']
            );

            return $this->fallo('inactivo', 'Tu cuenta no está activa. Contacta al administrador.');
        }

        // 7) Estado de la empresa
        if (!in_array($usuario['empresa_estado'], ['activa', 'prueba'], true)) {
            $this->registrarIntento(
                $identificador,
                $ip,
                $ua,
                'bloqueado',
                'empresa_suspendida',
                (int) $usuario['empresa_id']
            );

            return $this->fallo('suscripcion', 'La empresa se encuentra suspendida. Contacta al administrador.');
        }

        // 8) Suscripción activa (licencia mensual, anual o perpetua)
        $activa = (int) Database::value(
            'SELECT fn_suscripcion_activa(:eid) AS activa',
            [':eid' => (int) $usuario['empresa_id']]
        );

        if ($activa !== 1) {
            $this->registrarIntento(
                $identificador,
                $ip,
                $ua,
                'bloqueado',
                'suscripcion_inactiva',
                (int) $usuario['empresa_id']
            );

            return $this->fallo(
                'suscripcion',
                'La suscripción de tu empresa no está activa. Contacta al administrador.'
            );
        }

        // 9) Rehash automático si cambia el algoritmo o el costo
        if (password_needs_rehash((string) $usuario['password_hash'], PASSWORD_ARGON2ID)) {
            Database::run(
                'UPDATE usuarios SET password_hash = :hash WHERE id = :id',
                [
                    ':hash' => password_hash($password, PASSWORD_ARGON2ID),
                    ':id'   => (int) $usuario['id'],
                ]
            );
        }

        // 10) Limpiar contadores y crear sesión server-side
        Database::run(
            'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id',
            [':id' => (int) $usuario['id']]
        );

        $empresa = cargar_config_empresa($this->pdo, (int) $usuario['empresa_id']);

        session_create($usuario, $empresa);

        Database::run(
            "UPDATE usuarios
             SET ultimo_acceso = NOW(),
                 ultimo_acceso_ip = :ip,
                 token_sesion_hash = :th,
                 token_expira_at = DATE_ADD(NOW(), INTERVAL " . (int) SESSION_LIFETIME_HOURS . " HOUR)
             WHERE id = :id",
            [
                ':ip' => $ip,
                ':th' => (string) $_SESSION['token_hash'],
                ':id' => (int) $usuario['id'],
            ]
        );

        $this->registrarIntento(
            $identificador,
            $ip,
            $ua,
            'exitoso',
            null,
            (int) $usuario['empresa_id']
        );

        return [
            'ok'       => true,
            'codigo'   => 'ok',
            'mensaje'  => 'Acceso concedido.',
            'nombre'   => $usuario['nombre_completo'],
            'redirect' => APP_BASE_PATH . 'sistema/dashboard.php',
        ];
    }

    /* ------------------------------------------------------------------
     | Internos
     * ------------------------------------------------------------------ */

    /**
     * Devuelve los minutos de bloqueo restantes (0 = sin bloqueo).
     * Bloqueo corto: 5 fallos en 15 min → 15 min.
     * Bloqueo extendido: 10 fallos en 24 h → 60 min.
     */
    private function minutosBloqueo(string $identificador, string $ip): int
    {
        $params = [':id' => $identificador, ':ip' => $ip];

        $corto = (int) Database::value(
            "SELECT COUNT(*) FROM intentos_login
             WHERE resultado = 'fallido'
               AND (identificador = :id OR ip_address = :ip)
               AND fecha_intento >= DATE_SUB(NOW(), INTERVAL " . (int) LOGIN_VENTANA_MINUTOS . " MINUTE)",
            $params
        );

        $largo = (int) Database::value(
            "SELECT COUNT(*) FROM intentos_login
             WHERE resultado = 'fallido'
               AND (identificador = :id OR ip_address = :ip)
               AND fecha_intento >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $params
        );

        $minutos = 0;

        if ($corto >= LOGIN_MAX_INTENTOS) {
            $minutos = LOGIN_BLOQUEO_MINUTOS;
        }

        if ($largo >= LOGIN_MAX_INTENTOS_LARGO) {
            $minutos = LOGIN_BLOQUEO_LARGO_MINUTOS;
        }

        if ($minutos === 0) {
            return 0;
        }

        $restantes = (int) Database::value(
            "SELECT GREATEST(
                        0,
                        TIMESTAMPDIFF(
                            SECOND,
                            NOW(),
                            DATE_ADD(MAX(fecha_intento), INTERVAL {$minutos} MINUTE)
                        )
                    )
             FROM intentos_login
             WHERE resultado = 'fallido'
               AND (identificador = :id OR ip_address = :ip)
               AND fecha_intento >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $params
        );

        return $restantes > 0 ? (int) ceil($restantes / 60) : 0;
    }

    /** Incrementa el contador del usuario y aplica bloqueo si corresponde. */
    private function incrementarFallos(int $usuarioId): void
    {
        $max  = (int) LOGIN_MAX_INTENTOS;
        $mins = (int) LOGIN_BLOQUEO_MINUTOS;

        Database::run(
            "UPDATE usuarios
             SET intentos_fallidos = intentos_fallidos + 1,
                 bloqueado_hasta = IF(
                     intentos_fallidos + 1 >= {$max},
                     DATE_ADD(NOW(), INTERVAL {$mins} MINUTE),
                     NULL
                 )
             WHERE id = :id",
            [':id' => $usuarioId]
        );
    }

    /** Guarda el intento en `intentos_login` sin interrumpir el flujo. */
    private function registrarIntento(
        string $identificador,
        string $ip,
        string $ua,
        string $resultado,
        ?string $motivo,
        ?int $empresaId = null
    ): void {
        try {
            Database::run(
                "INSERT INTO intentos_login
                    (identificador, empresa_id, ip_address, user_agent, resultado, motivo_rechazo)
                 VALUES (:identificador, :empresa, :ip, :ua, :resultado, :motivo)",
                [
                    ':identificador' => mb_substr($identificador, 0, 150),
                    ':empresa'       => $empresaId,
                    ':ip'            => $ip,
                    ':ua'            => $ua,
                    ':resultado'     => $resultado,
                    ':motivo'        => $motivo,
                ]
            );
        } catch (Throwable $e) {
            error_log('[Auth::registrarIntento] ' . $e->getMessage());
        }
    }

    private function fallo(string $codigo, string $mensaje): array
    {
        return [
            'ok'      => false,
            'codigo'  => $codigo,
            'mensaje' => $mensaje,
        ];
    }
}
