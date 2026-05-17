<?php
header('Content-Type: application/json');
require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch(PDOException $e) {
    echo json_encode(['error' => 'Conexion fallida']);
    exit;
}

// Sincronizar clientes con users
$sql = "INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, direccion, estado, fecha_registro)
        SELECT 'cedula', COALESCE(cedula, CONCAT('USER-', id)), nombre, correo, telefono, COALESCE(direccion, 'No especificada'), estado, created_at
        FROM users u
        WHERE u.rol = 'usuario'
        AND NOT EXISTS (SELECT 1 FROM clientes c WHERE c.email = u.correo)
        ON DUPLICATE KEY UPDATE 
            nombre = VALUES(nombre),
            email = VALUES(email),
            telefono = VALUES(telefono),
            direccion = VALUES(direccion),
            estado = VALUES(estado)";

$stmt = $pdo->prepare($sql);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Clientes sincronizados correctamente']);
?>