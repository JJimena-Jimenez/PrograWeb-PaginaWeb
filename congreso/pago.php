<?php
require_once __DIR__ . '/includes/session.php';

if (empty($_SESSION['registro'])) {
    redirect('index.php#registro');
}
$r = $_SESSION['registro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago – Congreso 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
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

<div class="pago-box">
    <h2>💳 Resumen de Pago</h2>

    <div class="resumen-item"><span>Tipo de registro</span>
        <span class="badge"><?= ucfirst($r['tipo']) ?></span></div>
    <div class="resumen-item"><span>Nombre</span><span><?= htmlspecialchars($r['nombre']) ?></span></div>
    <div class="resumen-item"><span>Correo</span><span><?= htmlspecialchars($r['correo']) ?></span></div>
    <div class="resumen-item"><span>Institución</span><span><?= htmlspecialchars($r['institucion']) ?></span></div>
    <div class="resumen-item"><span>Modalidad</span><span><?= ucfirst($r['tipo_asistencia']) ?></span></div>
    <?php if ($r['tipo'] === 'ponente'): ?>
    <div class="resumen-item"><span>Trabajo</span><span><?= htmlspecialchars($r['titulo_trabajo']) ?></span></div>
    <div class="resumen-item"><span>Tipo envío</span><span><?= ucfirst($r['tipo_envio']) ?></span></div>
    <?php endif; ?>
    <div class="resumen-item resumen-total"><span>Total a pagar</span>
        <span>$<?= number_format($r['monto'], 2) ?> MXN</span></div>

    <div id="paypal-button-container"></div>
    <p style="text-align:center;margin-top:1rem;font-size:1.3rem;color:#888">
        Pago 100% seguro con PayPal
    </p>
</div>

<!-- PayPal JS SDK (Sandbox – cambia el client-id por el tuyo) -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=MXN"></script>
<script>
paypal.Buttons({
    style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'pay' },

    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units: [{
                description: 'Congreso Academia & Tecnología 2026 – <?= ucfirst($r['tipo']) ?>',
                amount: {
                    currency_code: 'MXN',
                    value: '<?= number_format($r['monto'], 2, '.', '') ?>'
                }
            }]
        });
    },

    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
            // Enviar order ID al servidor para confirmar pago
            fetch('confirmar_pago.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orderID: data.orderID, status: details.status })
            })
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                    window.location.href = 'gracias.php';
                } else {
                    alert('Hubo un error al confirmar el pago. Contacta al administrador.');
                }
            });
        });
    },

    onCancel: function() {
        window.location.href = 'index.php?cancelado=1#registro';
    },

    onError: function(err) {
        alert('Error en PayPal: ' + err);
    }
}).render('#paypal-button-container');
</script>
</body>
</html>
