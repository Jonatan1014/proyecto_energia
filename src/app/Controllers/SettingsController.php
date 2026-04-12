<?php
// src/app/Controllers/SettingsController.php

require_once __DIR__ . '/../Models/DeviceConfig.php';
require_once __DIR__ . '/../Services/AuthService.php';

class SettingsController {
    private $deviceConfig;

    public function __construct() {
        $this->deviceConfig = new DeviceConfig();
    }

    /**
     * POST /settings/device - Actualizar configuración del dispositivo
     */
    public function updateDevice() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = AuthService::getUserId();
        $device = $this->deviceConfig->getByUser($userId);

        if (!$device) {
            $_SESSION['error'] = 'No se encontró dispositivo configurado';
            header("Location: " . BASE_URL . "/settings");
            exit;
        }

        $data = [
            'device_name'     => trim($_POST['device_name'] ?? 'Monitor PZEM-004T'),
            'max_current'     => floatval($_POST['max_current'] ?? 100),
            'max_power'       => floatval($_POST['max_power'] ?? 22000),
            'alert_threshold' => floatval($_POST['alert_threshold'] ?? 0),
            'relay_default'   => ($_POST['relay_default'] ?? 'ON') === 'ON' ? 'ON' : 'OFF',
        ];

        $result = $this->deviceConfig->update($device['id'], $userId, $data);

        if ($result) {
            $_SESSION['success'] = 'Configuración del dispositivo actualizada';
        } else {
            $_SESSION['error'] = 'Error al actualizar la configuración';
        }

        header("Location: " . BASE_URL . "/settings");
        exit;
    }

    /**
     * POST /settings/regenerate-key - Regenerar API Key del dispositivo
     */
    public function regenerateKey() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = AuthService::getUserId();
        $device = $this->deviceConfig->getByUser($userId);

        if (!$device) {
            $_SESSION['error'] = 'No se encontró dispositivo configurado';
            header("Location: " . BASE_URL . "/settings");
            exit;
        }

        $newKey = $this->deviceConfig->regenerateApiKey($device['id'], $userId);

        if ($newKey) {
            $_SESSION['success'] = '¡API Key regenerada! Recuerda actualizar tu ESP32.';
        } else {
            $_SESSION['error'] = 'Error al regenerar la API Key';
        }

        header("Location: " . BASE_URL . "/settings");
        exit;
    }
}
