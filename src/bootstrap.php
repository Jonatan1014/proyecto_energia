<?php
// src/bootstrap.php

if (file_exists('/var/www/html/src')) {
    // Entorno Docker/Producción
    define('BASE_PATH', '/var/www/html/src');
    define('BASE_URL', ''); // Las URLs serán relativas desde la raíz
    define('ASSETS_URL', '/assets'); // Los assets están en /src/public/assets pero se sirven desde /assets
} else {
    // Entorno desarrollo local
    define('BASE_PATH', dirname(__FILE__));
    define('BASE_URL', '/Finanzas/src/public');
    define('ASSETS_URL', '/Finanzas/src/public/assets');
}

define('CONFIG_PATH', BASE_PATH . '/config');
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Nombre de la aplicación
define('APP_NAME', 'FinanzApp');
define('APP_VERSION', '1.0');

// Incluir funciones helper globales
require_once BASE_PATH . '/functions.php';