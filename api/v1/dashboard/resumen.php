<?php
try {
    $pdo = Database::getConnection();

    $hoy = date('Y-m-d');
    $inicioMes = date('Y-m-01');

    $stats = [];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE active = 1 AND deleted_at IS NULL");
    $stats['total_productos'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock <= 5 AND stock > 0 AND active = 1 AND deleted_at IS NULL");
    $stats['stock_bajo'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock = 0 AND active = 1 AND deleted_at IS NULL");
    $stats['sin_stock'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE estado = 'activo'");
    $stats['total_clientes'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
    $stats['total_pedidos'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as monto FROM pedidos WHERE DATE(created_at) = ?");
    $stmt->execute([$hoy]);
    $row = $stmt->fetch();
    $stats['pedidos_hoy'] = (int)$row['total'];
    $stats['ventas_hoy'] = (float)$row['monto'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as monto FROM pedidos WHERE created_at >= ?");
    $stmt->execute([$inicioMes]);
    $row = $stmt->fetch();
    $stats['pedidos_mes'] = (int)$row['total'];
    $stats['ventas_mes'] = (float)$row['monto'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM facturas WHERE estado = 'pendiente'");
    $stats['facturas_pendientes'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente' AND DATE(created_at) = ?");
    $stmt->execute([$hoy]);
    $stats['pedidos_pendientes_hoy'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(created_at) = CURDATE()");
    $stats['pedidos_nuevos_hoy'] = (int)$stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT categoria, COUNT(*) as total FROM (SELECT CASE WHEN category IS NULL OR category = '' THEN 'General' ELSE category END AS categoria FROM products WHERE active = 1 AND deleted_at IS NULL) sub GROUP BY categoria ORDER BY total DESC LIMIT 10");
    $stats['productos_por_categoria'] = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT p.nombre, COUNT(pd.id) as total_vendido FROM pedido_detalles pd JOIN products p ON pd.producto_id = p.id GROUP BY p.id, p.nombre ORDER BY total_vendido DESC LIMIT 10");
    $stats['productos_mas_vendidos'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE DATE(created_at) = ?");
    $stmt->execute([$hoy]);
    $stats['total_ventas_hoy'] = (float)$stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE created_at >= ?");
    $stmt->execute([$inicioMes]);
    $stats['total_ventas_mes'] = (float)$stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT DAY(created_at) as dia, COUNT(*) as pedidos, COALESCE(SUM(total), 0) as ventas FROM pedidos WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DAY(created_at) ORDER BY dia ASC");
    $stats['ventas_7_dias'] = $stmt->fetchAll();

    $stats['fecha_consulta'] = $hoy;

    apiResponse(['success' => true, 'data' => $stats]);
} catch (Exception $e) {
    apiError('Error al obtener resumen: ' . $e->getMessage(), 500);
}
