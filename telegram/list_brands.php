<?php
$pdo = new PDO("mysql:host=localhost;dbname=carrito_db;charset=utf8mb4","root","");
$stmt = $pdo->query("SELECT DISTINCT SUBSTRING_INDEX(name,' ',1) as marca FROM products WHERE active=1 ORDER BY marca");
$marcas = [];
foreach ($stmt as $r) {
    $m = trim($r["marca"]);
    if (strlen($m) > 2) $marcas[] = $m;
}
echo "Marcas:\n";
echo implode(", ", $marcas) . "\n";
echo "\nTotal: " . count($marcas) . " marcas\n";
