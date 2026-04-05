-- =====================================================
-- Base de datos para Alcancia Inteligente (1 alcancia)
-- =====================================================

DROP DATABASE IF EXISTS energia_db;

CREATE DATABASE IF NOT EXISTS energia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE energia_db;

-- Usuarios para login/registro de la app web
CREATE TABLE IF NOT EXISTS usuarios (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	nombre VARCHAR(100) NOT NULL,
	apellido VARCHAR(100) NOT NULL,
	telefono VARCHAR(30) NULL,
	email VARCHAR(150) NOT NULL,
	password VARCHAR(255) NOT NULL,
	edad TINYINT UNSIGNED NULL,
	foto VARCHAR(255) NULL,
	moneda CHAR(3) NOT NULL DEFAULT 'COP',
	ingreso_mensual DECIMAL(12,2) NOT NULL DEFAULT 0,
	dia_pago TINYINT UNSIGNED NOT NULL DEFAULT 1,
	porcentaje_ahorro TINYINT UNSIGNED NOT NULL DEFAULT 20,
	is_active TINYINT(1) NOT NULL DEFAULT 1,
	last_login DATETIME NULL,
	ip_ultimo_acceso VARCHAR(45) NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY uq_usuarios_email (email),
	INDEX idx_usuarios_estado (is_active)
) ENGINE=InnoDB;

-- Configuracion unica de la alcancia (solo una fila: id=1)
CREATE TABLE IF NOT EXISTS alcancia_config (
	id TINYINT UNSIGNED PRIMARY KEY,
	nombre VARCHAR(120) NOT NULL DEFAULT 'Alcancia Principal',
	moneda CHAR(3) NOT NULL DEFAULT 'COP',
	total_ahorrado DECIMAL(12,2) NOT NULL DEFAULT 0,
	meta_general DECIMAL(12,2) NOT NULL DEFAULT 100000,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Metas de ahorro de la alcancia
CREATE TABLE IF NOT EXISTS alcancia_metas (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	alcancia_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
	nombre VARCHAR(120) NOT NULL,
	descripcion VARCHAR(255) NULL,
	monto_objetivo DECIMAL(12,2) NOT NULL,
	monto_actual DECIMAL(12,2) NOT NULL DEFAULT 0,
	prioridad TINYINT UNSIGNED NOT NULL DEFAULT 1,
	activa TINYINT(1) NOT NULL DEFAULT 1,
	fecha_objetivo DATE NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_metas_config FOREIGN KEY (alcancia_id) REFERENCES alcancia_config(id) ON DELETE CASCADE,
	INDEX idx_metas_activas (alcancia_id, activa, prioridad)
) ENGINE=InnoDB;

-- Depositos enviados por la alcancia (ESP32)
CREATE TABLE IF NOT EXISTS alcancia_depositos (
	id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	alcancia_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
	monto DECIMAL(12,2) NOT NULL,
	pulsos SMALLINT UNSIGNED NULL,
	origen VARCHAR(50) NOT NULL DEFAULT 'esp32',
	referencia VARCHAR(120) NULL,
	sync_batch TINYINT(1) NOT NULL DEFAULT 0,
	metadata JSON NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_depositos_config FOREIGN KEY (alcancia_id) REFERENCES alcancia_config(id) ON DELETE CASCADE,
	INDEX idx_depositos_fecha (alcancia_id, created_at),
	INDEX idx_depositos_origen (origen)
) ENGINE=InnoDB;

-- Semilla base: solo una alcancia
INSERT INTO alcancia_config (id, nombre, moneda, total_ahorrado, meta_general)
VALUES (1, 'Alcancia Principal', 'COP', 0, 100000)
ON DUPLICATE KEY UPDATE id = id;

-- Metas ejemplo
INSERT INTO alcancia_metas (alcancia_id, nombre, descripcion, monto_objetivo, monto_actual, prioridad, activa, fecha_objetivo)
VALUES
	(1, 'Meta General', 'Objetivo principal de ahorro', 100000, 0, 1, 1, NULL),
	(1, 'Fondo de emergencia', 'Reserva para imprevistos', 300000, 0, 2, 1, NULL)
ON DUPLICATE KEY UPDATE id = id;

