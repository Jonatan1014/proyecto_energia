<?php
// src/app/Controllers/AlcanciaApiController.php

require_once __DIR__ . '/../Models/Alcancia.php';
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/SoketiService.php';

class AlcanciaApiController {
    private const MSG_METODO_NO_PERMITIDO = 'Metodo no permitido';

    private Alcancia $alcanciaModel;
    private SoketiService $soketiService;

    public function __construct() {
        $this->alcanciaModel = new Alcancia();
        $this->soketiService = new SoketiService();
    }

    public function registrarDeposito(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
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

            // Publicar evento realtime para dashboards conectados por Soketi
            $this->soketiService->publish('private-alcancia.1', 'deposito.registrado', [
                'monto' => (float)($payload['monto'] ?? 0),
                'pulsos' => isset($payload['pulsos']) ? (int)$payload['pulsos'] : null,
                'origen' => (string)($payload['origen'] ?? 'esp32'),
                'estado' => $estado,
            ]);

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
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
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

    public function obtenerEstadoDispositivo(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        try {
            $deviceState = $this->alcanciaModel->getEstadoDispositivo();
            $this->jsonResponse([
                'ok' => true,
                'data' => $deviceState,
            ], 200);
        } catch (Throwable $e) {
            error_log('Error en obtenerEstadoDispositivo: ' . $e->getMessage());
            $this->jsonResponse(['ok' => false, 'error' => 'Error interno al consultar estado dispositivo'], 500);
        }
    }

    public function streamEstado(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        @set_time_limit(0);
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $maxIterations = 30;
        for ($i = 0; $i < $maxIterations; $i++) {
            if (connection_aborted()) {
                break;
            }

            try {
                $estado = $this->alcanciaModel->getEstado(10);
                echo 'event: estado' . "\n";
                echo 'data: ' . json_encode(['ok' => true, 'data' => $estado], JSON_UNESCAPED_UNICODE) . "\n\n";
            } catch (Throwable $e) {
                echo 'event: error' . "\n";
                echo 'data: ' . json_encode(['ok' => false, 'error' => 'Error consultando estado'], JSON_UNESCAPED_UNICODE) . "\n\n";
            }

            @ob_flush();
            @flush();
            sleep(2);
        }
    }

    public function wsAuth(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        if (!AuthService::isLoggedIn()) {
            $this->jsonResponse(['error' => 'No autorizado'], 401);
            return;
        }

        $socketId = $_POST['socket_id'] ?? '';
        $channelName = $_POST['channel_name'] ?? '';

        if ($socketId === '' || $channelName === '') {
            $this->jsonResponse(['error' => 'socket_id y channel_name son requeridos'], 422);
            return;
        }

        $user = AuthService::getUser() ?: [];
        $presence = null;
        if (strpos($channelName, 'presence-') === 0) {
            $presence = [
                'user_id' => (string)($user['id'] ?? 'anon'),
                'user_info' => [
                    'nombre' => (string)($user['nombre'] ?? 'Usuario'),
                ],
            ];
        }

        $authPayload = $this->soketiService->buildAuth($socketId, $channelName, $presence);
        $this->jsonResponse($authPayload, 200);
    }

    public function enviarComando(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        if (!AuthService::isLoggedIn()) {
            $this->jsonResponse(['error' => 'No autorizado'], 401);
            return;
        }

        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($payload)) {
            $this->jsonResponse(['error' => 'JSON invalido'], 400);
            return;
        }

        $accion = trim((string)($payload['accion'] ?? 'sync_state'));
        $datos = $payload['datos'] ?? [];
        if ($accion === '') {
            $accion = 'sync_state';
        }

        $sender = AuthService::getUser() ?: [];
        $eventPayload = [
            'accion' => $accion,
            'datos' => is_array($datos) ? $datos : [],
            'emitido_por' => [
                'id' => (int)($sender['id'] ?? 0),
                'nombre' => (string)($sender['nombre'] ?? 'Usuario'),
            ],
            'timestamp' => date('c'),
        ];

        $ok = $this->soketiService->publish('private-dispositivo.1', 'device.comando', $eventPayload);
        // Tambien reflejar en dashboards conectados.
        $this->soketiService->publish('private-alcancia.1', 'comando.emitido', $eventPayload);

        $this->jsonResponse([
            'ok' => $ok,
            'message' => $ok ? 'Comando enviado por Soketi' : 'No se pudo publicar en Soketi',
            'data' => $eventPayload,
        ], $ok ? 200 : 500);
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
