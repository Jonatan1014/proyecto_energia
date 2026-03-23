<?php
// public/index.php - Punto de entrada de la aplicación

// Headers CORS para permitir solicitudes desde orígenes locales
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
AppFactory::setContainer($container);

$app = AppFactory::create();

// Incluir rutas
require_once __DIR__ . '/../routes/web.php';

// Ejecutar la aplicación
$app->run();