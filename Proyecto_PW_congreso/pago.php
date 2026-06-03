<?php
/* ============================================================
   ARCHIVO: pago.php
   FUNCIÓN: Página de pago con botones reales de PayPal
   ============================================================
   FLUJO:
   - Recibe control desde procesar_registro.php vía $_SESSION['registro']
   - Muestra el resumen del registro (nombre, tipo, monto, etc.)
   - Renderiza los botones de PayPal (PayPal + tarjeta) con el SDK JS
   - Al aprobar el pago (onApprove):
       1. Muestra toast verde con Toastify (JS libre)
       2. Envía el orderID a confirmar_pago.php via fetch() (AJAX)
       3. Si confirmar_pago responde {ok:true} → redirige a gracias.php
       4. Si falla → muestra alert de error
   - onCancel → regresa al formulario de registro
   - onError  → muestra el error de PayPal
   ============================================================ */
require_once __DIR__ . '/includes/session.php';

// ── Seguridad: si no hay sesión de registro, regresar al form ──
if (empty($_SESSION['registro'])) {
    redirect('index.php#registro');
}
$r = $_SESSION['registro']; // Datos guardados en procesar_registro.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago – Congreso 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <!-- Toastify: JS libre para notificación de pago completado -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        /* ── Estilos exclusivos de esta página ─────────────── */
        .pago-box { max-width: 520px; margin: 4rem auto; background:#fff; padding:3rem; border-radius:1rem;
                    box-shadow:0 5px 20px rgba(0,0,0,.15); }
        .pago-box h2 { color: var(--secundario); margin-bottom:2rem; }
        .resumen-item { display:flex; justify-content:space-between; padding:.8rem 0;
                        border-bottom:1px solid #eee; font-size:1.5rem; }
        .resumen-total { font-size:2rem; font-weight:bold; color:var(--secundario); }
        #paypal-button-container { margin-top:2rem; }
        .badge { display:inline-block; padding:.3rem .8rem; border-radius:2rem; font-size:1.2rem;
                 font-weight:bold; color:#fff; background:var(--secundario); }
    </style>
</head>
<body>
<header>
    <h1 class="titulo">Congreso Web <span>Academia & Tecnología 2026</span></h1>
</header>

<!-- ── Resumen del registro antes de pagar ─────────────────── -->
<div class="pago-box">
    <h2>💳 Resumen de Pago</h2>

    <!-- Datos del registro desde $_SESSION['registro'] -->
    <div class="resumen-item"><span>Tipo de registro</span>
        <span class="badge"><?= ucfirst($r['tipo']) ?></span></div>
    <div class="resumen-item"><span>Nombre</span><span><?= htmlspecialchars($r['nombre']) ?></span></div>
    <div class="resumen-item"><span>Correo</span><span><?= htmlspecialchars($r['correo']) ?></span></div>
    <div class="resumen-item"><span>Institución</span><span><?= htmlspecialchars($r['institucion']) ?></span></div>
    <div class="resumen-item"><span>Modalidad</span><span><?= ucfirst($r['tipo_asistencia']) ?></span></div>

    <!-- Datos extra solo visibles para ponentes -->
    <?php if ($r['tipo'] === 'ponente'): ?>
    <div class="resumen-item"><span>Trabajo</span><span><?= htmlspecialchars($r['titulo_trabajo']) ?></span></div>
    <div class="resumen-item"><span>Tipo envío</span><span><?= ucfirst($r['tipo_envio']) ?></span></div>
    <?php endif; ?>

    <!-- Monto calculado en procesar_registro.php según tipo y modalidad -->
    <div class="resumen-item resumen-total"><span>Total a pagar</span>
        <span>$<?= number_format($r['monto'], 2) ?> MXN</span></div>

    <!-- Aquí PayPal inyecta sus botones vía SDK JS -->
    <div id="paypal-button-container"></div>
    <p style="text-align:center;margin-top:1rem;font-size:1.3rem;color:#888">
        Pago 100% seguro con PayPal
    </p>
</div>

<!-- ── PayPal JS SDK ──────────────────────────────────────────
     IMPORTANTE: Cambiar client-id por el tuyo de developer.paypal.com
     currency=MXN → cobra en pesos mexicanos
     ──────────────────────────────────────────────────────── -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=MXN"></script>
<script>
paypal.Buttons({
    style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'pay' },

    // ── createOrder: crea la orden con el monto de la sesión ──
    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units: [{
                description: 'Congreso Academia & Tecnología 2026 – <?= ucfirst($r['tipo']) ?>',
                amount: {
                    currency_code: 'MXN',
                    value: '<?= number_format($r['monto'], 2, '.', '') ?>' // Monto sin comas
                }
            }]
        });
    },

    // ── onApprove: se ejecuta cuando el usuario aprueba el pago ──
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {

            // JS LIBRE: Toast verde de Toastify al completar el pago
            Toastify({
                text: '✅ ¡Pago completado! Redirigiendo a tu recibo...',
                duration: 3000,
                gravity: 'bottom',
                position: 'right',
                stopOnFocus: true,
                style: {
                    background: 'linear-gradient(135deg, #00897b, #0097A7)',
                    borderRadius: '1rem',
                    fontFamily: 'Krub, sans-serif',
                    fontSize: '1.5rem',
                    padding: '1.2rem 2rem',
                    boxShadow: '0 6px 20px rgba(0,0,0,0.25)',
                    cursor: 'pointer'
                },
                onClick: function() { window.location.href = 'gracias.php'; }
            }).showToast();

            // AJAX: Enviar orderID a confirmar_pago.php para guardar en BD
            fetch('confirmar_pago.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orderID: data.orderID, status: details.status })
            })
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                    // Esperar que el toast se vea y redirigir
                    setTimeout(() => { window.location.href = 'gracias.php'; }, 2800);
                } else {
                    alert('Hubo un error al confirmar el pago. Contacta al administrador.');
                }
            });
        });
    },

    // ── onCancel: usuario canceló el pago en PayPal ──────────
    onCancel: function() {
        window.location.href = 'index.php?cancelado=1#registro';
    },

    // ── onError: error técnico de PayPal ─────────────────────
    onError: function(err) {
        alert('Error en PayPal: ' + err);
    }

}).render('#paypal-button-container'); // Inyectar botones en el div
</script>
</body>
</html>
