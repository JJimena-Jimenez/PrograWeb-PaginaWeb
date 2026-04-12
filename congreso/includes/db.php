<?php
/**
 * Configuración de Base de Datos - Clever Cloud MySQL
 */

define('DB_HOST',     'bnrgqhattka68wnlezck-mysql.services.clever-cloud.com');
define('DB_PORT',     '3306');
define('DB_NAME',     'bnrgqhattka68wnlezck');
define('DB_USER',     'udi0kwg4o2yvmckt');
define('DB_PASSWORD', '8kdS1q59MFpl0FUuaSsl');

// Conexión PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
