<?php
/**
 * config/database.php
 * Conexión PDO única (patrón singleton) y helpers de consulta.
 * Todas las consultas del sistema deben usar sentencias preparadas.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $instance = null;

    /** La clase no se instancia: se usa Database::pdo() */
    private function __construct()
    {
    }

    public static function pdo(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            error_log('[database] ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }

        return self::$instance;
    }

    /** Ejecuta una sentencia preparada y devuelve el statement. */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /** Devuelve la primera fila o null. */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** Devuelve todas las filas. */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Devuelve el primer campo de la primera fila. */
    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }
}
