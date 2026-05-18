<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

require_once dirname(__DIR__) . '/conexion/conexion.php';

try {
    $db = conectarDB();
    $db->beginTransaction();

    // 1. Validar datos básicos
    $compraId = isset($_POST['compra_id']) ? intval($_POST['compra_id']) : 0;
    if ($compraId === 0) {
        throw new Exception('ID de compra no válido.');
    }

    // 2. Actualizar la cabecera de la compra
    $queryUpdate = "UPDATE compras SET 
                    proveedor_id = :proveedor_id,
                    fecha_orden = :fecha_orden,
                    fecha_requerida = :fecha_requerida,
                    estado = :estado,
                    metodo_pago = :metodo_pago,
                    condiciones_pago = :condiciones_pago,
                    observaciones = :observaciones,
                    subtotal = :subtotal,
                    iva = :iva,
                    total = :total,
                    updated_at = NOW()
                    WHERE id = :id";

    $stmt = $db->prepare($queryUpdate);
    
    // Limpiar formatos de moneda si vienen con comas/puntos de miles del JS
    $subtotal = str_replace(',', '', $_POST['subtotal']);
    $iva = str_replace(',', '', $_POST['iva']);
    $total = str_replace(',', '', $_POST['total']);

    $stmt->execute([
        ':proveedor_id' => $_POST['proveedor_id'],
        ':fecha_orden' => $_POST['fecha_orden'],
        ':fecha_requerida' => $_POST['fecha_requerida'],
        ':estado' => $_POST['estado'],
        ':metodo_pago' => $_POST['metodo_pago'],
        ':condiciones_pago' => $_POST['condiciones_pago'],
        ':observaciones' => $_POST['observaciones'],
        ':subtotal' => $subtotal,
        ':iva' => $iva,
        ':total' => $total,
        ':id' => $compraId
    ]);

    // 3. Gestionar los productos (Detalles)
    // La forma más limpia es eliminar los anteriores e insertar los nuevos
    $queryDelete = "DELETE FROM compra_detalles WHERE compra_id = :compra_id";
    $stmtDel = $db->prepare($queryDelete);
    $stmtDel->execute([':compra_id' => $compraId]);

    if (isset($_POST['productos']) && is_array($_POST['productos'])) {
        $queryInsertDetalle = "INSERT INTO compra_detalles (compra_id, producto_id, cantidad, precio_unitario, subtotal) 
                               VALUES (:compra_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
        $stmtInsert = $db->prepare($queryInsertDetalle);

        foreach ($_POST['productos'] as $prod) {
            $cant = floatval($prod['cantidad']);
            $prec = floatval($prod['precio_unitario']);
            $sub = $cant * $prec;

            $stmtInsert->execute([
                ':compra_id' => $compraId,
                ':producto_id' => $prod['producto_id'],
                ':cantidad' => $cant,
                ':precio_unitario' => $prec,
                ':subtotal' => $sub
            ]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Compra actualizada correctamente']);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>