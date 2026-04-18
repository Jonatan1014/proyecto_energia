-- =====================================================
-- SCHEMA: Monitor de Energía IoT
-- Base de datos: energia_db_monitoreo
-- =====================================================


drop database if exists energia_db_monitoreo;
CREATE DATABASE IF NOT EXISTS energia_db_monitoreo
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE energia_db_monitoreo;

-- =====================================================
-- TABLA: usuarios
-- Registro y autenticación de usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
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
-- TABLA: energy_readings
-- Lecturas del sensor PZEM-004T enviadas por el ESP32
-- =====================================================
CREATE TABLE IF NOT EXISTS energy_readings (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT DEFAULT NULL,
    voltage         DECIMAL(8,2) NOT NULL COMMENT 'Voltaje en V',
    current_val     DECIMAL(10,4) NOT NULL COMMENT 'Corriente en A',
    power           DECIMAL(10,2) NOT NULL COMMENT 'Potencia activa en W',
    reactive_power  DECIMAL(10,2) DEFAULT 0 COMMENT 'Potencia reactiva en VAR (calculada: S*sen(acos(PF)))',
    energy          DECIMAL(12,4) NOT NULL COMMENT 'Energía acumulada en kWh',
    frequency       DECIMAL(6,2) DEFAULT NULL COMMENT 'Frecuencia en Hz',
    power_factor    DECIMAL(4,2) DEFAULT NULL COMMENT 'Factor de potencia (0-1)',
    pulse_count     BIGINT DEFAULT 0 COMMENT 'Conteo de pulsos CF',
    relay_status    ENUM('ON','OFF') DEFAULT 'OFF' COMMENT 'Estado del relay',
    timestamp       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_timestamp (user_id, timestamp),
    INDEX idx_timestamp (timestamp),
    INDEX idx_user (user_id),

    CONSTRAINT fk_readings_user FOREIGN KEY (user_id) 
        REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: tariffs
-- Tarifas de kWh en COP configuradas por el usuario
-- =====================================================
CREATE TABLE IF NOT EXISTS tariffs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    name            VARCHAR(100) NOT NULL DEFAULT 'Tarifa Principal',
    rate_per_kwh    DECIMAL(10,2) NOT NULL COMMENT 'Valor del kWh en COP',
    is_active       TINYINT(1) DEFAULT 1,
    start_date      DATE DEFAULT NULL,
    end_date        DATE DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_active (user_id, is_active),

    CONSTRAINT fk_tariffs_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: device_config
-- Configuración del dispositivo ESP32 por usuario
-- =====================================================
CREATE TABLE IF NOT EXISTS device_config (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    device_name     VARCHAR(100) DEFAULT 'Monitor PZEM-004T',
    api_key         VARCHAR(64) NOT NULL UNIQUE,
    max_current     DECIMAL(8,2) DEFAULT 100.00 COMMENT 'Corriente máxima permitida en A',
    max_power       DECIMAL(10,2) DEFAULT 22000.00 COMMENT 'Potencia máxima permitida en W',
    alert_threshold DECIMAL(10,2) DEFAULT 0 COMMENT 'Umbral de alerta de consumo en kWh',
    relay_default   ENUM('ON','OFF') DEFAULT 'ON',
    is_active       TINYINT(1) DEFAULT 1,
    last_seen       DATETIME DEFAULT NULL COMMENT 'Última vez que el dispositivo envió datos',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_api_key (api_key),
    INDEX idx_user (user_id),

    CONSTRAINT fk_device_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: shared_devices
-- Permite que varios usuarios compartan el mismo
-- dispositivo (misma API key) para ver los mismos datos
-- en tiempo real. El propietario puede invitar a otros
-- usuarios con solo proporcionarles su API key.
-- =====================================================
CREATE TABLE IF NOT EXISTS shared_devices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id   INT NOT NULL COMMENT 'Usuario propietario del dispositivo',
    guest_user_id   INT NOT NULL COMMENT 'Usuario invitado con acceso de lectura',
    api_key         VARCHAR(64) NOT NULL COMMENT 'API key del dispositivo compartido',
    can_control     TINYINT(1) DEFAULT 0 COMMENT '1 = puede controlar relay, 0 = solo lectura',
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_guest_apikey (guest_user_id, api_key),
    INDEX idx_owner (owner_user_id),
    INDEX idx_apikey (api_key),

    CONSTRAINT fk_shared_owner FOREIGN KEY (owner_user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_shared_guest FOREIGN KEY (guest_user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: alerts
-- Alertas de consumo y eventos del sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS alerts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    type            ENUM('overcurrent','overpower','high_consumption','device_offline','info') NOT NULL,
    message         TEXT NOT NULL,
    is_read         TINYINT(1) DEFAULT 0,
    severity        ENUM('info','warning','danger') DEFAULT 'info',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at),

    CONSTRAINT fk_alerts_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: energy_daily_summary
-- Resumen diario precalculado para gráficas rápidas
-- =====================================================
CREATE TABLE IF NOT EXISTS energy_daily_summary (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    date            DATE NOT NULL,
    min_voltage     DECIMAL(8,2) DEFAULT NULL,
    max_voltage     DECIMAL(8,2) DEFAULT NULL,
    avg_voltage     DECIMAL(8,2) DEFAULT NULL,
    min_current     DECIMAL(10,4) DEFAULT NULL,
    max_current     DECIMAL(10,4) DEFAULT NULL,
    avg_current     DECIMAL(10,4) DEFAULT NULL,
    min_power       DECIMAL(10,2) DEFAULT NULL,
    max_power       DECIMAL(10,2) DEFAULT NULL,
    avg_power       DECIMAL(10,2) DEFAULT NULL,
    total_energy    DECIMAL(12,4) DEFAULT 0 COMMENT 'Energía total consumida en el día (kWh)',
    total_cost      DECIMAL(12,2) DEFAULT 0 COMMENT 'Costo total del día en COP',
    readings_count  INT DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_user_date (user_id, date),

    CONSTRAINT fk_summary_user FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MIGRACIONES: Columnas nuevas para bases de datos
-- existentes (se ignoran si ya existen)
-- =====================================================

-- Potencia reactiva en energy_readings
ALTER TABLE energy_readings 
    ADD COLUMN IF NOT EXISTS reactive_power DECIMAL(10,2) DEFAULT 0 
    COMMENT 'Potencia reactiva en VAR (calculada: S*sen(acos(PF)))' 
    AFTER power;

-- Tabla de dispositivos compartidos (en caso de que ya exista la BD)
CREATE TABLE IF NOT EXISTS shared_devices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id   INT NOT NULL,
    guest_user_id   INT NOT NULL,
    api_key         VARCHAR(64) NOT NULL,
    can_control     TINYINT(1) DEFAULT 0,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_guest_apikey (guest_user_id, api_key),
    INDEX idx_owner (owner_user_id),
    INDEX idx_apikey (api_key),
    CONSTRAINT fk_shared_owner2 FOREIGN KEY (owner_user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_shared_guest2 FOREIGN KEY (guest_user_id)
        REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
