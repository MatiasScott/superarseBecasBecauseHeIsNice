<?php

require_once '/ISuperarse/app/config/conexion.php';
require_once '/ISuperarse/app/controllers/BecarioController.php';

// Obtener la URI completa y eliminar la parte del subdirectorio
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
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

