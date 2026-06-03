-- ============================================================
--  CONGRESO ACADÉMICO 2026 – Base de Datos
--  IMPORTANTE: NO incluye CREATE DATABASE ni USE.
--  Ejecuta este script directamente sobre tu base de datos
--  existente en Clever Cloud (selecciónala antes en phpMyAdmin).
-- ============================================================

-- -------------------------------------------------------
-- Tabla de Participantes
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS participantes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    correo          VARCHAR(150) NOT NULL,
    telefono        VARCHAR(20)  NOT NULL,
    institucion     VARCHAR(200) NOT NULL,
    tipo_asistencia ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
    monto           DECIMAL(10,2) NOT NULL,
    paypal_order_id VARCHAR(100),
    paypal_status   ENUM('pendiente','completado','cancelado') NOT NULL DEFAULT 'pendiente',
    recibo_pdf      VARCHAR(255),
    ponencia_id     INT NULL DEFAULT NULL,
    usuario_id      INT NULL DEFAULT NULL,
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabla de Ponentes
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS ponentes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    correo          VARCHAR(150) NOT NULL,
    telefono        VARCHAR(20)  NOT NULL,
    institucion     VARCHAR(200) NOT NULL,
    tipo_asistencia ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
    titulo_trabajo  VARCHAR(300) NOT NULL,
    tipo_envio      ENUM('ponencia','memoria') NOT NULL,
    archivo_nombre  VARCHAR(255),
    archivo_ruta    VARCHAR(255),
    monto           DECIMAL(10,2) NOT NULL,
    paypal_order_id VARCHAR(100),
    paypal_status   ENUM('pendiente','completado','cancelado') NOT NULL DEFAULT 'pendiente',
    recibo_pdf      VARCHAR(255),
    usuario_id      INT NULL DEFAULT NULL,
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabla de Administradores
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario       VARCHAR(80)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre        VARCHAR(150) NOT NULL,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin por defecto: usuario=admin / contraseña=Admin2026!
INSERT IGNORE INTO admins (usuario, password_hash, nombre) VALUES (
    'admin',
    '$2y$12$6UeBIRnWw7lK0E0qkD2tNe1DqNfFZajdKm7DSqETzpJf4UX5UzVDG',
    'Administrador General'
);

-- -------------------------------------------------------
-- Tabla de Usuarios (login público)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(150) NOT NULL,
    correo        VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabla de Ponencias (catálogo del congreso)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS ponencias (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    titulo    VARCHAR(300) NOT NULL,
    area      VARCHAR(150) NOT NULL,
    ponente   VARCHAR(200) NOT NULL,
    fecha     VARCHAR(60)  NOT NULL,
    sala      VARCHAR(100) NOT NULL,
    modalidad ENUM('Presencial','Híbrido','Virtual') NOT NULL DEFAULT 'Presencial',
    dia       ENUM('lun','mar','mie') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ponencias del congreso 2026
INSERT IGNORE INTO ponencias (id, titulo, area, ponente, fecha, sala, modalidad, dia) VALUES
(1, 'Conferencia Magistral: IA Aplicada en la Industria',
    'Inteligencia Artificial', 'Dra. María García · UNAM',
    'Martes 20 Ene · 09:00 AM', 'Auditorio Principal', 'Presencial', 'lun'),
(2, 'Panel: Mujeres en la Ciencia y la Tecnología',
    'Género y Ciencia', 'Mtra. Ana Martínez · UAG',
    'Martes 20 Ene · 11:30 AM', 'Sala B', 'Híbrido', 'lun'),
(3, 'Taller: Ciberseguridad Práctica para Desarrolladores',
    'Ciberseguridad', 'Dr. Roberto López · ITESM',
    'Miércoles 21 Ene · 10:00 AM', 'Lab 3', 'Presencial', 'mar'),
(4, 'Clausura y Entrega de Reconocimientos',
    'Ciencias de la Computación', 'Comité Organizador · Congreso 2026',
    'Jueves 22 Ene · 09:30 AM', 'Auditorio Principal', 'Presencial', 'mie'),
(5, 'Cierre: Tendencias en Ciencia de Datos 2026',
    'Data Science', 'Dra. Laura Sánchez · UdeG',
    'Jueves 22 Ene · 11:00 AM', 'Sala A', 'Híbrido', 'mie');

-- historial
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS ponencia_id INT NULL DEFAULT NULL;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS usuario_id  INT NULL DEFAULT NULL;
ALTER TABLE ponentes      ADD COLUMN IF NOT EXISTS usuario_id  INT NULL DEFAULT NULL;

-- Columnas nuevas para ponentes (corre una por una si falla)
ALTER TABLE ponentes ADD COLUMN resumen       TEXT NULL;
ALTER TABLE ponentes ADD COLUMN area_tematica VARCHAR(100) NULL;
ALTER TABLE ponentes ADD COLUMN fecha_ponencia VARCHAR(20) NULL;
ALTER TABLE ponentes ADD COLUMN hora_ponencia  VARCHAR(10) NULL;
