<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

if (isAdminLoggedIn()) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = sanitize($_POST['usuario']  ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getDB()->prepare("SELECT id, nombre, password_hash FROM admins WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']     = $admin['id'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        redirect('dashboard.php');
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador – Congreso 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --primario:#FFC107; --secundario:#0097A7; --oscuro:#212121; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Krub',sans-serif; background:linear-gradient(135deg,#0097A7,#212121);
               min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .login-box { background:#fff; padding:4rem; border-radius:1rem;
                     box-shadow:0 10px 40px rgba(0,0,0,.3); width:100%; max-width:420px; }
        .login-box h2 { text-align:center; color:var(--secundario); font-size:2.4rem; margin-bottom:.5rem; }
        .login-box p  { text-align:center; color:#777; margin-bottom:2rem; }
        label { display:block; font-weight:bold; margin-bottom:.4rem; color:var(--oscuro); }
        input { width:100%; padding:1rem; border:1px solid #ddd; border-radius:.5rem;
                font-size:1.5rem; margin-bottom:1.5rem; font-family:inherit; }
        input:focus { outline:none; border-color:var(--secundario); }
        .btn { width:100%; background:var(--secundario); color:#fff; border:none;
               padding:1.2rem; font-size:1.6rem; font-weight:bold; border-radius:.5rem;
               cursor:pointer; transition:background .2s; }
        .btn:hover { background:#00838f; }
        .error { background:#fce4ec; color:#c62828; padding:1rem; border-radius:.5rem;
                 margin-bottom:1.5rem; text-align:center; font-size:1.4rem; }
        .back { display:block; text-align:center; margin-top:1.5rem; color:var(--secundario);
                text-decoration:none; font-size:1.3rem; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>🔐 Administrador</h2>
    <p>Panel del Congreso 2026</p>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Usuario</label>
        <input type="text" name="usuario" required autofocus placeholder="admin">
        <label>Contraseña</label>
        <input type="password" name="password" required placeholder="••••••••">
        <button type="submit" class="btn">Ingresar →</button>
    </form>
    <a class="back" href="../index.php">← Volver al sitio</a>
</div>
</body>
</html>
