<?php
/**
 * config/config.php
 * Punto de carga único de la configuración de StockiaPro.
 * Cualquier página debe incluir este archivo y nada más para arrancar.
 */

declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/session.php';
