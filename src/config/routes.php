<?php
// src/config/routes.php
// Monitor de Energía IoT - Rutas

return [
    // ========================================
    // AUTENTICACIÓN
    // ========================================
    '/'              => ['controller' => 'AuthController',      'action' => 'handleLogin'],
    '/login'         => ['controller' => 'AuthController',      'action' => 'handleLogin'],
    '/register'      => ['controller' => 'AuthController',      'action' => 'handleRegister'],
    '/logout'        => ['controller' => 'AuthController',      'action' => 'logout'],

    // ========================================
    // DASHBOARD PRINCIPAL
    // ========================================
    '/dashboard'     => ['controller' => 'DashboardController', 'action' => 'index'],
    '/reports'       => ['controller' => 'ReportController',    'action' => 'index'],

    // ========================================
    // CONFIGURACIONES (Tarifas + Dispositivo)
    // ========================================
    '/settings'             => ['controller' => 'TariffController',   'action' => 'index'],
    '/tariffs/create'       => ['controller' => 'TariffController',   'action' => 'create'],
    '/tariffs/update'       => ['controller' => 'TariffController',   'action' => 'update'],
    '/tariffs/delete'       => ['controller' => 'TariffController',   'action' => 'delete'],
    '/settings/device'      => ['controller' => 'SettingsController', 'action' => 'updateDevice'],
    '/settings/regenerate-key' => ['controller' => 'SettingsController', 'action' => 'regenerateKey'],
    '/settings/claim-device' => ['controller' => 'SettingsController', 'action' => 'claimDevice'],
    '/settings/link-device'  => ['controller' => 'SettingsController', 'action' => 'linkDevice'],
    '/settings/unlink-device'=> ['controller' => 'SettingsController', 'action' => 'unlinkDevice'],

    // ========================================
    // PERFIL
    // ========================================
    '/perfil'              => ['controller' => 'PerfilController',    'action' => 'index'],
    '/perfil/update'       => ['controller' => 'PerfilController',    'action' => 'update'],
    '/perfil/changePassword' => ['controller' => 'PerfilController',  'action' => 'changePassword'],

    // ========================================
    // API ENDPOINTS (ESP32 + Dashboard AJAX)
    // ========================================
    '/api/save'           => ['controller' => 'ApiController',  'action' => 'saveData'],
    '/api/data'           => ['controller' => 'ApiController',  'action' => 'getData'],
    '/api/chart-data'     => ['controller' => 'ApiController',  'action' => 'getChartData'],
    '/api/stats'          => ['controller' => 'ApiController',  'action' => 'getStats'],
    '/api/realtime'       => ['controller' => 'ApiController',  'action' => 'getRealtime'],
    '/api/device-status'  => ['controller' => 'ApiController',  'action' => 'getDeviceStatus'],
    '/api/reports'        => ['controller' => 'ApiController',  'action' => 'getReports'],
    '/api/relay-status'   => ['controller' => 'ApiController',  'action' => 'getRelayConfig'],
];