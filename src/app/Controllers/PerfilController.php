<?php
// src/app/Controllers/PerfilController.php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Models/User.php';

class PerfilController {

    public function index() {
        AuthService::requireLogin();
        $userId = AuthService::getUserId();
        
        // Obtener usuario completo
        $user = User::findById($userId);

        include __DIR__ . '/../Views/perfil.php';
    }

    public function update() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = AuthService::getUserId();

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
        ];

        User::updateProfile($userId, $data);
        AuthService::refreshSession();

        $_SESSION['success'] = 'Perfil actualizado correctamente';
        header("Location: " . BASE_URL . "/perfil");
        exit;
    }

    public function changePassword() {
        AuthService::requireLogin();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = AuthService::getUserId();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!User::verifyPassword($userId, $currentPassword)) {
            $_SESSION['error'] = 'La contraseña actual es incorrecta';
            header("Location: " . BASE_URL . "/perfil");
            exit;
        }

        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = 'La nueva contraseña debe tener al menos 6 caracteres';
            header("Location: " . BASE_URL . "/perfil");
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            header("Location: " . BASE_URL . "/perfil");
            exit;
        }

        User::changePassword($userId, $newPassword);
        $_SESSION['success'] = 'Contraseña actualizada correctamente';
        header("Location: " . BASE_URL . "/perfil");
        exit;
    }
}
