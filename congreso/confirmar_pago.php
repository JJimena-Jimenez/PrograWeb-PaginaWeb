<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['registro'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesión inválida']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$orderID = sanitize($input['orderID'] ?? '');
$status  = sanitize($input['status']  ?? '');

if (!$orderID) {
    echo json_encode(['ok' => false, 'msg' => 'Order ID requerido']);
    exit;
}

// ── Opcional: verificar con la API de PayPal ──────────────
// Aquí puedes agregar una llamada a la API de PayPal para validar
// el pago server-side. Por seguridad se recomienda en producción.

$r  = $_SESSION['registro'];
$db = getDB();

try {
    $db->beginTransaction();

    if ($r['tipo'] === 'participante') {
        $stmt = $db->prepare("
            INSERT INTO participantes
                (nombre, correo, telefono, institucion, tipo_asistencia, monto, paypal_order_id, paypal_status)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $r['nombre'], $r['correo'], $r['telefono'], $r['institucion'],
            $r['tipo_asistencia'], $r['monto'], $orderID, 'completado'
        ]);
        $insertId = $db->lastInsertId();

        // Generar PDF y guardar ruta
        $pdfPath = generarReciboPDF($insertId, $r, 'participante');
        $db->prepare("UPDATE participantes SET recibo_pdf=? WHERE id=?")
           ->execute([$pdfPath, $insertId]);

    } else {
        $stmt = $db->prepare("
            INSERT INTO ponentes
                (nombre, correo, telefono, institucion, tipo_asistencia,
                 titulo_trabajo, tipo_envio, archivo_nombre, archivo_ruta,
                 monto, paypal_order_id, paypal_status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $r['nombre'], $r['correo'], $r['telefono'], $r['institucion'],
            $r['tipo_asistencia'], $r['titulo_trabajo'], $r['tipo_envio'],
            $r['archivo_nombre'], $r['archivo_ruta'],
            $r['monto'], $orderID, 'completado'
        ]);
        $insertId = $db->lastInsertId();

        $pdfPath = generarReciboPDF($insertId, $r, 'ponente');
        $db->prepare("UPDATE ponentes SET recibo_pdf=? WHERE id=?")
           ->execute([$pdfPath, $insertId]);
    }

    $db->commit();

    // Guardar datos en sesión para la página de gracias
    $_SESSION['pago_ok'] = [
        'nombre'    => $r['nombre'],
        'correo'    => $r['correo'],
        'tipo'      => $r['tipo'],
        'monto'     => $r['monto'],
        'orderID'   => $orderID,
        'recibo'    => $pdfPath,
        'insertId'  => $insertId,
    ];
    unset($_SESSION['registro']);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Error pago congreso: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Error interno']);
}

// ── Generador de PDF de recibo ─────────────────────────────
function generarReciboPDF(int $id, array $r, string $tipo): string
{
    $dir = __DIR__ . '/uploads/recibos/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = "recibo_{$tipo}_{$id}_" . date('Ymd') . ".pdf";
    $filepath = $dir . $filename;

    // ── HTML → PDF con mPDF (si está disponible) ──────────
    // Si no tienes mPDF instalado, se genera el PDF manualmente.
    if (class_exists('Mpdf\Mpdf')) {
        generarConMpdf($filepath, $id, $r, $tipo);
    } else {
        // Fallback: PDF manual con solo cabeceras y contenido básico
        generarPDFManual($filepath, $id, $r, $tipo);
    }

    return 'uploads/recibos/' . $filename;
}

function generarPDFManual(string $filepath, int $id, array $r, string $tipo): void
{
    $fecha  = date('d/m/Y H:i');
    $folio  = strtoupper($tipo[0]) . str_pad($id, 5, '0', STR_PAD_LEFT);
    $nombre = $r['nombre'];
    $correo = $r['correo'];
    $inst   = $r['institucion'];
    $monto  = number_format($r['monto'], 2);
    $modal  = ucfirst($r['tipo_asistencia']);
    $tipoTxt= ucfirst($tipo);

    // PDF básico generado a mano (sin librería)
    $w = 595; $h = 842; // A4 en puntos
    $content  = "%PDF-1.4\n";
    $content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

    $pageContent = "BT\n/F1 20 Tf\n50 780 Td\n(RECIBO DE PAGO - CONGRESO 2026) Tj\n";
    $pageContent .= "/F1 12 Tf\n0 -40 Td\n(Folio: $folio) Tj\n";
    $pageContent .= "0 -20 Td\n(Fecha: $fecha) Tj\n";
    $pageContent .= "0 -30 Td\n(Nombre: $nombre) Tj\n";
    $pageContent .= "0 -20 Td\n(Correo: $correo) Tj\n";
    $pageContent .= "0 -20 Td\n(Institucion: $inst) Tj\n";
    $pageContent .= "0 -20 Td\n(Tipo: $tipoTxt - $modal) Tj\n";
    $pageContent .= "0 -20 Td\n(Monto Pagado: \$$monto MXN) Tj\n";
    $pageContent .= "0 -20 Td\n(Estado: PAGADO) Tj\n";
    $pageContent .= "ET\n";

    $streamLen = strlen($pageContent);
    $content .= "4 0 obj\n<< /Length $streamLen >>\nstream\n$pageContent\nendstream\nendobj\n";
    $content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $w $h] ";
    $content .= "/Contents 4 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >>\nendobj\n";
    $content .= "xref\n0 5\n0000000000 65535 f \n";
    $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n9\n%%EOF\n";

    file_put_contents($filepath, $content);
}

function generarConMpdf(string $filepath, int $id, array $r, string $tipo): void
{
    $fecha  = date('d/m/Y H:i');
    $folio  = strtoupper($tipo[0]) . str_pad($id, 5, '0', STR_PAD_LEFT);
    $monto  = number_format($r['monto'], 2);
    $modal  = ucfirst($r['tipo_asistencia']);
    $tipoTxt= ucfirst($tipo);
    $extra  = '';
    if ($tipo === 'ponente') {
        $extra = "<tr><td><b>Trabajo:</b></td><td>" . htmlspecialchars($r['titulo_trabajo']) . "</td></tr>
                  <tr><td><b>Tipo envío:</b></td><td>" . ucfirst($r['tipo_envio']) . "</td></tr>";
    }

    $html = "
    <style>
        body { font-family: sans-serif; }
        .header { background:#0097A7; color:#fff; padding:20px; text-align:center; }
        .folio  { color:#FFC107; font-size:14pt; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        td { padding:10px; border-bottom:1px solid #ddd; }
        .monto { font-size:20pt; color:#0097A7; font-weight:bold; text-align:center; margin:20px 0; }
        .sello { background:#e8f5e9; border:2px solid #4caf50; padding:10px; text-align:center;
                 color:#2e7d32; font-weight:bold; font-size:14pt; margin-top:20px; border-radius:6px; }
    </style>
    <div class='header'>
        <h2>RECIBO DE PAGO</h2>
        <p>Congreso Academia &amp; Tecnología 2026</p>
        <p class='folio'>Folio: $folio</p>
    </div>
    <table>
        <tr><td><b>Fecha:</b></td><td>$fecha</td></tr>
        <tr><td><b>Nombre:</b></td><td>" . htmlspecialchars($r['nombre']) . "</td></tr>
        <tr><td><b>Correo:</b></td><td>" . htmlspecialchars($r['correo']) . "</td></tr>
        <tr><td><b>Teléfono:</b></td><td>" . htmlspecialchars($r['telefono']) . "</td></tr>
        <tr><td><b>Institución:</b></td><td>" . htmlspecialchars($r['institucion']) . "</td></tr>
        <tr><td><b>Tipo registro:</b></td><td>$tipoTxt</td></tr>
        <tr><td><b>Modalidad:</b></td><td>$modal</td></tr>
        $extra
    </table>
    <div class='monto'>Total pagado: \$$monto MXN</div>
    <div class='sello'>✔ PAGO COMPLETADO VÍA PAYPAL</div>
    <p style='font-size:9pt;color:#999;text-align:center;margin-top:30px;'>
        Conserva este recibo como comprobante de inscripción.
    </p>";

    $mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_top' => 0, 'margin_bottom' => 15]);
    $mpdf->WriteHTML($html);
    $mpdf->Output($filepath, 'F');
}
