<?php
// src/app/Controllers/DashboardController.php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/AlertaService.php';
require_once __DIR__ . '/../Models/Dashboard.php';
require_once __DIR__ . '/../Models/Alerta.php';
require_once __DIR__ . '/../Models/Cuenta.php';
require_once __DIR__ . '/../Services/AdvisorService.php';

class DashboardController {

    public function index() {
        AuthService::requireLogin();
        $userId = AuthService::getUserId();

        // Generar alertas automáticas
        AlertaService::checkAlerts($userId);

        $stats = Dashboard::getStats($userId);
        $alertasNoLeidas = Alerta::countNoLeidas($userId);
        // Obtener usuario fresco de la BD para tener datos actualizados (presupuesto, ahorros, etc)
        $user = User::findById($userId);
        
        // Asesor Inteligente
        $presupuesto = AdvisorService::getPresupuesto($userId);
        
        // Cuentas Bancarias y Ahorro
        $cuentas = Cuenta::getAllForUser($userId);
        $resumenCuentas = Cuenta::getBalancesresumen($userId);

        include __DIR__ . '/../Views/dashboard.php';
    }
}
