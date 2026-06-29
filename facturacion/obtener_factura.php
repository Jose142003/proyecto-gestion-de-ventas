<?php
session_start();
header('Content-Type: application/json');
error_reporting(0); ini_set('display_errors', 0);

date_default_timezone_set('America/Caracas');

$usuario_autenticado = false;
$usuario_id = null;

if (isset($_SESSION['user_id'])) {
    $usuario_autenticado = true;
    $usuario_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['usuario_id'])) {
    $usuario_autenticado = true;
    $usuario_id = $_SESSION['usuario_id'];
} elseif (isset($_SESSION['id'])) {
    $usuario_autenticado = true;
    $usuario_id = $_SESSION['id'];
}

if (!$usuario_autenticado) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado - Inicie sesión nuevamente',
        'facturas' => []
    ]);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'facturas' => []
    ]);
    exit;
}

$facturaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($facturaId <= 0) {
    $sql = "SELECT f.*,
                   c.nombre as cliente_nombre,
                   c.documento as cliente_documento,
                   c.email as cliente_email,
                   c.telefono as cliente_telefono,
                   p.numero_pedido,
                   p.metodo_pago as pedido_metodo_pago,
                   p.observaciones as pedido_observaciones,
                   p.created_at as pedido_fecha,
                   p.referencia_pago as pedido_referencia_pago,
                   p.estado as pedido_estado,
                   u.nombre as usuario_nombre
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            LEFT JOIN pedidos p ON f.pedido_id = p.id
            LEFT JOIN users u ON f.usuario_id = u.id
            ORDER BY f.id DESC";

    try {
        $stmt = $pdo->query($sql);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor', 'facturas' => []]);
        exit;
    }

    $facturas = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $metodo_detectado = detectarMetodoPago($row);
        switch ($metodo_detectado) {
            case 'efectivo': $metodo_mostrar = 'efectivo'; break;
            case 'mixto': $metodo_mostrar = 'mixto'; break;
            case 'transferencia': $metodo_mostrar = 'transferencia'; break;
            case 'pago_movil': $metodo_mostrar = 'pago_movil'; break;
            default: $metodo_mostrar = 'pendiente';
        }
        $facturas[] = [
            'id' => $row['id'],
            'numero_factura' => $row['numero_factura'],
            'cliente_id' => $row['cliente_id'],
            'cliente_nombre' => $row['cliente_nombre'] ?? 'Cliente no registrado',
            'cliente_documento' => $row['cliente_documento'] ?? 'N/A',
            'cliente_email' => $row['cliente_email'] ?? 'N/A',
            'cliente_telefono' => $row['cliente_telefono'] ?? 'N/A',
            'pedido_id' => $row['pedido_id'],
            'numero_pedido' => $row['numero_pedido'] ?? 'N/A',
            'fecha_emision' => $row['fecha_emision'],
            'fecha_vencimiento' => $row['fecha_vencimiento'],
            'subtotal' => floatval($row['subtotal'] ?? 0),
            'iva' => floatval($row['iva'] ?? 0),
            'total' => floatval($row['total'] ?? 0),
            'metodo_pago' => $metodo_mostrar,
            'metodo_pago_raw' => $metodo_detectado,
            'estado' => $row['estado'] ?? 'pendiente',
            'usuario_id' => $row['usuario_id'],
            'usuario_nombre' => $row['usuario_nombre'] ?? 'Sistema',
            'created_at' => $row['created_at'],
            'pedido_fecha' => $row['pedido_fecha'] ?? null
        ];
    }

    echo json_encode([
        'success' => true,
        'facturas' => $facturas,
        'total' => count($facturas),
        'message' => count($facturas) . ' factura(s) encontrada(s)'
    ]);
    exit;
}

// Single factura by ID
$sql = "SELECT f.*,
               c.nombre as cliente_nombre,
               c.documento as cliente_documento,
               c.email as cliente_email,
               c.telefono as cliente_telefono,
               p.numero_pedido,
               p.metodo_pago as pedido_metodo_pago,
               p.observaciones as pedido_observaciones,
               p.created_at as pedido_fecha,
               p.referencia_pago as pedido_referencia_pago,
               p.estado as pedido_estado,
               u.nombre as usuario_nombre
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN pedidos p ON f.pedido_id = p.id
        LEFT JOIN users u ON f.usuario_id = u.id
        WHERE f.id = :id
        LIMIT 1";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $facturaId]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit;
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
    exit;
}

$metodo_detectado = detectarMetodoPago($row);
switch ($metodo_detectado) {
    case 'efectivo': $metodo_mostrar = 'efectivo'; break;
    case 'mixto': $metodo_mostrar = 'mixto'; break;
    case 'transferencia': $metodo_mostrar = 'transferencia'; break;
    case 'pago_movil': $metodo_mostrar = 'pago_movil'; break;
    default: $metodo_mostrar = 'pendiente';
}

$factura = [
    'id' => $row['id'],
    'numero_factura' => $row['numero_factura'],
    'cliente_id' => $row['cliente_id'],
    'cliente_nombre' => $row['cliente_nombre'] ?? 'Cliente no registrado',
    'cliente_documento' => $row['cliente_documento'] ?? 'N/A',
    'cliente_email' => $row['cliente_email'] ?? 'N/A',
    'cliente_telefono' => $row['cliente_telefono'] ?? 'N/A',
    'pedido_id' => $row['pedido_id'],
    'numero_pedido' => $row['numero_pedido'] ?? 'N/A',
    'fecha_emision' => $row['fecha_emision'],
    'fecha_vencimiento' => $row['fecha_vencimiento'],
    'subtotal' => floatval($row['subtotal'] ?? 0),
    'iva' => floatval($row['iva'] ?? 0),
    'total' => floatval($row['total'] ?? 0),
    'metodo_pago' => $metodo_mostrar,
    'metodo_pago_raw' => $metodo_detectado,
    'estado' => $row['estado'] ?? 'pendiente',
    'usuario_id' => $row['usuario_id'],
    'usuario_nombre' => $row['usuario_nombre'] ?? 'Sistema',
    'created_at' => $row['created_at'],
    'pedido_fecha' => $row['pedido_fecha'] ?? null
];

// Get detalles (line items)
$detallesSql = "SELECT fd.id, fd.producto_id, fd.cantidad, fd.precio_unitario, fd.subtotal,
                       p.name as producto_nombre, p.sku as producto_sku
                FROM factura_detalles fd
                LEFT JOIN products p ON fd.producto_id = p.id
                WHERE fd.factura_id = :factura_id
                ORDER BY fd.id ASC";

try {
    $detallesStmt = $pdo->prepare($detallesSql);
    $detallesStmt->execute([':factura_id' => $facturaId]);
    $detalles = [];
    while ($det = $detallesStmt->fetch(PDO::FETCH_ASSOC)) {
        $detalles[] = [
            'id' => (int)$det['producto_id'],
            'producto_id' => (int)$det['producto_id'],
            'factura_detalle_id' => (int)$det['id'],
            'producto_nombre' => $det['producto_nombre'] ?? 'Producto #' . $det['producto_id'],
            'producto_sku' => $det['producto_sku'] ?? '',
            'cantidad' => (int)$det['cantidad'],
            'precio_unitario' => floatval($det['precio_unitario']),
            'subtotal' => floatval($det['subtotal'])
        ];
    }
    $factura['detalles'] = $detalles;
} catch (PDOException $e) {
    $factura['detalles'] = [];
}

echo json_encode([
    'success' => true,
    'factura' => $factura
]);

function detectarMetodoPago($row) {
    $metodo_detectado = null;

    if (!empty($row['pedido_metodo_pago'])) {
        $metodo_pedido = strtolower(trim($row['pedido_metodo_pago']));
        if (in_array($metodo_pedido, ['efectivo', 'mixto', 'transferencia', 'pago_movil'])) {
            $metodo_detectado = $metodo_pedido;
        }
    }

    if (empty($metodo_detectado) && !empty($row['pedido_observaciones'])) {
        $obs = strtolower(trim($row['pedido_observaciones']));
        if (strpos($obs, 'mixto') !== false) {
            $metodo_detectado = 'mixto';
        } elseif (strpos($obs, 'efectivo') !== false) {
            $metodo_detectado = 'efectivo';
        } elseif (strpos($obs, 'transferencia') !== false) {
            $metodo_detectado = 'transferencia';
        } elseif (strpos($obs, 'pago movil') !== false || strpos($obs, 'pago_movil') !== false) {
            $metodo_detectado = 'pago_movil';
        }
    }

    if (empty($metodo_detectado) && !empty($row['referencia_pago'])) {
        $ref = strtolower(trim($row['referencia_pago']));
        if (strpos($ref, 'movil') !== false) {
            $metodo_detectado = 'pago_movil';
        } elseif (strpos($ref, 'transfer') !== false) {
            $metodo_detectado = 'transferencia';
        }
    }

    if (empty($metodo_detectado) && !empty($row['metodo_pago'])) {
        $metodo_factura = strtolower(trim($row['metodo_pago']));
        if (in_array($metodo_factura, ['efectivo', 'mixto', 'transferencia', 'pago_movil'])) {
            $metodo_detectado = $metodo_factura;
        }
    }

    if (empty($metodo_detectado)) {
        $metodo_detectado = 'pendiente';
    }

    return $metodo_detectado;
}
