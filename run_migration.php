<?php
require __DIR__ . '/conexion/conexion.php';
$pdo = conectarDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents(__DIR__ . '/sql/migracion_stored_procedures.sql');
// Split by delimiter statements since PDO doesn't support DELIMITER
// Remove DELIMITER lines and execute each statement separately
$sql = preg_replace('/DELIMITER\s+\S+\s*/i', '', $sql);
$statements = explode('//', $sql);
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
        echo "OK: " . substr($stmt, 0, 60) . "...\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
echo "Migración completada.\n";
