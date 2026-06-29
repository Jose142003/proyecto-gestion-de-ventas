<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

session_start();
requerirSesion();

$conn = Database::getConnection();

$conn->exec("CREATE TABLE IF NOT EXISTS favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorito (usuario_id, producto_id),
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE
)");

$input = json_decode(file_get_contents('php://input'), true);
if ($input === null && !empty($_POST)) {
    $input = $_POST;
}
$usuario_id = $_SESSION['user_id'];
$producto_id = isset($input['producto_id']) ? intval($input['producto_id']) : 0;

if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

if ($producto_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Producto inválido']);
    exit;
}

try {
    $check = $conn->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
    $check->execute([$usuario_id, $producto_id]);

    if ($check->fetch()) {
        $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?")->execute([$usuario_id, $producto_id]);
        echo json_encode(['success' => true, 'favorito' => false, 'message' => 'Eliminado de favoritos']);
    } else {
        $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id) VALUES (?, ?)")->execute([$usuario_id, $producto_id]);
        echo json_encode(['success' => true, 'favorito' => true, 'message' => 'Añadido a favoritos']);
    }
} catch (Exception $e) {
    error_log("Error en agregar_favorito: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar']);
}
