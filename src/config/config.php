<?php
// src/config/config.php

// Configurar zona horaria para Colombia
date_default_timezone_set('America/Bogota');

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Eliminar comillas si existen
            if (preg_match('/^([\'\"])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            if (!array_key_exists($key, $_ENV)) {
                putenv(sprintf('%s=%s', $key, $value));
                $_ENV[$key] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/../../.env');

// Constantes de la app
define('DEFAULT_CURRENCY', $_ENV['APP_CURRENCY'] ?? 'COP');
define('CURRENCY_SYMBOL', '$');