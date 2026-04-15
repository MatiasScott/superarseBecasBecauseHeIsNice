<?php
/**
 * Script de Migración: Inicializa contraseñas_login para todos los usuarios
 * 
 * Uso: php scripts/migrate-passwords.php
 * 
 * Este script:
 * 1. Conecta a la base de datos
 * 2. Obtiene usuarios sin contrasenia_login (NULL)
 * 3. Genera hash = bcrypt(cedula)
 * 4. Actualiza cada registro
 * 
 * Seguro: Usa prepared statements y hash bcrypt
 */

// Configurar path base
$projectRoot = dirname(dirname(__FILE__));
define('BASE_PATH', $projectRoot);

// Cargar dependencias
require_once BASE_PATH . '/config/conexion.php';
require_once BASE_PATH . '/app/models/Becario.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  MIGRACIÓN: Inicializar Contraseñas de Login\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // Crear instancia del modelo
    $becarioModel = new Becario($pdo);
    
    // Obtener usuarios sin contraseña de login
    echo "📊 Buscando usuarios sin contraseña de login...\n";
    $stmt = $pdo->query("SELECT cedula FROM usuarios WHERE contrasenia_login IS NULL ORDER BY cedula");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($usuarios)) {
        echo "✓ Todos los usuarios ya tienen contraseña de login asignada.\n";
        echo "\n═══════════════════════════════════════════════════════════════\n";
        exit(0);
    }
    
    $total = count($usuarios);
    echo "⚠️  Encontrados: {$total} usuarios sin contraseña\n\n";
    
    // Migrar cada usuario
    $exitosos = 0;
    $errores = 0;
    
    foreach ($usuarios as $index => $usuario) {
        $cedula = $usuario['cedula'];
        $actual = $index + 1;
        
        try {
            // Generar hash: bcrypt(cedula)
            $hash = $becarioModel->hashPassword($cedula);
            
            // Actualizar en BD
            $updated = $becarioModel->updatePassword($cedula, $hash);
            
            if ($updated) {
                echo "[{$actual}/{$total}] ✓ {$cedula} - Hash generado correctamente\n";
                $exitosos++;
            } else {
                echo "[{$actual}/{$total}] ✗ {$cedula} - Error al actualizar\n";
                $errores++;
            }
        } catch (Exception $e) {
            echo "[{$actual}/{$total}] ✗ {$cedula} - Exception: {$e->getMessage()}\n";
            $errores++;
        }
    }
    
    // Resumen
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "  RESUMEN DE MIGRACIÓN\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Total procesados:  {$total}\n";
    echo "Exitosos:          {$exitosos} ✓\n";
    echo "Errores:           {$errores} ✗\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    if ($errores === 0) {
        echo "✓ ¡Migración completada exitosamente!\n\n";
        exit(0);
    } else {
        echo "⚠️  Migración completada con errores. Revisa los registros arriba.\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: {$e->getMessage()}\n";
    echo "Stack Trace: {$e->getTraceAsString()}\n";
    exit(1);
}
