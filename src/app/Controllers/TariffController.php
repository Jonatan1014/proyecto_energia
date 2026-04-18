<?php
// src/app/Controllers/TariffController.php

require_once __DIR__ . '/../Models/Tariff.php';
require_once __DIR__ . '/../Services/AuthService.php';

class TariffController {
    private $tariff;

    public function __construct() {
        $this->tariff = new Tariff();
    }

    /**
     * GET /settings - Página de configuración (tarifas + dispositivo)
     */
    public function index() {
        AuthService::requireLogin();
        $userId = AuthService::getUserId();
        $user = AuthService::getUser();

        $tariffs = $this->tariff->getAllByUser($userId);
        $activeTariff = $this->tariff->getActive($userId);

        // Obtener config del dispositivo
        require_once __DIR__ . '/../Models/DeviceConfig.php';
        $deviceConfig = new DeviceConfig();
        $device = $deviceConfig->getByUser($userId);

        // Si no tiene dispositivo, crear uno automáticamente
        if (!$device) {
            $result = $deviceConfig->create($userId);
            $device = $deviceConfig->getByUser($userId);
        }

        // Dispositivos compartidos con este usuario (como invitado)
        $sharedDevices = $deviceConfig->getSharedDevicesByUser($userId);

        include __DIR__ . '/../Views/settings.php';
    }

    /**
     * POST /tariffs/create - Crear una nueva tarifa
     */
    public function create() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = AuthService::getUserId();
        $name = trim($_POST['name'] ?? 'Tarifa');
        $rate = floatval($_POST['rate'] ?? 0);
        $startDate = $_POST['start_date'] ?: null;
        $endDate = $_POST['end_date'] ?: null;

        if ($rate <= 0) {
            $_SESSION['error'] = 'El valor del kWh debe ser mayor a 0';
            header("Location: " . BASE_URL . "/settings");
            exit;
        }

        $result = $this->tariff->save($userId, $name, $rate, $startDate, $endDate);

        if ($result) {
            $_SESSION['success'] = 'Tarifa creada exitosamente';
        } else {
            $_SESSION['error'] = 'Error al crear la tarifa';
        }

        header("Location: " . BASE_URL . "/settings");
        exit;
    }

    /**
     * POST /tariffs/update - Actualizar tarifa existente
     */
    public function update() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = AuthService::getUserId();
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? 'Tarifa');
        $rate = floatval($_POST['rate'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $startDate = $_POST['start_date'] ?: null;
        $endDate = $_POST['end_date'] ?: null;

        if ($rate <= 0) {
            $_SESSION['error'] = 'El valor del kWh debe ser mayor a 0';
            header("Location: " . BASE_URL . "/settings");
            exit;
        }

        $result = $this->tariff->update($id, $userId, $name, $rate, $isActive, $startDate, $endDate);

        if ($result) {
            $_SESSION['success'] = 'Tarifa actualizada exitosamente';
        } else {
            $_SESSION['error'] = 'Error al actualizar la tarifa';
        }

        header("Location: " . BASE_URL . "/settings");
        exit;
    }

    /**
     * POST /tariffs/delete - Eliminar tarifa
     */
    public function delete() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = AuthService::getUserId();
        $id = intval($_POST['id'] ?? 0);

        $result = $this->tariff->delete($id, $userId);

        if ($result) {
            $_SESSION['success'] = 'Tarifa eliminada';
        } else {
            $_SESSION['error'] = 'Error al eliminar la tarifa';
        }

        header("Location: " . BASE_URL . "/settings");
        exit;
    }
}