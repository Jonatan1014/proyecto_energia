<?php
// src/app/Models/DeviceConfig.php

require_once __DIR__ . '/../../config/database.php';

class DeviceConfig {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->ensureUserDevicesTable();
    }

    private function ensureUserDevicesTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                hardware_id VARCHAR(100) NOT NULL,
                label VARCHAR(100) DEFAULT 'Mi Monitor',
                is_main TINYINT(1) DEFAULT 1,
                UNIQUE KEY idx_user_hw (user_id, hardware_id),
                FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    /**
     * Buscar o crear un dispositivo por su Hardware ID (MAC) de forma robusta
     */
    public function findOrCreateByHardwareId($hardwareId) {
        if (empty($hardwareId)) return null;
        
        $hardwareId = trim(strtoupper($hardwareId)); // Normalizar a mayúsculas
        
        try {
            // 1. Intentar buscar dispositivo existente
            $stmt = $this->pdo->prepare("SELECT * FROM device_config WHERE UPPER(hardware_id) = ? LIMIT 1");
            $stmt->execute([$hardwareId]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($device) {
                return $device;
            }

            // 2. Si no existe, intentar insertarlo
            $apiKey = bin2hex(random_bytes(16));
            $stmt = $this->pdo->prepare("
                INSERT INTO device_config (hardware_id, device_name, api_key, user_id)
                VALUES (?, ?, ?, NULL)
            ");
            
            $success = $stmt->execute([
                $hardwareId, 
                "Monitor ESP32 ($hardwareId)", 
                $apiKey
            ]);

            if ($success) {
                // Devolver el dispositivo recién creado
                $stmt = $this->pdo->prepare("SELECT * FROM device_config WHERE hardware_id = ? LIMIT 1");
                $stmt->execute([$hardwareId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return null;
        } catch (Exception $e) {
            error_log("CRITICAL ERROR in findOrCreateByHardwareId: " . $e->getMessage());
            // Si el error es por duplicado (otra carrera), intentar buscar de nuevo
            if (strpos($e->getMessage(), '1062') !== false) {
                 $stmt = $this->pdo->prepare("SELECT * FROM device_config WHERE hardware_id = ? LIMIT 1");
                 $stmt->execute([$hardwareId]);
                 return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return ['error_debug' => $e->getMessage()];
        }
    }

    /**
     * Obtener lista de dispositivos detectados que no tienen dueño
     */
    public function getUnclaimedDevices() {
        try {
            $stmt = $this->pdo->query("
                SELECT hardware_id, device_name, last_seen 
                FROM device_config 
                WHERE user_id IS NULL AND is_active = 1
                ORDER BY last_seen DESC LIMIT 10
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting unclaimed devices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vincular un dispositivo a un usuario (soporta múltiples usuarios por dispositivo)
     */
    public function linkDeviceToUser($userId, $hardwareId, $label = null) {
        try {
            // Asegurarse de que el hardware existe en el registro global
            $this->findOrCreateByHardwareId($hardwareId);

            $stmt = $this->pdo->prepare("
                INSERT INTO user_devices (user_id, hardware_id, label)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE label = VALUES(label)
            ");
            return $stmt->execute([$userId, $hardwareId, $label ?? "Monitor $hardwareId"]);
        } catch (Exception $e) {
            error_log("Error linking device: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener los dispositivos vinculados de un usuario
     */
    public function getDevicesByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ud.*, dc.* 
                FROM user_devices ud
                JOIN device_config dc ON ud.hardware_id = dc.hardware_id
                WHERE ud.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user devices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener dispositivos vinculados (para retrocompatibilidad en vistas)
     */
    public function getSharedDevicesByUser($userId) {
        return $this->getDevicesByUser($userId);
    }

    /**
     * Obtener el dispositivo principal de un usuario (retrocompatibilidad)
     */
    public function getByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ud.*, dc.* 
                FROM user_devices ud
                JOIN device_config dc ON ud.hardware_id = dc.hardware_id
                WHERE ud.user_id = ? AND ud.is_main = 1
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user main device: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los dispositivos registrados en el sistema (para selección múltiple)
     */
    public function getAllAvailableDevices() {
        try {
            // Mostrar dispositivos vistos en las últimas 24h
            $stmt = $this->pdo->query("
                SELECT * FROM device_config 
                WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                OR last_seen IS NULL
                ORDER BY last_seen DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting available devices: " . $e->getMessage());
            return [];
        }
    }

    public function getUsageByHourOfDay($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    HOUR(er.timestamp) as hora,
                    ROUND(AVG(er.power), 1) as avg_power
                FROM energy_readings er
                JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                WHERE ud.user_id = ?
                GROUP BY hora
                ORDER BY hora ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting usage by hour: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar last_seen del dispositivo por hardware_id
     */
    public function updateLastSeenByHardware($hardwareId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE device_config SET last_seen = NOW() WHERE hardware_id = ?");
            $stmt->execute([$hardwareId]);
        } catch (Exception $e) {
            error_log("Error updating last seen by hardware: " . $e->getMessage());
        }
    }
}
