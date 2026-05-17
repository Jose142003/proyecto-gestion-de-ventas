<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "carrito_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $conn->connect_error
    ]);
    exit();
}

// Obtener parámetros de filtro
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;

$sql = "SELECT 
            hs.*,
            p.nombre as producto_nombre,
            p.stock as stock_actual,
            u.nombre as usuario_nombre,
            (SELECT stock FROM productos WHERE id = hs.producto_id) - hs.cantidad as stock_anterior
        FROM historial_stock hs
        LEFT JOIN productos p ON hs.producto_id = p.id
        LEFT JOIN usuarios u ON hs.usuario_id = u.id
        WHERE 1=1";

if ($fecha) {
    $sql .= " AND DATE(hs.fecha) = ?";
}

if ($tipo) {
    $sql .= " AND hs.tipo = ?";
}

$sql .= " ORDER BY hs.fecha DESC LIMIT 100";

$stmt = $conn->prepare($sql);

if ($fecha && $tipo) {
    $stmt->bind_param("ss", $fecha, $tipo);
} elseif ($fecha) {
    $stmt->bind_param("s", $fecha);
} elseif ($tipo) {
    $stmt->bind_param("s", $tipo);
}

$stmt->execute();
$result = $stmt->get_result();

$historial = [];
while ($row = $result->fetch_assoc()) {
    // Calcular stock nuevo
    $row['stock_nuevo'] = $row['stock_actual'];
    $historial[] = $row;
}

echo json_encode([
    'success' => true,
    'historial' => $historial,
    'total' => count($historial)
]);

$stmt->close();
$conn->close();
?>