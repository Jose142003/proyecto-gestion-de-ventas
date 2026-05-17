<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'carrito_db'; 
$username = 'root'; // Usuario por defecto de XAMPP
$password = '';     // Contraseña por defecto de XAMPP

try {
    // 1. Conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    // Respuesta en caso de error de conexión o consulta
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor',
        'error' => $e->getMessage()
    ]);
}
?>