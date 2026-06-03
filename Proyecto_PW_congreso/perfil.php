<?php
/* ============================================================
   ARCHIVO: perfil.php
   FUNCIÓN: Perfil del usuario logueado — historial de actividad
   ============================================================
   ACCESO: Solo para usuarios logueados ($_SESSION['usuario_id'])
   Si no hay sesión → redirige a index.php

   SECCIONES:
   1. Tab "Ponencias a las que asistí":
      - Consulta tabla 'participantes' filtrada por correo del usuario
      - Si la columna ponencia_id existe → hace JOIN con 'ponencias'
        para mostrar título, área, sala, ponente, etc.
      - Si ponencia_id no existe aún en la BD → muestra datos básicos
        (compatibilidad con BDs que no han corrido el ALTER TABLE)

   2. Tab "Ponencias que presenté":
      - Consulta tabla 'ponentes' filtrada por correo del usuario
      - Muestra: título, tipo (ponencia/memoria), modalidad,
        estado del pago y link al recibo PDF

   NAV: Muestra dropdown con nombre del usuario (mismo que index.php)
   ============================================================ */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

if (empty($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$uid  = (int) $_SESSION['usuario_id'];
$db   = getDB();

// ── Datos del usuario logueado ────────────────────────────────
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$usuario = $stmt->fetch();

// ── Compatibilidad: verificar si ponencia_id existe en la BD ──
// Si no se corrió el ALTER TABLE aún, usa query sin JOIN
$colCheck = $db->query("SHOW COLUMNS FROM participantes LIKE 'ponencia_id'")->fetch();
$tienePonenciaId = !empty($colCheck);

// Historial de ponencias asistidas
if ($tienePonenciaId) {
    $asistencias = $db->prepare("
        SELECT p.nombre AS nombre_usuario, p.fecha_registro, p.tipo_asistencia,
               pn.titulo AS ponencia_titulo, pn.area, pn.fecha AS ponencia_fecha,
               pn.sala, pn.modalidad, pn.ponente
        FROM participantes p
        LEFT JOIN ponencias pn ON p.ponencia_id = pn.id
        WHERE p.correo = ?
        ORDER BY p.fecha_registro DESC
    ");
} else {
    $asistencias = $db->prepare("
        SELECT nombre AS nombre_usuario, fecha_registro, tipo_asistencia,
               NULL AS ponencia_titulo, NULL AS area, NULL AS ponencia_fecha,
               NULL AS sala, NULL AS modalidad, NULL AS ponente
        FROM participantes
        WHERE correo = ?
        ORDER BY fecha_registro DESC
    ");
}
$asistencias->execute([$usuario['correo']]);
$historial_asistencias = $asistencias->fetchAll();

// Historial de ponencias presentadas (ponente)
$presentadas = $db->prepare("
    SELECT po.titulo_trabajo, po.tipo_envio, po.tipo_asistencia,
           po.fecha_registro, po.paypal_status, po.recibo_pdf
    FROM ponentes po
    WHERE po.correo = ?
    ORDER BY po.fecha_registro DESC
");
$presentadas->execute([$usuario['correo']]);
$historial_ponencias = $presentadas->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil – Congreso 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .perfil-wrap   { max-width: 860px; margin: 3rem auto; padding: 0 2rem 4rem; }
        .perfil-header { background: var(--secundario); color: #fff; border-radius: 1rem 1rem 0 0;
                         padding: 3rem 3rem 2rem; display: flex; align-items: center; gap: 2rem; }
        .avatar-circle { width: 7rem; height: 7rem; border-radius: 50%; background: var(--primario);
                         color: var(--oscuro); font-size: 3rem; display: flex;
                         align-items: center; justify-content: center; font-weight: 700;
                         flex-shrink: 0; }
        .perfil-info h2 { color: #fff; font-size: 2.4rem; margin: 0 0 .4rem; text-align:left; }
        .perfil-info p  { font-size: 1.4rem; opacity: .85; margin: 0; }
        .perfil-body   { background: #fff; border-radius: 0 0 1rem 1rem;
                         box-shadow: 0 5px 20px rgba(0,0,0,.1); padding: 3rem; }
        .perfil-tabs   { display: flex; gap: 1rem; margin-bottom: 2.5rem; flex-wrap: wrap; }
        .perfil-tab    { padding: .8rem 2rem; border-radius: 2rem; border: 2px solid var(--secundario);
                         background: transparent; color: var(--secundario); font-family: inherit;
                         font-size: 1.5rem; font-weight: 700; cursor: pointer; transition: all .2s; }
        .perfil-tab.activo { background: var(--secundario); color: #fff; }
        .tab-panel     { display: none; }
        .tab-panel.activo { display: block; }

        /* Tarjeta historial */
        .historial-card { border: 1px solid #e0e0e0; border-radius: .8rem; padding: 1.8rem 2rem;
                          margin-bottom: 1.2rem; background: #fafafa;
                          border-left: 5px solid var(--secundario); }
        .historial-card h4 { font-size: 1.7rem; color: var(--oscuro); margin: 0 0 .8rem; }
        .historial-meta    { display: flex; flex-wrap: wrap; gap: .8rem; }
        .hm-badge { font-size: 1.2rem; padding: .3rem .9rem; border-radius: 2rem;
                    background: var(--grisClaro); color: var(--oscuro); }
        .hm-badge.verde  { background: #e8f5e9; color: #2e7d32; }
        .hm-badge.azul   { background: #e3f2fd; color: #1565c0; }
        .hm-badge.amarillo { background: #fff8e1; color: #e65100; }
        .empty-state { text-align: center; padding: 3rem; color: var(--gris); font-size: 1.5rem; }
        .empty-state span { font-size: 4rem; display: block; margin-bottom: 1rem; }

        .nav-back { display: inline-block; margin-bottom: 2rem; color: var(--secundario);
                    font-size: 1.5rem; text-decoration: none; font-weight: 700; }
        .nav-back:hover { text-decoration: underline; }
    </style>
</head>
<body>
<header>
    <h1 class="titulo">Congreso Web <span>Academia & Tecnología 2026</span></h1>
</header>
<div class="nav-bg">
    <nav class="navegacion-principal contenedor">
        <a href="index.php#inicio">Inicio</a>
        <a href="index.php#programa">Programa</a>
        <a href="index.php#ponencias">Ponencias</a>
        <a href="index.php#memorias">Memorias</a>
        <a href="index.php#registro">Registro</a>
        <div class="nav-usuario">
            <div class="nav-dropdown-wrap">
                <button class="nav-dropdown-btn" onclick="toggleDropdown(event)">
                    <span class="nav-avatar-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                        </svg>
                    </span>
                    <span class="nav-saludo">Hola, <?= htmlspecialchars(explode(' ', $usuario['nombre'])[0]) ?>!</span>
                    <span class="nav-chevron">▾</span>
                </button>
                <div class="nav-dropdown-menu" id="nav-dropdown-menu">
                    <a href="perfil.php">👤 Mi perfil</a>
                    <a href="perfil.php">🎟 Mis asistencias</a>
                    <a href="perfil.php">🎤 Mis ponencias</a>
                    <div class="nav-dropdown-sep"></div>
                    <a href="logout_usuario.php" class="nav-dropdown-salir">🚪 Cerrar sesión</a>
                </div>
            </div>
        </div>
    </nav>
</div>

<div class="perfil-wrap">
    <a href="index.php" class="nav-back">← Volver al inicio</a>

    <div class="perfil-header">
        <div class="avatar-circle"><?= mb_strtoupper(mb_substr($usuario['nombre'], 0, 1)) ?></div>
        <div class="perfil-info">
            <h2><?= htmlspecialchars($usuario['nombre']) ?></h2>
            <p>📧 <?= htmlspecialchars($usuario['correo']) ?></p>
            <p>🗓 Miembro desde <?= date('d/m/Y', strtotime($usuario['creado_en'])) ?></p>
        </div>
    </div>

    <div class="perfil-body">
        <div class="perfil-tabs">
            <button class="perfil-tab activo" onclick="verTab('asistencias', this)">🎟 Ponencias a las que asistí</button>
            <button class="perfil-tab"        onclick="verTab('ponencias',   this)">🎤 Ponencias que presenté</button>
        </div>

        <!-- TAB: Asistencias -->
        <div id="tab-asistencias" class="tab-panel activo">
            <?php if (empty($historial_asistencias)): ?>
                <div class="empty-state">
                    <span>📭</span>
                    Todavía no te has registrado para ninguna ponencia.<br>
                    <a href="index.php#registro" style="color:var(--secundario);font-weight:700;">¡Regístrate aquí!</a>
                </div>
            <?php else: ?>
                <?php foreach ($historial_asistencias as $a): ?>
                <div class="historial-card">
                    <h4><?= htmlspecialchars($a['ponencia_titulo'] ?? 'Registro de asistencia') ?></h4>
                    <div class="historial-meta">
                        <?php if (!empty($a['ponencia_fecha'])): ?>
                        <span class="hm-badge azul">📅 <?= htmlspecialchars($a['ponencia_fecha']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($a['sala'])): ?>
                        <span class="hm-badge">📍 <?= htmlspecialchars($a['sala']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($a['modalidad'])): ?>
                        <span class="hm-badge">🔗 <?= htmlspecialchars($a['modalidad']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($a['area'])): ?>
                        <span class="hm-badge amarillo">🏷 <?= htmlspecialchars($a['area']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($a['ponente'])): ?>
                        <span class="hm-badge verde">👤 <?= htmlspecialchars($a['ponente']) ?></span>
                        <?php endif; ?>
                        <span class="hm-badge">Modalidad: <?= ucfirst($a['tipo_asistencia']) ?></span>
                        <span class="hm-badge">Registrado: <?= date('d/m/Y', strtotime($a['fecha_registro'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB: Ponencias presentadas -->
        <div id="tab-ponencias" class="tab-panel">
            <?php if (empty($historial_ponencias)): ?>
                <div class="empty-state">
                    <span>🎤</span>
                    No tienes ponencias registradas como ponente.<br>
                    <a href="index.php#registro" style="color:var(--secundario);font-weight:700;">¡Registra tu ponencia!</a>
                </div>
            <?php else: ?>
                <?php foreach ($historial_ponencias as $p): ?>
                <div class="historial-card" style="border-left-color:var(--primario)">
                    <h4><?= htmlspecialchars($p['titulo_trabajo']) ?></h4>
                    <div class="historial-meta">
                        <span class="hm-badge azul">📋 <?= ucfirst($p['tipo_envio']) ?></span>
                        <span class="hm-badge">🪑 <?= ucfirst($p['tipo_asistencia']) ?></span>
                        <span class="hm-badge verde">✅ <?= ucfirst($p['paypal_status']) ?></span>
                        <span class="hm-badge">Fecha: <?= date('d/m/Y', strtotime($p['fecha_registro'])) ?></span>
                        <?php if (!empty($p['recibo_pdf'])): ?>
                            <a href="<?= htmlspecialchars($p['recibo_pdf']) ?>" target="_blank"
                               class="hm-badge amarillo" style="text-decoration:none">📄 Ver recibo</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="footer">
    <p>Todos los derechos reservados. Congreso Académico 2026.</p>
</footer>

<script>
function verTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('activo'));
    document.querySelectorAll('.perfil-tab').forEach(b => b.classList.remove('activo'));
    document.getElementById('tab-' + id).classList.add('activo');
    btn.classList.add('activo');
}
function toggleDropdown(e) {
    e.stopPropagation();
    e.preventDefault();
    const btn  = e.currentTarget;
    const menu = document.getElementById('nav-dropdown-menu');
    if (!menu) return;
    const estaAbierto = menu.classList.contains('abierto');
    document.querySelectorAll('.nav-dropdown-menu').forEach(m => m.classList.remove('abierto'));
    if (!estaAbierto) {
        const rect = btn.getBoundingClientRect();
        menu.style.top   = (rect.bottom + 6) + 'px';
        menu.style.right = (window.innerWidth - rect.right) + 'px';
        menu.style.left  = 'auto';
        menu.classList.add('abierto');
    }
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-dropdown-wrap')) {
        document.querySelectorAll('.nav-dropdown-menu').forEach(m => m.classList.remove('abierto'));
    }
});
</script>
</body>
</html>
