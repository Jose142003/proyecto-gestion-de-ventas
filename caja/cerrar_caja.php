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

    // Obtener caja abierta
    $stmt = $db->prepare("SELECT * FROM caja_arqueos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$caja) {
        echo json_encode(['success' => false, 'message' => 'No hay caja abierta para cerrar']);
        exit;
    }

    $totalEsperado = $caja['monto_inicial'] + $caja['monto_ingresos'] - $caja['monto_egresos'];

    // Aceptar monto_real del POST si se envio, sino usar el esperado
    $input = json_decode(file_get_contents('php://input'), true);
    $montoReal = isset($input['monto_real']) ? floatval($input['monto_real']) : $totalEsperado;
    $diferencia = $montoReal - $totalEsperado;

    // Cerrar caja
    $query = "UPDATE caja_arqueos SET estado = 'cerrada', fecha_cierre = NOW(), usuario_cierre_id = ?, monto_esperado = ?, monto_real = ?, diferencia = ? WHERE id = ?";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$_SESSION['user_id'], $totalEsperado, $montoReal, $diferencia, $caja['id']])) {
        auditoriaRegistrar('cerrar_caja', 'caja', "Caja cerrada - Caja ID: {$caja['id']} - Número: {$caja['numero_arqueo']} - Monto esperado: $totalEsperado - Diferencia: $diferencia");
        echo json_encode(['success' => true, 'message' => 'Caja cerrada correctamente', 'monto_esperado' => $totalEsperado, 'monto_real' => $montoReal, 'diferencia' => $diferencia]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cerrar caja']);
    }
} catch (Exception $e) {
    error_log("Cerrar caja error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cerrar caja']);
}
?>