-- =====================================================
-- SCHEMA: Monitor de Energía IoT (v2.0 - Hardware ID Based)
-- Base de datos: energia_db_monitoreo
-- =====================================================

DROP DATABASE IF EXISTS energia_db_monitoreo;
CREATE DATABASE IF NOT EXISTS energia_db_monitoreo
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE energia_db_monitoreo;

-- =====================================================
-- TABLA: usuarios
-- Registro y autenticación de usuarios
-- =====================================================
CREATE TABLE usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100) NOT NULL,
    telefono        VARCHAR(20) DEFAULT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    edad            INT DEFAULT NULL,
    foto            VARCHAR(255) DEFAULT NULL,
    moneda          VARCHAR(10) DEFAULT 'COP',
    is_active       TINYINT(1) DEFAULT 1,
    last_login      DATETIME DEFAULT NULL,
    ip_ultimo_acceso VARCHAR(45) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: device_config
-- Configuración del hardware ESP32 y asignación a usuarios
-- =====================================================
CREATE TABLE device_config (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NULL COMMENT 'Dueño del dispositivo (NULL = dispositivo detectado pero no reclamado)',
    hardware_id     VARCHAR(100) NOT NULL UNIQUE COMMENT 'Identificador único físico (MAC Address)',
    device_name     VARCHAR(100) DEFAULT 'Monitor PZEM-004T',
    api_key         VARCHAR(64) NOT NULL UNIQUE COMMENT 'Clave interna para compatibilidad legacy',
    max_current     DECIMAL(8,2) DEFAULT 100.00,
    max_power       DECIMAL(10,2) DEFAULT 22000.00,
    alert_threshold DECIMAL(10,2) DEFAULT 0,
    relay_default   ENUM('ON','OFF') DEFAULT 'ON',
    is_active       TINYINT(1) DEFAULT 1,
    last_seen       DATETIME DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_hardware (hardware_id),
    INDEX idx_device_user (user_id),

    CONSTRAINT fk_device_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: energy_readings
-- Lecturas enviadas por el ESP32 (asociadas por hardware_id)
-- =====================================================
CREATE TABLE energy_readings (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NULL COMMENT 'Copia del dueño al momento de la lectura',
    hardware_id     VARCHAR(100) DEFAULT NULL COMMENT 'ID del hardware que envió la lectura',
    voltage         DECIMAL(8,2) NOT NULL,
    current_val     DECIMAL(10,4) NOT NULL,
    power           DECIMAL(10,2) NOT NULL,
    reactive_power  DECIMAL(10,2) DEFAULT 0,
    energy          DECIMAL(12,4) NOT NULL,
    frequency       DECIMAL(6,2) DEFAULT NULL,
    power_factor    DECIMAL(4,2) DEFAULT NULL,
    pulse_count     BIGINT DEFAULT 0,
    relay_status    ENUM('ON','OFF') DEFAULT 'OFF',
    timestamp       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_readings_user (user_id, timestamp),
    INDEX idx_readings_hw (hardware_id, timestamp),
    INDEX idx_timestamp (timestamp),

    CONSTRAINT fk_readings_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: tariffs
-- Valor del kWh en COP
-- =====================================================
CREATE TABLE tariffs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    name            VARCHAR(100) NOT NULL DEFAULT 'Tarifa Principal',
    rate_per_kwh    DECIMAL(10,2) NOT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    start_date      DATE DEFAULT NULL,
    end_date        DATE DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tariff_user (user_id, is_active),

    CONSTRAINT fk_tariffs_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: shared_devices
-- Acceso de lectura de otros usuarios a monitores físicos
-- =====================================================
CREATE TABLE shared_devices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id   INT NOT NULL COMMENT 'Dueño del hardware',
    guest_user_id   INT NOT NULL COMMENT 'Usuario con acceso permitido',
    hardware_id     VARCHAR(100) NOT NULL COMMENT 'ID del dispositivo compartido',
    can_control     TINYINT(1) DEFAULT 0,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_guest_hardware (guest_user_id, hardware_id),
    INDEX idx_owner_hw (owner_user_id),

    CONSTRAINT fk_shared_owner FOREIGN KEY (owner_user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_shared_guest FOREIGN KEY (guest_user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: alerts
-- Registro de eventos y alarmas
-- =====================================================
CREATE TABLE alerts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    type            ENUM('overcurrent','overpower','high_consumption','device_offline','info') NOT NULL,
    message         TEXT NOT NULL,
    is_read         TINYINT(1) DEFAULT 0,
    severity        ENUM('info','warning','danger') DEFAULT 'info',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_alerts_user (user_id, is_read),
    CONSTRAINT fk_alerts_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: energy_daily_summary
-- Datos pre-agregados para reportería rápida
-- =====================================================
CREATE TABLE energy_daily_summary (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    date            DATE NOT NULL,
    total_energy    DECIMAL(12,4) DEFAULT 0,
    total_cost      DECIMAL(12,2) DEFAULT 0,
    avg_power       DECIMAL(10,2) DEFAULT 0,
    max_power       DECIMAL(10,2) DEFAULT 0,
    readings_count  INT DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_summary_user_date (user_id, date),
    CONSTRAINT fk_summary_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
