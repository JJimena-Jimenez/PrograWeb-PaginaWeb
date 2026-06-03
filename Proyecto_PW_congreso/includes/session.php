<?php
/* ============================================================
   ARCHIVO: includes/session.php
   FUNCIÓN: Configuración global — se incluye en TODOS los archivos
   ============================================================
   Contiene:
   1. Inicio de sesión PHP ($_SESSION)
   2. Función sanitize()   → limpia inputs del usuario (XSS)
   3. Función redirect()   → redirige y termina ejecución
   4. Función flash()      → mensajes de error/éxito entre páginas
   5. Función isAdminLoggedIn() → verifica sesión de administrador
   
   SESIONES USADAS EN EL PROYECTO:
   - $_SESSION['registro']       → datos del formulario entre procesar y pago
   - $_SESSION['pago_ok']        → datos del pago completado para gracias.php
   - $_SESSION['usuario_id']     → ID del usuario logueado (portal público)
   - $_SESSION['usuario_nombre'] → nombre del usuario para el nav
   - $_SESSION['admin_id']       → ID del admin logueado (panel admin)
   - $_SESSION['flash']          → mensajes temporales entre redirecciones
   ============================================================ */

// ── 1. Iniciar sesión si no está activa ───────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── 2. sanitize() — Limpia input del usuario (previene XSS) ──
// USO: $nombre = sanitize($_POST['nombre'] ?? '');
function sanitize(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// ── 3. redirect() — Redirige a otra URL y detiene ejecución ──
// USO: redirect('index.php');  o  redirect('pago.php');
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ── 4. flash() — Mensajes temporales entre páginas ────────────
// Guardar:  flash('error', 'El correo ya existe');
// Leer:     $msg = flash('error');  → devuelve y borra el mensaje
function flash(string $key, string $msg = ''): string {
    if ($msg) {
        // Guardar mensaje en sesión
        $_SESSION['flash'][$key] = $msg;
        return '';
    }
    // Leer y borrar mensaje de sesión
    $out = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $out;
}

// ── 5. isAdminLoggedIn() — Verifica sesión del administrador ──
// USO: if (!isAdminLoggedIn()) redirect('../index.php');
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}
