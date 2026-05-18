<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
    exit;
}

// Verificar permisos de administrador
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT rol FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$es_admin = false;
$roles_admin = ['admin', 'superadmin', 'ceo'];
if ($user && in_array(strtolower($user['rol']), $roles_admin)) {
    $es_admin = true;
}

// Verificar en admin_users
if (!$es_admin) {
    $stmt = $pdo->prepare("SELECT rol FROM admin_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $admin_user = $stmt->fetch();
    if ($admin_user) {
        $es_admin = true;
    }
}

if (!$es_admin) {
    echo json_encode([
        'success' => false,
        'error' => 'No tienes permisos de administrador'
    ]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !is_array($data)) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos no válidos. Se esperaba un objeto JSON.'
    ]);
    exit;
}

try {
    $actualizadas = 0;
    $no_editables = [];
    
    foreach ($data as $clave => $valor) {
        // Verificar que la clave existe y es editable
        $checkStmt = $pdo->prepare("SELECT id, editable FROM configuracion_sistema WHERE clave = :clave");
        $checkStmt->execute([':clave' => $clave]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            // Actualizar solo si es editable
            if ($exists['editable'] == 1) {
                $updateStmt = $pdo->prepare("UPDATE configuracion_sistema SET valor = :valor, updated_at = NOW() WHERE clave = :clave");
                $updateStmt->execute([
                    ':clave' => $clave,
                    ':valor' => $valor
                ]);
                $actualizadas++;
            } else {
                $no_editables[] = $clave;
            }
        } else {
            // Insertar nueva configuración si no existe (editable por defecto)
            $insertStmt = $pdo->prepare("INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, editable) VALUES (:clave, :valor, 'text', 'general', 1)");
            $insertStmt->execute([
                ':clave' => $clave,
                ':valor' => $valor
            ]);
            $actualizadas++;
        }
    }
    
    $mensaje = "Configuración guardada correctamente. ($actualizadas valores actualizados)";
    if (!empty($no_editables)) {
        $mensaje .= " No se pudieron modificar: " . implode(', ', $no_editables) . " (solo lectura)";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'actualizadas' => $actualizadas,
        'no_editables' => $no_editables
    ]);
    
} catch (Exception $e) {
    error_log("Error en guardar_configuracion.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>