<?php
// src/public/index.php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$routes = include '../config/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Eliminar el prefijo base si es necesario (XAMPP local)
$base = '/proyecto_energia/src/public';
if (strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

// Asegurar que la URI comience con /
if (empty($uri)) {
    $uri = '/';
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
            include '../app/Views/404.php';
        }
    } else {
        include $route;
    }
} else {
    http_response_code(404);
    include '../app/Views/404.php';
}