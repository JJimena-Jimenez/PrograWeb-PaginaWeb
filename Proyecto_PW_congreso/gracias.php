<?php
/* ============================================================
   ARCHIVO: gracias.php
   FUNCIÓN: Página de confirmación después del pago exitoso
   ============================================================
   FLUJO:
   - Recibe control desde pago.php vía $_SESSION['pago_ok']
   - Muestra el resumen del pago: nombre, tipo, monto, orden PayPal
   - Botón para abrir/descargar el recibo generado
   - Al cargar: muestra toast amarillo de Toastify (JS libre)
     que al hacer click abre el recibo en nueva pestaña
   - Destruye $_SESSION['pago_ok'] al leerla (solo se muestra 1 vez)
   ============================================================ */
require_once __DIR__ . '/includes/session.php';

// ── Seguridad: solo accesible después de un pago exitoso ─────
if (empty($_SESSION['pago_ok'])) {
    redirect('index.php');
}

// ── Leer y destruir la sesión (flash — solo se ve 1 vez) ─────
$p = $_SESSION['pago_ok'];
unset($_SESSION['pago_ok']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Registro Exitoso! – Congreso 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <!-- Toastify: JS libre para toast de confirmación -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        /* ── Estilos exclusivos de esta página ─────────────── */
        .gracias-box { max-width:560px; margin:5rem auto; background:#fff; padding:4rem;
                       border-radius:1rem; box-shadow:0 5px 20px rgba(0,0,0,.15); text-align:center; }
        .check { font-size:6rem; }
        .gracias-box h2 { color:var(--secundario); }
        .dato { background:var(--grisClaro); padding:1rem 2rem; border-radius:.5rem;
                margin:.6rem 0; font-size:1.5rem; text-align:left; }
        .dato b { color:var(--secundario); }
        .btn-recibo { display:inline-block; margin-top:2rem; background:var(--primario);
                      color:var(--oscuro); padding:1rem 3rem; font-size:1.6rem;
                      font-weight:bold; border-radius:.5rem; text-decoration:none; }
    </style>
</head>
<body>
<header>
    <h1 class="titulo">Congreso Web <span>Academia & Tecnología 2026</span></h1>
</header>

<div class="gracias-box">
    <div class="check">🎉</div>
    <h2>¡Registro completado!</h2>
    <p>Gracias, <strong><?= htmlspecialchars($p['nombre']) ?></strong>. Tu pago fue procesado con éxito.</p>

    <!-- ── Datos del pago desde $_SESSION['pago_ok'] ──────── -->
    <div class="dato"><b>Tipo:</b> <?= ucfirst($p['tipo']) ?></div>
    <div class="dato"><b>Correo:</b> <?= htmlspecialchars($p['correo']) ?></div>
    <div class="dato"><b>Monto pagado:</b> $<?= number_format($p['monto'], 2) ?> MXN</div>
    <div class="dato"><b>PayPal Order:</b> <?= htmlspecialchars($p['orderID']) ?></div>

    <!-- ── Botón para abrir el recibo generado ──────────────
         El recibo está en uploads/recibos/ como HTML o PDF
         dependiendo de si FPDF está instalado o no -->
    <?php if (!empty($p['recibo'])): ?>
        <a class="btn-recibo btn-recibo" href="<?= htmlspecialchars($p['recibo']) ?>" target="_blank">
            📄 Descargar mi Recibo PDF
        </a>
    <?php endif; ?>

    <p style="margin-top:2rem;font-size:1.3rem;color:#888">
        Recibirás un correo de confirmación en <strong><?= htmlspecialchars($p['correo']) ?></strong>.
    </p>
    <a href="index.php" style="display:block;margin-top:1rem;color:var(--secundario);">← Volver al inicio</a>
</div>

<script>
window.addEventListener('load', function () {
    /* JS LIBRE: Toast amarillo de Toastify al cargar la página
       duration: -1 → no desaparece solo, el usuario lo cierra
       onClick   → abre el recibo en nueva pestaña              */
    Toastify({
        text: '🎉 Pago completado — Click para ver tu recibo',
        duration: -1,
        gravity: 'bottom',
        position: 'right',
        stopOnFocus: true,
        style: {
            background: 'linear-gradient(135deg, #FFC107, #ff8f00)',
            color: '#212121',
            borderRadius: '1rem',
            fontFamily: 'Krub, sans-serif',
            fontSize: '1.5rem',
            fontWeight: '700',
            padding: '1.4rem 2.2rem',
            boxShadow: '0 8px 25px rgba(0,0,0,0.25)',
            cursor: 'pointer'
        },
        onClick: function () {
            <?php if (!empty($p['recibo'])): ?>
            window.open('<?= htmlspecialchars($p['recibo']) ?>', '_blank');
            <?php else: ?>
            document.querySelector('.btn-recibo')?.scrollIntoView({ behavior: 'smooth' });
            <?php endif; ?>
        }
    }).showToast();
});
</script>
</body>
</html>
