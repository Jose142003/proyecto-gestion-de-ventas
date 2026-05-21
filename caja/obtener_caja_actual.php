<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = conectarDB();

    $stmt = $db->prepare("SELECT * FROM caja_arqueos WHERE estado = 'abierta' AND DATE(fecha_apertura) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($caja) {
        $stmtMov = $db->prepare("SELECT m.*, u.nombre as usuario_nombre 
                                  FROM caja_movimientos m 
                                  LEFT JOIN users u ON m.usuario_id = u.id 
                                  WHERE m.arqueo_id = ? 
                                  ORDER BY m.fecha_movimiento DESC");
        $stmtMov->execute([$caja['id']]);
        $movimientos = $stmtMov->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'estado' => 'abierta',
            'caja_id' => $caja['id'],
            'monto_inicial' => (float)$caja['monto_inicial'],
            'ingresos' => (float)$caja['monto_ingresos'],
            'egresos' => (float)$caja['monto_egresos'],
            'total' => (float)($caja['monto_inicial'] + $caja['monto_ingresos']),
            'saldo_actual' => (float)($caja['monto_inicial'] + $caja['monto_ingresos'] - $caja['monto_egresos']),
            'movimientos' => $movimientos
        ]);
    } else {
        echo json_encode([
            'estado' => 'cerrada',
            'monto_inicial' => 0,
            'ingresos' => 0,
            'egresos' => 0,
            'total' => 0,
            'saldo_actual' => 0,
            'movimientos' => []
        ]);
    }
} catch (Exception $e) {
    error_log("Caja error: " . $e->getMessage());
    echo json_encode([
        'estado' => 'cerrada',
        'monto_inicial' => 0,
        'ingresos' => 0,
        'egresos' => 0,
        'total' => 0,
        'saldo_actual' => 0,
        'movimientos' => []
    ]);
}
?>