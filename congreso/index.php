<?php
require_once __DIR__ . '/includes/session.php';
$tipo_registro = sanitize($_GET['tipo'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Congreso Internacional de Tecnología 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Krub:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
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
    </nav>
</div>

<section class="hero" id="inicio">
    <div class="contenido-hero">
        <h2>Convocatoria Abierta 2026</h2>
        <div class="ubicacion">
            <p>📍 Sede: Guadalajara, Jalisco | 📅 20–22 de Octubre</p>
        </div>
        <div class="botones-hero">
            <a class="boton" href="#registro" onclick="setTipo('ponente')">Registrar Ponencia</a>
            <a class="boton" href="#registro" onclick="setTipo('participante')">Asistir al Evento</a>
        </div>
    </div>
</section>

<main class="contenedor sombra">

    <!-- PROGRAMA -->
    <section id="programa">
        <h2>Agenda del Congreso</h2>
        <div class="filtros">
            <button class="boton-filtro activo" onclick="filtrarDia('lun',this)">Lunes 20</button>
            <button class="boton-filtro" onclick="filtrarDia('mar',this)">Martes 21</button>
            <button class="boton-filtro" onclick="filtrarDia('mie',this)">Miércoles 22</button>
        </div>
        <div class="programa-grid">
            <div class="entrada-agenda" data-dia="lun">
                <div class="hora">09:00 AM</div>
                <div class="info"><strong>Conferencia Magistral: IA Aplicada</strong><p>Auditorio Principal | Presencial</p></div>
            </div>
            <div class="entrada-agenda" data-dia="lun">
                <div class="hora">11:30 AM</div>
                <div class="info"><strong>Panel: Mujeres en la Ciencia</strong><p>Sala B | Híbrido</p></div>
            </div>
            <div class="entrada-agenda" data-dia="mar" style="display:none">
                <div class="hora">10:00 AM</div>
                <div class="info"><strong>Taller: Ciberseguridad Práctica</strong><p>Lab 3 | Presencial</p></div>
            </div>
            <div class="entrada-agenda" data-dia="mie" style="display:none">
                <div class="hora">09:30 AM</div>
                <div class="info"><strong>Clausura y Entrega de Reconocimientos</strong><p>Auditorio Principal | Presencial</p></div>
            </div>
        </div>
    </section>

    <!-- PONENCIAS -->
    <section id="ponencias" class="margin-top">
        <h2>Listado de Ponencias</h2>
        <div class="servicios">
            <article class="servicio">
                <h3>Ciencias de la Computación</h3>
                <div class="iconos">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <p>Algoritmos, estructuras de datos e inteligencia artificial.</p>
                <a href="#" class="boton-sm">Ver Detalles</a>
            </article>
            <article class="servicio">
                <h3>Ingeniería y Sociedad</h3>
                <div class="iconos">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m12-9a4 4 0 01-4-4 4 4 0 014-4 4 4 0 014 4 4 4 0 01-4 4zm7 9v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg>
                </div>
                <p>Impacto de la tecnología en el desarrollo humano sostenible.</p>
                <a href="#" class="boton-sm">Ver Detalles</a>
            </article>
        </div>
    </section>

    <!-- MEMORIAS -->
    <section id="memorias" class="margin-top">
        <h2>Memorias de Ediciones Anteriores</h2>
        <div class="filtros">
            <select class="input-text"><option>Edición 2025</option><option>Edición 2024</option></select>
            <select class="input-text"><option>Todas las Categorías</option><option>Tecnología</option><option>Educación</option></select>
        </div>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Título del Artículo</th><th>Autor Principal</th><th>Año</th><th>Enlace</th></tr></thead>
                <tbody>
                    <tr><td>Análisis de Redes Neuronales</td><td>García, M.</td><td>2025</td><td><a href="#" class="enlace-pdf">Ver PDF</a></td></tr>
                    <tr><td>Sistemas Embebidos en la Industria</td><td>López, R.</td><td>2024</td><td><a href="#" class="enlace-pdf">Ver PDF</a></td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- REGISTRO -->
    <section id="registro" class="margin-top">
        <h2>Formulario de Registro</h2>

        <?php if (!empty($_SESSION['flash']['error'])): ?>
            <div class="alert alert-error"><?= flash('error') ?></div>
        <?php endif; ?>

        <!-- Selector de tipo -->
        <div class="tipo-selector">
            <button id="btn-participante" class="tipo-btn <?= $tipo_registro !== 'ponente' ? 'activo' : '' ?>" onclick="cambiarTipo('participante')">
                🎟️ Participante / Asistente
            </button>
            <button id="btn-ponente" class="tipo-btn <?= $tipo_registro === 'ponente' ? 'activo' : '' ?>" onclick="cambiarTipo('ponente')">
                🎤 Ponente
            </button>
        </div>

        <!-- Precios dinámicos -->
        <div class="info-precio" id="info-precio">
            <p id="txt-precio"></p>
        </div>

        <!-- Formulario PARTICIPANTE -->
        <form id="form-participante" class="formulario" action="procesar_registro.php" method="POST" style="<?= $tipo_registro === 'ponente' ? 'display:none' : '' ?>">
            <input type="hidden" name="tipo_usuario" value="participante">
            <fieldset>
                <legend>Registro de Participante</legend>
                <div class="contenedor-campos">
                    <div class="campo">
                        <label>Nombre Completo *</label>
                        <input class="input-text" type="text" name="nombre" required placeholder="Tu nombre completo">
                    </div>
                    <div class="campo">
                        <label>Correo Electrónico *</label>
                        <input class="input-text" type="email" name="correo" required placeholder="email@ejemplo.com">
                    </div>
                    <div class="campo">
                        <label>Teléfono *</label>
                        <input class="input-text" type="tel" name="telefono" required placeholder="10 dígitos">
                    </div>
                    <div class="campo">
                        <label>Institución *</label>
                        <input class="input-text" type="text" name="institucion" required placeholder="Universidad / Empresa">
                    </div>
                    <div class="campo full-width">
                        <label>Tipo de Asistencia</label>
                        <select class="input-text" name="tipo_asistencia" onchange="actualizarPrecioParticipante(this.value)">
                            <option value="presencial">Presencial ($1,500 MXN)</option>
                            <option value="virtual">Virtual ($800 MXN)</option>
                        </select>
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
              enctype="multipart/form-data" style="<?= $tipo_registro === 'ponente' ? '' : 'display:none' ?>">
            <input type="hidden" name="tipo_usuario" value="ponente">
            <fieldset>
                <legend>Registro de Ponente</legend>
                <div class="contenedor-campos">
                    <div class="campo">
                        <label>Nombre Completo *</label>
                        <input class="input-text" type="text" name="nombre" required placeholder="Tu nombre completo">
                    </div>
                    <div class="campo">
                        <label>Correo Electrónico *</label>
                        <input class="input-text" type="email" name="correo" required placeholder="email@ejemplo.com">
                    </div>
                    <div class="campo">
                        <label>Teléfono *</label>
                        <input class="input-text" type="tel" name="telefono" required placeholder="10 dígitos">
                    </div>
                    <div class="campo">
                        <label>Institución *</label>
                        <input class="input-text" type="text" name="institucion" required placeholder="Universidad / Empresa">
                    </div>
                    <div class="campo full-width">
                        <label>Título de la Ponencia / Memoria *</label>
                        <input class="input-text" type="text" name="titulo_trabajo" required placeholder="Título oficial del trabajo">
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
                    <div class="campo full-width">
                        <label>Archivo (PDF, DOC, DOCX – máx. 10 MB) *</label>
                        <input class="input-text input-file" type="file" name="archivo" required
                               accept=".pdf,.doc,.docx,.ppt,.pptx">
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
    const modalidad = document.querySelector(`#form-${tipoActual} select[name="tipo_asistencia"]`)?.value || 'presencial';
    const monto = precios[tipoActual][modalidad];
    document.getElementById('txt-precio').textContent =
        `Costo de registro (${tipoActual}): $${monto.toLocaleString('es-MX')} MXN`;
}

function actualizarPrecioParticipante(val) { if (tipoActual==='participante') actualizarInfoPrecio(); }
function actualizarPrecioPonente(val)      { if (tipoActual==='ponente')      actualizarInfoPrecio(); }

function filtrarDia(dia, btn) {
    document.querySelectorAll('.entrada-agenda').forEach(e => {
        e.style.display = e.dataset.dia === dia ? '' : 'none';
    });
    document.querySelectorAll('.boton-filtro').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
}

// Inicializar precio
actualizarInfoPrecio();
</script>
</body>
</html>
