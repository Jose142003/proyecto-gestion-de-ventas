<?php
require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = Database::getConnection();
    $sql = file_get_contents(__DIR__ . '/migracion_5_features.sql');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec($sql);
    echo "Migracion 5 features ejecutada exitosamente\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
