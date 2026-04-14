<?php
if (!function_exists('loadDotEnv')) {
    function loadDotEnv($filePath)
    {
        if (!is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === '') {
                continue;
            }

            $value = trim($value, "\"'");
            if (getenv($name) === false) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
            }
        }
    }
}

$projectRoot = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
loadDotEnv($projectRoot . '/.env');

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db = getenv('DB_NAME') ?: 'becasupe_sistema_usuarios';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Excepciones en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Resultado como array asociativo
    ]);
} catch (PDOException $e) {
    error_log("Error de conexion: " . $e->getMessage());
    die("Error al conectar con la base de datos.");
}
?>
