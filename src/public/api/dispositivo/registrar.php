<?php
/**
 * API Endpoint: Registrar / Consultar Dispositivo
 * URL: /carwash/src/public/api/dispositivo/registrar.php
 * Método: POST
 *
 * Request JSON:
 * {
 *   "deviceId": "abc123-unique-id",
 *   "deviceName": "Samsung Galaxy S24",
 *   "platform": "android"
 * }
 *
 * Response (asignado):
 * {
 *   "status": "success",
 *   "targetUrl": "https://example.com?device_token=xxx",
 *   "deviceNameAssigned": "Estación Norte 01",
 *   "negocio": { "nombre": "...", "imagen": "..." }
 * }
 *
 * Response (pendiente):
 * {
 *   "status": "pending",
 *   "message": "Dispositivo registrado. Pendiente de asignación.",
 *   "deviceId": "abc123-unique-id",
 *   "registeredAt": "2026-03-04 12:00:00"
 * }
 */

// CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Use POST.']);
    exit;
}

// Bootstrap
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

// Leer JSON body
$json = file_get_contents('php://input');
$body = json_decode($json, true);

if (!$body || empty($body['deviceId'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Se requiere deviceId en el body JSON.',
        'example' => [
            'deviceId' => 'unique-device-id',
            'deviceName' => 'Mi Dispositivo',
            'platform' => 'android'
        ]
    ]);
    exit;
}

$deviceId   = trim($body['deviceId']);
$deviceName = trim($body['deviceName'] ?? '');
$platform   = trim($body['platform'] ?? '');
$ip         = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $db = Database::getConnection();

    // ---- Buscar si ya existe ----
    $stmt = $db->prepare("
        SELECT d.*, n.nombre AS negocio_nombre, n.url AS negocio_url, n.imagen AS negocio_imagen
        FROM dispositivos d
        LEFT JOIN negocios n ON d.negocio_id = n.id
        WHERE d.device_id = ?
    ");
    $stmt->execute([$deviceId]);
    $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dispositivo) {
        // ---- YA EXISTE → Actualizar último acceso ----
        $upd = $db->prepare("
            UPDATE dispositivos SET
                nombre_dispositivo = COALESCE(?, nombre_dispositivo),
                plataforma = COALESCE(?, plataforma),
                ultimo_acceso = NOW(),
                ip_ultimo_acceso = ?
            WHERE id = ?
        ");
        $upd->execute([
            $deviceName ?: null,
            $platform   ?: null,
            $ip,
            $dispositivo['id']
        ]);
    } else {
        // ---- NO EXISTE → Registrar nuevo ----
        $token = bin2hex(random_bytes(32));
        $ins = $db->prepare("
            INSERT INTO dispositivos
                (device_id, nombre_dispositivo, plataforma, ip_registro, user_agent, ultimo_acceso, ip_ultimo_acceso, device_token)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
        ");
        $ins->execute([
            $deviceId,
            $deviceName ?: null,
            $platform   ?: null,
            $ip,
            $userAgent,
            $ip,
            $token
        ]);

        // Releer el registro completo
        $stmt->execute([$deviceId]);
        $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ---- Construir respuesta ----
    if ($dispositivo && $dispositivo['negocio_id']) {
        // Dispositivo asignado a un negocio
        $targetUrl = $dispositivo['negocio_url'];

        // Agregar token al URL
        if (!empty($dispositivo['device_token'])) {
            $sep = (strpos($targetUrl, '?') !== false) ? '&' : '?';
            $targetUrl .= $sep . 'device_token=' . $dispositivo['device_token'];
        }

        $respuesta = [
            'status'             => 'success',
            'targetUrl'          => $targetUrl,
            'deviceNameAssigned' => $dispositivo['nombre_asignado'] ?? $dispositivo['negocio_nombre'],
            'negocio' => [
                'nombre' => $dispositivo['negocio_nombre'],
                'imagen' => $dispositivo['negocio_imagen'],
            ],
            'device' => [
                'id'           => $dispositivo['device_id'],
                'token'        => $dispositivo['device_token'],
                'estado'       => $dispositivo['estado'],
                'registeredAt' => $dispositivo['created_at'],
            ],
        ];
    } else {
        // Registrado pero sin asignar
        $respuesta = [
            'status'       => 'pending',
            'message'      => 'Dispositivo registrado. Pendiente de asignación por el administrador.',
            'deviceId'     => $deviceId,
            'token'        => $dispositivo['device_token'] ?? null,
            'registeredAt' => $dispositivo['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    // ---- Guardar log ----
    $log = $db->prepare("
        INSERT INTO dispositivos_log (dispositivo_id, device_id_raw, ip, user_agent, payload, respuesta)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $log->execute([
        $dispositivo['id'] ?? null,
        $deviceId,
        $ip,
        $userAgent,
        $json,
        json_encode($respuesta)
    ]);

    http_response_code(200);
    echo json_encode($respuesta);

} catch (Exception $e) {
    error_log('API dispositivo/registrar error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor.']);
}
