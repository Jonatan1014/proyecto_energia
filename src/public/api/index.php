<?php
/**
 * API - Sistema de Gestión de Dispositivos
 * Documentación de endpoints disponibles
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'api'       => 'DeviceManager API',
    'version'   => '1.0',
    'endpoints' => [
        [
            'method'      => 'POST',
            'url'         => '/api/dispositivo/registrar.php',
            'description' => 'Registrar o consultar un dispositivo. Si no existe lo crea; si ya existe devuelve su info.',
            'body'        => [
                'deviceId'   => '(string, requerido) ID único del dispositivo',
                'deviceName' => '(string, opcional) Nombre del dispositivo',
                'platform'   => '(string, opcional) android | ios | web',
            ],
            'responses' => [
                'assigned' => ['status' => 'success', 'targetUrl' => '...', 'deviceNameAssigned' => '...'],
                'pending'  => ['status' => 'pending', 'message' => '...', 'deviceId' => '...'],
            ],
        ],
        [
            'method'      => 'GET',
            'url'         => '/api/dispositivo/verificar.php',
            'description' => 'Verificar el estado actual de un dispositivo.',
            'params'      => [
                'device_id' => '(string) ID del dispositivo',
                'token'     => '(string) Token del dispositivo (alternativo)',
            ],
        ],
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
