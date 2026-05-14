<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);

require_once dirname(__DIR__) . '/conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si se solicitó un ID específico
    $compraId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($compraId > 0) {
        // Obtener detalle de una compra específica
        $query = "SELECT c.*, 
                         p.nombre_comercial as proveedor_nombre,
                         p.razon_social as proveedor_razon_social,
                         p.ruc
                  FROM compras c 
                  LEFT JOIN proveedores p ON c.proveedor_id = p.id 
                  WHERE c.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $compraId, PDO::PARAM_INT);
        $stmt->execute();
        
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$compra) {
            echo json_encode(['success' => false, 'message' => 'Compra no encontrada']);
            exit;
        }
        
        // Obtener los productos de la compra
        $queryProductos = "SELECT cd.*, 
                                  pr.name as producto_nombre,
                                  pr.sku as producto_codigo,
                                  pr.category as categoria
                           FROM compra_detalles cd
                           LEFT JOIN products pr ON cd.producto_id = pr.id
                           WHERE cd.compra_id = :compra_id
                           ORDER BY cd.id ASC";
        
        $stmtProductos = $db->prepare($queryProductos);
        $stmtProductos->bindParam(':compra_id', $compraId, PDO::PARAM_INT);
        $stmtProductos->execute();
        
        $productos = [];
        while ($row = $stmtProductos->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = [
                'id' => $row['id'],
                'producto_id' => $row['producto_id'],
                'producto_nombre' => $row['producto_nombre'],
                'producto_codigo' => $row['producto_codigo'],
                'cantidad' => (float)$row['cantidad'],
                'cantidad_recibida' => (float)($row['cantidad_recibida'] ?? 0),
                'precio_unitario' => (float)$row['precio_unitario'],
                'subtotal' => (float)$row['subtotal'],
                'categoria' => $row['categoria']
            ];
        }
        
        // Calcular total de productos
        $totalProductos = count($productos);
        
        // Devolver el detalle completo
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $compra['id'],
                'numero_orden' => $compra['numero_orden'],
                'proveedor_id' => $compra['proveedor_id'],
                'proveedor_nombre' => $compra['proveedor_nombre'],
                'proveedor_razon_social' => $compra['proveedor_razon_social'],
                'proveedor_ruc' => $compra['ruc'],
                'fecha_orden' => $compra['fecha_orden'],
                'fecha_requerida' => $compra['fecha_requerida'],
                'fecha_recibido' => $compra['fecha_recibido'],
                'estado' => $compra['estado'],
                'subtotal' => (float)$compra['subtotal'],
                'iva' => (float)$compra['iva'],
                'descuento' => (float)($compra['descuento'] ?? 0),
                'total' => (float)$compra['total'],
                'observaciones' => $compra['observaciones'],
                'metodo_pago' => $compra['metodo_pago'],
                'condiciones_pago' => $compra['condiciones_pago'],
                'productos' => $productos,
                'total_productos' => $totalProductos
            ]
        ]);
        
    } else {
        // Listar todas las compras (para la tabla principal)
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Construir filtros
        $where = [];
        $params = [];
        
        if (!empty($_GET['orden'])) {
            $where[] = "c.numero_orden LIKE :orden";
            $params[':orden'] = '%' . $_GET['orden'] . '%';
        }
        
        if (!empty($_GET['proveedor'])) {
            $where[] = "(p.nombre_comercial LIKE :proveedor OR p.razon_social LIKE :proveedor)";
            $params[':proveedor'] = '%' . $_GET['proveedor'] . '%';
        }
        
        if (!empty($_GET['estado'])) {
            $where[] = "c.estado = :estado";
            $params[':estado'] = $_GET['estado'];
        }
        
        if (!empty($_GET['desde'])) {
            $where[] = "c.fecha_orden >= :desde";
            $params[':desde'] = $_GET['desde'];
        }
        
        if (!empty($_GET['hasta'])) {
            $where[] = "c.fecha_orden <= :hasta";
            $params[':hasta'] = $_GET['hasta'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Obtener total de registros
        $queryCount = "SELECT COUNT(*) as total 
                       FROM compras c 
                       LEFT JOIN proveedores p ON c.proveedor_id = p.id 
                       $whereClause";
        $stmtCount = $db->prepare($queryCount);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($total / $limit);
        
        // Ordenamiento
        $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'c.id';
        $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        // Mapeo de campos para ordenamiento
        $sortMap = [
            'numero_orden' => 'c.numero_orden',
            'proveedor' => 'p.nombre_comercial',
            'fecha_orden' => 'c.fecha_orden',
            'subtotal' => 'c.subtotal',
            'total' => 'c.total'
        ];
        
        $sortFieldDb = isset($sortMap[$sortField]) ? $sortMap[$sortField] : 'c.id';
        
        // Obtener compras paginadas - CORREGIDO: ahora selecciona todos los campos necesarios
        $query = "SELECT c.*, 
                         p.nombre_comercial as proveedor_nombre,
                         p.razon_social as proveedor_razon_social,
                         p.ruc,
                         (SELECT COUNT(*) FROM compra_detalles WHERE compra_id = c.id) as total_productos
                  FROM compras c 
                  LEFT JOIN proveedores p ON c.proveedor_id = p.id 
                  $whereClause
                  ORDER BY $sortFieldDb $sortOrder
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $compras = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $compras[] = [
                'id' => $row['id'],
                'numero_orden' => $row['numero_orden'],
                'numero_compra' => $row['numero_orden'], // Alias para compatibilidad
                'proveedor_id' => $row['proveedor_id'],
                'proveedor_nombre' => $row['proveedor_nombre'],
                'nombre_proveedor' => $row['proveedor_nombre'], // Alias para compatibilidad
                'proveedor' => $row['proveedor_nombre'], // Alias para compatibilidad
                'fecha_orden' => $row['fecha_orden'],
                'fecha' => $row['fecha_orden'], // Alias para compatibilidad
                'subtotal' => (float)$row['subtotal'],
                'iva' => (float)$row['iva'],
                'descuento' => (float)($row['descuento'] ?? 0),
                'total' => (float)$row['total'],
                'estado' => $row['estado'],
                'total_productos' => (int)$row['total_productos'],
                'fecha_creacion' => $row['fecha_orden']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $compras,
            'total' => $total,
            'total_pages' => $totalPages,
            'current_page' => $page
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Compras PDO Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Compras error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar los datos: ' . $e->getMessage()]);
}
?>