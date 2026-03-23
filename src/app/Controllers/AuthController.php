<?php
// src/app/Controllers/AuthController.php

require_once __DIR__ . '/../Services/AuthService.php';

class AuthController {

    public function handleLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            if (AuthService::isLoggedIn()) {
                header("Location: " . BASE_URL . "/dashboard");
                exit;
            }
            include __DIR__ . '/../Views/pages-login.php';
        } elseif ($method === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            if (empty($email) || empty($password)) {
                $_SESSION['error'] = 'Por favor completa todos los campos';
                header("Location: " . BASE_URL . "/login");
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'El formato del correo electrónico no es válido';
                header("Location: " . BASE_URL . "/login");
                exit;
            }

            $result = AuthService::login($email, $password, $remember);

            if ($result['success']) {
                $user = $_SESSION['user'];
                $_SESSION['success'] = "¡Bienvenido, {$user['nombre']}!";
                header("Location: " . BASE_URL . "/dashboard");
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
                header("Location: " . BASE_URL . "/login");
                exit;
            }
        }
    }

    public function handleRegister() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            if (AuthService::isLoggedIn()) {
                header("Location: " . BASE_URL . "/dashboard");
                exit;
            }
            include __DIR__ . '/../Views/pages-register.php';
        } elseif ($method === 'POST') {
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'edad' => intval($_POST['edad'] ?? 0),
            ];

            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($data['password'] !== $confirmPassword) {
                $_SESSION['error'] = 'Las contraseñas no coinciden';
                $_SESSION['form_data'] = $data;
                header("Location: " . BASE_URL . "/register");
                exit;
            }

            $result = AuthService::register($data);

            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                header("Location: " . BASE_URL . "/login");
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
                $_SESSION['form_data'] = $data;
                header("Location: " . BASE_URL . "/register");
                exit;
            }
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $nombre = $_SESSION['user']['nombre'] ?? 'Usuario';
        
        AuthService::logout();
        
        $_SESSION['success'] = "Hasta pronto, {$nombre}. Has cerrado sesión correctamente.";
        header("Location: " . BASE_URL . "/login");
        exit;
    }
}