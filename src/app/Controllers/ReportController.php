<?php
// src/app/Controllers/ReportController.php

require_once __DIR__ . '/../Services/EnergyService.php';
require_once __DIR__ . '/../Services/AuthService.php';

class ReportController {
    private $energyService;

    public function __construct() {
        $this->energyService = new EnergyService();
    }

    /**
     * GET /reports - Página de reportes históricos y análisis de picos
     */
    public function index() {
        AuthService::requireLogin();
        $userId = AuthService::getUserId();
        
        // Parámetros de filtro
        $startDate = $_GET['start'] ?? date('Y-m-01'); // Primer día del mes
        $endDate   = $_GET['end']   ?? date('Y-m-d');
        
        // Obtener historial diario en el rango
        $historical = $this->energyService->getConsumptionReport($userId, $startDate, $endDate);
        
        // Análisis de picos (consumo por hora del día)
        $peakHours = $this->energyService->getPeakHoursUsage($userId);
        
        // Identificar hora de mayor y menor consumo
        $maxHour = ['hora' => 0, 'avg_power' => 0];
        $minHour = ['hora' => 0, 'avg_power' => PHP_INT_MAX];
        
        foreach ($peakHours as $ph) {
            if ($ph['avg_power'] > $maxHour['avg_power']) {
                $maxHour = $ph;
            }
            if ($ph['avg_power'] < $minHour['avg_power']) {
                $minHour = $ph;
            }
        }
        
        if ($minHour['avg_power'] === PHP_INT_MAX) $minHour['avg_power'] = 0;

        include __DIR__ . '/../Views/reports.php';
    }
}