<?php
// src/config/database.php

require_once __DIR__ . '/config.php';

class Database {
    private static $connection = null;

    public static function getConnection(): \PDO {
        if (!self::$connection) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db_name = $_ENV['DB_NAME'] ?? 'energia_db';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';

            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
                
                self::$connection = new PDO(
                    $dsn,
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                
                // Configurar zona horaria de MySQL para Colombia
                self::$connection->exec("SET time_zone = '-05:00'");
            } catch (PDOException $e) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
            }
        }
        return self::$connection;
    }
}