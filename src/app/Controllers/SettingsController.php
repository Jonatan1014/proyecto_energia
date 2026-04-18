<?php
// src/app/Controllers/SettingsController.php

require_once __DIR__ . '/../Models/DeviceConfig.php';
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/EnergyService.php';

class SettingsController {
    private $deviceConfig;
    private $energyService;

    public function __construct() {
        $this->deviceConfig = new DeviceConfig();
        $this->energyService = new EnergyService();
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

    /**
     * POST /settings/link-device - Vincular API key de otro dispositivo (acceso compartido)
     */
    public function linkDevice() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId  = AuthService::getUserId();
        $apiKey  = trim($_POST['shared_api_key'] ?? '');

        if (empty($apiKey)) {
            $_SESSION['error'] = 'Debes ingresar una API Key válida';
            header("Location: " . BASE_URL . "/settings");
            exit;
        }

        $result = $this->energyService->linkSharedDevice($userId, $apiKey);

        $messages = [
            'ok'         => '¡Dispositivo vinculado! Ahora puedes ver sus datos en el dashboard.',
            'not_found'  => 'La API Key ingresada no existe. Verifica con el propietario del dispositivo.',
            'already'    => 'Ya tienes acceso a ese dispositivo.',
            'own_device' => 'No puedes vincularte con tu propio dispositivo.',
            'error'      => 'Error interno al vincular el dispositivo.',
        ];

        if ($result === 'ok') {
            $_SESSION['success'] = $messages[$result];
        } else {
            $_SESSION['error'] = $messages[$result] ?? 'Error desconocido';
        }

        header("Location: " . BASE_URL . "/settings");
        exit;
    }

    /**
     * POST /settings/unlink-device - Desvincular acceso a un dispositivo compartido
     */
    public function unlinkDevice() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = AuthService::getUserId();
        $apiKey = trim($_POST['api_key'] ?? '');

        if (empty($apiKey)) {
            $_SESSION['error'] = 'API Key inválida';
            header("Location: " . BASE_URL . "/settings");
            exit;
        }

        $result = $this->energyService->unlinkSharedDevice($userId, $apiKey);

        if ($result) {
            $_SESSION['success'] = 'Acceso al dispositivo compartido eliminado.';
        } else {
            $_SESSION['error'] = 'No se pudo eliminar el acceso.';
        }

        header("Location: " . BASE_URL . "/settings");
        exit;
    }
}
