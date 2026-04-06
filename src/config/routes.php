<?php
// src/config/routes.php
// FinanzApp - Gestor de Finanzas Personales

return [
    // ========================================
    // AUTENTICACIÓN
    // ========================================
    '/' => ['controller' => 'AuthController', 'action' => 'handleLogin'],
    '/login' => ['controller' => 'AuthController', 'action' => 'handleLogin'],
    '/register' => ['controller' => 'AuthController', 'action' => 'handleRegister'],
    '/logout' => ['controller' => 'AuthController', 'action' => 'logout'],


    // ========================================
    // ENERGÍA (Medidor IoT)
    // ========================================
    '/dashboard' => ['controller' => 'DashboardController', 'action' => 'index'],
    '/api/data' => ['controller' => 'ApiController', 'action' => 'getData'],
    '/api/save' => ['controller' => 'ApiController', 'action' => 'saveData'],
    '/api/relay' => ['controller' => 'ApiController', 'action' => 'controlRelay'],
    '/api/reports' => ['controller' => 'ApiController', 'action' => 'getReports'],
    '/tariffs' => ['controller' => 'TariffController', 'action' => 'index'],
    '/tariffs/create' => ['controller' => 'TariffController', 'action' => 'create'],
    '/tariffs/update' => ['controller' => 'TariffController', 'action' => 'update'],
    '/tariffs/delete' => ['controller' => 'TariffController', 'action' => 'delete'],
    '/reports' => ['controller' => 'ReportController', 'action' => 'index'],


    '/recurrente/guardar' => ['controller' => 'RecurrenteController', 'action' => 'store'],
    '/recurrente/editar' => ['controller' => 'RecurrenteController', 'action' => 'edit'],
    '/recurrente/actualizar' => ['controller' => 'RecurrenteController', 'action' => 'update'],
    '/recurrente/eliminar' => ['controller' => 'RecurrenteController', 'action' => 'delete'],
    '/recurrente/compartir' => ['controller' => 'RecurrenteController', 'action' => 'compartir'],
    '/recurrente/pagar' => ['controller' => 'RecurrenteController', 'action' => 'confirmarPago'],
    '/recurrente/procesar_pago' => ['controller' => 'RecurrenteController', 'action' => 'procesarPago'],

    // ========================================
    // PERFIL
    // ========================================
    '/perfil' => ['controller' => 'PerfilController', 'action' => 'index'],
    '/perfil/update' => ['controller' => 'PerfilController', 'action' => 'update'],
    '/perfil/changePassword' => ['controller' => 'PerfilController', 'action' => 'changePassword'],

];