<?php
// src/app/Services/AuthService.php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../../config/database.php';

class AuthService {

    /**
     * Iniciar sesión
     */
    public static function login($email, $password, $remember = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $user = User::authenticate($email, $password);

            if ($user) {
                $_SESSION['user'] = [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'email' => $user->email,
                    'foto' => $user->foto,
                    'moneda' => $user->moneda,
                    'ingreso_mensual' => $user->ingreso_mensual,
                    'dia_pago' => $user->dia_pago,
                    'porcentaje_ahorro' => $user->porcentaje_ahorro,
                    'is_active' => $user->is_active,
                    'login_time' => time(),
                ];

                if ($remember) {
                    $cookieParams = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        session_id(),
                        time() + (30 * 24 * 60 * 60),
                        $cookieParams['path'],
                        $cookieParams['domain'],
                        $cookieParams['secure'],
                        $cookieParams['httponly']
                    );
                }

                self::logUserAccess($user->id);

                return ['success' => true, 'message' => 'Inicio de sesión exitoso'];
            } else {
                return ['success' => false, 'message' => 'Correo electrónico o contraseña incorrectos'];
            }
        } catch (Exception $e) {
            error_log('Error en login: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar la solicitud. Por favor intenta de nuevo.'];
        }
    }

    /**
     * Registrar nuevo usuario
     */
    public static function register($data) {
        try {
            // Verificar que el email no exista
            if (User::emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Este correo electrónico ya está registrado'];
            }

            // Validar campos requeridos
            if (empty($data['nombre']) || empty($data['apellido']) || empty($data['email']) || empty($data['password'])) {
                return ['success' => false, 'message' => 'Todos los campos marcados son obligatorios'];
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'El formato del correo electrónico no es válido'];
            }

            if (strlen($data['password']) < 6) {
                return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
            }

            // Crear usuario
            $userId = User::register($data);

            if ($userId) {
                return ['success' => true, 'message' => 'Registro exitoso. Ahora puedes iniciar sesión.', 'user_id' => $userId];
            }

            return ['success' => false, 'message' => 'Error al crear la cuenta'];
        } catch (Exception $e) {
            error_log('Error en registro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al procesar el registro'];
        }
    }

    /**
     * Registrar acceso del usuario
     */
    private static function logUserAccess($userId) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE usuarios SET last_login = NOW(), ip_ultimo_acceso = ? WHERE id = ?");
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $userId]);
        } catch (Exception $e) {
            error_log('Error logging user access: ' . $e->getMessage());
        }
    }

    /**
     * Cerrar sesión
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $message = $_SESSION['success'] ?? null;
        unset($_SESSION['user']);
        session_destroy();
        session_start();
        if ($message) {
            $_SESSION['success'] = $message;
        }
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    /**
     * Obtener el ID del usuario actual
     */
    public static function getUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Obtener todos los datos del usuario actual
     */
    public static function getUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Requerir autenticación (y verificar que el usuario exista en la DB)
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['error'] = 'Debes iniciar sesión para acceder a esta página';
            header("Location: " . BASE_URL . "/login");
            exit;
        }

        // Verificar si el usuario aún existe en la base de datos (evita Zombie Sessions tras un reset de DB)
        $user = User::findById(self::getUserId());
        if (!$user) {
            self::logout();
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = 'Tu sesión ha expirado o el usuario ya no existe.';
            header("Location: " . BASE_URL . "/login");
            exit;
        }
    }

    /**
     * Refrescar datos de sesión desde DB
     */
    public static function refreshSession() {
        if (!self::isLoggedIn()) return;
        $userId = self::getUserId();
        $userData = User::findById($userId);
        if ($userData) {
            $_SESSION['user']['nombre'] = $userData['nombre'];
            $_SESSION['user']['apellido'] = $userData['apellido'];
            $_SESSION['user']['foto'] = $userData['foto'];
            $_SESSION['user']['moneda'] = $userData['moneda'];
        }
    }
}