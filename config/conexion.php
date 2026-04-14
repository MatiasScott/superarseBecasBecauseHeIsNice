<?php
$host = 'localhost';
$db   = 'becasupe_sistema_usuarios';
$user = 'root';
$pass = 'Superarse.2025';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Excepciones en errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Resultado como array asociativo
    ]);
} catch (PDOException $e) {
    error_log("Error de conexi車n: " . $e->getMessage());
    die("Error al conectar con la base de datos.");
}
?>
