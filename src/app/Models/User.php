<?php
// src/app/Models/User.php

require_once __DIR__ . '/../../config/database.php';

class User {
    public $id;
    public $nombre;
    public $apellido;
    public $telefono;
    public $email;
    public $edad;
    public $foto;
    public $moneda;
    public $ingreso_mensual;
    public $dia_pago;
    public $porcentaje_ahorro;
    public $is_active;

    public function __construct($data) {
        $this->id = $data['id'];
        $this->nombre = $data['nombre'];
        $this->apellido = $data['apellido'];
        $this->telefono = $data['telefono'] ?? null;
        $this->email = $data['email'];
        $this->edad = $data['edad'] ?? null;
        $this->foto = $data['foto'] ?? null;
        $this->moneda = $data['moneda'] ?? 'COP';
        $this->ingreso_mensual = $data['ingreso_mensual'] ?? 0;
        $this->dia_pago = $data['dia_pago'] ?? 1;
        $this->porcentaje_ahorro = $data['porcentaje_ahorro'] ?? 20;
        $this->is_active = $data['is_active'] ?? 1;
    }

    /**
     * Obtener nombre completo
     */
    public function getNombreCompleto() {
        return $this->nombre . ' ' . $this->apellido;
    }

    /**
     * Autenticar usuario por email y contraseña
     */
    public static function authenticate($email, $password) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) return false;
        if ($userData['is_active'] != 1) return false;
        if (password_verify($password, $userData['password'])) {
            return new User($userData);
        }
        return false;
    }

    /**
     * Registrar un nuevo usuario
     */
    public static function register($data) {
        $db = Database::getConnection();
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO usuarios (nombre, apellido, telefono, email, password, edad, moneda)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['telefono'] ?? null,
            $data['email'],
            $hashedPassword,
            $data['edad'] ?? null,
            $data['moneda'] ?? 'COP'
        ]);

        return $db->lastInsertId();
    }

    /**
     * Buscar usuario por ID
     */
    public static function findById($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar usuario por email
     */
    public static function findByEmail($email) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar perfil del usuario
     */
    public static function updateProfile($id, $data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?, edad = ?, moneda = ?, ingreso_mensual = ?, dia_pago = ?, porcentaje_ahorro = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['telefono'] ?? null,
            $data['edad'] ?? null,
            $data['moneda'] ?? 'COP',
            $data['ingreso_mensual'] ?? 0,
            $data['dia_pago'] ?? 1,
            $data['porcentaje_ahorro'] ?? 20,
            $id
        ]);
    }

    /**
     * Actualizar foto de perfil
     */
    public static function updateFoto($id, $foto) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
        return $stmt->execute([$foto, $id]);
    }

    /**
     * Cambiar contraseña
     */
    public static function changePassword($id, $newPassword) {
        $db = Database::getConnection();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    /**
     * Verificar si un email ya existe
     */
    public static function emailExists($email, $excludeId = null) {
        $db = Database::getConnection();
        if ($excludeId) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
        }
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verificar contraseña actual
     */
    public static function verifyPassword($id, $password) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return password_verify($password, $user['password']);
        }
        return false;
    }
}