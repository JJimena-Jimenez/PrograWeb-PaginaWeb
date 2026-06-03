<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
$tipo_registro  = sanitize($_GET['tipo'] ?? '');
$usuario_logueado = !empty($_SESSION['usuario_id']);
$usuario_nombre   = $usuario_logueado ? htmlspecialchars($_SESSION['usuario_nombre']) : '';

// Ponencias registradas (solo completadas) para mostrar en el listado público
try {
    $db = getDB();
    $ponencias_registradas = $db->query("
        SELECT nombre, titulo_trabajo, institucion, tipo_envio,
               archivo_ruta, fecha_registro
        FROM ponentes
        WHERE paypal_status = 'completado'
        ORDER BY fecha_registro DESC
    ")->fetchAll();
} catch (Exception $e) {
    $ponencias_registradas = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Congreso Internacional de Tecnología 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <!-- jQuery (requerido por Slick) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Slick Carousel (librería jQuery - slider/carrusel) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <!-- Toastify (JS libre - notificaciones toast) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>

<header>
    <h1 class="titulo">Congreso Web <span>Academia & Tecnología 2026</span></h1>
</header>

<div class="nav-bg">
    <nav class="navegacion-principal contenedor">
        <a href="#inicio">Inicio</a>
        <a href="#programa">Programa</a>
        <a href="#ponencias">Ponencias</a>
        <a href="#memorias">Memorias</a>
        <a href="#registro">Registro</a>
        <div class="nav-usuario">
            <?php if ($usuario_logueado): ?>
                <div class="nav-dropdown-wrap">
                    <button class="nav-dropdown-btn" onclick="toggleDropdown(event)">
                        <span class="nav-avatar-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                            </svg>
                        </span>
                        <span class="nav-saludo">Hola, <?= htmlspecialchars(explode(' ', $usuario_nombre)[0]) ?>!</span>
                        <span class="nav-chevron">▾</span>
                    </button>
                    <div class="nav-dropdown-menu" id="nav-dropdown-menu">
                        <a href="perfil.php">👤 Mi perfil</a>
                        <a href="perfil.php#tab-asistencias" onclick="sessionStorage.setItem('perfilTab','asistencias')">🎟 Mis asistencias</a>
                        <a href="perfil.php#tab-ponencias"   onclick="sessionStorage.setItem('perfilTab','ponencias')">🎤 Mis ponencias</a>
                        <div class="nav-dropdown-sep"></div>
                        <a href="logout_usuario.php" class="nav-dropdown-salir">🚪 Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="nav-avatar-btn" onclick="abrirLogin();return false;" title="Iniciar sesión">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                        <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    </nav>
</div>

<!-- HERO -->
<section class="hero" id="inicio">
    <div class="contenido-hero">
        <div class="hero-eyebrow">🏆 Edición 2026 — Congreso Internacional</div>
        <h2>Convocatoria Abierta 2026</h2>
        <div class="ubicacion">
            <p>📍 Sede: Guadalajara, Jalisco &nbsp;|&nbsp; 📅 20–22 de Enero 2026</p>
        </div>

        <div class="hero-razones">
            <div class="razon-item">
                <span class="razon-icon">🌐</span>
                <div>
                    <strong>Red académica real</strong>
                    <p>Conecta con investigadores, docentes y profesionales de toda Latinoamérica en un solo lugar.</p>
                </div>
            </div>
            <div class="razon-item">
                <span class="razon-icon">📄</span>
                <div>
                    <strong>Publica tu trabajo</strong>
                    <p>Las memorias aceptadas quedan registradas con ISBN y disponibles en nuestro repositorio digital.</p>
                </div>
            </div>
            <div class="razon-item">
                <span class="razon-icon">🎤</span>
                <div>
                    <strong>Ponentes de élite</strong>
                    <p>Conferencias magistrales de expertos de UNAM, ITESM, CINVESTAV y más instituciones líderes.</p>
                </div>
            </div>
            <div class="razon-item">
                <span class="razon-icon">🎓</span>
                <div>
                    <strong>Constancia con valor curricular</strong>
                    <p>Todos los asistentes y ponentes reciben constancia oficial avalada por el comité organizador.</p>
                </div>
            </div>
        </div>

        <div class="botones-hero">
            <a class="boton" href="#registro" onclick="setTipo('ponente')">🎤 Registrar mi Ponencia</a>
            <a class="boton boton-outline" href="#registro" onclick="setTipo('participante')">🎟 Quiero Asistir</a>
        </div>
        <p class="hero-cupos">⚡ Cupos limitados — Cierre de convocatoria: <strong>15 de Enero 2026</strong></p>
    </div>
</section>

<!-- SLIDER de ponentes destacados (jQuery Slick) -->
<section id="slider-destacados" class="slider-section">
    <div class="contenedor">
        <h2>Ponentes Destacados</h2>
        <div class="ponentes-slider">
            <div class="slide-item">
                <div class="slide-avatar">👩‍💻</div>
                <h3>Dra. María García</h3>
                <p class="slide-area">Inteligencia Artificial</p>
                <p class="slide-institucion">UNAM · Ciudad de México</p>
                <span class="slide-badge">Conferencia Magistral</span>
            </div>
            <div class="slide-item">
                <div class="slide-avatar">👨‍🔬</div>
                <h3>Dr. Roberto López</h3>
                <p class="slide-area">Ciberseguridad</p>
                <p class="slide-institucion">ITESM · Monterrey</p>
                <span class="slide-badge">Taller Práctico</span>
            </div>
            <div class="slide-item">
                <div class="slide-avatar">👩‍🏫</div>
                <h3>Mtra. Ana Martínez</h3>
                <p class="slide-area">Género y Ciencia</p>
                <p class="slide-institucion">UAG · Guadalajara</p>
                <span class="slide-badge">Panel</span>
            </div>
            <div class="slide-item">
                <div class="slide-avatar">👨‍💼</div>
                <h3>Ing. Carlos Pérez</h3>
                <p class="slide-area">Sistemas Embebidos</p>
                <p class="slide-institucion">CINVESTAV · Querétaro</p>
                <span class="slide-badge">Ponencia</span>
            </div>
            <div class="slide-item">
                <div class="slide-avatar">👩‍🔭</div>
                <h3>Dra. Laura Sánchez</h3>
                <p class="slide-area">Data Science</p>
                <p class="slide-institucion">UdeG · Guadalajara</p>
                <span class="slide-badge">Conferencia</span>
            </div>
            <div class="slide-item">
                <div class="slide-avatar">👨‍🎓</div>
                <h3>Dr. Javier Morales</h3>
                <p class="slide-area">Robótica</p>
                <p class="slide-institucion">IPN · Ciudad de México</p>
                <span class="slide-badge">Demo en Vivo</span>
            </div>
        </div>
    </div>
</section>

<main class="contenedor sombra">

    <!-- PROGRAMA -->
    <section id="programa">
        <h2>Agenda del Congreso</h2>
        <div class="filtros agenda-filtro">
            <!-- Datepicker -->
            <div class="datepicker-wrap">
                <div class="datepicker-input-wrap">
                    <input type="text" id="agenda-fecha-input" class="input-text datepicker-input"
                           placeholder="dd/mm/aaaa" readonly onclick="toggleCalendario()">
                    <button class="datepicker-toggle-btn" onclick="toggleCalendario()">📅</button>
                </div>
                <div id="mini-calendario" class="mini-calendario" style="display:none">
                    <div class="cal-header">
                        <button class="cal-nav" onclick="cambiarMesCal(-1)">◀</button>
                        <span id="cal-titulo-mes"></span>
                        <button class="cal-nav" onclick="cambiarMesCal(1)">▶</button>
                    </div>
                    <div class="cal-semana-header">
                        <span>Dom</span><span>Lun</span><span>Mar</span>
                        <span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
                    </div>
                    <div class="cal-grid" id="cal-grid"></div>
                    <button class="cal-hoy-btn" onclick="irHoy()">Hoy</button>
                </div>
            </div>
            <!-- Filtros adicionales -->
            <select class="input-text filtro-select" id="filtro-tipo" onchange="aplicarFiltrosAgenda()">
                <option value="todos">Todos los tipos</option>
                <option value="magistral">Conferencia Magistral</option>
                <option value="panel">Panel</option>
                <option value="taller">Taller</option>
                <option value="clausura">Clausura</option>
            </select>
            <select class="input-text filtro-select" id="filtro-sala" onchange="aplicarFiltrosAgenda()">
                <option value="todos">Todas las salas</option>
                <option value="auditorio">Auditorio Principal</option>
                <option value="sala-b">Sala B</option>
                <option value="lab-3">Lab 3</option>
            </select>
        </div>
        <div class="programa-grid">
            <div class="entrada-agenda" data-dia="mar" data-tipo="magistral" data-sala="auditorio">
                <div class="hora">09:00 AM</div>
                <div class="info">
                    <strong>Conferencia Magistral: IA Aplicada</strong>
                    <p>Auditorio Principal | Presencial</p>
                    <span class="agenda-badge">Magistral</span>
                </div>
            </div>
            <div class="entrada-agenda" data-dia="mar" data-tipo="panel" data-sala="sala-b">
                <div class="hora">11:30 AM</div>
                <div class="info">
                    <strong>Panel: Mujeres en la Ciencia</strong>
                    <p>Sala B | Híbrido</p>
                    <span class="agenda-badge">Panel</span>
                </div>
            </div>
            <div class="entrada-agenda" data-dia="mie" data-tipo="taller" data-sala="lab-3" style="display:none">
                <div class="hora">10:00 AM</div>
                <div class="info">
                    <strong>Taller: Ciberseguridad Práctica</strong>
                    <p>Lab 3 | Presencial</p>
                    <span class="agenda-badge">Taller</span>
                </div>
            </div>
            <div class="entrada-agenda" data-dia="jue" data-tipo="clausura" data-sala="auditorio" style="display:none">
                <div class="hora">09:30 AM</div>
                <div class="info">
                    <strong>Clausura y Entrega de Reconocimientos</strong>
                    <p>Auditorio Principal | Presencial</p>
                    <span class="agenda-badge">Clausura</span>
                </div>
            </div>
        </div>
        <p id="agenda-vacia" style="display:none;text-align:center;padding:2rem;color:var(--gris);font-size:1.5rem;">
            No hay eventos para los filtros seleccionados.
        </p>
    </section>

    <!-- PONENCIAS con flyer dinámico por día -->
    <section id="ponencias" class="margin-top">
        <h2>Listado de Ponencias</h2>

        <!-- DOM #1 - Flyer/carrusel dinámico del día seleccionado -->
        <div id="ponencias-dia-container"></div>

        <!-- DOM #2 - "Te puede interesar" (otros días) -->
        <div id="ponencias-otros-container" style="display:none;">
            <div class="te-puede-interesar-header">
                <span class="tpi-icon">💡</span>
                <h3>Te puede interesar</h3>
                <p>Ponencias de otros días del congreso</p>
            </div>
            <div class="ponencias-otros-slider"></div>
        </div>

        <!-- Áreas temáticas compactas -->
        <div class="areas-tematicas-compact">
            <span class="area-chip">💻 Ciencias de la Computación</span>
            <span class="area-chip">👥 Ingeniería y Sociedad</span>
            <span class="area-chip">🎓 Educación Digital</span>
            <span class="area-chip">🔒 Ciberseguridad</span>
            <span class="area-chip">📊 Ciencia de Datos</span>
            <span class="area-chip">🤖 Inteligencia Artificial</span>
        </div>

        <!-- Ponencias registradas con sus PDFs -->
        <?php if (!empty($ponencias_registradas)): ?>
        <div class="ponencias-registradas-section">
            <h3 class="subtitulo-areas">📄 Trabajos Registrados</h3>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Título del Trabajo</th>
                            <th>Autor</th>
                            <th>Institución</th>
                            <th>Tipo</th>
                            <th>Documento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ponencias_registradas as $pr): ?>
                        <tr>
                            <td><?= htmlspecialchars($pr['titulo_trabajo']) ?></td>
                            <td><?= htmlspecialchars($pr['nombre']) ?></td>
                            <td><?= htmlspecialchars($pr['institucion']) ?></td>
                            <td>
                                <span class="cat-badge">
                                    <?= $pr['tipo_envio'] === 'ponencia' ? '🎤 Ponencia' : '📄 Memoria' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($pr['archivo_ruta'])): ?>
                                    <a href="<?= htmlspecialchars($pr['archivo_ruta']) ?>"
                                       class="enlace-pdf" download target="_blank">⬇ Descargar</a>
                                <?php else: ?>
                                    <span style="color:var(--gris);font-size:1.2rem">Sin archivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- MEMORIAS -->
    <section id="memorias" class="margin-top">
        <h2>Memorias de Ediciones Anteriores</h2>
        <div class="filtros">
            <select class="input-text" id="filtro-edicion" onchange="filtrarMemorias()">
                <option value="todos">Todas las Ediciones</option>
                <option value="2026">Edición 2026</option>
                <option value="2025">Edición 2025</option>
                <option value="2024">Edición 2024</option>
            </select>
            <select class="input-text" id="filtro-categoria" onchange="filtrarMemorias()">
                <option value="todos">Todas las Categorías</option>
                <option value="IA">Inteligencia Artificial</option>
                <option value="Sistemas">Sistemas Embebidos</option>
                <option value="Educacion">Educación Digital</option>
                <option value="Ciberseguridad">Ciberseguridad</option>
                <option value="Datos">Ciencia de Datos</option>
            </select>
        </div>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Título del Artículo</th>
                        <th>Autor Principal</th>
                        <th>Año</th>
                        <th>Categoría</th>
                        <th>Enlace</th>
                    </tr>
                </thead>
                <tbody id="tabla-memorias">
                    <tr data-año="2026" data-cat="IA">
                        <td>Modelos de Lenguaje Aplicados a la Educación Superior</td>
                        <td>Ramírez, C.</td><td>2026</td>
                        <td><span class="cat-badge">IA</span></td>
                        <td><a href="uploads/memorias/2026_ramirez_modelos_lenguaje.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                    <tr data-año="2026" data-cat="Ciberseguridad">
                        <td>Amenazas Emergentes en Infraestructuras Críticas</td>
                        <td>Torres, P.</td><td>2026</td>
                        <td><span class="cat-badge">Ciberseguridad</span></td>
                        <td><a href="uploads/memorias/2026_torres_amenazas_infraestructura.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                    <tr data-año="2025" data-cat="IA">
                        <td>Análisis de Redes Neuronales para Diagnóstico Médico</td>
                        <td>García, M.</td><td>2025</td>
                        <td><span class="cat-badge">IA</span></td>
                        <td><a href="uploads/memorias/2025_garcia_redes_neuronales.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                    <tr data-año="2025" data-cat="Educacion">
                        <td>Gamificación en Entornos de Aprendizaje Virtual</td>
                        <td>Hernández, L.</td><td>2025</td>
                        <td><span class="cat-badge">Educación</span></td>
                        <td><a href="uploads/memorias/2025_hernandez_gamificacion.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                    <tr data-año="2025" data-cat="Datos">
                        <td>Visualización de Datos en Tiempo Real con Python</td>
                        <td>Sánchez, L.</td><td>2025</td>
                        <td><span class="cat-badge">Datos</span></td>
                        <td><a href="uploads/memorias/2025_sanchez_visualizacion_datos.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                    <tr data-año="2024" data-cat="Sistemas">
                        <td>Sistemas Embebidos en la Industria 4.0</td>
                        <td>López, R.</td><td>2024</td>
                        <td><span class="cat-badge">Sistemas</span></td>
                        <td><a href="uploads/memorias/2024_lopez_sistemas_embebidos.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                    <tr data-año="2024" data-cat="Ciberseguridad">
                        <td>Protocolos de Seguridad en IoT: Un Análisis Comparativo</td>
                        <td>Morales, J.</td><td>2024</td>
                        <td><span class="cat-badge">Ciberseguridad</span></td>
                        <td><a href="uploads/memorias/2024_morales_seguridad_iot.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                    <tr data-año="2024" data-cat="Educacion">
                        <td>Impacto de la IA Generativa en el Aula Universitaria</td>
                        <td>Pérez, C.</td><td>2024</td>
                        <td><span class="cat-badge">Educación</span></td>
                        <td><a href="uploads/memorias/2024_perez_ia_generativa_aula.pdf" class="enlace-pdf" download>⬇ Ver PDF</a></td>
                    </tr>
                </tbody>
            </table>
            <p id="memorias-empty" style="display:none;text-align:center;padding:2rem;color:var(--gris);font-size:1.5rem;">
                No hay memorias para los filtros seleccionados.
            </p>
        </div>
        <div class="memorias-nota">
            📁 ¿Eres autor? Contacta al comité para subir tu memoria: <strong>congreso@academia2026.mx</strong>
        </div>
    </section>

    <!-- REGISTRO -->
    <section id="registro" class="margin-top">
        <h2>Formulario de Registro</h2>

        <?php if (!empty($_SESSION['flash']['error'])): ?>
            <div class="alert alert-error"><?= flash('error') ?></div>
        <?php endif; ?>

        <div class="tipo-selector">
            <button id="btn-participante" class="tipo-btn <?= $tipo_registro !== 'ponente' ? 'activo' : '' ?>" onclick="cambiarTipo('participante')">
                🎟️ Participante / Asistente
            </button>
            <button id="btn-ponente" class="tipo-btn <?= $tipo_registro === 'ponente' ? 'activo' : '' ?>" onclick="cambiarTipo('ponente')">
                🎤 Ponente
            </button>
        </div>

        <div class="info-precio" id="info-precio">
            <p id="txt-precio"></p>
            <p class="precio-detalle" id="txt-precio-detalle"></p>
        </div>

        <!-- Formulario PARTICIPANTE -->
        <form id="form-participante" class="formulario" action="procesar_registro.php" method="POST"
              style="<?= $tipo_registro === 'ponente' ? 'display:none' : '' ?>"
              onsubmit="return validarFormulario(event, 'participante')">
            <input type="hidden" name="tipo_usuario" value="participante">
            <fieldset>
                <legend>Registro de Participante</legend>
                <div class="contenedor-campos">
                    <div class="campo">
                        <label>Nombre Completo *</label>
                        <input class="input-text" type="text" name="nombre" id="p-nombre" placeholder="Tu nombre completo">
                        <span class="error-msg" id="err-p-nombre"></span>
                    </div>
                    <div class="campo">
                        <label>Correo Electrónico *</label>
                        <input class="input-text" type="text" name="correo" id="p-correo" placeholder="email@ejemplo.com">
                        <span class="error-msg" id="err-p-correo"></span>
                    </div>
                    <div class="campo">
                        <label>Teléfono *</label>
                        <input class="input-text" type="text" name="telefono" id="p-telefono" placeholder="10 dígitos (ej. 3312345678)">
                        <span class="error-msg" id="err-p-telefono"></span>
                    </div>
                    <div class="campo">
                        <label>Institución *</label>
                        <input class="input-text" type="text" name="institucion" id="p-institucion" placeholder="Universidad / Empresa">
                        <span class="error-msg" id="err-p-institucion"></span>
                    </div>
                    <div class="campo full-width">
                        <label>Tipo de Asistencia</label>
                        <select class="input-text" name="tipo_asistencia" onchange="actualizarPrecioParticipante(this.value)">
                            <option value="presencial">Presencial ($1,500 MXN)</option>
                            <option value="virtual">Virtual ($800 MXN)</option>
                        </select>
                    </div>
                    <!-- Selector de ponencia -->
                    <div class="campo full-width">
                        <label>Ponencia a la que deseas asistir *</label>
                        <select class="input-text" name="ponencia_id" id="p-ponencia">
                            <option value="">— Selecciona una ponencia —</option>
                            <optgroup label="Martes 20 de Enero">
                                <option value="1">09:00 AM · Conferencia Magistral: IA Aplicada en la Industria</option>
                                <option value="2">11:30 AM · Panel: Mujeres en la Ciencia y la Tecnología</option>
                            </optgroup>
                            <optgroup label="Miércoles 21 de Enero">
                                <option value="3">10:00 AM · Taller: Ciberseguridad Práctica para Desarrolladores</option>
                            </optgroup>
                            <optgroup label="Jueves 22 de Enero">
                                <option value="4">09:30 AM · Clausura y Entrega de Reconocimientos</option>
                                <option value="5">11:00 AM · Cierre: Tendencias en Ciencia de Datos 2026</option>
                            </optgroup>
                        </select>
                        <span class="error-msg" id="err-p-ponencia"></span>
                    </div>
                </div>
                <div class="alinear-derecha flex">
                    <button type="submit" class="boton w-sm-100">Continuar al Pago 💳</button>
                </div>
                <div class="pagos-simulacion">
                    <p>Pagos seguros con:</p>
                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg" alt="PayPal y tarjetas">
                </div>
            </fieldset>
        </form>

        <!-- Formulario PONENTE -->
        <form id="form-ponente" class="formulario" action="procesar_registro.php" method="POST"
              enctype="multipart/form-data"
              style="<?= $tipo_registro === 'ponente' ? '' : 'display:none' ?>"
              onsubmit="return validarFormulario(event, 'ponente')">
            <input type="hidden" name="tipo_usuario" value="ponente">
            <fieldset>
                <legend>Registro de Ponente</legend>
                <div class="contenedor-campos">
                    <div class="campo">
                        <label>Nombre Completo *</label>
                        <input class="input-text" type="text" name="nombre" id="po-nombre" placeholder="Tu nombre completo">
                        <span class="error-msg" id="err-po-nombre"></span>
                    </div>
                    <div class="campo">
                        <label>Correo Electrónico *</label>
                        <input class="input-text" type="text" name="correo" id="po-correo" placeholder="email@ejemplo.com">
                        <span class="error-msg" id="err-po-correo"></span>
                    </div>
                    <div class="campo">
                        <label>Teléfono *</label>
                        <input class="input-text" type="text" name="telefono" id="po-telefono" placeholder="10 dígitos (ej. 3312345678)">
                        <span class="error-msg" id="err-po-telefono"></span>
                    </div>
                    <div class="campo">
                        <label>Institución *</label>
                        <input class="input-text" type="text" name="institucion" id="po-institucion" placeholder="Universidad / Empresa">
                        <span class="error-msg" id="err-po-institucion"></span>
                    </div>
                    <div class="campo full-width">
                        <label>Título de la Ponencia / Memoria *</label>
                        <input class="input-text" type="text" name="titulo_trabajo" id="po-titulo" placeholder="Título oficial del trabajo">
                        <span class="error-msg" id="err-po-titulo"></span>
                    </div>
                    <div class="campo full-width">
                        <label>Resumen / Abstract *</label>
                        <textarea class="input-text" name="resumen" id="po-resumen" rows="4"
                            placeholder="Describe brevemente tu trabajo (100-500 caracteres)"
                            style="resize:vertical;font-family:inherit;font-size:1.5rem"></textarea>
                        <span class="error-msg" id="err-po-resumen"></span>
                    </div>
                    <div class="campo">
                        <label>Área Temática *</label>
                        <select class="input-text" name="area_tematica" id="po-area">
                            <option value="">— Selecciona un área —</option>
                            <option value="IA">Inteligencia Artificial</option>
                            <option value="Computacion">Ciencias de la Computación</option>
                            <option value="Ingenieria">Ingeniería y Sociedad</option>
                            <option value="Educacion">Educación Digital</option>
                            <option value="Ciberseguridad">Ciberseguridad</option>
                            <option value="Datos">Ciencia de Datos</option>
                            <option value="Robotica">Robótica</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <span class="error-msg" id="err-po-area"></span>
                    </div>
                    <div class="campo">
                        <label>Tipo de Envío *</label>
                        <select class="input-text" name="tipo_envio">
                            <option value="ponencia">Ponencia (presentación oral)</option>
                            <option value="memoria">Memoria (artículo escrito)</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label>Tipo de Asistencia</label>
                        <select class="input-text" name="tipo_asistencia" onchange="actualizarPrecioPonente(this.value)">
                            <option value="presencial">Presencial ($2,000 MXN)</option>
                            <option value="virtual">Virtual ($1,200 MXN)</option>
                        </select>
                    </div>
                    <!-- Fecha propuesta para la ponencia -->
                    <div class="campo">
                        <label>Fecha propuesta para presentar *</label>
                        <div class="datepicker-wrap">
                            <div class="datepicker-input-wrap">
                                <input type="text" class="input-text datepicker-input" name="fecha_ponencia"
                                       id="po-fecha" placeholder="dd/mm/aaaa" readonly onclick="toggleCalendarioPonente()">
                                <button type="button" class="datepicker-toggle-btn" onclick="toggleCalendarioPonente()">📅</button>
                            </div>
                            <div id="mini-calendario-ponente" class="mini-calendario" style="display:none">
                                <div class="cal-header">
                                    <button type="button" class="cal-nav" onclick="cambiarMesCalPon(-1)">◀</button>
                                    <span id="cal-titulo-mes-pon"></span>
                                    <button type="button" class="cal-nav" onclick="cambiarMesCalPon(1)">▶</button>
                                </div>
                                <div class="cal-semana-header">
                                    <span>Dom</span><span>Lun</span><span>Mar</span>
                                    <span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span>
                                </div>
                                <div class="cal-grid" id="cal-grid-ponente"></div>
                                <button type="button" class="cal-hoy-btn" onclick="calFechaPon=new Date();renderCalendarioPonente()">Hoy</button>
                            </div>
                        </div>
                        <span class="error-msg" id="err-po-fecha"></span>
                    </div>
                    <!-- Hora propuesta -->
                    <div class="campo">
                        <label>Hora propuesta *</label>
                        <select class="input-text" name="hora_ponencia" id="po-hora">
                            <option value="">— Selecciona una hora —</option>
                            <option value="09:00">09:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="11:30">11:30 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="14:00">02:00 PM</option>
                            <option value="15:00">03:00 PM</option>
                            <option value="16:00">04:00 PM</option>
                        </select>
                        <span class="error-msg" id="err-po-hora"></span>
                    </div>
                    <div class="campo full-width">
                        <label>Archivo (PDF, DOC, DOCX – máx. 10 MB) *</label>
                        <input class="input-text input-file" type="file" name="archivo" id="po-archivo"
                               accept=".pdf,.doc,.docx,.ppt,.pptx">
                        <span class="error-msg" id="err-po-archivo"></span>
                    </div>
                </div>
                <div class="alinear-derecha flex">
                    <button type="submit" class="boton w-sm-100">Continuar al Pago 💳</button>
                </div>
                <div class="pagos-simulacion">
                    <p>Pagos seguros con:</p>
                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg" alt="PayPal y tarjetas">
                </div>
            </fieldset>
        </form>
    </section>

</main>

<footer class="footer margin-top">
    <p>Todos los derechos reservados. Congreso Académico 2026.</p>
</footer>

<script>
/* ============================================================
   JAVASCRIPT — index.php
   ============================================================
   ESTRUCTURA DE ESTE BLOQUE:

   1. DATOS: ponenciasPorDia, nombresDias, DIAS_CONGRESO, MESES
      → Datos del congreso usados por el slider y el calendario

   2. SLIDER JQUERY (Slick Carousel)
      → buildFlyer()       : genera HTML de un flyer de ponencia
      → renderPonenciasDia(): DOM #1 — renderiza flyers en el contenedor
      → mostrarPonenciasOtros(): DOM #2 — muestra/oculta "Te puede interesar"
      → $(document).ready  : inicializa Slick en ponentes y ponencias

   3. FILTROS DE AGENDA
      → filtrarDia()          : filtra entradas por día seleccionado
      → aplicarFiltrosAgenda(): aplica filtros de tipo y sala combinados

   4. CALENDARIO DATEPICKER
      → toggleCalendario()    : abre/cierra el calendario de agenda
      → cambiarMesCal()       : navega entre meses
      → renderCalendario()    : dibuja el grid del calendario
      → seleccionarDiaCal()   : selecciona un día del calendario
      → toggleCalendarioPonente(): lo mismo para el form de ponente
      → renderCalendarioPonente(): dibuja el grid del calendario del ponente
      → seleccionarFechaPonente(): selecciona fecha para el ponente

   5. PRECIOS Y TIPO DE REGISTRO
      → cambiarTipo()         : alterna entre form participante y ponente
      → actualizarInfoPrecio(): muestra precios según tipo seleccionado

   6. VALIDACIÓN DEL CLIENTE (RegEx)
      → mostrarError() / limpiarError() : muestran/ocultan mensajes de error
      → validarCampo()   : valida un campo individual con regex
      → validarFormulario(): valida el formulario completo antes de enviar
         REGEX USADAS:
         - nombre:      /^[A-Za-záéíóú...]{3,80}$/
         - correo:      /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/
         - telefono:    /^\d{10}$/
         - institucion: /.{3,100}/
         - titulo:      /^.{5,200}$/
         - resumen:     /^.{50,1000}$/

   7. FILTRO DE MEMORIAS (DOM #3)
      → filtrarMemorias(): filtra filas de la tabla por edición y categoría
        (manipulación directa del DOM sin recargar la página)

   8. DROPDOWN DE USUARIO (nav)
      → toggleDropdown()  : abre/cierra el menú del usuario logueado
      → Cierra al click fuera con document.addEventListener

   9. MODAL DE LOGIN/REGISTRO
      → abrirLogin() / cerrarLogin(): muestra/oculta el modal
      → switchTab()  : alterna entre tabs de login y registro
      → submitLogin() / submitRegister(): validan y envían los formularios
        (usan validación propia del modal, independiente del formulario de registro)

   JS LIBRE: Toastify — usado en pago.php y gracias.php (no en este archivo)
   ============================================================ */

/* ── 1. DATOS DE PONENCIAS POR DÍA ───────────────────────── */
const ponenciasPorDia = {
    mar: [
        {
            area: 'Inteligencia Artificial',
            icono: '🤖',
            nombre: 'Conferencia Magistral: IA Aplicada en la Industria',
            fecha: 'Martes 20 Ene · 09:00 AM',
            sala: 'Auditorio Principal',
            modalidad: 'Presencial',
            ponente: 'Dra. María García · UNAM'
        },
        {
            area: 'Género y Ciencia',
            icono: '🔬',
            nombre: 'Panel: Mujeres en la Ciencia y la Tecnología',
            fecha: 'Martes 20 Ene · 11:30 AM',
            sala: 'Sala B',
            modalidad: 'Híbrido',
            ponente: 'Mtra. Ana Martínez · UAG'
        }
    ],
    mie: [
        {
            area: 'Ciberseguridad',
            icono: '🔒',
            nombre: 'Taller: Ciberseguridad Práctica para Desarrolladores',
            fecha: 'Miércoles 21 Ene · 10:00 AM',
            sala: 'Lab 3',
            modalidad: 'Presencial',
            ponente: 'Dr. Roberto López · ITESM'
        }
    ],
    jue: [
        {
            area: 'Ciencias de la Computación',
            icono: '🎓',
            nombre: 'Clausura y Entrega de Reconocimientos',
            fecha: 'Jueves 22 Ene · 09:30 AM',
            sala: 'Auditorio Principal',
            modalidad: 'Presencial',
            ponente: 'Comité Organizador · Congreso 2026'
        },
        {
            area: 'Data Science',
            icono: '📊',
            nombre: 'Cierre: Tendencias en Ciencia de Datos 2026',
            fecha: 'Jueves 22 Ene · 11:00 AM',
            sala: 'Sala A',
            modalidad: 'Híbrido',
            ponente: 'Dra. Laura Sánchez · UdeG'
        }
    ]
};

const nombresDias = { mar: 'Martes 20', mie: 'Miércoles 21', jue: 'Jueves 22' };

/* ── 2. SLIDER JQUERY — Construir HTML de un flyer ──────── */
function buildFlyer(p) {
    return `
    <div class="ponencia-flyer">
        <div class="flyer-area-badge">${p.area}</div>
        <div class="flyer-icono">${p.icono}</div>
        <h4 class="flyer-nombre">${p.nombre}</h4>
        <div class="flyer-meta">
            <span class="flyer-row">📅 ${p.fecha}</span>
            <span class="flyer-row">📍 ${p.sala} · ${p.modalidad}</span>
            <span class="flyer-row">👤 ${p.ponente}</span>
        </div>
        <a href="#registro" class="boton-sm flyer-btn" onclick="setTipo('participante')">Ver Detalles</a>
    </div>`;
}

/* ── DOM USO #1: renderizar ponencias del día en el contenedor ─ */
function renderPonenciasDia(dia) {
    const contenedor = document.getElementById('ponencias-dia-container');
    const ponencias  = ponenciasPorDia[dia] || [];

    // Destruir sliders existentes
    if ($('.ponencias-dia-slider').hasClass('slick-initialized')) {
        $('.ponencias-dia-slider').slick('destroy');
    }
    if ($('.ponencias-otros-slider').hasClass('slick-initialized')) {
        $('.ponencias-otros-slider').slick('destroy');
    }

    if (ponencias.length === 0) {
        /* DOM USO #2: mensaje "sin ponencias" + mostrar "Te puede interesar" */
        contenedor.innerHTML = `
            <div class="sin-ponencias">
                <div class="sin-ponencias-icono">📭</div>
                <p>No hay ponencias registradas para este día.</p>
            </div>`;
        mostrarPonenciasOtros(dia); // dia puede ser null si no es día del congreso
    } else {
        let html = `<div class="flyers-header">
                        <span class="dia-badge">${nombresDias[dia]}</span>
                        <span class="conteo-badge">${ponencias.length} ponencia${ponencias.length > 1 ? 's' : ''}</span>
                    </div>
                    <div class="ponencias-dia-slider">`;
        ponencias.forEach(p => { html += buildFlyer(p); });
        html += '</div>';
        contenedor.innerHTML = html;

        // Inicializar Slick en el slider del día
        setTimeout(() => {
            $('.ponencias-dia-slider').slick({
                slidesToShow: 2, slidesToScroll: 1,
                autoplay: true, autoplaySpeed: 3500,
                dots: true, arrows: true,
                responsive: [{ breakpoint: 768, settings: { slidesToShow: 1 } }]
            });
        }, 50);

        /* DOM USO #3: ocultar "te puede interesar" cuando hay ponencias */
        document.getElementById('ponencias-otros-container').style.display = 'none';
    }
}

/* ── Mostrar ponencias de otros días (DOM #2) ────────────────
   Si diaActual es null (día no del congreso) → muestra TODAS
   Si diaActual tiene valor → muestra las de los otros días    */
function mostrarPonenciasOtros(diaActual) {
    const wrapper = document.getElementById('ponencias-otros-container');
    const sliderEl = wrapper.querySelector('.ponencias-otros-slider');

    let html = '';
    Object.keys(ponenciasPorDia).forEach(dia => {
        // Si diaActual es null mostramos todo, si tiene valor excluimos ese día
        if (diaActual === null || dia !== diaActual) {
            ponenciasPorDia[dia].forEach(p => { html += buildFlyer(p); });
        }
    });

    sliderEl.innerHTML = html;
    wrapper.style.display = 'block'; // DOM #2: hace visible la sección

    setTimeout(() => {
        if ($('.ponencias-otros-slider').hasClass('slick-initialized')) {
            $('.ponencias-otros-slider').slick('destroy');
        }
        $('.ponencias-otros-slider').slick({
            slidesToShow: 2, slidesToScroll: 1,
            autoplay: true, autoplaySpeed: 4000,
            dots: true, arrows: true,
            responsive: [{ breakpoint: 768, settings: { slidesToShow: 1 } }]
        });
    }, 50);
}

/* ── 3. FILTROS DE AGENDA ───────────────────────────────── */
let diaActualFiltro = 'mar';

function filtrarDia(dia) {
    diaActualFiltro = dia;
    aplicarFiltrosAgenda();
    if ($('.ponencias-dia-slider').hasClass('slick-initialized')) $('.ponencias-dia-slider').slick('destroy');
    if ($('.ponencias-otros-slider').hasClass('slick-initialized')) $('.ponencias-otros-slider').slick('destroy');
    renderPonenciasDia(dia);
    setTimeout(() => {
        document.getElementById('ponencias').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

function aplicarFiltrosAgenda() {
    const tipo = document.getElementById('filtro-tipo')?.value || 'todos';
    const sala = document.getElementById('filtro-sala')?.value || 'todos';
    const entradas = document.querySelectorAll('.entrada-agenda');
    let visibles = 0;

    entradas.forEach(e => {
        const okDia  = e.dataset.dia === diaActualFiltro;
        const okTipo = tipo === 'todos' || e.dataset.tipo === tipo;
        const okSala = sala === 'todos' || e.dataset.sala === sala;
        const mostrar = okDia && okTipo && okSala;
        e.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });

    const vacia = document.getElementById('agenda-vacia');
    if (vacia) vacia.style.display = visibles === 0 ? 'block' : 'none';
}

/* ── 4. CALENDARIO DATEPICKER ───────────────────────────── */
const DIAS_CONGRESO = {
    '2026-01-20': 'mar',   // Martes 20 enero
    '2026-01-21': 'mie',   // Miércoles 21 enero
    '2026-01-22': 'jue'    // Jueves 22 enero
};
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

let calFecha = new Date(); // Mes actual

function toggleCalendario() {
    const cal = document.getElementById('mini-calendario');
    const abierto = cal.style.display !== 'none';
    cal.style.display = abierto ? 'none' : 'block';
    if (!abierto) renderCalendario();
    const calPon = document.getElementById('mini-calendario-ponente');
    if (calPon) calPon.style.display = 'none';
}

function cambiarMesCal(delta) {
    calFecha = new Date(calFecha.getFullYear(), calFecha.getMonth() + delta, 1);
    renderCalendario();
}

function irHoy() {
    calFecha = new Date();
    renderCalendario();
}

function renderCalendario() {
    const hoy     = new Date(); hoy.setHours(0,0,0,0);
    const anio    = calFecha.getFullYear();
    const mes     = calFecha.getMonth();
    const primerDia = new Date(anio, mes, 1).getDay();
    const diasEnMes = new Date(anio, mes + 1, 0).getDate();

    document.getElementById('cal-titulo-mes').textContent = `${MESES[mes]} ${anio}`;

    let html = '';
    for (let i = 0; i < primerDia; i++) html += '<span class="cal-celda vacio"></span>';

    for (let d = 1; d <= diasEnMes; d++) {
        const fechaObj = new Date(anio, mes, d); fechaObj.setHours(0,0,0,0);
        const key      = `${anio}-${String(mes+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const esPasado = fechaObj < hoy;
        const esHoy    = fechaObj.getTime() === hoy.getTime();
        const esCongreso = !!DIAS_CONGRESO[key];
        const fechaStr = `${String(d).padStart(2,'0')}/${String(mes+1).padStart(2,'0')}/${anio}`;

        let cls = 'cal-celda';
        if (esPasado)   cls += ' pasado';
        if (esHoy)      cls += ' hoy';
        if (esCongreso) cls += ' congreso'; // solo resaltado visual

        // Seleccionable: hoy y cualquier día futuro (pasados no)
        if (!esPasado) {
            const dia = DIAS_CONGRESO[key] || null;
            html += `<button class="${cls}" onclick="seleccionarDiaCal(${dia ? `'${dia}'` : 'null'},'${fechaStr}')">${d}</button>`;
        } else {
            html += `<span class="${cls}">${d}</span>`;
        }
    }
    document.getElementById('cal-grid').innerHTML = html;
}

function seleccionarDiaCal(dia, fecha) {
    document.getElementById('agenda-fecha-input').value = fecha;
    document.getElementById('mini-calendario').style.display = 'none';

    if (dia) {
        // Día del congreso → filtrar agenda y mostrar sus ponencias
        filtrarDia(dia);
    } else {
        // Día fuera del congreso → mostrar mensaje en agenda
        const vacia = document.getElementById('agenda-vacia');
        if (vacia) {
            document.querySelectorAll('.entrada-agenda').forEach(e => e.style.display = 'none');
            vacia.style.display = 'block';
            vacia.textContent = '📅 No hay eventos programados para esta fecha.';
        }
        // DOM #2: mostrar "Te puede interesar" con todas las ponencias del congreso
        if ($('.ponencias-dia-slider').hasClass('slick-initialized')) $('.ponencias-dia-slider').slick('destroy');
        if ($('.ponencias-otros-slider').hasClass('slick-initialized')) $('.ponencias-otros-slider').slick('destroy');
        const contenedor = document.getElementById('ponencias-dia-container');
        contenedor.innerHTML = `
            <div class="sin-ponencias">
                <div class="sin-ponencias-icono">📭</div>
                <p>No hay ponencias registradas para este día.</p>
            </div>`;
        mostrarPonenciasOtros(null); // null = mostrar TODAS las ponencias
    }
}

/* ── Calendario ponente ─────────────────────────────────────── */
let calFechaPon = new Date(); // Mes actual

function toggleCalendarioPonente() {
    const cal = document.getElementById('mini-calendario-ponente');
    const abierto = cal.style.display !== 'none';
    cal.style.display = abierto ? 'none' : 'block';
    if (!abierto) renderCalendarioPonente();
    const calAg = document.getElementById('mini-calendario');
    if (calAg) calAg.style.display = 'none';
}

function cambiarMesCalPon(delta) {
    calFechaPon = new Date(calFechaPon.getFullYear(), calFechaPon.getMonth() + delta, 1);
    renderCalendarioPonente();
}

function renderCalendarioPonente() {
    const hoy     = new Date(); hoy.setHours(0,0,0,0);
    const anio    = calFechaPon.getFullYear();
    const mes     = calFechaPon.getMonth();
    const primerDia = new Date(anio, mes, 1).getDay();
    const diasEnMes = new Date(anio, mes + 1, 0).getDate();

    document.getElementById('cal-titulo-mes-pon').textContent = `${MESES[mes]} ${anio}`;

    let html = '';
    for (let i = 0; i < primerDia; i++) html += '<span class="cal-celda vacio"></span>';

    for (let d = 1; d <= diasEnMes; d++) {
        const fechaObj = new Date(anio, mes, d); fechaObj.setHours(0,0,0,0);
        const key      = `${anio}-${String(mes+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const esPasado   = fechaObj < hoy;
        const esHoy      = fechaObj.getTime() === hoy.getTime();
        const esCongreso = !!DIAS_CONGRESO[key];
        const fechaStr   = `${String(d).padStart(2,'0')}/${String(mes+1).padStart(2,'0')}/${anio}`;

        let cls = 'cal-celda';
        if (esPasado)   cls += ' pasado';
        if (esHoy)      cls += ' hoy';
        if (esCongreso) cls += ' congreso'; // solo resaltado visual

        if (!esPasado) {
            html += `<button class="${cls}" onclick="seleccionarFechaPonente('${fechaStr}')">${d}</button>`;
        } else {
            html += `<span class="${cls}">${d}</span>`;
        }
    }
    document.getElementById('cal-grid-ponente').innerHTML = html;
}

function seleccionarFechaPonente(fecha) {
    document.getElementById('po-fecha').value = fecha;
    document.getElementById('mini-calendario-ponente').style.display = 'none';
    limpiarError('po-fecha', 'err-po-fecha');
}

// Cerrar calendarios al click fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.datepicker-wrap') && !e.target.closest('.datepicker-toggle-btn') && !e.target.closest('.datepicker-input-wrap')) {
        ['mini-calendario','mini-calendario-ponente'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
    }
});

/* ── Slick ponentes destacados + render inicial ─────────────── */
$(document).ready(function () {
    $('.ponentes-slider').slick({
        slidesToShow: 3, slidesToScroll: 1,
        autoplay: true, autoplaySpeed: 2500,
        dots: false, arrows: true,
        responsive: [
            { breakpoint: 900, settings: { slidesToShow: 2 } },
            { breakpoint: 600, settings: { slidesToShow: 1 } }
        ]
    });
    renderPonenciasDia('mar');
    // No establecemos fecha por defecto — el usuario elige
});

/* ── 5. PRECIOS Y TIPO DE REGISTRO ──────────────────────── */
const precios = {
    participante: { presencial: 1500, virtual: 800 },
    ponente:      { presencial: 2000, virtual: 1200 }
};
let tipoActual = '<?= $tipo_registro === "ponente" ? "ponente" : "participante" ?>';

function cambiarTipo(tipo) {
    tipoActual = tipo;
    document.getElementById('form-participante').style.display = tipo === 'participante' ? '' : 'none';
    document.getElementById('form-ponente').style.display      = tipo === 'ponente'      ? '' : 'none';
    document.getElementById('btn-participante').classList.toggle('activo', tipo === 'participante');
    document.getElementById('btn-ponente').classList.toggle('activo', tipo === 'ponente');
    actualizarInfoPrecio();
}
function setTipo(tipo) { cambiarTipo(tipo); }
function actualizarInfoPrecio() {
    const p = precios[tipoActual];
    document.getElementById('txt-precio').textContent =
        `Costos de registro (${tipoActual}):`;
    document.getElementById('txt-precio-detalle').textContent =
        `Presencial $${p.presencial.toLocaleString('es-MX')} MXN  •  Virtual $${p.virtual.toLocaleString('es-MX')} MXN`;
}
function actualizarPrecioParticipante() { if (tipoActual==='participante') actualizarInfoPrecio(); }
function actualizarPrecioPonente()      { if (tipoActual==='ponente')      actualizarInfoPrecio(); }
actualizarInfoPrecio();

/* ── 6. VALIDACIÓN DEL CLIENTE CON RegEx ────────────────────
   Cada campo tiene su regex definida en el objeto 'reglas'.
   mostrarError() → pinta el campo en rojo y muestra el mensaje
   limpiarError() → quita el color rojo y borra el mensaje
   validarCampo() → valida un campo individual
   validarFormulario() → valida todos los campos antes de enviar
   ─────────────────────────────────────────────────────────── */
const reglas = {
    nombre:     { regex: /^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]{3,80}$/, msg: 'Solo letras y espacios, mínimo 3 caracteres.' },
    correo:     { regex: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/, msg: 'Ingresa un correo válido (ej. usuario@dominio.com).' },
    telefono:   { regex: /^\d{10}$/, msg: 'El teléfono debe tener exactamente 10 dígitos.' },
    institucion:{ regex: /^.{3,100}$/, msg: 'Mínimo 3 caracteres.' },
    titulo:     { regex: /^.{5,200}$/, msg: 'El título debe tener entre 5 y 200 caracteres.' },
    resumen:    { regex: /^.{50,1000}$/, msg: 'El resumen debe tener entre 50 y 1000 caracteres.' }
};

function mostrarError(idInput, idError, mensaje) {
    const inp = document.getElementById(idInput);
    const sp  = document.getElementById(idError);
    if (sp)  sp.textContent = mensaje;
    if (inp) inp.classList.add('input-invalido');
}
function limpiarError(idInput, idError) {
    const inp = document.getElementById(idInput);
    const sp  = document.getElementById(idError);
    if (sp)  sp.textContent = '';
    if (inp) inp.classList.remove('input-invalido');
}

function validarCampo(prefijo, campo) {
    const id    = `${prefijo}-${campo}`;
    const errId = `err-${prefijo}-${campo}`;
    const inp   = document.getElementById(id);
    if (!inp) return true;
    const val = inp.value.trim();
    if (val === '') { mostrarError(id, errId, '⚠️ Este campo es obligatorio.'); return false; }
    if (reglas[campo] && !reglas[campo].regex.test(val)) { mostrarError(id, errId, `⚠️ ${reglas[campo].msg}`); return false; }
    limpiarError(id, errId);
    return true;
}

function validarFormulario(e, tipo) {
    e.preventDefault();
    const pref   = tipo === 'participante' ? 'p' : 'po';
    const campos = tipo === 'participante'
        ? ['nombre','correo','telefono','institucion']
        : ['nombre','correo','telefono','institucion','titulo','resumen'];

    let valido = campos.reduce((ok, c) => validarCampo(pref, c) && ok, true);

    // Validar selección de ponencia para participante
    if (tipo === 'participante') {
        const sel = document.getElementById('p-ponencia');
        if (!sel || sel.value === '') {
            mostrarError('p-ponencia', 'err-p-ponencia', '⚠️ Debes seleccionar una ponencia.');
            valido = false;
        } else {
            limpiarError('p-ponencia', 'err-p-ponencia');
        }
    }

    // Validar archivo para ponente
    if (tipo === 'ponente') {
        // Área temática
        const area = document.getElementById('po-area');
        if (!area || area.value === '') {
            mostrarError('po-area','err-po-area','⚠️ Selecciona un área temática.');
            valido = false;
        } else limpiarError('po-area','err-po-area');

        // Fecha
        const fecha = document.getElementById('po-fecha');
        if (!fecha || !fecha.value.trim()) {
            mostrarError('po-fecha','err-po-fecha','⚠️ Selecciona una fecha para tu ponencia.');
            valido = false;
        } else limpiarError('po-fecha','err-po-fecha');

        // Hora
        const hora = document.getElementById('po-hora');
        if (!hora || hora.value === '') {
            mostrarError('po-hora','err-po-hora','⚠️ Selecciona una hora para tu ponencia.');
            valido = false;
        } else limpiarError('po-hora','err-po-hora');

        // Archivo
        const arch = document.getElementById('po-archivo');
        if (!arch || !arch.files || arch.files.length === 0) {
            mostrarError('po-archivo','err-po-archivo','⚠️ Debes adjuntar tu documento.');
            valido = false;
        } else {
            const ext = arch.files[0].name.split('.').pop().toLowerCase();
            if (!['pdf','doc','docx','ppt','pptx'].includes(ext)) {
                mostrarError('po-archivo','err-po-archivo','⚠️ Solo PDF, DOC, DOCX, PPT o PPTX.');
                valido = false;
            } else { limpiarError('po-archivo','err-po-archivo'); }
        }
    }

    if (!valido) {
        Toastify({
            text: '⚠️ Revisa los campos marcados antes de continuar.',
            duration: 4000, gravity: 'top', position: 'center',
            style: { background:'#c62828', borderRadius:'8px', fontFamily:'Krub,sans-serif', fontSize:'1.4rem' }
        }).showToast();
        return false;
    }

    Toastify({
        text: '✅ Formulario válido. Redirigiendo al pago…',
        duration: 2000, gravity: 'top', position: 'center',
        style: { background:'#00897b', borderRadius:'8px', fontFamily:'Krub,sans-serif', fontSize:'1.4rem' }
    }).showToast();
    setTimeout(() => { e.target.submit(); }, 1500);
    return false;
}

// Limpiar errores al escribir
document.querySelectorAll('.input-text, .input-modal').forEach(inp => {
    inp.addEventListener('input', function () {
        if (this.id) limpiarError(this.id, 'err-' + this.id);
    });
});

/* ── 7. FILTRO DE MEMORIAS — DOM USO #3 ─────────────────────
   Manipula directamente el DOM para mostrar/ocultar filas
   de la tabla de memorias según los filtros seleccionados.
   No recarga la página — todo ocurre en el cliente.        */
function filtrarMemorias() {
    const edicion  = document.getElementById('filtro-edicion').value;
    const cat      = document.getElementById('filtro-categoria').value;
    const filas    = document.querySelectorAll('#tabla-memorias tr');
    let   visibles = 0;

    filas.forEach(tr => {
        const año   = tr.dataset.año;
        const trCat = tr.dataset.cat;
        const okAño = edicion === 'todos' || año === edicion;
        const okCat = cat     === 'todos' || trCat === cat;
        tr.style.display = (okAño && okCat) ? '' : 'none';
        if (okAño && okCat) visibles++;
    });

    document.getElementById('memorias-empty').style.display = visibles === 0 ? 'block' : 'none';
}
</script>
<!-- ── MODAL LOGIN ─────────────────────────────────────────── -->
<div id="modal-login" class="modal-overlay" style="display:none" onclick="cerrarLoginSiFondo(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="cerrarLogin()">✕</button>

        <?php if ($usuario_logueado): ?>
        <div class="modal-perfil-menu">
            <div class="modal-avatar-grande"><?= mb_strtoupper(mb_substr($usuario_nombre,0,1)) ?></div>
            <p class="modal-bienvenida">¡Hola, <strong><?= htmlspecialchars($usuario_nombre) ?></strong>!</p>
            <a href="perfil.php" class="modal-menu-item">👤 Ver mi perfil</a>
            <a href="perfil.php" class="modal-menu-item">🎟 Mis asistencias</a>
            <a href="perfil.php" class="modal-menu-item">🎤 Mis ponencias</a>
            <a href="logout_usuario.php" class="modal-menu-item modal-menu-salir">🚪 Cerrar sesión</a>
        </div>

        <?php else: ?>
        <div class="modal-tabs">
            <button class="modal-tab activo" id="tab-login"    onclick="switchTab('login')">Iniciar Sesión</button>
            <button class="modal-tab"        id="tab-register" onclick="switchTab('register')">Registrar</button>
        </div>

        <?php if (!empty($_SESSION['flash']['login_error'])): ?>
            <div class="alert alert-error" style="margin-bottom:1rem"><?= flash('login_error') ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash']['register_error'])): ?>
            <div class="alert alert-error" style="margin-bottom:1rem;display:none" id="reg-flash"><?= flash('register_error') ?></div>
        <?php endif; ?>

        <!-- Login -->
        <form id="modal-form-login" action="auth_usuario.php" method="POST">
            <input type="hidden" name="accion" value="login">
            <div class="modal-campo">
                <input class="input-modal" type="text" name="correo" id="ml-correo" placeholder="Correo electrónico" autocomplete="email">
                <span class="error-msg" id="err-ml-correo"></span>
            </div>
            <div class="modal-campo">
                <input class="input-modal" type="password" name="password" id="ml-password" placeholder="Contraseña" autocomplete="current-password">
                <span class="error-msg" id="err-ml-password"></span>
            </div>
            <label class="modal-check"><input type="checkbox"> Recordar contraseña</label>
            <button type="button" class="modal-btn-submit" onclick="submitLogin()">Entrar</button>
        </form>

        <!-- Registro -->
        <form id="modal-form-register" action="auth_usuario.php" method="POST" style="display:none">
            <input type="hidden" name="accion" value="register">
            <div class="modal-campo">
                <input class="input-modal" type="text" name="nombre" id="mr-nombre" placeholder="Nombre completo" autocomplete="name">
                <span class="error-msg" id="err-mr-nombre"></span>
            </div>
            <div class="modal-campo">
                <input class="input-modal" type="text" name="correo" id="mr-correo" placeholder="Correo electrónico" autocomplete="email">
                <span class="error-msg" id="err-mr-correo"></span>
            </div>
            <div class="modal-campo">
                <input class="input-modal" type="password" name="password" id="mr-password" placeholder="Contraseña (mín. 6 caracteres)" autocomplete="new-password">
                <span class="error-msg" id="err-mr-password"></span>
            </div>
            <label class="modal-check">
                <input type="checkbox" id="mr-terminos"> Acepto los Términos y Condiciones
            </label>
            <span class="error-msg" id="err-mr-terminos" style="display:block;margin-bottom:.8rem"></span>
            <button type="button" class="modal-btn-submit" onclick="submitRegister()">Registrar</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
/* ── Dropdown nav ───────────────────────────────────────── */
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

/* ── Modal ──────────────────────────────────────────────── */
function abrirLogin() {
    document.getElementById('modal-login').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function cerrarLogin() {
    document.getElementById('modal-login').style.display = 'none';
    document.body.style.overflow = '';
}
function cerrarLoginSiFondo(e) {
    if (e.target === document.getElementById('modal-login')) cerrarLogin();
}
function switchTab(tab) {
    const fl = document.getElementById('reg-flash');
    document.getElementById('modal-form-login').style.display    = tab==='login'    ? '' : 'none';
    document.getElementById('modal-form-register').style.display = tab==='register' ? '' : 'none';
    document.getElementById('tab-login').classList.toggle('activo',    tab==='login');
    document.getElementById('tab-register').classList.toggle('activo', tab==='register');
    if (fl) fl.style.display = tab==='register' ? 'block' : 'none';
}

/* ── Validación y submit login ──────────────────────────── */
function submitLogin() {
    const correo = document.getElementById('ml-correo');
    const pass   = document.getElementById('ml-password');
    const reC    = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    let ok = true;

    if (!correo.value.trim()) {
        setErr('ml-correo','err-ml-correo','⚠️ Campo obligatorio.'); ok=false;
    } else if (!reC.test(correo.value.trim())) {
        setErr('ml-correo','err-ml-correo','⚠️ Correo inválido.'); ok=false;
    } else clearErr('ml-correo','err-ml-correo');

    if (!pass.value.trim()) {
        setErr('ml-password','err-ml-password','⚠️ Campo obligatorio.'); ok=false;
    } else clearErr('ml-password','err-ml-password');

    if (ok) document.getElementById('modal-form-login').submit();
}

/* ── Validación y submit registro ───────────────────────── */
function submitRegister() {
    const nombre = document.getElementById('mr-nombre');
    const correo = document.getElementById('mr-correo');
    const pass   = document.getElementById('mr-password');
    const terms  = document.getElementById('mr-terminos');
    const reC    = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    let ok = true;

    if (nombre.value.trim().length < 3) {
        setErr('mr-nombre','err-mr-nombre','⚠️ Mínimo 3 caracteres.'); ok=false;
    } else clearErr('mr-nombre','err-mr-nombre');

    if (!reC.test(correo.value.trim())) {
        setErr('mr-correo','err-mr-correo','⚠️ Correo inválido.'); ok=false;
    } else clearErr('mr-correo','err-mr-correo');

    if (pass.value.length < 6) {
        setErr('mr-password','err-mr-password','⚠️ Mínimo 6 caracteres.'); ok=false;
    } else clearErr('mr-password','err-mr-password');

    if (!terms.checked) {
        setErr('mr-terminos','err-mr-terminos','⚠️ Debes aceptar los términos.'); ok=false;
    } else clearErr('mr-terminos','err-mr-terminos');

    if (ok) document.getElementById('modal-form-register').submit();
}

function setErr(inp, err, msg) {
    const i = document.getElementById(inp);
    const s = document.getElementById(err);
    if (s) s.textContent = msg;
    if (i) i.classList.add('input-invalido');
}
function clearErr(inp, err) {
    const i = document.getElementById(inp);
    const s = document.getElementById(err);
    if (s) s.textContent = '';
    if (i) i.classList.remove('input-invalido');
}

// Limpiar error al escribir
document.querySelectorAll('.input-modal').forEach(inp => {
    inp.addEventListener('input', function() {
        this.classList.remove('input-invalido');
        const err = document.getElementById('err-' + this.id);
        if (err) err.textContent = '';
    });
});

// Abrir modal automáticamente si hubo error flash
<?php if (!empty($_SESSION['flash']['login_error'])): ?>
window.addEventListener('load', () => { abrirLogin(); });
<?php elseif (!empty($_SESSION['flash']['register_error'])): ?>
window.addEventListener('load', () => { abrirLogin(); switchTab('register'); });
<?php endif; ?>
</script>
</body>
</html>
