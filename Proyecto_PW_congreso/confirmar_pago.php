<?php
/* ============================================================
   ARCHIVO: confirmar_pago.php
   FUNCIÓN: Confirmar pago PayPal, guardar en BD y generar recibo
   ============================================================
   FLUJO:
   - Es llamado por FETCH (AJAX) desde pago.php cuando PayPal
     dispara el evento onApprove (pago aprobado)
   - Recibe el orderID de PayPal en el body JSON
   - Lee los datos de $_SESSION['registro'] (guardados en procesar_registro.php)
   - Inserta el registro en la tabla correspondiente (participantes o ponentes)
   - Genera el recibo en uploads/recibos/ (HTML siempre, PDF si FPDF está instalado)
   - Guarda la ruta del recibo en la BD
   - Crea $_SESSION['pago_ok'] con los datos para mostrar en gracias.php
   - Destruye $_SESSION['registro'] (ya no se necesita)
   - Devuelve JSON: {"ok": true} o {"ok": false, "msg": "..."}

   RESPUESTA JSON: {"ok": true} → pago.php redirige a gracias.php
                   {"ok": false} → pago.php muestra alerta de error

   RECIBO:
   - Si vendor/fpdf/fpdf.php existe → genera PDF profesional con FPDF
   - Si no existe → genera recibo HTML imprimible (funciona siempre)
   - Ambos se guardan en uploads/recibos/
   ============================================================ */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

// ── Esta página solo responde JSON ───────────────────────────
header('Content-Type: application/json');

// ── Verificar que viene de un POST con sesión válida ─────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['registro'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesión inválida']);
    exit;
}

// ── Leer el orderID enviado por PayPal desde pago.php ────────
$input   = json_decode(file_get_contents('php://input'), true);
$orderID = sanitize($input['orderID'] ?? '');

if (!$orderID) {
    echo json_encode(['ok' => false, 'msg' => 'Order ID requerido']);
    exit;
}

// ── Recuperar datos guardados en procesar_registro.php ───────
$r  = $_SESSION['registro'];
$db = getDB();

try {
    $db->beginTransaction(); // Si algo falla, se hace rollback completo

    // ── INSERT según tipo de registro ─────────────────────────
    if ($r['tipo'] === 'participante') {

        // Intenta con columnas nuevas (ponencia_id, usuario_id)
        // Si la BD aún no tiene esas columnas, usa el INSERT básico
        try {
            $stmt = $db->prepare("
                INSERT INTO participantes
                    (nombre, correo, telefono, institucion, tipo_asistencia,
                     monto, paypal_order_id, paypal_status, ponencia_id, usuario_id)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $r['nombre'], $r['correo'], $r['telefono'], $r['institucion'],
                $r['tipo_asistencia'], $r['monto'], $orderID, 'completado',
                $r['ponencia_id'] ?: null,   // ID de la ponencia elegida (puede ser null)
                $r['usuario_id']  ?: null    // ID del usuario logueado (puede ser null)
            ]);
        } catch (PDOException $e2) {
            // Fallback: columnas ponencia_id/usuario_id no existen aún en la BD
            $stmt = $db->prepare("
                INSERT INTO participantes
                    (nombre, correo, telefono, institucion, tipo_asistencia,
                     monto, paypal_order_id, paypal_status)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $r['nombre'], $r['correo'], $r['telefono'], $r['institucion'],
                $r['tipo_asistencia'], $r['monto'], $orderID, 'completado'
            ]);
        }
        $insertId = $db->lastInsertId(); // ID del registro recién creado

    } else {

        // Intenta con todas las columnas nuevas del ponente
        try {
            $stmt = $db->prepare("
                INSERT INTO ponentes
                    (nombre, correo, telefono, institucion, tipo_asistencia,
                     titulo_trabajo, tipo_envio, archivo_nombre, archivo_ruta,
                     monto, paypal_order_id, paypal_status, usuario_id,
                     resumen, area_tematica, fecha_ponencia, hora_ponencia)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $r['nombre'], $r['correo'], $r['telefono'], $r['institucion'],
                $r['tipo_asistencia'], $r['titulo_trabajo'], $r['tipo_envio'],
                $r['archivo_nombre'], $r['archivo_ruta'],
                $r['monto'], $orderID, 'completado', $r['usuario_id'] ?: null,
                $r['resumen']        ?? null,  // Abstract del trabajo
                $r['area_tematica']  ?? null,  // Área temática seleccionada
                $r['fecha_ponencia'] ?? null,  // Fecha propuesta del datepicker
                $r['hora_ponencia']  ?? null   // Hora propuesta del select
            ]);
        } catch (PDOException $e2) {
            // Fallback: columnas nuevas no existen aún en la BD
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
        }
        $insertId = $db->lastInsertId();
    }

    // ── Generar recibo y guardarlo en BD ─────────────────────
    // Detecta si FPDF está instalado para generar PDF, si no genera HTML
    $reciboPath = generarRecibo($insertId, $r, $orderID);
    $tabla = $r['tipo'] === 'participante' ? 'participantes' : 'ponentes';
    $db->prepare("UPDATE $tabla SET recibo_pdf=? WHERE id=?")
       ->execute([$reciboPath, $insertId]);

    $db->commit(); // Todo salió bien, confirmar la transacción

    // ── Guardar datos del pago en sesión para gracias.php ────
    $_SESSION['pago_ok'] = [
        'nombre'   => $r['nombre'],
        'correo'   => $r['correo'],
        'tipo'     => $r['tipo'],
        'monto'    => $r['monto'],
        'orderID'  => $orderID,
        'recibo'   => $reciboPath,  // Ruta al recibo generado
        'insertId' => $insertId,
    ];

    // ── Limpiar la sesión de registro (ya no se necesita) ────
    unset($_SESSION['registro']);

    echo json_encode(['ok' => true]); // pago.php redirigirá a gracias.php

} catch (Exception $e) {
    $db->rollBack(); // Deshacer todos los cambios de esta transacción
    error_log('Error pago congreso: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar el registro']);
}

/* ============================================================
   GENERADOR DE RECIBO — Detecta FPDF o usa HTML
   ============================================================ */
function generarRecibo(int $id, array $r, string $orderID): string
{
    // Si fpdf.php está en vendor/fpdf/ usa PDF, si no usa HTML
    $fpdfPath = __DIR__ . '/vendor/fpdf/fpdf.php';
    if (file_exists($fpdfPath)) {
        return generarPDF($id, $r, $orderID, $fpdfPath);
    } else {
        return generarReciboHTML($id, $r, $orderID);
    }
}

/* ── Recibo HTML (siempre funciona, sin dependencias externas) ──
   Genera un archivo .html en uploads/recibos/
   El usuario puede imprimirlo con Ctrl+P y guardarlo como PDF */
function generarReciboHTML(int $id, array $r, string $orderID): string
{
    $dir = __DIR__ . '/uploads/recibos/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Generar número de folio: P00001 para participante, P00001 para ponente
    $tipo   = $r['tipo'];
    $folio  = strtoupper($tipo[0]) . str_pad($id, 5, '0', STR_PAD_LEFT);
    $fecha  = date('d/m/Y H:i');
    $monto  = number_format($r['monto'], 2);
    $modal  = ucfirst($r['tipo_asistencia']);

    // Filas extra solo para ponentes
    $extraFilas = '';
    if ($tipo === 'ponente' && !empty($r['titulo_trabajo'])) {
        $t = htmlspecialchars($r['titulo_trabajo']);
        $e = ucfirst($r['tipo_envio'] ?? '');
        $extraFilas = "
        <tr><td>Título del trabajo</td><td>$t</td></tr>
        <tr><td>Tipo de envío</td><td>$e</td></tr>";
    }

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recibo {$folio} – Congreso 2026</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 30px 20px; color: #333; }
  .recibo { max-width: 600px; margin: 0 auto; background: #fff;
            border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,.12); }
  .header { background: #0097A7; color: #fff; padding: 30px 30px 20px; text-align: center; }
  .header h1 { font-size: 22px; margin-bottom: 4px; }
  .header p  { font-size: 14px; opacity: .85; }
  .folio { display: inline-block; background: #FFC107; color: #333;
           font-weight: bold; font-size: 15px; padding: 5px 18px;
           border-radius: 20px; margin-top: 12px; }
  .body { padding: 28px 30px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  tr:nth-child(even) { background: #f8fafb; }
  td { padding: 11px 14px; font-size: 14px; border-bottom: 1px solid #eee; }
  td:first-child { font-weight: bold; color: #555; width: 45%; }
  .total-row { background: #0097A7 !important; }
  .total-row td { color: #fff; font-size: 16px; font-weight: bold; border: none; }
  .total-row td:last-child { color: #FFC107; font-size: 18px; }
  .sello { margin: 24px 0 0; background: #e8f5e9; border: 2px solid #4caf50;
           border-radius: 8px; padding: 14px; text-align: center;
           color: #2e7d32; font-weight: bold; font-size: 15px; }
  .pie { text-align: center; font-size: 11px; color: #999; padding: 20px 30px 24px; line-height: 1.6; }
  @media print { body { background: #fff; padding: 0; } .recibo { box-shadow: none; } }
</style>
</head>
<body>
<div class="recibo">
  <div class="header">
    <h1>RECIBO DE PAGO</h1>
    <p>Congreso Academia &amp; Tecnología 2026</p>
    <span class="folio">Folio: {$folio}</span>
  </div>
  <div class="body">
    <table>
      <tr><td>Fecha de pago</td><td>{$fecha}</td></tr>
      <tr><td>Nombre</td><td>{$r['nombre']}</td></tr>
      <tr><td>Correo</td><td>{$r['correo']}</td></tr>
      <tr><td>Teléfono</td><td>{$r['telefono']}</td></tr>
      <tr><td>Institución</td><td>{$r['institucion']}</td></tr>
      <tr><td>Tipo de registro</td><td>{ucfirst($tipo)}</td></tr>
      <tr><td>Modalidad</td><td>{$modal}</td></tr>
      {$extraFilas}
      <tr><td>Order ID PayPal</td><td style="font-size:12px">{$orderID}</td></tr>
      <tr class="total-row">
        <td>TOTAL PAGADO</td>
        <td>\${$monto} MXN</td>
      </tr>
    </table>
    <div class="sello">✔ PAGO COMPLETADO VÍA PAYPAL</div>
  </div>
  <div class="pie">
    Este documento es tu comprobante oficial de inscripción al<br>
    Congreso Academia &amp; Tecnología 2026.<br>
    Puedes imprimirlo con Ctrl+P (o Cmd+P en Mac) para guardarlo como PDF.<br>
    Emitido el {$fecha}.
  </div>
</div>
</body>
</html>
HTML;

    $filename = "recibo_{$tipo}_{$id}_" . date('Ymd') . ".html";
    file_put_contents($dir . $filename, $html);
    return 'uploads/recibos/' . $filename;
}

/* ── Recibo PDF con FPDF (solo si vendor/fpdf/fpdf.php existe) ──
   Para activarlo: descargar fpdf.php de www.fpdf.org
   y copiarlo en vendor/fpdf/fpdf.php */
function generarPDF(int $id, array $r, string $orderID, string $fpdfPath): string
{
    require_once $fpdfPath;
    $dir  = __DIR__ . '/uploads/recibos/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $tipo  = $r['tipo'];
    $folio = strtoupper($tipo[0]) . str_pad($id, 5, '0', STR_PAD_LEFT);
    $fecha = date('d/m/Y H:i');
    $monto = number_format($r['monto'], 2);
    $modal = ucfirst($r['tipo_asistencia']);

    // ── Crear documento PDF A4 vertical ──────────────────────
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 20);

    // Encabezado teal
    $pdf->SetFillColor(0, 151, 167);
    $pdf->Rect(0, 0, 210, 40, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->SetXY(10, 9);
    $pdf->Cell(190, 10, 'RECIBO DE PAGO', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetX(10);
    $pdf->Cell(190, 7, 'Congreso Academia & Tecnologia 2026', 0, 1, 'C');
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(255, 193, 7); // Folio en amarillo
    $pdf->SetX(10);
    $pdf->Cell(190, 8, 'Folio: ' . $folio, 0, 1, 'C');
    $pdf->SetTextColor(40, 40, 40);
    $pdf->Ln(8);

    // Filas de datos con fondo alternado
    $filas = [
        ['Fecha de pago',    $fecha],
        ['Nombre',           $r['nombre']],
        ['Correo',           $r['correo']],
        ['Telefono',         $r['telefono']],
        ['Institucion',      $r['institucion']],
        ['Tipo de registro', ucfirst($tipo)],
        ['Modalidad',        $modal],
    ];
    if ($tipo === 'ponente' && !empty($r['titulo_trabajo'])) {
        $filas[] = ['Titulo del trabajo', $r['titulo_trabajo']];
        $filas[] = ['Tipo de envio',      ucfirst($r['tipo_envio'] ?? '')];
    }
    $filas[] = ['Order ID PayPal', $orderID];

    $fill = false;
    foreach ($filas as [$label, $valor]) {
        $pdf->SetFillColor($fill ? 240 : 255, $fill ? 248 : 255, $fill ? 250 : 255);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(65, 9, $label, 0, 0, 'L', true);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(125, 9, $valor, 0, 1, 'L', true);
        $fill = !$fill;
    }

    // Fila de total
    $pdf->Ln(4);
    $pdf->SetFillColor(0, 151, 167);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell(65, 12, 'TOTAL PAGADO', 0, 0, 'L', true);
    $pdf->SetTextColor(255, 193, 7);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(125, 12, '$' . $monto . ' MXN', 0, 1, 'R', true);

    // Sello verde de pago completado
    $pdf->Ln(8);
    $pdf->SetFillColor(232, 245, 233);
    $pdf->SetDrawColor(76, 175, 80);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(190, 12, '  PAGO COMPLETADO VIA PAYPAL', 1, 1, 'C', true);

    // Pie de página
    $pdf->Ln(10);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->MultiCell(190, 5,
        'Comprobante oficial de inscripcion al Congreso Academia & Tecnologia 2026. Emitido el ' . $fecha . '.', 0, 'C');

    $filename = "recibo_{$tipo}_{$id}_" . date('Ymd') . ".pdf";
    $pdf->Output('F', __DIR__ . '/uploads/recibos/' . $filename);
    return 'uploads/recibos/' . $filename;
}
