<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../conexion/conexion.php';

try {
    $db = conectarDB();
    
    // Obtener caja abierta actual
    $stmt = $db->prepare("SELECT * FROM caja_arqueos WHERE estado = 'abierta' AND DATE(fecha_apertura) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$caja) {
        echo json_encode([
            'success' => true,
            'estado' => 'cerrada',
            'monto_inicial' => 0,
            'total_ingresos' => 0,
            'total_egresos' => 0,
            'total' => 0,
            'saldo_actual' => 0
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'estado' => $caja['estado'],
        'monto_inicial' => floatval($caja['monto_inicial']),
        'total_ingresos' => floatval($caja['monto_ingresos']),
        'total_egresos' => floatval($caja['monto_egresos']),
        'total' => floatval($caja['monto_inicial'] + $caja['monto_ingresos']),
        'saldo_actual' => floatval($caja['monto_inicial'] + $caja['monto_ingresos'] - $caja['monto_egresos'])
    ]);
    
} catch (Exception $e) {
    error_log("Error obtener estado caja: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al obtener estado de caja',
        'estado' => 'cerrada',
        'monto_inicial' => 0,
        'total_ingresos' => 0,
        'total_egresos' => 0,
        'total' => 0,
        'saldo_actual' => 0
    ]);
}
?>