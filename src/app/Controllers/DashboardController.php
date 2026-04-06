<?php
// src/app/Controllers/DashboardController.php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/EnergyService.php';

class DashboardController {

    public function index() {
        AuthService::requireLogin();

        $energyService = new EnergyService();
        $data = $energyService->getRealTimeData() ?: [];
        $stats = [
            'total_energy' => $data['energy'] ?? 0,
            'total_cost' => $data['cost'] ?? 0,
        ];

        // Pasar datos a la vista
        ob_start();
        include __DIR__ . '/../Views/dashboard.php';
        $html = ob_get_clean();
        echo $html;
    }
}

