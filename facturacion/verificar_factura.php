<?php
// verificar_factura.php - Verificar si ya se generó factura para un pedido
session_start();
require_once '../conexion/conexion.php';

$pedido_id = $_GET['pedido_id'] ?? null;

if (!$pedido_id) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
    exit;
}

try {
    $pdo = conectarDB();
    
    $stmt = $pdo->prepare("SELECT id FROM facturas WHERE pedido_id = ?");
    $stmt->execute([$pedido_id]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($factura) {
        echo json_encode([
            'success' => true,
            'factura_id' => $factura['id'],
            'message' => 'Factura encontrada'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Factura aún no generada'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>