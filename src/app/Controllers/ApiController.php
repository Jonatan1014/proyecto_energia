<?php
// src/app/Controllers/ApiController.php

require_once __DIR__ . '/../Services/EnergyService.php';
require_once __DIR__ . '/../Services/AuthService.php';

class ApiController {
    private $energyService;

    public function __construct() {
        $this->energyService = new EnergyService();
    }

    /**
     * POST /api/save
     * Recibir datos del ESP32 (autenticado por API key)
     */
    public function saveData() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            return;
        }

        // Obtener API key del header o del body
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!$data) {
            // Intentar con form data
            $data = $_POST;
        }

        if (!$apiKey && isset($data['api_key'])) {
            $apiKey = $data['api_key'];
        }

        if (!$apiKey) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'API key requerida']);
            return;
        }

        $result = $this->energyService->saveReading($apiKey, $data);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
        }
    }

    /**
     * GET /api/data
     * Obtener datos en tiempo real (requiere sesión)
     */
    public function getData() {
        header('Content-Type: application/json');

        if (!AuthService::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            return;
        }

        $userId = AuthService::getUserId();
        $data = $this->energyService->getRealTimeData($userId);

        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'success', 'data' => null, 'message' => 'Sin datos disponibles']);
        }
    }

    /**
     * GET /api/chart-data
     * Obtener datos para gráficas
     */
    public function getChartData() {
        header('Content-Type: application/json');

        if (!AuthService::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            return;
        }

        $userId = AuthService::getUserId();
        $period = $_GET['period'] ?? '24h';
        $data = $this->energyService->getChartData($userId, $period);

        echo json_encode(['status' => 'success', 'data' => $data, 'period' => $period]);
    }

    /**
     * GET /api/stats
     * Obtener estadísticas de consumo
     */
    public function getStats() {
        header('Content-Type: application/json');

        if (!AuthService::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            return;
        }

        $userId = AuthService::getUserId();
        $stats = $this->energyService->getConsumptionStats($userId);

        echo json_encode(['status' => 'success', 'data' => $stats]);
    }

    /**
     * GET /api/realtime
     * Obtener últimas lecturas para actualización en vivo
     */
    public function getRealtime() {
        header('Content-Type: application/json');

        if (!AuthService::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            return;
        }

        $userId = AuthService::getUserId();
        $count = intval($_GET['count'] ?? 20);
        $count = min($count, 100); // Max 100

        $readings = $this->energyService->getRealtimeReadings($userId, $count);
        $device = $this->energyService->getDeviceStatus($userId);

        echo json_encode([
            'status' => 'success',
            'data' => $readings,
            'device' => $device
        ]);
    }

    /**
     * GET /api/device-status
     * Obtener estado del dispositivo
     */
    public function getDeviceStatus() {
        header('Content-Type: application/json');

        if (!AuthService::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            return;
        }

        $userId = AuthService::getUserId();
        $device = $this->energyService->getDeviceStatus($userId);

        echo json_encode(['status' => 'success', 'data' => $device]);
    }

    /**
     * GET /api/reports
     * Obtener reportes históricos
     */
    public function getReports() {
        header('Content-Type: application/json');

        if (!AuthService::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            return;
        }

        $userId = AuthService::getUserId();
        $startDate = $_GET['start'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        $endDate = $_GET['end'] ?? date('Y-m-d H:i:s');

        $reports = $this->energyService->getHistoricalReports($userId, $startDate, $endDate);

        echo json_encode(['status' => 'success', 'data' => $reports]);
    }

    /**
     * GET /api/relay-status
     * Obtener el estado configurado del relay por el usuario para el dispositivo (Requiere API Key)
     */
    public function getRelayConfig() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            return;
        }

        // Obtener API key del header o query param
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

        if (!$apiKey) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'API key requerida']);
            return;
        }

        $relayStatus = $this->energyService->getRelayConfig($apiKey);

        if ($relayStatus) {
            echo json_encode(['status' => 'success', 'relay' => $relayStatus]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'API key inválida']);
        }
    }
}