<?php
// src/app/Controllers/ReportController.php

require_once __DIR__ . '/../Services/EnergyService.php';

class ReportController {
    private $energyService;

    public function __construct() {
        $this->energyService = new EnergyService();
    }

    public function index($request, $response) {
        $params = $request->getQueryParams();
        $startDate = $params['start'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $params['end'] ?? date('Y-m-d');
        $reports = $this->energyService->getHistoricalReports($startDate . ' 00:00:00', $endDate . ' 23:59:59');

        // Pasar datos a la vista
        ob_start();
        include __DIR__ . '/../Views/reports.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }
}