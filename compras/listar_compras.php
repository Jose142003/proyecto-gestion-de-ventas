<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once dirname(__DIR__) . '/conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Parámetros de paginación y filtros
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    $filters = [];
    $params = [];
    
    // Construir condiciones WHERE
    if (!empty($_GET['orden'])) {
        $filters[] = "c.numero_orden LIKE :orden";
        $params[':orden'] = '%' . $_GET['orden'] . '%';
    }
    
    if (!empty($_GET['proveedor'])) {
        $filters[] = "(p.nombre_comercial LIKE :proveedor OR p.razon_social LIKE :proveedor)";
        $params[':proveedor'] = '%' . $_GET['proveedor'] . '%';
    }
    
    if (!empty($_GET['estado'])) {
        $filters[] = "c.estado = :estado";
        $params[':estado'] = $_GET['estado'];
    }
    
    if (!empty($_GET['desde'])) {
        $filters[] = "c.fecha_orden >= :desde";
        $params[':desde'] = $_GET['desde'];
    }
    
    if (!empty($_GET['hasta'])) {
        $filters[] = "c.fecha_orden <= :hasta";
        $params[':hasta'] = $_GET['hasta'];
    }
    
    $whereClause = !empty($filters) ? "WHERE " . implode(" AND ", $filters) : "";
    
    // Ordenamiento
    $orderBy = "c.id DESC";
    $sortField = $_GET['sort'] ?? '';
    $sortOrder = $_GET['order'] ?? 'ASC';
    
    $allowedSortFields = ['numero_orden', 'fecha_orden', 'subtotal', 'total'];
    if (in_array($sortField, $allowedSortFields)) {
        $orderBy = "c.{$sortField} {$sortOrder}";
    }
    
    // Consulta para contar total
    $countSql = "
        SELECT COUNT(*) as total 
        FROM compras c
        LEFT JOIN proveedores p ON c.proveedor_id = p.id
        {$whereClause}
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);
    
    // Consulta principal
    $sql = "
        SELECT 
            c.id,
            c.numero_orden,
            c.proveedor_id,
            c.fecha_orden,
            c.fecha_recibido,
            c.subtotal,
            c.iva,
            c.total,
            c.estado,
            c.observaciones,
            p.nombre_comercial as proveedor_nombre,
            p.razon_social,
            (SELECT COUNT(*) FROM compra_detalles WHERE compra_id = c.id) as total_productos
        FROM compras c
        LEFT JOIN proveedores p ON c.proveedor_id = p.id
        {$whereClause}
        ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $compras,
        'total' => $total,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);
    
} catch (PDOException $e) {
    error_log("Error listar compras: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar las compras: ' . $e->getMessage()
    ]);
}
?>