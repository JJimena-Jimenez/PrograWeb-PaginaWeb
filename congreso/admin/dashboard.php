<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$db = getDB();

// ── Estadísticas ───────────────────────────────────────────
$stats = [];
$stats['total_participantes'] = $db->query("SELECT COUNT(*) FROM participantes WHERE paypal_status='completado'")->fetchColumn();
$stats['total_ponentes']      = $db->query("SELECT COUNT(*) FROM ponentes WHERE paypal_status='completado'")->fetchColumn();
$stats['total_pendientes']    = $db->query("SELECT COUNT(*) FROM participantes WHERE paypal_status='pendiente'")->fetchColumn()
                               + $db->query("SELECT COUNT(*) FROM ponentes WHERE paypal_status='pendiente'")->fetchColumn();
$stats['ingresos']            = $db->query("SELECT COALESCE(SUM(monto),0) FROM participantes WHERE paypal_status='completado'")->fetchColumn()
                               + $db->query("SELECT COALESCE(SUM(monto),0) FROM ponentes WHERE paypal_status='completado'")->fetchColumn();

// ── Tablas ─────────────────────────────────────────────────
$participantes = $db->query("SELECT * FROM participantes ORDER BY fecha_registro DESC")->fetchAll();
$ponentes      = $db->query("SELECT * FROM ponentes ORDER BY fecha_registro DESC")->fetchAll();

$seccion = $_GET['s'] ?? 'resumen';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin – Congreso 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --p:#FFC107; --s:#0097A7; --d:#212121; --bg:#f0f4f8; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Krub',sans-serif; background:var(--bg); min-height:100vh; }

        /* Sidebar */
        .sidebar { width:240px; background:var(--d); min-height:100vh; position:fixed;
                   top:0; left:0; padding:2rem 0; }
        .sidebar h2 { color:var(--p); text-align:center; font-size:1.6rem; padding:0 1rem 2rem; }
        .sidebar nav a { display:block; color:#ccc; text-decoration:none; padding:1.2rem 2rem;
                         font-size:1.4rem; transition:background .2s; }
        .sidebar nav a:hover, .sidebar nav a.activo { background:var(--s); color:#fff; }
        .sidebar .logout { display:block; color:#f44336; padding:1.2rem 2rem;
                           text-decoration:none; font-size:1.4rem; margin-top:auto; }

        /* Main */
        .main { margin-left:240px; padding:3rem; }
        .topbar { display:flex; justify-content:space-between; align-items:center;
                  background:#fff; padding:1.5rem 2rem; border-radius:.8rem;
                  box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:2rem; }
        .topbar h1 { font-size:2rem; color:var(--d); }
        .topbar span { color:#777; font-size:1.4rem; }

        /* Cards */
        .cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1.5rem; margin-bottom:3rem; }
        .card { background:#fff; border-radius:.8rem; padding:2rem; text-align:center;
                box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .card .num { font-size:3.5rem; font-weight:bold; color:var(--s); }
        .card .lbl { font-size:1.3rem; color:#777; margin-top:.5rem; }
        .card.ingresos .num { color:#4caf50; }

        /* Tablas */
        .tabla-box { background:#fff; border-radius:.8rem; padding:2rem;
                     box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:2rem; overflow-x:auto; }
        .tabla-box h3 { font-size:1.8rem; color:var(--s); margin-bottom:1.5rem; }
        table { width:100%; border-collapse:collapse; font-size:1.3rem; }
        th { background:var(--s); color:#fff; padding:.8rem 1rem; text-align:left; }
        td { padding:.8rem 1rem; border-bottom:1px solid #eee; }
        tr:hover td { background:#f9f9f9; }
        .badge { padding:.3rem .7rem; border-radius:2rem; font-size:1.1rem; font-weight:bold; color:#fff; }
        .badge-ok      { background:#4caf50; }
        .badge-pend    { background:#ff9800; }
        .badge-cancel  { background:#f44336; }
        .badge-ponen   { background:#9c27b0; }
        .badge-memo    { background:#2196f3; }
        .btn-sm { padding:.3rem .8rem; border-radius:.3rem; font-size:1.1rem;
                  color:#fff; background:var(--s); text-decoration:none; white-space:nowrap; }
        .search { padding:.8rem 1rem; border:1px solid #ddd; border-radius:.5rem;
                  font-size:1.4rem; width:100%; max-width:300px; margin-bottom:1.5rem; font-family:inherit; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>⚙️ Admin Panel<br><small style="font-size:1.1rem;color:#aaa">Congreso 2026</small></h2>
    <nav>
        <a href="?s=resumen"       class="<?= $seccion==='resumen'       ? 'activo' : '' ?>">📊 Resumen</a>
        <a href="?s=participantes" class="<?= $seccion==='participantes' ? 'activo' : '' ?>">🎟️ Participantes</a>
        <a href="?s=ponentes"      class="<?= $seccion==='ponentes'      ? 'activo' : '' ?>">🎤 Ponentes</a>
    </nav>
    <a class="logout" href="logout.php">🚪 Cerrar sesión</a>
</div>

<div class="main">
    <div class="topbar">
        <h1>
            <?php
            $titulos = ['resumen'=>'Dashboard','participantes'=>'Participantes','ponentes'=>'Ponentes'];
            echo $titulos[$seccion] ?? 'Dashboard';
            ?>
        </h1>
        <span>👤 <?= htmlspecialchars($_SESSION['admin_nombre']) ?></span>
    </div>

    <?php if ($seccion === 'resumen'): ?>
    <!-- ── RESUMEN ── -->
    <div class="cards">
        <div class="card">
            <div class="num"><?= $stats['total_participantes'] ?></div>
            <div class="lbl">🎟️ Participantes<br>Pagados</div>
        </div>
        <div class="card">
            <div class="num"><?= $stats['total_ponentes'] ?></div>
            <div class="lbl">🎤 Ponentes<br>Pagados</div>
        </div>
        <div class="card">
            <div class="num"><?= $stats['total_participantes'] + $stats['total_ponentes'] ?></div>
            <div class="lbl">👥 Total<br>Asistentes</div>
        </div>
        <div class="card">
            <div class="num"><?= $stats['total_pendientes'] ?></div>
            <div class="lbl">⏳ Registros<br>Pendientes</div>
        </div>
        <div class="card ingresos">
            <div class="num">$<?= number_format($stats['ingresos'], 0, '.', ',') ?></div>
            <div class="lbl">💰 Ingresos<br>MXN (total)</div>
        </div>
    </div>

    <!-- Últimos registros -->
    <div class="tabla-box">
        <h3>Últimos Participantes</h3>
        <table>
            <thead><tr><th>#</th><th>Nombre</th><th>Correo</th><th>Institución</th><th>Monto</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($participantes, 0, 5) as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['correo']) ?></td>
                    <td><?= htmlspecialchars($p['institucion']) ?></td>
                    <td>$<?= number_format($p['monto'],2) ?></td>
                    <td><span class="badge badge-<?= $p['paypal_status']==='completado' ? 'ok' : ($p['paypal_status']==='pendiente' ? 'pend' : 'cancel') ?>">
                        <?= ucfirst($p['paypal_status']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_registro'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="tabla-box">
        <h3>Últimos Ponentes</h3>
        <table>
            <thead><tr><th>#</th><th>Nombre</th><th>Título</th><th>Tipo</th><th>Monto</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($ponentes, 0, 5) as $po): ?>
                <tr>
                    <td><?= $po['id'] ?></td>
                    <td><?= htmlspecialchars($po['nombre']) ?></td>
                    <td><?= htmlspecialchars($po['titulo_trabajo']) ?></td>
                    <td><span class="badge <?= $po['tipo_envio']==='ponencia' ? 'badge-ponen' : 'badge-memo' ?>">
                        <?= ucfirst($po['tipo_envio']) ?></span></td>
                    <td>$<?= number_format($po['monto'],2) ?></td>
                    <td><span class="badge badge-<?= $po['paypal_status']==='completado' ? 'ok' : ($po['paypal_status']==='pendiente' ? 'pend' : 'cancel') ?>">
                        <?= ucfirst($po['paypal_status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($seccion === 'participantes'): ?>
    <!-- ── PARTICIPANTES ── -->
    <div class="tabla-box">
        <h3>🎟️ Todos los Participantes (<?= count($participantes) ?>)</h3>
        <input class="search" type="text" id="buscar-part" onkeyup="filtrar('tabla-part','buscar-part')"
               placeholder="🔍 Buscar...">
        <table id="tabla-part">
            <thead><tr>
                <th>#</th><th>Nombre</th><th>Correo</th><th>Teléfono</th>
                <th>Institución</th><th>Modalidad</th><th>Monto</th>
                <th>Estado</th><th>Recibo</th><th>Fecha</th>
            </tr></thead>
            <tbody>
            <?php foreach ($participantes as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['correo']) ?></td>
                    <td><?= htmlspecialchars($p['telefono']) ?></td>
                    <td><?= htmlspecialchars($p['institucion']) ?></td>
                    <td><?= ucfirst($p['tipo_asistencia']) ?></td>
                    <td>$<?= number_format($p['monto'],2) ?></td>
                    <td><span class="badge badge-<?= $p['paypal_status']==='completado' ? 'ok' : ($p['paypal_status']==='pendiente' ? 'pend' : 'cancel') ?>">
                        <?= ucfirst($p['paypal_status']) ?></span></td>
                    <td>
                        <?php if ($p['recibo_pdf']): ?>
                            <a class="btn-sm" href="../<?= htmlspecialchars($p['recibo_pdf']) ?>" target="_blank">PDF</a>
                        <?php else: ?> – <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($seccion === 'ponentes'): ?>
    <!-- ── PONENTES ── -->
    <div class="tabla-box">
        <h3>🎤 Todos los Ponentes (<?= count($ponentes) ?>)</h3>
        <input class="search" type="text" id="buscar-pon" onkeyup="filtrar('tabla-pon','buscar-pon')"
               placeholder="🔍 Buscar...">
        <table id="tabla-pon">
            <thead><tr>
                <th>#</th><th>Nombre</th><th>Correo</th><th>Teléfono</th>
                <th>Institución</th><th>Título del Trabajo</th>
                <th>Tipo</th><th>Archivo</th><th>Modalidad</th>
                <th>Monto</th><th>Estado</th><th>Recibo</th><th>Fecha</th>
            </tr></thead>
            <tbody>
            <?php foreach ($ponentes as $po): ?>
                <tr>
                    <td><?= $po['id'] ?></td>
                    <td><?= htmlspecialchars($po['nombre']) ?></td>
                    <td><?= htmlspecialchars($po['correo']) ?></td>
                    <td><?= htmlspecialchars($po['telefono']) ?></td>
                    <td><?= htmlspecialchars($po['institucion']) ?></td>
                    <td><?= htmlspecialchars($po['titulo_trabajo']) ?></td>
                    <td><span class="badge <?= $po['tipo_envio']==='ponencia' ? 'badge-ponen' : 'badge-memo' ?>">
                        <?= ucfirst($po['tipo_envio']) ?></span></td>
                    <td>
                        <?php if ($po['archivo_ruta']): ?>
                            <a class="btn-sm" href="../<?= htmlspecialchars($po['archivo_ruta']) ?>" target="_blank">
                                📎 Ver
                            </a>
                        <?php else: ?> – <?php endif; ?>
                    </td>
                    <td><?= ucfirst($po['tipo_asistencia']) ?></td>
                    <td>$<?= number_format($po['monto'],2) ?></td>
                    <td><span class="badge badge-<?= $po['paypal_status']==='completado' ? 'ok' : ($po['paypal_status']==='pendiente' ? 'pend' : 'cancel') ?>">
                        <?= ucfirst($po['paypal_status']) ?></span></td>
                    <td>
                        <?php if ($po['recibo_pdf']): ?>
                            <a class="btn-sm" href="../<?= htmlspecialchars($po['recibo_pdf']) ?>" target="_blank">PDF</a>
                        <?php else: ?> – <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($po['fecha_registro'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function filtrar(tablaId, inputId) {
    const val = document.getElementById(inputId).value.toLowerCase();
    const rows = document.querySelectorAll('#' + tablaId + ' tbody tr');
    rows.forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
}
</script>
</body>
</html>
