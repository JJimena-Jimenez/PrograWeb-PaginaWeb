<?php
/* ============================================================
   ARCHIVO: logout_usuario.php
   FUNCIÓN: Cierre de sesión del usuario del portal público
   ============================================================
   - Destruye las variables de sesión del usuario logueado
   - NO destruye la sesión completa (para no afectar al admin)
   - Redirige al inicio (index.php)
   
   VARIABLES QUE ELIMINA:
   - $_SESSION['usuario_id']     → ID del usuario
   - $_SESSION['usuario_nombre'] → Nombre mostrado en el nav
   ============================================================ */
require_once __DIR__ . '/includes/session.php';

// Eliminar solo las variables del usuario (no del admin ni registro)
unset($_SESSION['usuario_id'], $_SESSION['usuario_nombre']);
redirect('index.php');
