<?php
require_once __DIR__ . '/../conexion/conexion.php';
iniciarSesion();
header('Content-Type: application/json');
echo json_encode([
    'token' => generarTokenCSRF()
]);
