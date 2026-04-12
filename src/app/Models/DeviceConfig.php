<?php
// src/app/Models/DeviceConfig.php

require_once __DIR__ . '/../../config/database.php';

class DeviceConfig {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Crear configuración de dispositivo con API key única
     */
    public function create($userId, $deviceName = 'Monitor PZEM-004T') {
        try {
            $apiKey = $this->generateApiKey();
            $stmt = $this->pdo->prepare("
                INSERT INTO device_config (user_id, device_name, api_key)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $deviceName, $apiKey]);
            return [
                'id' => $this->pdo->lastInsertId(),
                'api_key' => $apiKey
            ];
        } catch (Exception $e) {
            error_log("Error creating device config: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener configuración del dispositivo del usuario
     */
    public function getByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM device_config WHERE user_id = ? AND is_active = 1 LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting device config: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar API key y obtener user_id asociado
     */
    public function validateApiKey($apiKey) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dc.*, u.nombre, u.email 
                FROM device_config dc
                JOIN usuarios u ON dc.user_id = u.id
                WHERE dc.api_key = ? AND dc.is_active = 1 AND u.is_active = 1
            ");
            $stmt->execute([$apiKey]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error validating API key: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar last_seen del dispositivo
     */
    public function updateLastSeen($apiKey) {
        try {
            $stmt = $this->pdo->prepare("UPDATE device_config SET last_seen = NOW() WHERE api_key = ?");
            $stmt->execute([$apiKey]);
        } catch (Exception $e) {
            error_log("Error updating last seen: " . $e->getMessage());
        }
    }

    /**
     * Actualizar configuración
     */
    public function update($id, $userId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE device_config 
                SET device_name = ?, max_current = ?, max_power = ?, 
                    alert_threshold = ?, relay_default = ?
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([
                $data['device_name'],
                $data['max_current'],
                $data['max_power'],
                $data['alert_threshold'],
                $data['relay_default'],
                $id,
                $userId
            ]);
        } catch (Exception $e) {
            error_log("Error updating device config: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Regenerar API key
     */
    public function regenerateApiKey($id, $userId) {
        try {
            $newKey = $this->generateApiKey();
            $stmt = $this->pdo->prepare("UPDATE device_config SET api_key = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$newKey, $id, $userId]);
            return $newKey;
        } catch (Exception $e) {
            error_log("Error regenerating API key: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar API key segura
     */
    private function generateApiKey() {
        return bin2hex(random_bytes(32));
    }
}
