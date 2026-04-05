<?php
// src/app/Controllers/AlertaController.php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Models/Alerta.php';

class AlertaController {

    public function index() {
        AuthService::requireLogin();
        $userId = AuthService::getUserId();
        $user = AuthService::getUser();
        $alertasNoLeidas = Alerta::countNoLeidas($userId);
        $alertas = Alerta::getByUser($userId);

        include __DIR__ . '/../Views/alertas/index.php';
    }

    public function create() {
        AuthService::requireLogin();
        $userId = AuthService::getUserId();
        $user = AuthService::getUser();
        $alertasNoLeidas = Alerta::countNoLeidas($userId);

        include __DIR__ . '/../Views/alertas/crear.php';
    }

    public function store() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = AuthService::getUserId();

        $data = [
            'user_id' => $userId,
            'titulo' => trim($_POST['titulo'] ?? ''),
            'mensaje' => trim($_POST['mensaje'] ?? ''),
            'tipo' => $_POST['tipo'] ?? 'general',
            'fecha_alerta' => $_POST['fecha_alerta'] ?? date('Y-m-d'),
        ];

        Alerta::create($data);
        $_SESSION['success'] = 'Recordatorio creado correctamente';
        header("Location: " . BASE_URL . "alertas");
        exit;
    }

    public function markRead() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if ($id) {
            Alerta::markAsRead($id);
        }
        header("Location: " . BASE_URL . "alertas");
        exit;
    }

    public function markAllRead() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = AuthService::getUserId();
        Alerta::markAllAsRead($userId);
        $_SESSION['success'] = 'Todas las alertas marcadas como leídas';
        header("Location: " . BASE_URL . "alertas");
        exit;
    }

    public function delete() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if ($id) {
            Alerta::delete($id);
            $_SESSION['success'] = 'Alerta eliminada';
        }
        header("Location: " . BASE_URL . "alertas");
        exit;
    }
}
