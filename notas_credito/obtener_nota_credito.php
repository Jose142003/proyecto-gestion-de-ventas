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
        errorResponse('ID de nota de crédito requerido');
    }

    $stmt = $pdo->prepare("SELECT nc.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento, c.email AS cliente_email, f.numero_factura, f.total AS factura_total FROM notas_credito nc JOIN clientes c ON nc.cliente_id = c.id JOIN facturas f ON nc.factura_id = f.id WHERE nc.id = ?");
    $stmt->execute([$id]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota) {
        errorResponse('Nota de crédito no encontrada', 404);
    }

    $stmt = $pdo->prepare("SELECT ncd.*, p.name AS producto_nombre, p.sku FROM notas_credito_detalles ncd LEFT JOIN products p ON ncd.producto_id = p.id WHERE ncd.nota_credito_id = ?");
    $stmt->execute([$id]);
    $nota['detalles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'nota' => $nota]);

} catch (PDOException $e) {
    error_log("Error en obtener_nota_credito: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
