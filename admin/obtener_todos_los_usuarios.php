<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    
    // 2. Verificar si la tabla 'users' existe (según tu SQL se llama 'users', no 'usuarios')
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'La tabla "users" no existe. Ejecuta el archivo SQL primero.'
        ]);
        exit;
    }
    
    // 3. Consultar usuarios con los nombres de columna exactos de tu SQL:
    // Cambios realizados: 
    // - 'email' por 'correo'
    // - 'fecha_registro' por 'created_at'
    // - tabla 'usuarios' por 'users'
    $sql = "SELECT id, nombre, correo, telefono, cedula, rol, created_at FROM users ORDER BY nombre";
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Respuesta exitosa
    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios,
        'total' => count($usuarios)
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>