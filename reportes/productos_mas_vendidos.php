<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
} catch(PDOException $e) {
    echo json_encode([]);
    exit;
}

// Consulta para productos más vendidos
$sql = "SELECT 
            p.id as id,
            ANY_VALUE(p.name) as nombre,
            ANY_VALUE(p.category) as categoria,
            ANY_VALUE(p.price) as precio,
            ANY_VALUE(p.stock) as stock_actual,
            COUNT(DISTINCT pd.pedido_id) as veces_vendido,
            COALESCE(SUM(pd.cantidad), 0) as unidades_vendidas,
            COALESCE(SUM(pd.subtotal), 0) as ingresos
        FROM products p
        LEFT JOIN pedido_detalles pd ON p.id = pd.producto_id
        LEFT JOIN pedidos ped ON pd.pedido_id = ped.id AND ped.estado IN ('completado', 'facturado', 'pagada')
        GROUP BY p.id
        ORDER BY unidades_vendidas DESC, veces_vendido DESC
        LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear los datos
$resultado = [];
foreach ($productos as $p) {
    $resultado[] = [
        'id' => $p['id'],
        'nombre' => $p['nombre'],
        'categoria' => $p['categoria'] ?? 'General',
        'veces_vendido' => (int)$p['veces_vendido'],
        'unidades_vendidas' => (int)$p['unidades_vendidas'],
        'ingresos' => (float)$p['ingresos'],
        'stock_actual' => (int)$p['stock_actual']
    ];
}

echo json_encode($resultado);
?>