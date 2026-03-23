<?php
/**
 * API Endpoint: Verificar estado de un dispositivo
 * URL: /carwash/src/public/api/dispositivo/verificar.php
 * Método: GET
 *
 * Parámetros (query string):
 *   device_id=abc123   ó   token=xxxx
 *
 * Response (asignado):
 * {
 *   "status": "success",
 *   "targetUrl": "https://example.com?device_token=xxx",
 *   "deviceNameAssigned": "Estación Norte 01",
 *   "estado": "activo",
 *   "negocio": { "nombre": "...", "url": "...", "imagen": "..." }
 * }
 *
 * Response (pendiente):
 * {
 *   "status": "pending",
 *   "message": "Dispositivo pendiente de asignación",
 *   "estado": "activo"
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Bootstrap
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

$deviceId = $_GET['device_id'] ?? null;
$token    = $_GET['token'] ?? null;

if (!$deviceId && !$token) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Se requiere device_id o token como parámetro GET.',
        'example' => '?device_id=abc123  o  ?token=xxx'
    ]);
    exit;
}

try {
    $db = Database::getConnection();

    if ($deviceId) {
        $stmt = $db->prepare("
            SELECT d.*, n.nombre AS negocio_nombre, n.url AS negocio_url, n.imagen AS negocio_imagen
            FROM dispositivos d
            LEFT JOIN negocios n ON d.negocio_id = n.id
            WHERE d.device_id = ?
        ");
        $stmt->execute([trim($deviceId)]);
    } else {
        $stmt = $db->prepare("
            SELECT d.*, n.nombre AS negocio_nombre, n.url AS negocio_url, n.imagen AS negocio_imagen
            FROM dispositivos d
            LEFT JOIN negocios n ON d.negocio_id = n.id
            WHERE d.device_token = ?
        ");
        $stmt->execute([trim($token)]);
    }

    $dispositivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dispositivo) {
        http_response_code(404);
        echo json_encode(['status' => 'not_found', 'message' => 'Dispositivo no encontrado.']);
        exit;
    }

    // Actualizar último acceso
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $db->prepare("UPDATE dispositivos SET ultimo_acceso = NOW(), ip_ultimo_acceso = ? WHERE id = ?")
       ->execute([$ip, $dispositivo['id']]);

    if ($dispositivo['negocio_id']) {
        $targetUrl = $dispositivo['negocio_url'];
        if (!empty($dispositivo['device_token'])) {
            $sep = (strpos($targetUrl, '?') !== false) ? '&' : '?';
            $targetUrl .= $sep . 'device_token=' . $dispositivo['device_token'];
        }

        echo json_encode([
            'status'             => 'success',
            'targetUrl'          => $targetUrl,
            'deviceNameAssigned' => $dispositivo['nombre_asignado'] ?? $dispositivo['negocio_nombre'],
            'estado'             => $dispositivo['estado'],
            'negocio' => [
                'nombre' => $dispositivo['negocio_nombre'],
                'url'    => $dispositivo['negocio_url'],
                'imagen' => $dispositivo['negocio_imagen'],
            ],
            'device' => [
                'id'           => $dispositivo['device_id'],
                'token'        => $dispositivo['device_token'],
                'registeredAt' => $dispositivo['created_at'],
            ],
        ]);
    } else {
        echo json_encode([
            'status'  => 'pending',
            'message' => 'Dispositivo pendiente de asignación.',
            'estado'  => $dispositivo['estado'],
            'device' => [
                'id'           => $dispositivo['device_id'],
                'token'        => $dispositivo['device_token'],
                'registeredAt' => $dispositivo['created_at'],
            ],
        ]);
    }

} catch (Exception $e) {
    error_log('API dispositivo/verificar error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor.']);
}
