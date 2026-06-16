<?php
require __DIR__ . '/conexion/conexion.php';
$pdo = conectarDB();

echo "=== FAVORITOS ===\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'favoritos'");
if ($stmt->fetch()) {
    $stmt = $pdo->query("SHOW COLUMNS FROM favoritos");
    foreach ($stmt as $c) echo "  " . $c['Field'] . " (" . $c['Type'] . ")\n";
} else {
    echo "  (no existe)\n";
    // Check if there's another favorites table
    $stmt = $pdo->query("SHOW TABLES LIKE '%favor%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables)) echo "  Tablas similares: " . implode(', ', $tables) . "\n";
}

echo "\n=== STORED PROCEDURES ===\n";
$db = $pdo->query("SELECT DATABASE()")->fetchColumn();
echo "  DB: $db\n";
$stmt = $pdo->query("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '$db'");
foreach ($stmt as $r) echo "  " . $r['ROUTINE_TYPE'] . ": " . $r['ROUTINE_NAME'] . "\n";
$cnt = $stmt->rowCount();
if ($cnt == 0) echo "  (ninguno)\n";

echo "\n=== PEDIDOS COLUMNS ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM pedidos");
foreach ($stmt as $c) echo "  " . str_pad($c['Field'], 25) . " " . $c['Type'] . "\n";

echo "\n=== PEDIDO_DETALLES COLUMNS ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM pedido_detalles");
foreach ($stmt as $c) echo "  " . str_pad($c['Field'], 25) . " " . $c['Type'] . "\n";
