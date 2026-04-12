<?php
// src/app/Controllers/DashboardController.php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/EnergyService.php';

class DashboardController {

    public function index() {
        AuthService::requireLogin();

        $userId = AuthService::getUserId();
        $user = AuthService::getUser();
        $energyService = new EnergyService();

        // Datos en tiempo real
        $realtime = $energyService->getRealTimeData($userId);

        // Estadísticas de consumo
        $stats = $energyService->getConsumptionStats($userId);

        // Datos para gráficas iniciales (últimas 24h)
        $chartData = $energyService->getChartData($userId, '24h');

        // Estado del dispositivo
        $device = $energyService->getDeviceStatus($userId);

        // Últimas lecturas para la gráfica en tiempo real
        $realtimeReadings = $energyService->getRealtimeReadings($userId, 20);

        include __DIR__ . '/../Views/dashboard.php';
    }
}
