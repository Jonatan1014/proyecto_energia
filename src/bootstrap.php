<?php
// src/bootstrap.php

if (file_exists('/var/www/html/src')) {
    // Entorno Docker/Producción
    define('BASE_PATH', '/var/www/html/src');
    define('BASE_URL', '/');
    define('ASSETS_URL', '/assets');
} else {
    // Entorno local: construir la base a partir del script actual para evitar hardcodes.
    define('BASE_PATH', __DIR__);
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if ($scriptDir === '.' || $scriptDir === '/') {
        $scriptDir = '';
    }
    define('BASE_URL', ($scriptDir === '' ? '/' : $scriptDir . '/'));
    define('ASSETS_URL', BASE_URL . 'assets');
}

define('CONFIG_PATH', BASE_PATH . '/config');
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Nombre de la aplicación
define('APP_NAME', 'AlcanciaApp');
define('APP_VERSION', '1.0');

// Incluir funciones helper globales
require_once BASE_PATH . '/functions.php';

// Incluir constantes
require_once CONFIG_PATH . '/constants.php';