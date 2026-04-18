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

    // ==========================================================
    // SHARED DEVICE METHODS
    // ==========================================================

    /**
     * Vincular un dispositivo compartido al usuario actual
     * usando la API key del propietario.
     * Retorna: 'ok' | 'not_found' | 'already' | 'own_device' | 'error'
     */
    public function linkSharedDevice($guestUserId, $apiKey) {
        try {
            // Verificar que la API key existe y pertenece a otro usuario
            $device = $this->validateApiKey($apiKey);
            if (!$device) {
                return 'not_found';
            }
            if ($device['user_id'] == $guestUserId) {
                return 'own_device'; // No tiene sentido agregarse a sí mismo
            }

            // Verificar si ya está vinculado
            $stmt = $this->pdo->prepare("
                SELECT id FROM shared_devices 
                WHERE guest_user_id = ? AND api_key = ? AND is_active = 1
            ");
            $stmt->execute([$guestUserId, $apiKey]);
            if ($stmt->fetch()) {
                return 'already';
            }

            // Crear vínculo
            $stmt = $this->pdo->prepare("
                INSERT INTO shared_devices (owner_user_id, guest_user_id, api_key)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE is_active = 1
            ");
            $stmt->execute([$device['user_id'], $guestUserId, $apiKey]);
            return 'ok';
        } catch (Exception $e) {
            error_log("Error linking shared device: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Desvincular un dispositivo compartido del usuario actual
     */
    public function unlinkSharedDevice($guestUserId, $apiKey) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE shared_devices SET is_active = 0
                WHERE guest_user_id = ? AND api_key = ?
            ");
            $stmt->execute([$guestUserId, $apiKey]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error unlinking shared device: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los dispositivos compartidos con un usuario invitado
     */
    public function getSharedDevicesByUser($guestUserId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sd.*, dc.device_name, dc.last_seen, u.nombre as owner_name, u.email as owner_email
                FROM shared_devices sd
                JOIN device_config dc ON sd.api_key = dc.api_key
                JOIN usuarios u ON sd.owner_user_id = u.id
                WHERE sd.guest_user_id = ? AND sd.is_active = 1
                ORDER BY sd.created_at DESC
            ");
            $stmt->execute([$guestUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting shared devices: " . $e->getMessage());
            return [];
        }
    }
}
