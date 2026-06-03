<?php
/* ============================================================
   ARCHIVO: procesar_registro.php
   FUNCIÓN: Validación server-side del formulario de registro
   ============================================================
   FLUJO:
   1. Recibe el POST del formulario (index.php #registro)
   2. Valida los campos con PHP (segunda capa después de JS)
   3. Si es ponente: sube el archivo PDF/DOCX a uploads/
   4. Guarda todos los datos en $_SESSION['registro']
   5. Redirige a pago.php para procesar el pago con PayPal
   
   SESIÓN QUE CREA: $_SESSION['registro'] con todos los datos
   del formulario, para que pago.php y confirmar_pago.php
   puedan acceder a ellos sin pasar datos por URL.
   ============================================================ */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

// ── Solo acepta peticiones POST ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php#registro');
}

// ── Verificar que el tipo sea válido ─────────────────────────
$tipo = sanitize($_POST['tipo_usuario'] ?? '');
if (!in_array($tipo, ['participante', 'ponente'])) {
    redirect('index.php#registro');
}

// ── Leer y sanitizar campos comunes (participante y ponente) ──
// sanitize() limpia el input para prevenir XSS
$resumen         = sanitize($_POST['resumen']         ?? '');
$area_tematica   = sanitize($_POST['area_tematica']   ?? '');
$fecha_ponencia  = sanitize($_POST['fecha_ponencia']  ?? '');
$hora_ponencia   = sanitize($_POST['hora_ponencia']   ?? '');
$ponencia_id     = (int)($_POST['ponencia_id'] ?? 0);   // ID de ponencia seleccionada (participante)
$usuario_id      = $_SESSION['usuario_id'] ?? null;      // Si el usuario está logueado, vincularlo
$nombre          = sanitize($_POST['nombre']          ?? '');
$correo          = sanitize($_POST['correo']          ?? '');
$telefono        = sanitize($_POST['telefono']        ?? '');
$institucion     = sanitize($_POST['institucion']     ?? '');
$tipo_asistencia = sanitize($_POST['tipo_asistencia'] ?? 'presencial');

// ── Validación server-side ────────────────────────────────────
// Esta validación es independiente de la del cliente (JS).
// Protege contra usuarios que deshabiliten JavaScript.
$errores = [];
if (!$nombre)      $errores[] = 'El nombre es obligatorio.';
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
if (!preg_match('/^\d{10}$/', preg_replace('/\D/', '', $telefono))) $errores[] = 'El teléfono debe tener 10 dígitos.';
if (!$institucion) $errores[] = 'La institución es obligatoria.';
if ($tipo === 'participante' && $ponencia_id <= 0) $errores[] = 'Debes seleccionar una ponencia.';

// ── Tabla de precios según tipo y modalidad ───────────────────
// Participante: Presencial $1,500 / Virtual $800
// Ponente:      Presencial $2,000 / Virtual $1,200
$precios = [
    'participante' => ['presencial' => 1500.00, 'virtual' => 800.00],
    'ponente'      => ['presencial' => 2000.00, 'virtual' => 1200.00],
];
$monto = $precios[$tipo][$tipo_asistencia] ?? 1500.00;

// ── Campos exclusivos del ponente ────────────────────────────
$archivo_nombre = null;
$archivo_ruta   = null;
$titulo_trabajo = null;
$tipo_envio     = null;

if ($tipo === 'ponente') {
    $titulo_trabajo = sanitize($_POST['titulo_trabajo'] ?? '');
    // Solo acepta 'ponencia' o 'memoria', cualquier otro valor usa 'ponencia' por defecto
    $tipo_envio = in_array($_POST['tipo_envio'] ?? '', ['ponencia', 'memoria'])
                  ? $_POST['tipo_envio'] : 'ponencia';

    if (!$titulo_trabajo) $errores[] = 'El título del trabajo es obligatorio.';

    // ── Validación del archivo subido ─────────────────────────
    // Verifica que existe, que tiene extensión permitida y que no supera 10 MB
    if (empty($_FILES['archivo']['tmp_name'])) {
        $errores[] = 'Debes subir tu archivo (ponencia o memoria).';
    } else {
        $ext_permitidas = ['pdf','doc','docx','ppt','pptx'];
        $info = pathinfo($_FILES['archivo']['name']);
        $ext  = strtolower($info['extension'] ?? '');
        if (!in_array($ext, $ext_permitidas)) {
            $errores[] = 'Formato de archivo no permitido. Usa PDF, DOC, DOCX, PPT o PPTX.';
        } elseif ($_FILES['archivo']['size'] > 10 * 1024 * 1024) {
            $errores[] = 'El archivo no debe superar 10 MB.';
        }
    }
}

// ── Si hay errores, regresar al formulario con flash message ──
if ($errores) {
    flash('error', implode('<br>', $errores));
    redirect('index.php#registro');
}

// ── Verificar correo duplicado en la BD ──────────────────────
// Evita registros duplicados con el mismo correo
$db   = getDB();
$tabla = $tipo === 'participante' ? 'participantes' : 'ponentes';
$stmt  = $db->prepare("SELECT id FROM $tabla WHERE correo = ?");
$stmt->execute([$correo]);
if ($stmt->fetch()) {
    flash('error', 'Ya existe un registro con ese correo electrónico.');
    redirect('index.php#registro');
}

// ── Subir archivo del ponente al servidor ────────────────────
// El archivo se guarda en uploads/ con un nombre único (uniqid)
// para evitar sobrescribir archivos con el mismo nombre
if ($tipo === 'ponente' && !empty($_FILES['archivo']['tmp_name'])) {
    $dir = __DIR__ . '/uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $safe_name      = uniqid('doc_', true) . '.' . $ext;  // Nombre seguro único
    $archivo_ruta   = 'uploads/' . $safe_name;
    $archivo_nombre = htmlspecialchars($_FILES['archivo']['name'], ENT_QUOTES);

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $dir . $safe_name)) {
        flash('error', 'No se pudo guardar el archivo. Intenta de nuevo.');
        redirect('index.php#registro');
    }
}

// ── Guardar datos en sesión (pago pendiente) ─────────────────
// Los datos se guardan en sesión para que pago.php los muestre
// y confirmar_pago.php los inserte en la BD después del pago
$_SESSION['registro'] = [
    'tipo'           => $tipo,            // 'participante' o 'ponente'
    'nombre'         => $nombre,
    'correo'         => $correo,
    'telefono'       => $telefono,
    'institucion'    => $institucion,
    'tipo_asistencia'=> $tipo_asistencia, // 'presencial' o 'virtual'
    'monto'          => $monto,           // Monto calculado según tipo y modalidad
    'titulo_trabajo' => $titulo_trabajo,  // Solo ponente
    'tipo_envio'     => $tipo_envio,      // 'ponencia' o 'memoria' — solo ponente
    'archivo_nombre' => $archivo_nombre,  // Nombre original del archivo — solo ponente
    'archivo_ruta'   => $archivo_ruta,    // Ruta en servidor — solo ponente
    'resumen'        => $resumen,         // Abstract — solo ponente
    'area_tematica'  => $area_tematica,   // Área temática — solo ponente
    'fecha_ponencia' => $fecha_ponencia,  // Fecha propuesta — solo ponente
    'hora_ponencia'  => $hora_ponencia,   // Hora propuesta — solo ponente
    'ponencia_id'    => $ponencia_id,     // Ponencia seleccionada — solo participante
    'usuario_id'     => $usuario_id,      // FK usuario logueado (puede ser null)
];

// ── Redirigir a la página de pago ────────────────────────────
redirect('pago.php');
