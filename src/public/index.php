<?php
// src/public/index.php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$routes = include __DIR__ . '/../config/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Detectar base real desde el script para soportar /src y /src/public.
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$candidateBases = [];
if ($scriptDir !== '' && $scriptDir !== '.') {
    $candidateBases[] = $scriptDir;
    if (substr($scriptDir, -7) === '/public') {
        $candidateBases[] = substr($scriptDir, 0, -7);
    }
}

usort($candidateBases, function ($a, $b) {
    return strlen($b) - strlen($a);
});

foreach ($candidateBases as $base) {
    if ($base !== '' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
        break;
    }
}

// Asegurar que la URI comience con /
if (empty($uri)) {
    $uri = '/';
}

if ($uri !== '/') {
    $uri = rtrim($uri, '/');
    if ($uri === '') {
        $uri = '/';
    }
}

if (array_key_exists($uri, $routes)) {
    $route = $routes[$uri];

    if (is_array($route)) {
        $controllerName = $route['controller'];
        $action = $route['action'];

        $controllerFile = __DIR__ . "/../app/Controllers/{$controllerName}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;

            $controller = new $controllerName();
            $controller->$action();
        } else {
            http_response_code(404);
            include __DIR__ . '/../app/Views/404.php';
        }
    } else {
        include $route;
    }
} else {
    http_response_code(404);
    include __DIR__ . '/../app/Views/404.php';
}