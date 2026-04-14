<?php

define('BASE_PATH', dirname(__DIR__));

$baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}

define('BASE_URL', rtrim($baseUrl, '/'));

require_once BASE_PATH . '/config/conexion.php';
require_once BASE_PATH . '/app/controllers/BecarioController.php';

// Obtener la URI completa y eliminar la parte del subdirectorio
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$basePath = BASE_URL;

if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, strlen('/index.php'));
}

// Si la URI es la raíz, la normalizamos a '/'
if ($uri === '') {
    $uri = '/';
}

// Lógica del enrutador
$becarioController = new BecarioController();

switch ($uri) {
    case '/':
    case '/home':
        $becarioController->home();
        break;

    case '/becario/buscar':
        $becarioController->buscar();
        break;
    
    case '/becario/procesar':
        $becarioController->procesar();
        break;
        
    case '/becario/procesarSubida':
        $becarioController->procesarSubida();
        break;
        
    case '/becario/descargar':
        $becarioController->descargar();
        break;

    default:
        http_response_code(404);
        echo "404 - Página no encontrada";
        break;
}

