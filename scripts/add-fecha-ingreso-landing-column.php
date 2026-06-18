<?php
/**
 * Script de migracion: agrega la columna fecha_ingreso_landing en usuarios.
 *
 * Uso:
 * php scripts/add-fecha-ingreso-landing-column.php
 */

$projectRoot = dirname(dirname(__FILE__));
define('BASE_PATH', $projectRoot);

require_once BASE_PATH . '/config/conexion.php';

echo "============================================================\n";
echo " MIGRACION: columna fecha_ingreso_landing\n";
echo "============================================================\n\n";

try {
    $dbNameStmt = $pdo->query('SELECT DATABASE() AS db_name');
    $dbNameRow = $dbNameStmt ? $dbNameStmt->fetch(PDO::FETCH_ASSOC) : null;
    $dbName = (string) ($dbNameRow['db_name'] ?? '');

    if ($dbName === '') {
        throw new RuntimeException('No se pudo determinar la base de datos activa.');
    }

    $checkSql = "SELECT COUNT(*) AS total
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME = 'usuarios'
                   AND COLUMN_NAME = 'fecha_ingreso_landing'";

    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$dbName]);
    $exists = (int) (($checkStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;

    if ($exists) {
        echo "La columna fecha_ingreso_landing ya existe.\n";
        exit(0);
    }

    $alterSql = "ALTER TABLE usuarios
                 ADD COLUMN fecha_ingreso_landing DATETIME NULL DEFAULT NULL
                 COMMENT 'Ultima fecha y hora de ingreso a la landing page'";

    $pdo->exec($alterSql);

    echo "OK: columna fecha_ingreso_landing creada correctamente.\n";
    echo "Tipo: DATETIME NULL\n";
    echo "Actualizacion: automatica en cada login exitoso.\n";
    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
