<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de registro no proporcionado']);
    exit;
}

$id = intval($_GET['id']);
$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT 
                id,
                fecha_creacion,
                COALESCE(usuario_nombre, 'Sistema') as usuario_nombre,
                COALESCE(usuario_rol, 'sistema') as usuario_rol,
                COALESCE(accion, 'N/A') as accion,
                COALESCE(descripcion, 'Sin descripción') as descripcion,
                edit_count,
                edit_history
            FROM auditoria_logs 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }
    
    $historial = [];
    if (!empty($registro['edit_history'])) {
        $lineas = explode("\n", trim($registro['edit_history']));
        foreach ($lineas as $linea) {
            if (empty(trim($linea))) continue;
            if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \| Usuario: (.*?) \(ID: (\d+)\) \| Motivo: (.*?) \| Descripción anterior: (.*)$/', $linea, $matches)) {
                $historial[] = [
                    'fecha' => $matches[1],
                    'usuario' => $matches[2],
                    'usuario_id' => intval($matches[3]),
                    'motivo' => $matches[4],
                    'descripcion_anterior' => $matches[5]
                ];
            }
        }
    }
    
    echo json_encode(['success' => true, 'registro' => $registro, 'historial' => $historial]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>