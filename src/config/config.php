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

// Configuracion Soketi (protocolo Pusher)
define('SOKETI_APP_ID', $_ENV['SOKETI_APP_ID'] ?? 'alcancia-app');
define('SOKETI_APP_KEY', $_ENV['SOKETI_APP_KEY'] ?? 'alcancia-key');
define('SOKETI_APP_SECRET', $_ENV['SOKETI_APP_SECRET'] ?? 'alcancia-secret');
define('SOKETI_HOST', $_ENV['SOKETI_HOST'] ?? 'soketi');
define('SOKETI_PORT', (int)($_ENV['SOKETI_PORT'] ?? 6001));
define('SOKETI_SCHEME', $_ENV['SOKETI_SCHEME'] ?? 'http');

// Configuracion para clientes web (JS)
define('SOKETI_WS_HOST', $_ENV['SOKETI_WS_HOST'] ?? 'websocket.systemautomatic.xyz');
define('SOKETI_WS_PORT', (int)($_ENV['SOKETI_WS_PORT'] ?? 443));
define('SOKETI_FORCE_TLS', (($_ENV['SOKETI_FORCE_TLS'] ?? 'false') === 'true'));
define('SOKETI_METRICS_HOST', $_ENV['SOKETI_METRICS_HOST'] ?? 'metrics.systemautomatic.xyz');
define('SOKETI_METRICS_PORT', (int)($_ENV['SOKETI_METRICS_PORT'] ?? 9601));
