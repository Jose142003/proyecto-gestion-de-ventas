<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

requerirAdmin();

try {
    $pdo = Database::getConnection();

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if (!$id) {
        errorResponse('ID de nota de débito requerido');
    }

    $stmt = $pdo->prepare("SELECT nd.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento, c.email AS cliente_email, f.numero_factura, f.total AS factura_total FROM notas_debito nd JOIN clientes c ON nd.cliente_id = c.id JOIN facturas f ON nd.factura_id = f.id WHERE nd.id = ?");
    $stmt->execute([$id]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota) {
        errorResponse('Nota de débito no encontrada', 404);
    }

    $stmt = $pdo->prepare("SELECT * FROM notas_debito_detalles WHERE nota_debito_id = ?");
    $stmt->execute([$id]);
    $nota['detalles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'nota' => $nota]);

} catch (PDOException $e) {
    error_log("Error en obtener_nota_debito: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
