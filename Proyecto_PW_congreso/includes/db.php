<?php
/* ============================================================
   ARCHIVO: includes/db.php
   FUNCIÓN: Conexión a la base de datos MySQL mediante PDO
   ============================================================
   - Define las credenciales de conexión (host, nombre de BD,
     usuario y contraseña del servidor Clever Cloud)
   - La función getDB() devuelve una instancia PDO reutilizable
   - Usa PDO::ERRMODE_EXCEPTION para lanzar errores como excepciones
   - charset utf8mb4 para soportar tildes y caracteres especiales
   
   USO: require_once 'includes/db.php';  →  $db = getDB();
   ============================================================ */

// ── Credenciales de conexión ──────────────────────────────────
// IMPORTANTE: Cambiar estos valores si migras de servidor
$host   = 'bnrgqhattka68wnlezck-mysql.services.clever-cloud.com';
$dbname = 'bnrgqhattka68wnlezck';
$user   = 'udi0kwg4o2yvmckt';
$pass   = '8kdS1q59MFpl0FUuaSsl';
$port   = '3306';

// ── Instancia PDO (singleton simple) ─────────────────────────
$pdo = null;

function getDB(): PDO {
    global $pdo, $host, $dbname, $user, $pass, $port;
    
    // Reutiliza la conexión si ya existe
    if ($pdo !== null) return $pdo;
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // En producción no mostrar el mensaje de error real
        die('Error de conexión a la base de datos.');
    }
    
    return $pdo;
}
