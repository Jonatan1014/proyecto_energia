<?php
// src/app/Controllers/DashboardController.php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Models/Alcancia.php';

class DashboardController {

    public function index() {
        AuthService::requireLogin();

        $user = AuthService::getUser() ?: [];
        $alcanciaModel = new Alcancia();
        $estado = $alcanciaModel->getEstado(10);

        include_once __DIR__ . '/../Views/dashboard.php';
    }
}

