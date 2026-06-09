<?php
session_start();
header('Content-Type: application/json');
error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    // Obtener parámetros
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    $tipo = $_GET['tipo'] ?? 'ventas';
    $estado = $_GET['estado'] ?? '';
    $buscar = $_GET['buscar'] ?? '';
    
    $datos = [];
    $total_registros = 0;
    $total_monto = 0;
    
    switch ($tipo) {
        case 'ventas':
            // Consulta SIMPLIFICADA que obtiene el vendedor correctamente
            $sql = "SELECT 
                        f.id,
                        DATE(f.fecha_emision) as fecha,
                        f.numero_factura,
                        c.nombre as cliente_nombre,
                        c.email as cliente_email,
                        c.telefono as cliente_telefono,
                        f.total,
                        f.estado,
                        f.metodo_pago,
                        f.usuario_id as vendedor_id,
                        -- OBTENER VENDEDOR: Buscar en admin_users O users
                        COALESCE(
                            (SELECT nombre FROM admin_users WHERE id = f.usuario_id LIMIT 1),
                            (SELECT nombre FROM users WHERE id = f.usuario_id LIMIT 1),
                            'No asignado'
                        ) as vendedor_nombre
                    FROM facturas f
                    LEFT JOIN clientes c ON f.cliente_id = c.id
                    WHERE DATE(f.fecha_emision) BETWEEN :desde AND :hasta
                    AND f.estado != 'anulada'";
            
            if ($estado && $estado != '') {
                $sql .= " AND f.estado = :estado";
            }
            if ($buscar && $buscar != '') {
                $sql .= " AND (c.nombre LIKE :buscar1 OR c.email LIKE :buscar2 OR f.numero_factura LIKE :buscar3)";
            }
            $sql .= " ORDER BY f.fecha_emision DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':desde', $desde);
            $stmt->bindParam(':hasta', $hasta);
            if ($estado && $estado != '') $stmt->bindParam(':estado', $estado);
            if ($buscar && $buscar != '') {
                $stmt->bindValue(':buscar1', "%$buscar%");
                $stmt->bindValue(':buscar2', "%$buscar%");
                $stmt->bindValue(':buscar3', "%$buscar%");
            }
            break;

        case 'pedidos':
            $sql = "SELECT p.id, DATE(p.created_at) as fecha, p.numero_pedido,
                           u.nombre as cliente_nombre, u.correo as cliente_email,
                           p.total, p.estado, p.metodo_pago
                    FROM pedidos p
                    LEFT JOIN users u ON p.usuario_id = u.id
                    WHERE DATE(p.created_at) BETWEEN :desde AND :hasta";
            if ($estado) { $sql .= " AND p.estado = :estado"; }
            if ($buscar) { $sql .= " AND (u.nombre LIKE :buscar1 OR p.numero_pedido LIKE :buscar2)"; }
            $sql .= " ORDER BY p.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':desde', $desde);
            $stmt->bindParam(':hasta', $hasta);
            if ($estado) $stmt->bindParam(':estado', $estado);
            if ($buscar) {
                $stmt->bindValue(':buscar1', "%$buscar%");
                $stmt->bindValue(':buscar2', "%$buscar%");
            }
            break;

        case 'compras':
            $sql = "SELECT c.id, c.fecha_orden as fecha, c.numero_orden,
                           pr.nombre_comercial as proveedor_nombre, pr.ruc as proveedor_ruc,
                           c.total, c.estado, c.metodo_pago
                    FROM compras c
                    LEFT JOIN proveedores pr ON c.proveedor_id = pr.id
                    WHERE c.fecha_orden BETWEEN :desde AND :hasta";
            if ($estado) { $sql .= " AND c.estado = :estado"; }
            if ($buscar) { $sql .= " AND (pr.nombre_comercial LIKE :buscar1 OR c.numero_orden LIKE :buscar2)"; }
            $sql .= " ORDER BY c.fecha_orden DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':desde', $desde);
            $stmt->bindParam(':hasta', $hasta);
            if ($estado) $stmt->bindParam(':estado', $estado);
            if ($buscar) {
                $stmt->bindValue(':buscar1', "%$buscar%");
                $stmt->bindValue(':buscar2', "%$buscar%");
            }
            break;

        case 'clientes':
            $sql = "SELECT id, nombre, email, telefono, ciudad, estado as cliente_estado,
                           DATE(fecha_registro) as fecha_registro
                    FROM clientes
                    WHERE DATE(fecha_registro) BETWEEN :desde AND :hasta";
            if ($estado) { $sql .= " AND estado = :estado"; }
            if ($buscar) { $sql .= " AND (nombre LIKE :buscar1 OR email LIKE :buscar2 OR documento LIKE :buscar3)"; }
            $sql .= " ORDER BY fecha_registro DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':desde', $desde);
            $stmt->bindParam(':hasta', $hasta);
            if ($estado) $stmt->bindParam(':estado', $estado);
            if ($buscar) {
                $stmt->bindValue(':buscar1', "%$buscar%");
                $stmt->bindValue(':buscar2', "%$buscar%");
                $stmt->bindValue(':buscar3', "%$buscar%");
            }
            break;

        case 'productos':
            $sql = "SELECT id, sku, name as nombre, category as categoria,
                           price as precio, stock, is_featured as destacado
                    FROM products
                    WHERE deleted_at IS NULL";
            if ($buscar) { $sql .= " AND (name LIKE :buscar1 OR sku LIKE :buscar2 OR category LIKE :buscar3)"; }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            if ($buscar) {
                $stmt->bindValue(':buscar1', "%$buscar%");
                $stmt->bindValue(':buscar2', "%$buscar%");
                $stmt->bindValue(':buscar3', "%$buscar%");
            }
            // productos no tiene filtro de fecha ni estado igual que los demas
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_registros = count($datos);
            $total_monto = 0;
            $promedio = 0;
            echo json_encode(['success' => true, 'data' => $datos, 'total_registros' => $total_registros, 'total_monto' => $total_monto, 'promedio' => $promedio]);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Tipo de reporte no válido']);
            exit;
    }
    
    $stmt->execute();
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_registros = count($datos);
    
    foreach ($datos as $d) {
        $total_monto += floatval($d['total'] ?? 0);
    }
    
    $promedio = $total_registros > 0 ? $total_monto / $total_registros : 0;
    
    echo json_encode([
        'success' => true,
        'data' => $datos,
        'total_registros' => $total_registros,
        'total_monto' => $total_monto,
        'promedio' => $promedio
    ]);
    
} catch (Exception $e) {
    $msg = 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    error_log("[reporte_especifico] $msg");
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>