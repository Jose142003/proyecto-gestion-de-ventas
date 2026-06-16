<?php
require __DIR__ . '/conexion/conexion.php';
$pdo = conectarDB();
$db = $pdo->query('SELECT DATABASE()')->fetchColumn();
$stmt = $pdo->query("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '$db'");
foreach ($stmt as $r) echo $r['ROUTINE_NAME'] . "\n";
