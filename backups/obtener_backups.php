<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Crear tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(512) NOT NULL,
        tamanio_bytes BIGINT NOT NULL,
        tipo VARCHAR(50) DEFAULT 'completo',
        estado VARCHAR(50) DEFAULT 'completado',
        usuario_id INT NOT NULL,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Obtener backups
    $stmt = $pdo->query("SELECT * FROM backups ORDER BY fecha_creacion DESC");
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $backups = [];
    foreach ($resultados as $fila) {
        $bytes = $fila['tamanio_bytes'];
        
        if ($bytes < 1024) {
            $tamaño = $bytes . ' B';
        } elseif ($bytes < 1048576) {
            $tamaño = round($bytes / 1024, 2) . ' KB';
        } else {
            $tamaño = round($bytes / 1048576, 2) . ' MB';
        }
        
        $backups[] = [
            'id' => $fila['id'],
            'archivo' => $fila['nombre_archivo'],
            'tamaño' => $tamaño,
            'tipo' => $fila['tipo'],
            'fecha' => $fila['fecha_creacion'],
            'estado' => $fila['estado']
        ];
    }
    
    echo json_encode(['success' => true, 'backups' => $backups]);
    
} catch (Exception $e) {
    error_log("Error en obtener_backups.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'backups' => []]);
}
?>