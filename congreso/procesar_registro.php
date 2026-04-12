<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php#registro');
}

$tipo = sanitize($_POST['tipo_usuario'] ?? '');
if (!in_array($tipo, ['participante', 'ponente'])) {
    redirect('index.php#registro');
}

// ── Campos comunes ─────────────────────────────────────────
$nombre          = sanitize($_POST['nombre']          ?? '');
$correo          = sanitize($_POST['correo']          ?? '');
$telefono        = sanitize($_POST['telefono']        ?? '');
$institucion     = sanitize($_POST['institucion']     ?? '');
$tipo_asistencia = sanitize($_POST['tipo_asistencia'] ?? 'presencial');

// ── Validación básica ──────────────────────────────────────
$errores = [];
if (!$nombre)      $errores[] = 'El nombre es obligatorio.';
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
if (!preg_match('/^\d{10}$/', preg_replace('/\D/', '', $telefono))) $errores[] = 'El teléfono debe tener 10 dígitos.';
if (!$institucion) $errores[] = 'La institución es obligatoria.';

// ── Precio ─────────────────────────────────────────────────
$precios = [
    'participante' => ['presencial' => 1500.00, 'virtual' => 800.00],
    'ponente'      => ['presencial' => 2000.00, 'virtual' => 1200.00],
];
$monto = $precios[$tipo][$tipo_asistencia] ?? 1500.00;

// ── Ponente: validaciones extra + subida de archivo ────────
$archivo_nombre = null;
$archivo_ruta   = null;
$titulo_trabajo = null;
$tipo_envio     = null;

if ($tipo === 'ponente') {
    $titulo_trabajo = sanitize($_POST['titulo_trabajo'] ?? '');
    $tipo_envio     = in_array($_POST['tipo_envio'] ?? '', ['ponencia', 'memoria'])
                      ? $_POST['tipo_envio'] : 'ponencia';

    if (!$titulo_trabajo) $errores[] = 'El título del trabajo es obligatorio.';

    // Archivo
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

if ($errores) {
    flash('error', implode('<br>', $errores));
    redirect('index.php#registro');
}

// ── Verificar correo duplicado ─────────────────────────────
$db   = getDB();
$tabla = $tipo === 'participante' ? 'participantes' : 'ponentes';
$stmt  = $db->prepare("SELECT id FROM $tabla WHERE correo = ?");
$stmt->execute([$correo]);
if ($stmt->fetch()) {
    flash('error', 'Ya existe un registro con ese correo electrónico.');
    redirect('index.php#registro');
}

// ── Subir archivo del ponente ──────────────────────────────
if ($tipo === 'ponente' && !empty($_FILES['archivo']['tmp_name'])) {
    $dir = __DIR__ . '/uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $safe_name     = uniqid('doc_', true) . '.' . $ext;
    $archivo_ruta  = 'uploads/' . $safe_name;
    $archivo_nombre = htmlspecialchars($_FILES['archivo']['name'], ENT_QUOTES);

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $dir . $safe_name)) {
        flash('error', 'No se pudo guardar el archivo. Intenta de nuevo.');
        redirect('index.php#registro');
    }
}

// ── Guardar en sesión (pago pendiente) ─────────────────────
$_SESSION['registro'] = [
    'tipo'           => $tipo,
    'nombre'         => $nombre,
    'correo'         => $correo,
    'telefono'       => $telefono,
    'institucion'    => $institucion,
    'tipo_asistencia'=> $tipo_asistencia,
    'monto'          => $monto,
    'titulo_trabajo' => $titulo_trabajo,
    'tipo_envio'     => $tipo_envio,
    'archivo_nombre' => $archivo_nombre,
    'archivo_ruta'   => $archivo_ruta,
];

// Redirigir a página de pago
redirect('pago.php');
