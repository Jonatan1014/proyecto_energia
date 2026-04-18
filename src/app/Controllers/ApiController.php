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
     * Obtener un header de forma robusta (maneja diferentes servidores)
     */
    private function getHeader($name) {
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        if (isset($_SERVER[$headerName])) {
            return $_SERVER[$headerName];
        }
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) === 0) {
                    return $value;
                }
            }
        }
        return null;
    }

    /**
     * POST /api/save
     * Recibir datos del ESP32 (autenticado por Hardware ID / MAC)
     */
    public function saveData() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            return;
        }

        // Obtener Hardware ID del header de forma robusta
        $hardwareId = $this->getHeader('X-HARDWARE-ID');
        
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (!$data) {
            $data = $_POST;
        }

        if (!$hardwareId && isset($data['hardware_id'])) {
            $hardwareId = $data['hardware_id'];
        }

        if (!$hardwareId) {
            error_log("DEBUG: Hardware ID missing. Headers: " . json_encode($this->getHeader('X-HARDWARE-ID')));
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Hardware ID requerido']);
            return;
        }

        error_log("DEBUG: Processing hardwareId: $hardwareId");
        $result = $this->energyService->saveReading($hardwareId, $data);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
        }
    }

    /**
     * GET /api/relay-status
     * Obtener el estado configurado del relay por hardware_id
     */
    public function getRelayConfig() {
        header('Content-Type: application/json');

        // Obtener Hardware ID (Header o Parámetro)
        $hardwareId = $this->getHeader('X-HARDWARE-ID') ?? $_GET['hardware_id'] ?? null;

        if (!$hardwareId) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Hardware ID requerido']);
            return;
        }

        require_once __DIR__ . '/../Models/DeviceConfig.php';
        $model = new DeviceConfig();
        $device = $model->findOrCreateByHardwareId($hardwareId);

        if ($device) {
            echo json_encode(['status' => 'success', 'relay' => $device['relay_default']]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Dispositivo no encontrado']);
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

}