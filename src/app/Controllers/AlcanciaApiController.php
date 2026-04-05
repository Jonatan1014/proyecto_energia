<?php
// src/app/Controllers/AlcanciaApiController.php

require_once __DIR__ . '/../Models/Alcancia.php';

class AlcanciaApiController {
    private Alcancia $alcanciaModel;

    public function __construct() {
        $this->alcanciaModel = new Alcancia();
    }

    public function registrarDeposito(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metodo no permitido'], 405);
            return;
        }

        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '{}', true);

        if (!is_array($payload)) {
            $this->jsonResponse(['error' => 'JSON invalido'], 400);
            return;
        }

        try {
            $estado = $this->alcanciaModel->registrarDeposito($payload);
            $this->jsonResponse([
                'ok' => true,
                'message' => 'Deposito registrado correctamente',
                'data' => $estado,
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('Error en registrarDeposito: ' . $e->getMessage());
            $this->jsonResponse(['ok' => false, 'error' => 'Error interno al registrar deposito'], 500);
        }
    }

    public function obtenerEstado(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Metodo no permitido'], 405);
            return;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        try {
            $estado = $this->alcanciaModel->getEstado($limit);
            $this->jsonResponse([
                'ok' => true,
                'data' => $estado,
            ], 200);
        } catch (Throwable $e) {
            error_log('Error en obtenerEstado: ' . $e->getMessage());
            $this->jsonResponse(['ok' => false, 'error' => 'Error interno al consultar estado'], 500);
        }
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
