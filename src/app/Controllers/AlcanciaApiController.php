<?php
// src/app/Controllers/AlcanciaApiController.php

require_once __DIR__ . '/../Models/Alcancia.php';
require_once __DIR__ . '/../Services/AuthService.php';

class AlcanciaApiController {
    private const MSG_METODO_NO_PERMITIDO = 'Metodo no permitido';
    private const MSG_JSON_INVALIDO = 'JSON invalido';
    private const MSG_NO_AUTORIZADO = 'No autorizado';
    private const INPUT_STREAM = 'php://input';

    private Alcancia $alcanciaModel;

    public function __construct() {
        $this->alcanciaModel = new Alcancia();
    }

    public function registrarDeposito(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        $rawBody = file_get_contents(self::INPUT_STREAM);
        $payload = json_decode($rawBody ?: '{}', true);

        if (!is_array($payload)) {
            $this->jsonResponse(['error' => self::MSG_JSON_INVALIDO], 400);
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

    public function enviarComando(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        if (!AuthService::isLoggedIn()) {
            $this->jsonResponse(['error' => self::MSG_NO_AUTORIZADO], 401);
            return;
        }

        $payload = json_decode(file_get_contents(self::INPUT_STREAM) ?: '{}', true);
        if (!is_array($payload)) {
            $this->jsonResponse(['error' => self::MSG_JSON_INVALIDO], 400);
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

        if ($accion === 'sync_state') {
            $eventPayload['datos'] = $this->alcanciaModel->getEstadoDispositivo();
        }

        $this->jsonResponse([
            'ok' => true,
            'message' => 'Comando procesado correctamente',
            'data' => $eventPayload,
        ], 200);
    }

    public function actualizarMeta(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        if (!AuthService::isLoggedIn()) {
            $this->jsonResponse(['error' => self::MSG_NO_AUTORIZADO], 401);
            return;
        }

        $payload = json_decode(file_get_contents(self::INPUT_STREAM) ?: '{}', true);
        if (!is_array($payload)) {
            $this->jsonResponse(['error' => self::MSG_JSON_INVALIDO], 400);
            return;
        }

        $metaId = (int)($payload['meta_id'] ?? 0);
        $nombre = (string)($payload['nombre'] ?? '');
        $montoObjetivo = (float)($payload['monto_objetivo'] ?? 0);

        try {
            $estado = $this->alcanciaModel->actualizarMeta($metaId, $nombre, $montoObjetivo);

            $this->jsonResponse([
                'ok' => true,
                'message' => 'Meta actualizada correctamente',
                'data' => $estado,
            ], 200);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('Error en actualizarMeta: ' . $e->getMessage());
            $this->jsonResponse(['ok' => false, 'error' => 'Error interno al actualizar meta'], 500);
        }
    }

    public function iniciarSesionPersonal(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        if (!AuthService::isLoggedIn()) {
            $this->jsonResponse(['error' => self::MSG_NO_AUTORIZADO], 401);
            return;
        }

        $payload = json_decode(file_get_contents(self::INPUT_STREAM) ?: '{}', true);
        $segundos = (int)($payload['segundos'] ?? 0);
        $userId = AuthService::getUserId();

        try {
            $estado = $this->alcanciaModel->iniciarSesionPersonal($userId, $segundos);
            $this->jsonResponse(['ok' => true, 'message' => 'Sesion personal iniciada', 'data' => $estado]);
        } catch (Throwable $e) {
            $this->jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function crearMetaPersonal(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        if (!AuthService::isLoggedIn()) {
            $this->jsonResponse(['error' => self::MSG_NO_AUTORIZADO], 401);
            return;
        }

        $payload = json_decode(file_get_contents(self::INPUT_STREAM) ?: '{}', true);
        $nombre = (string)($payload['nombre'] ?? '');
        $monto = (float)($payload['monto'] ?? 0);
        $userId = AuthService::getUserId();

        try {
            $estado = $this->alcanciaModel->crearMetaPersonal($userId, $nombre, $monto);
            $this->jsonResponse(['ok' => true, 'message' => 'Meta personal creada', 'data' => $estado]);
        } catch (Throwable $e) {
            $this->jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function vaciar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => self::MSG_METODO_NO_PERMITIDO], 405);
            return;
        }

        if (!AuthService::isLoggedIn()) {
            $this->jsonResponse(['error' => self::MSG_NO_AUTORIZADO], 401);
            return;
        }

        $payload = json_decode(file_get_contents(self::INPUT_STREAM) ?: '{}', true);
        if (!is_array($payload)) {
            $this->jsonResponse(['error' => self::MSG_JSON_INVALIDO], 400);
            return;
        }

        $monto = isset($payload['monto']) && is_numeric($payload['monto']) ? (float)$payload['monto'] : null;
        $motivo = isset($payload['motivo']) ? (string)$payload['motivo'] : null;
        $user = AuthService::getUser() ?: [];
        $userId = isset($user['id']) ? (int)$user['id'] : null;
        $userName = trim((string)($user['nombre'] ?? 'Usuario'));

        try {
            $estado = $this->alcanciaModel->retirarDinero($userId, $userName, $monto, $motivo);

            $this->jsonResponse([
                'ok' => true,
                'message' => 'Dinero retirado correctamente',
                'data' => $estado,
            ], 200);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('Error en vaciar: ' . $e->getMessage());
            $this->jsonResponse(['ok' => false, 'error' => 'Error interno al vaciar alcancia'], 500);
        }
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
