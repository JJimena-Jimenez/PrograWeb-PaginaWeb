<?php
/* ============================================================
   ARCHIVO: auth_usuario.php
   FUNCIÓN: Login y registro de usuarios del portal público
   ============================================================
   Acciones disponibles (via $_POST['accion']):
   
   'login':
   - Recibe correo y contraseña del modal de login en index.php
   - Busca el usuario en la tabla 'usuarios'
   - Verifica la contraseña con password_verify() (bcrypt)
   - Si es correcto: guarda $_SESSION['usuario_id'] y ['usuario_nombre']
   - Redirige a perfil.php
   - Si falla: flash('login_error') y regresa a index.php

   'register':
   - Recibe nombre, correo y contraseña del modal de registro
   - Valida que la contraseña tenga mínimo 6 caracteres
   - Verifica que el correo no esté ya registrado (SELECT)
   - Guarda la contraseña con password_hash() (bcrypt, nunca en texto plano)
   - Inserta en tabla 'usuarios' y crea sesión automáticamente
   - Redirige a perfil.php
   - Si falla: flash('register_error') y regresa a index.php
   ============================================================ */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

$accion = sanitize($_POST['accion'] ?? '');

// ── ACCIÓN: LOGIN ─────────────────────────────────────────────
if ($accion === 'login') {
    $correo   = trim($_POST['correo']   ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validación básica server-side (la validación JS ya corrió antes)
    if (!$correo || !$password) {
        flash('login_error', 'Completa todos los campos.');
        redirect('index.php#registro');
    }

    $db   = getDB();
    // Buscar usuario por correo
    $stmt = $db->prepare("SELECT id, nombre, password_hash FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    // Verificar contraseña con bcrypt (password_verify compara con el hash guardado)
    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('login_error', 'Correo o contraseña incorrectos.');
        redirect('index.php');
    }

    // ── Crear sesión del usuario ──────────────────────────────
    $_SESSION['usuario_id']     = $user['id'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    redirect('perfil.php');
}

// ── ACCIÓN: REGISTRO ──────────────────────────────────────────
if ($accion === 'register') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $correo   = trim($_POST['correo']   ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validaciones server-side
    if (!$nombre || !$correo || !$password) {
        flash('register_error', 'Completa todos los campos.');
        redirect('index.php');
    }
    if (strlen($password) < 6) {
        flash('register_error', 'La contraseña debe tener al menos 6 caracteres.');
        redirect('index.php');
    }

    $db = getDB();

    // Verificar que el correo no esté ya registrado
    $check = $db->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $check->execute([$correo]);
    if ($check->fetch()) {
        flash('register_error', 'Ese correo ya está registrado. Inicia sesión.');
        redirect('index.php');
    }

    // ── Guardar contraseña con hash bcrypt (nunca en texto plano) ──
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins  = $db->prepare("INSERT INTO usuarios (nombre, correo, password_hash) VALUES (?,?,?)");
    $ins->execute([$nombre, $correo, $hash]);

    // ── Crear sesión automáticamente al registrarse ───────────
    $uid = $db->lastInsertId();
    $_SESSION['usuario_id']     = $uid;
    $_SESSION['usuario_nombre'] = $nombre;
    redirect('perfil.php');
}

// ── Si llegan sin acción válida, regresar al inicio ───────────
redirect('index.php');
