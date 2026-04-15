-- ====================================================================================
-- SCRIPT DE INICIALIZACIÓN: Crear hashes iniciales para contrasenia_login
-- ====================================================================================
-- Este script proporciona instrucciones para inicializar contraseñas de login
-- para usuarios existentes
--
-- Fecha: 14 de abril de 2026
-- ====================================================================================

-- OPCIÓN 1: RECOMENDADA - Inicialización Automática
-- El sistema ahora inicializa automáticamente las contraseñas NULL con la cédula hasheada
-- cuando el usuario busca su cédula por primera vez.
-- 
-- NO REQUIERE ACCIÓN MANUAL - El controlador lo hace automáticamente:
-- 1. Usuario ingresa su cédula
-- 2. Sistema verifica: IF contrasenia_login IS NULL THEN
-- 3. Se genera: hash = bcrypt(cedula)
-- 4. Se guarda: UPDATE usuarios SET contrasenia_login = hash
--
-- Resultado: Primera búsqueda de usuario = Contraseña inicial (cédula hasheada)

-- OPCIÓN 2: Inicializar Todas las Contraseñas Manualmente (Batch)
-- Si deseas inicializar TODAS a la vez, ejecuta esto desde PHP CLI:
-- Archivo: scripts/migrate-passwords.php

<?php
// scripts/migrate-passwords.php
require_once 'config/conexion.php';
require_once 'app/models/Becario.php';

$becarioModel = new Becario($pdo);

// Obtener usuarios sin contraseña de login
$stmt = $pdo->query("SELECT cedula FROM usuarios WHERE contrasenia_login IS NULL");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Migrando " . count($usuarios) . " usuarios...\n";

foreach ($usuarios as $usuario) {
    $cedula = $usuario['cedula'];
    $hash = $becarioModel->hashPassword($cedula);
    $becarioModel->updatePassword($cedula, $hash);
    echo "✓ {$cedula}\n";
}

echo "¡Migración completada!\n";
?>

-- OPCIÓN 3: Script SQL puro (sin bcrypt, solo placeholder)
-- NOTA: No genera bcrypt real, solo marca que necesita contraseña
-- UPDATE usuarios SET contrasenia_login = '0' WHERE contrasenia_login IS NULL;

-- VERIFICACIÓN
-- Después de migrar, verifica con:
SELECT cedula, contrasenia, contrasenia_login FROM usuarios LIMIT 10;

-- Esperado:
-- contrasenia       = contraseña Moodle (puede ser NULL o valor)
-- contrasenia_login = password hash bcrypt (ej: $2y$10$...) o NULL
