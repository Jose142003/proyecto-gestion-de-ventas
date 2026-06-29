<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $usuarios = [];
    
    // Intentar con 'users' primero
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        // Detectar columnas disponibles
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $hasCorreo = in_array('correo', $cols);
        $hasEmail = in_array('email', $cols);
        $hasCedula = in_array('cedula', $cols);
        $hasTelefono = in_array('telefono', $cols);
        $hasCreatedAt = in_array('created_at', $cols);
        $hasFechaRegistro = in_array('fecha_registro', $cols);
        
        $selectCols = ['id', 'nombre'];
        $selectCols[] = $hasCorreo ? 'correo' : ($hasEmail ? 'email' : "'N/A' AS correo");
        $selectCols[] = $hasCedula ? 'cedula' : "'N/A' AS cedula";
        $selectCols[] = $hasTelefono ? 'telefono' : "'N/A' AS telefono";
        $selectCols[] = 'rol';
        $selectCols[] = $hasCreatedAt ? 'created_at' : ($hasFechaRegistro ? 'fecha_registro' : 'NULL AS created_at');
        
        $sql = "SELECT " . implode(', ', $selectCols) . " FROM users ORDER BY nombre";
        $stmt = $pdo->query($sql);
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback: buscar admin_users u otras tablas
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        if ($stmt->rowCount() > 0) {
            $cols = $pdo->query("SHOW COLUMNS FROM admin_users")->fetchAll(PDO::FETCH_COLUMN);
            $hasCorreo = in_array('correo', $cols);
            $hasEmail = in_array('email', $cols);
            $hasUsuario = in_array('usuario', $cols);
            $hasTelefono = in_array('telefono', $cols);
            
            $selectCols = ['id', 'nombre'];
            $selectCols[] = $hasCorreo ? 'correo' : ($hasEmail ? 'email' : ($hasUsuario ? 'usuario' : "'N/A'") . ' AS correo');
            $selectCols[] = "'N/A' AS cedula";
            $selectCols[] = $hasTelefono ? 'telefono' : "'N/A' AS telefono";
            $selectCols[] = 'rol';
            $selectCols[] = 'fecha_registro AS created_at';
            
            $sql = "SELECT " . implode(', ', $selectCols) . " FROM admin_users ORDER BY nombre";
            $stmt = $pdo->query($sql);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios,
        'total' => count($usuarios)
    ]);
    
} catch(PDOException $e) {
    error_log("Error en obtener_todos_los_usuarios: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'usuarios' => []
    ]);
}
?>