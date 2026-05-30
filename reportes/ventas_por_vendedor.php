<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

error_reporting(0); ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

try {
    $pdo = conectarDB();
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error interno del servidor']);
    exit;
}

try {
    $vendedor_id = isset($_GET['vendedor_id']) ? (int)$_GET['vendedor_id'] : 0;

    // Obtener vendedores (solo admin_users)
    $stmt = $pdo->prepare("
        SELECT id, nombre, correo as email, rol
        FROM admin_users
        WHERE activo = 1
    ");
    $stmt->execute();
    $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Detectar columnas disponibles en la BD
    $pedidosColumns = $pdo->query("DESCRIBE pedidos")->fetchAll(PDO::FETCH_COLUMN);
    $facturasColumns = $pdo->query("DESCRIBE facturas")->fetchAll(PDO::FETCH_COLUMN);

    $resultado = [];

    foreach ($vendedores as $v) {
        // Las ventas del vendedor se determinan a través de facturas.usuario_id
        // (facturas registra qué admin procesó la factura/pedido)
        $sqlVentas = "
            SELECT
                COUNT(DISTINCT f.id) as total_ventas,
                COALESCE(SUM(p.total), 0) as monto_total,
                COALESCE(SUM(pd.cantidad), 0) as total_productos,
                MAX(p.created_at) as ultima_venta
            FROM facturas f
            LEFT JOIN pedidos p ON f.pedido_id = p.id
            LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
            WHERE f.usuario_id = :vendedor_id
        ";

        // Si no existe la tabla facturas o la columna, fallback a pedidos
        if (!in_array('usuario_id', $facturasColumns)) {
            // Intentar por pedidos buscando coincidencia directa del admin_users.id
            $condiciones = [];
            if (in_array('usuario_id', $pedidosColumns)) {
                $condiciones[] = "p.usuario_id = :vendedor_id";
            }
            if (in_array('usuario_procesa_id', $pedidosColumns)) {
                $condiciones[] = "p.usuario_procesa_id = :vendedor_id";
            }
            if (in_array('vendedor_id', $pedidosColumns)) {
                $condiciones[] = "p.vendedor_id = :vendedor_id";
            }
            if (in_array('created_by', $pedidosColumns)) {
                $condiciones[] = "p.created_by = :vendedor_id";
            }
            $cond_str = empty($condiciones) ? '1=0' : implode(' OR ', $condiciones);

            $sqlVentas = "
                SELECT
                    COUNT(DISTINCT p.id) as total_ventas,
                    COALESCE(SUM(p.total), 0) as monto_total,
                    COALESCE(SUM(pd.cantidad), 0) as total_productos,
                    MAX(p.created_at) as ultima_venta
                FROM pedidos p
                LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
                WHERE ($cond_str)
                  AND p.estado IN ('facturado', 'entregado', 'pendiente')
            ";
        }

        $stmtVentas = $pdo->prepare($sqlVentas);
        $stmtVentas->bindParam(':vendedor_id', $v['id'], PDO::PARAM_INT);
        $stmtVentas->execute();
        $ventas = $stmtVentas->fetch(PDO::FETCH_ASSOC);

        $total_ventas = (int)($ventas['total_ventas'] ?? 0);
        $monto_total = (float)($ventas['monto_total'] ?? 0);
        $total_productos = (int)($ventas['total_productos'] ?? 0);
        $promedio = $total_ventas > 0 ? $monto_total / $total_ventas : 0;

        $fecha_formateada = '';
        if ($total_ventas > 0 && !empty($ventas['ultima_venta']) && $ventas['ultima_venta'] !== '0000-00-00 00:00:00') {
            try {
                $fecha_obj = new DateTime($ventas['ultima_venta']);
                $fecha_formateada = $fecha_obj->format('Y-m-d');
            } catch(Exception $e) {
                $fecha_formateada = '';
            }
        }

        $resultado[] = [
            'id' => $v['id'],
            'nombre' => $v['nombre'],
            'email' => $v['email'] ?? 'N/A',
            'total_ventas' => $total_ventas,
            'total_productos' => $total_productos,
            'monto_total' => $monto_total,
            'promedio' => round($promedio, 2),
            'ultima_venta' => $fecha_formateada
        ];
    }

    // Aplicar filtro por vendedor_id
    if ($vendedor_id > 0) {
        $resultado = array_values(array_filter($resultado, function($v) use ($vendedor_id) {
            return $v['id'] === $vendedor_id;
        }));
    }

    // Ordenar por monto total descendente
    usort($resultado, function($a, $b) {
        return $b['monto_total'] <=> $a['monto_total'];
    });

    echo json_encode($resultado);

} catch(PDOException $e) {
    error_log("Error en ventas_por_vendedor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
} catch(Exception $e) {
    error_log("Error general en ventas_por_vendedor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
