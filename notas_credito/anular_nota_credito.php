<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

requerirAdmin();
verificarCSRF();

try {
    $pdo = Database::getConnection();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = isset($data['id']) ? (int) $data['id'] : 0;
    if (!$id) {
        errorResponse('ID de nota de crédito requerido');
    }

    $stmt = $pdo->prepare("SELECT * FROM notas_credito WHERE id = ?");
    $stmt->execute([$id]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota) {
        errorResponse('Nota de crédito no encontrada', 404);
    }

    if ($nota['estado'] !== 'emitida') {
        errorResponse('Solo se pueden anular notas de crédito en estado emitida');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE notas_credito SET estado = 'anulada' WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();

    auditoriaRegistrar('anular_nota_credito', 'notas_credito', "Nota de Crédito {$nota['numero_nota']} anulada");

    require_once __DIR__ . '/../telegram/notificar_notas.php';
    telegramNotificarNotaCreditoAnulada($pdo, $id, $nota['numero_nota']);

    jsonResponse(['success' => true, 'message' => 'Nota de crédito anulada exitosamente']);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en anular_nota_credito: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
