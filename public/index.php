<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');

define('BASE_PATH', dirname(__DIR__));

$baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}

define('BASE_URL', rtrim($baseUrl, '/'));

require_once BASE_PATH . '/config/conexion.php';
require_once BASE_PATH . '/app/controllers/BecarioController.php';
require_once BASE_PATH . '/app/controllers/AdminController.php';

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
$adminController = new AdminController();

switch ($uri) {
    case '/':
    case '/home':
        $becarioController->home();
        break;

    case '/becario/buscar':
        $becarioController->buscar();
        break;

    case '/becario/login':
        $becarioController->login();
        break;

    case '/becario/panel':
        $becarioController->panel();
        break;

    case '/becario/logout':
        $becarioController->logout();
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

    case '/becario/change-password':
        $becarioController->changePassword();
        break;

    case '/becario/forgot-password':
        $becarioController->forgotPassword();
        break;

    case '/admin/dashboard':
        $adminController->adminDashboard();
        break;

    case '/admin/login':
        $adminController->adminLogin();
        break;

    case '/admin':
        $adminController->adminLogin();
        break;

    case '/admin/logout':
        $adminController->adminLogout();
        break;

    case '/admin/change-password':
        $adminController->adminChangePassword();
        break;

    case '/admin/reset-password':
        $adminController->adminResetPassword();
        break;

    case '/admin/discard-reset-request':
        $adminController->adminDiscardResetRequest();
        break;

    case '/admin/create-account':
        $adminController->adminCreateAccount();
        break;

    case '/admin/bulk-upload-certificados':
        $adminController->adminBulkUploadCertificados();
        break;

    default:
        http_response_code(404);
        echo "404 - Página no encontrada";
        break;
}

