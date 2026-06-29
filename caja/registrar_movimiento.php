<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();
verificarCSRF();

$usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tipo']) || !isset($data['monto']) || !isset($data['descripcion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $db = conectarDB();
    
    // =============================================================
    // AUTOMATIZACIÓN: Verificar si existe caja abierta para hoy
    // Si no existe, se CREA AUTOMÁTICAMENTE
    // =============================================================
    
    $stmt = $db->prepare("SELECT * FROM caja_arqueos WHERE estado = 'abierta' AND DATE(fecha_apertura) = CURDATE() ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $caja = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si NO hay caja abierta para hoy, la creamos automáticamente
    if (!$caja) {
        // Verificar si ya existe una caja cerrada para hoy (para no duplicar)
        $stmtCheck = $db->prepare("SELECT id FROM caja_arqueos WHERE DATE(fecha_apertura) = CURDATE()");
        $stmtCheck->execute();
        $existeCajaHoy = $stmtCheck->rowCount() > 0;
        
        if (!$existeCajaHoy) {
            // Crear nueva caja automática con monto inicial 0
            $numeroArqueo = 'CAJA-AUTO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $stmtInsert = $db->prepare("INSERT INTO caja_arqueos (numero_arqueo, fecha_apertura, usuario_apertura_id, monto_inicial, estado, observaciones) 
                                        VALUES (?, NOW(), ?, 0, 'abierta', 'Apertura automática por primer movimiento del día')");
            $stmtInsert->execute([$numeroArqueo, $usuario_id]);
            
            // Obtener la caja recién creada
            $stmt = $db->prepare("SELECT * FROM caja_arqueos WHERE estado = 'abierta' AND DATE(fecha_apertura) = CURDATE() ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $caja = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Si existe pero está cerrada, buscar la más reciente del día (cerrada)
            $stmtClosed = $db->prepare("SELECT * FROM caja_arqueos WHERE DATE(fecha_apertura) = CURDATE() ORDER BY id DESC LIMIT 1");
            $stmtClosed->execute();
            $cajaCerrada = $stmtClosed->fetch(PDO::FETCH_ASSOC);
            
            if ($cajaCerrada) {
                echo json_encode(['success' => false, 'message' => 'La caja de hoy ya fue cerrada. No se pueden registrar más movimientos.']);
                exit;
            }
        }
    }
    
    // Volver a verificar que tenemos caja
    if (!$caja) {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear o encontrar una caja para hoy']);
        exit;
    }
    
    $tipo = $data['tipo'];
    $monto = floatval($data['monto']);
    $categoria = $data['categoria'] ?? ($tipo == 'ingreso' ? 'Venta' : 'Gasto');
    $descripcion = $data['descripcion'];
    $referencia = $data['referencia'] ?? null;
    $metodo_pago = $data['metodo_pago'] ?? 'efectivo';
    
    // Verificar que no haya saldo negativo para egresos
    if ($tipo == 'egreso') {
        $saldo_actual = $caja['monto_inicial'] + $caja['monto_ingresos'] - $caja['monto_egresos'];
        if ($saldo_actual < $monto) {
            echo json_encode(['success' => false, 'message' => 'Saldo insuficiente en caja']);
            exit;
        }
    }
    
    $db->beginTransaction();
    
    // Insertar movimiento
    $stmt = $db->prepare("INSERT INTO caja_movimientos (arqueo_id, tipo, categoria, monto, descripcion, referencia, metodo_pago, usuario_id, fecha_movimiento) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$caja['id'], $tipo, $categoria, $monto, $descripcion, $referencia, $metodo_pago, $usuario_id]);
    
    // Actualizar montos en caja_arqueos
    if ($tipo == 'ingreso') {
        $stmt = $db->prepare("UPDATE caja_arqueos SET monto_ingresos = monto_ingresos + ? WHERE id = ?");
    } else {
        $stmt = $db->prepare("UPDATE caja_arqueos SET monto_egresos = monto_egresos + ? WHERE id = ?");
    }
    $stmt->execute([$monto, $caja['id']]);
    
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Movimiento registrado correctamente']);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("Error registrar movimiento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al registrar movimiento']);
}
?>