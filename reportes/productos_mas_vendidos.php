<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Configuración de conexión
$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([]);
    exit;
}

// Consulta para productos más vendidos
$sql = "SELECT 
            p.id as id,
            p.name as nombre,
            p.category as categoria,
            p.price as precio,
            p.stock as stock_actual,
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