<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos requeridos
if (empty($input['nombre']) || empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Faltan datos requeridos: nombre, email o password'
    ]);
    exit;
}

$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si el email ya existe
    $checkSql = "SELECT id FROM users WHERE correo = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['email' => $input['email']]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => 'El email ya está registrado'
        ]);
        exit;
    }
    
    // Hash de la contraseña
    $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $insertSql = "INSERT INTO users (nombre, correo, password, telefono, cedula, rol) 
                  VALUES (:nombre, :email, :password, :telefono, :cedula, :rol)";
    
    $insertStmt = $pdo->prepare($insertSql);
    
    $data = [
        'nombre' => $input['nombre'],
        'email' => $input['email'],
        'password' => $passwordHash,
        'telefono' => $input['telefono'] ?? '',
        'cedula' => $input['cedula'] ?? '',
        'rol' => $input['rol'] ?? 'usuario'
    ];
    
    if ($insertStmt->execute($data)) {
        $userId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user_id' => $userId
        ]);
    } else {
        throw new Exception('Error al insertar usuario');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error general',
        'message' => $e->getMessage()
    ]);
}
?>