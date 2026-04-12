-- ============================================================
--  CONGRESO ACADÉMICO 2026 – Base de Datos
--  Compatible con MySQL 5.7+ / Clever Cloud
--  Ejecuta este script en tu panel de Clever Cloud (phpMyAdmin
--  o con mysql cli).
-- ============================================================

CREATE DATABASE IF NOT EXISTS congreso_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE congreso_db;

-- -------------------------------------------------------
-- Tabla de Participantes
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS participantes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    correo          VARCHAR(150) NOT NULL UNIQUE,
    telefono        VARCHAR(20)  NOT NULL,
    institucion     VARCHAR(200) NOT NULL,
    tipo_asistencia ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
    monto           DECIMAL(10,2) NOT NULL,
    paypal_order_id VARCHAR(100),
    paypal_status   ENUM('pendiente','completado','cancelado') NOT NULL DEFAULT 'pendiente',
    recibo_pdf      VARCHAR(255),          -- ruta del PDF generado
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabla de Ponentes
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS ponentes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    correo          VARCHAR(150) NOT NULL UNIQUE,
    telefono        VARCHAR(20)  NOT NULL,
    institucion     VARCHAR(200) NOT NULL,
    tipo_asistencia ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
    titulo_trabajo  VARCHAR(300) NOT NULL,
    tipo_envio      ENUM('ponencia','memoria') NOT NULL,
    archivo_nombre  VARCHAR(255),          -- nombre original
    archivo_ruta    VARCHAR(255),          -- ruta en servidor
    monto           DECIMAL(10,2) NOT NULL,
    paypal_order_id VARCHAR(100),
    paypal_status   ENUM('pendiente','completado','cancelado') NOT NULL DEFAULT 'pendiente',
    recibo_pdf      VARCHAR(255),
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Tabla de Administradores
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario         VARCHAR(80) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    nombre          VARCHAR(150) NOT NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin por defecto: usuario = admin / contraseña = Admin2026!
-- (Cámbiala inmediatamente después de instalar)
INSERT IGNORE INTO admins (usuario, password_hash, nombre)
VALUES (
    'admin',
    '$2y$12$6UeBIRnWw7lK0E0qkD2tNe1DqNfFZajdKm7DSqETzpJf4UX5UzVDG',
    'Administrador General'
);
