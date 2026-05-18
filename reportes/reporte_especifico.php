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
                $sql .= " AND (c.nombre LIKE :buscar OR c.email LIKE :buscar OR f.numero_factura LIKE :buscar)";
            }
            $sql .= " ORDER BY f.fecha_emision DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':desde', $desde);
            $stmt->bindParam(':hasta', $hasta);
            if ($estado && $estado != '') $stmt->bindParam(':estado', $estado);
            if ($buscar && $buscar != '') $stmt->bindValue(':buscar', "%$buscar%");
            break;
            
        // ... resto de casos (pedidos, compras, clientes, productos) igual que antes ...
        
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
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>