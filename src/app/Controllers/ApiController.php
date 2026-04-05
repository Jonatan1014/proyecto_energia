<?php
// src/app/Controllers/ApiController.php

require_once __DIR__ . '/../Services/EnergyService.php';

class ApiController {
    private $energyService;

    public function __construct() {
        $this->energyService = new EnergyService();
    }

    public function getData($request, $response) {
        $data = $this->energyService->getRealTimeData();
        if ($data) {
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', CONTENT_TYPE_JSON);
        } else {
            $response->getBody()->write(json_encode(['error' => 'No data available']));
            return $response->withStatus(404)->withHeader('Content-Type', CONTENT_TYPE_JSON);
        }
    }

    public function saveData($request, $response) {
        // Autenticación requerida para APIs críticas
        if (!AuthService::isLoggedIn()) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'No autorizado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $data = json_decode($request->getBody(), true);
        if (isset($data['voltage'], $data['current'], $data['power'], $data['energy'])) {
            // Validación básica
            $voltage = floatval($data['voltage']);
            $current = floatval($data['current']);
            $power = floatval($data['power']);
            $energy = floatval($data['energy']);

            if ($voltage < 0 || $voltage > 500 || $current < 0 || $power < 0 || $energy < 0) {
                $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Datos inválidos']));
            return $response->withStatus(400)->withHeader('Content-Type', CONTENT_TYPE_JSON);
            }

            $success = $this->energyService->saveData($voltage, $current, $power, $energy);
            if ($success) {
                $response->getBody()->write(json_encode(['status' => 'success']));
                return $response->withHeader('Content-Type', CONTENT_TYPE_JSON);
            }
        }
        $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Datos faltantes']));
        return $response->withStatus(400)->withHeader('Content-Type', CONTENT_TYPE_JSON);
    }

    public function controlRelay($request, $response) {
        // Autenticación requerida
        if (!AuthService::isLoggedIn()) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'No autorizado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $data = json_decode($request->getBody(), true);
        $action = $data['action'] ?? 'off';
        $result = $this->energyService->controlRelay($action);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getReports($request, $response) {
        $params = $request->getQueryParams();
        $startDate = $params['start'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        $endDate = $params['end'] ?? date('Y-m-d H:i:s');
        $reports = $this->energyService->getHistoricalReports($startDate, $endDate);
        $response->getBody()->write(json_encode($reports));
        return $response->withHeader('Content-Type', 'application/json');
    }
}