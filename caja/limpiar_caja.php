<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/conexion/conexion.php';

requerirAdmin();

try {
    $db = conectarDB();
    verificarCSRF();
    $data = json_decode(file_get_contents('php://input'), true);
    $accion = $data['accion'] ?? 'limpiar';

    // Obtener caja abierta actual
    $stmt = $db->prepare("SELECT * FROM caja_arqueos WHERE estado = 'abierta' AND DATE(fecha_apertura) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$caja) {
        echo json_encode(['success' => false, 'message' => 'No hay caja abierta para limpiar']);
        exit;
    }

    $db->beginTransaction();

    if ($accion === 'reiniciar') {
        // Reiniciar completamente la caja del día
        $stmtDel = $db->prepare("DELETE FROM caja_movimientos WHERE arqueo_id = ?");
        $stmtDel->execute([$caja['id']]);

        $stmtUpdate = $db->prepare("UPDATE caja_arqueos SET monto_ingresos = 0, monto_egresos = 0, monto_esperado = NULL, monto_real = NULL, diferencia = NULL, estado = 'abierta', fecha_cierre = NULL, usuario_cierre_id = NULL WHERE id = ?");
        $stmtUpdate->execute([$caja['id']]);

        auditoriaRegistrar('reiniciar_caja', 'caja', "Caja reiniciada - ID: {$caja['id']} - Número: {$caja['numero_arqueo']}");
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Caja reiniciada correctamente. Todos los movimientos han sido eliminados.']);
    } else {
        // Limpiar solo movimientos de hoy (mantener estructura de caja)
        $stmtDel = $db->prepare("DELETE FROM caja_movimientos WHERE arqueo_id = ? AND DATE(fecha_movimiento) = CURDATE()");
        $stmtDel->execute([$caja['id']]);

        $stmtUpdate = $db->prepare("UPDATE caja_arqueos SET monto_ingresos = 0, monto_egresos = 0 WHERE id = ?");
        $stmtUpdate->execute([$caja['id']]);

        auditoriaRegistrar('limpiar_caja', 'caja', "Caja limpiada - ID: {$caja['id']} - Número: {$caja['numero_arqueo']}");
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Movimientos de caja limpiados correctamente.']);
    }
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    error_log("Error limpiar caja: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al limpiar caja: ' . $e->getMessage()]);
}
