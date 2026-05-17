<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Establecer zona horaria de Venezuela
date_default_timezone_set('America/Caracas');
require_once '../conexion/conexion.php';

// ====================================================================
// VERIFICAR AUTENTICACIÓN
// ====================================================================
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

// Configuración de la base de datos - usando conexión centralizada
try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error de conexión: ' . $e->getMessage(), 
        'facturas' => []
    ]);
    exit;
}

// Obtener facturas con toda la información necesaria - INCLUYENDO PEDIDOS RELACIONADOS
$sql = "SELECT f.*, 
               c.nombre as cliente_nombre, 
               c.documento as cliente_documento,
               c.email as cliente_email,
               c.telefono as cliente_telefono,
               p.numero_pedido,
               p.metodo_pago as pedido_metodo_pago,
               p.observaciones as pedido_observaciones,
               p.created_at as pedido_fecha,
               p.referencia_pago,
               p.estado as pedido_estado,
               u.nombre as usuario_nombre
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN pedidos p ON f.pedido_id = p.id
        LEFT JOIN users u ON f.usuario_id = u.id
        ORDER BY f.id DESC";

try {
    $result = $pdo->query($sql);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en consulta: ' . $e->getMessage(),
        'facturas' => []
    ]);
    exit;
}

$facturas = [];

// FUNCIÓN DETECTAR MÉTODO DE PAGO - CORREGIDA
function detectarMetodoPago($row) {
    $metodo_detectado = null;
    
    // 1. PRIORIDAD 1: Método de pago del pedido (es el más confiable)
    if (!empty($row['pedido_metodo_pago'])) {
        $metodo_pedido = strtolower(trim($row['pedido_metodo_pago']));
        if (in_array($metodo_pedido, ['efectivo', 'mixto', 'transferencia', 'pago_movil'])) {
            $metodo_detectado = $metodo_pedido;
        }
    }
    
    // 2. PRIORIDAD 2: Verificar observaciones del pedido
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
    
    // 3. PRIORIDAD 3: Verificar referencias de pago
    if (empty($metodo_detectado) && !empty($row['referencia_pago'])) {
        $ref = strtolower(trim($row['referencia_pago']));
        if (strpos($ref, 'movil') !== false) {
            $metodo_detectado = 'pago_movil';
        } elseif (strpos($ref, 'transfer') !== false) {
            $metodo_detectado = 'transferencia';
        }
    }
    
    // 4. PRIORIDAD 4: Método de pago de la factura (si existe y es válido)
    if (empty($metodo_detectado) && !empty($row['metodo_pago'])) {
        $metodo_factura = strtolower(trim($row['metodo_pago']));
        if (in_array($metodo_factura, ['efectivo', 'mixto', 'transferencia', 'pago_movil'])) {
            $metodo_detectado = $metodo_factura;
        }
    }
    
    // 5. VALOR POR DEFECTO
    if (empty($metodo_detectado)) {
        $metodo_detectado = 'pendiente';
    }
    
    return $metodo_detectado;
}

if ($result->rowCount() > 0) {
    while ($row = $result->fetch()) {
        // Detectar el método de pago usando la función mejorada
        $metodo_detectado = detectarMetodoPago($row);
        
        // Mapear a formato legible para mostrar
        switch ($metodo_detectado) {
            case 'efectivo':
                $metodo_mostrar = 'efectivo';
                break;
            case 'mixto':
                $metodo_mostrar = 'mixto';
                break;
            case 'transferencia':
                $metodo_mostrar = 'transferencia';
                break;
            case 'pago_movil':
                $metodo_mostrar = 'pago_movil';
                break;
            default:
                $metodo_mostrar = 'pendiente';
        }
        
        // DEBUG: Para ver en el log del servidor
        error_log("=== FACTURA ID: {$row['id']} ===");
        error_log("  metodo_pago_factura: {$row['metodo_pago']}");
        error_log("  pedido_metodo_pago: {$row['pedido_metodo_pago']}");
        error_log("  pedido_observaciones: {$row['pedido_observaciones']}");
        error_log("  referencia_pago: {$row['referencia_pago']}");
        error_log("  MÉTODO DETECTADO: $metodo_detectado");
        error_log("  MÉTODO MOSTRAR: $metodo_mostrar");
        error_log("-----------------------------------");
        
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
}

$pdo = null;

echo json_encode([
    'success' => true,
    'facturas' => $facturas,
    'total' => count($facturas),
    'message' => count($facturas) . ' factura(s) encontrada(s)'
]);
?>