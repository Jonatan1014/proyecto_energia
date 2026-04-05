<?php
// src/app/Controllers/PerfilController.php

require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Alerta.php';

class PerfilController {

    public function index() {
        AuthService::requireLogin();
        $userId = AuthService::getUserId();
        $alertasNoLeidas = Alerta::countNoLeidas($userId);
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
            'telefono' => trim($_POST['telefono'] ?? ''),
            'edad' => intval($_POST['edad'] ?? 0),
            'moneda' => $_POST['moneda'] ?? 'COP',
            'ingreso_mensual' => floatval(str_replace(['.', ','], ['', '.'], $_POST['ingreso_mensual'] ?? 0)),
            'dia_pago' => intval($_POST['dia_pago'] ?? 1),
            'porcentaje_ahorro' => intval($_POST['porcentaje_ahorro'] ?? 20),
        ];

        // Manejar foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $filepath)) {
                User::updateFoto($userId, 'uploads/profiles/' . $filename);
            }
        }

        User::updateProfile($userId, $data);
        AuthService::refreshSession();

        $_SESSION['success'] = 'Perfil actualizado correctamente';
        header("Location: " . BASE_URL . "perfil");
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
            header("Location: " . BASE_URL . "perfil");
            exit;
        }

        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = 'La nueva contraseña debe tener al menos 6 caracteres';
            header("Location: " . BASE_URL . "perfil");
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            header("Location: " . BASE_URL . "perfil");
            exit;
        }

        User::changePassword($userId, $newPassword);
        $_SESSION['success'] = 'Contraseña actualizada correctamente';
        header("Location: " . BASE_URL . "perfil");
        exit;
    }
}
