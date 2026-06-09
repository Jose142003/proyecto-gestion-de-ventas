<?php
// eliminar_usuario.php (API Corregida - Compatible con panel_admin.php)
session_start();
require_once __DIR__ . '/../conexion/conexion.php';

// Configurar para JSON
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

verificarCSRF();

// ==============================================
// VERIFICACIÓN DEL MÉTODO HTTP
// ==============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método no permitido. Se requiere POST.'
    ]);
    exit;
}

// ==============================================
// PROCESAMIENTO DE DATOS DE ENTRADA (MÚLTIPLES FORMATOS)
// ==============================================
$usuario_a_eliminar_id = 0;
$input_source = 'unknown';

// Opción 1: Datos JSON (application/json)
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input_source = 'json';
    $json_input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_input)) {
        if (isset($json_input['id'])) {
            $usuario_a_eliminar_id = intval($json_input['id']);
        }
    }
    
    // Si no se pudo obtener del JSON, intentar otros métodos
    if ($usuario_a_eliminar_id <= 0) {
        $input_source .= '_fallback';
    }
}

// Opción 2: Datos POST tradicional (application/x-www-form-urlencoded o multipart/form-data)
if ($usuario_a_eliminar_id <= 0 && !empty($_POST)) {
    $input_source = 'post';
    if (isset($_POST['id'])) {
        $usuario_a_eliminar_id = intval($_POST['id']);
    }
}

// Opción 3: Datos GET (solo para pruebas/backwards compatibility)
if ($usuario_a_eliminar_id <= 0 && !empty($_GET) && isset($_GET['id'])) {
    $input_source = 'get';
    $usuario_a_eliminar_id = intval($_GET['id']);
}

// Opción 4: Intentar parsear cualquier dato JSON que haya llegado
if ($usuario_a_eliminar_id <= 0) {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $json_attempt = json_decode($raw_input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json_attempt)) {
            if (isset($json_attempt['id'])) {
                $input_source = 'php_input_json';
                $usuario_a_eliminar_id = intval($json_attempt['id']);
            }
        }
    }
}

// ==============================================
// VALIDACIÓN DE DATOS RECIBIDOS
// ==============================================
if ($usuario_a_eliminar_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Datos inválidos. Se requiere el ID del usuario (campo "id").'
    ]);
    exit;
}

// ==============================================
// VERIFICACIÓN DE SESIÓN - CORREGIDO PARA panel_admin.php
// ==============================================

$admin_id = null;
$admin_info = null;
$admin_rol = null;

try {
    $db = Database::getConnection();
    
    // ==============================================
    // VERIFICAR SESIÓN - Usando el mismo sistema que panel_admin.php
    // ==============================================
    
    // Verificar si existe user_id en sesión (lo establece panel_admin.php)
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        
        $user_id = $_SESSION['user_id'];
        
        // PRIMERO: Buscar en admin_users (administradores)
        $sql = "SELECT id, nombre, correo, usuario, rol, activo 
                FROM admin_users 
                WHERE id = ? AND activo = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar si tiene rol de administrador
            if (in_array($admin['rol'], ['superadmin', 'admin'])) {
                $admin_id = $admin['id'];
                $admin_info = $admin;
                $admin_rol = $admin['rol'];
            }
        }
        
        // SEGUNDO: Si no está en admin_users, buscar en users
        if (!$admin_id) {
            $sql = "SELECT id, nombre, correo, rol, is_active as activo 
                    FROM users 
                    WHERE id = ? AND is_active = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar si tiene rol de admin (por compatibilidad)
                if (strtolower($user['rol']) === 'admin') {
                    $admin_id = $user['id'];
                    $admin_info = $user;
                    $admin_rol = 'admin';
                }
            }
        }
    }
    
    // Verificar también si existe es_admin en sesión (lo establece panel_admin.php)
    if (!$admin_id && isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true) {
        // Intentar obtener el usuario actual desde la sesión
        if (isset($_SESSION['user_nombre'])) {
            // Verificar en admin_users por el nombre/correo
            $nombre = $_SESSION['user_nombre'];
            $sql = "SELECT id, nombre, correo, usuario, rol, activo 
                    FROM admin_users 
                    WHERE (nombre = ? OR correo = ?) AND activo = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$nombre, $nombre]);
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                $admin_id = $admin['id'];
                $admin_info = $admin;
                $admin_rol = $admin['rol'];
            } else {
                // Si no está en admin_users, buscar en users
                $sql = "SELECT id, nombre, correo, rol, is_active as activo 
                        FROM users 
                        WHERE nombre = ? AND is_active = 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$nombre]);
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $admin_id = $user['id'];
                    $admin_info = $user;
                    $admin_rol = $user['rol'];
                }
            }
        }
        
        // Último recurso: crear admin_info desde datos de sesión
        if (!$admin_id && isset($_SESSION['user_id'])) {
            $admin_id = $_SESSION['user_id'];
            $admin_info = [
                'id' => $_SESSION['user_id'],
                'nombre' => $_SESSION['user_nombre'] ?? 'Administrador',
                'correo' => $_SESSION['user_correo'] ?? '',
                'rol' => $_SESSION['user_rol'] ?? 'admin'
            ];
            $admin_rol = $admin_info['rol'];
        }
    }
    
    // Si no se encontró administrador, devolver 401
    if (!$admin_id) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Acceso denegado. Debes ser administrador para realizar esta acción.'
        ]);
        exit;
    }
    
    // ==============================================
    // VERIFICACIÓN DEL USUARIO A ELIMINAR
    // ==============================================
    
    // Verificar que el ID es válido
    if ($usuario_a_eliminar_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de usuario inválido.']);
        exit;
    }
    
    // Verificar que el usuario existe
    $sql = "SELECT id, nombre, correo, rol, is_active FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$usuario_a_eliminar_id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'El usuario no existe en el sistema.']);
        exit;
    }
    
    $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
    // Verificar que no se está intentando eliminar a sí mismo
    if ($usuario_a_eliminar_id == $admin_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta desde esta interfaz.']);
        exit;
    }
        
    // Verificar que no se está intentando eliminar a otro administrador
    if ($usuario_info['rol'] === 'admin') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar a otro administrador.']);
        exit;
    }
        
    // ==============================================
    // VERIFICAR DEPENDENCIAS
    // ==============================================
        
    $dependencies = [];
    $total_dependencies = 0;
    
    // Verificar facturas
    $sql = "SELECT COUNT(*) as count FROM facturas WHERE usuario_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$usuario_a_eliminar_id]);
    $facturas_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($facturas_count > 0) {
        $dependencies[] = "Facturas: $facturas_count";
        $total_dependencies += $facturas_count;
    }
    
    // Verificar movimientos de inventario
    $sql = "SELECT COUNT(*) as count FROM movimientos_inventario WHERE usuario_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$usuario_a_eliminar_id]);
    $movimientos_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($movimientos_count > 0) {
        $dependencies[] = "Movimientos: $movimientos_count";
        $total_dependencies += $movimientos_count;
    }
    
    // Verificar items en el carrito
    $sql = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$usuario_a_eliminar_id]);
    $carrito_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($carrito_count > 0) {
        $dependencies[] = "Items en carrito: $carrito_count";
        $total_dependencies += $carrito_count;
    }
    
    // Verificar pedidos asociados
    $sql = "SELECT COUNT(*) as count FROM pedidos WHERE usuario_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$usuario_a_eliminar_id]);
    $pedidos_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($pedidos_count > 0) {
        $dependencies[] = "Pedidos: $pedidos_count";
        $total_dependencies += $pedidos_count;
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // ==============================================
    // REGISTRAR EN AUDITORÍA ANTES DE LA ACCIÓN
    // ==============================================
    
    $admin_nombre = $admin_info['nombre'] ?? $admin_info['usuario'] ?? 'Administrador';
    $admin_correo = $admin_info['correo'] ?? $admin_info['email'] ?? '';
    $accion = ($total_dependencies > 0) ? 'USER_DEACTIVATED' : 'USER_DELETED';
    
    // Obtener IP del usuario
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $details = ($total_dependencies > 0) 
        ? "Usuario desactivado: {$usuario_info['nombre']} ({$usuario_info['correo']}). Dependencias: " . implode(', ', $dependencies)
        : "Usuario eliminado permanentemente: {$usuario_info['nombre']} ({$usuario_info['correo']})";
    
    $sql = "INSERT INTO auditoria_logs (usuario_id, usuario_nombre, usuario_rol, accion, modulo, descripcion, ip_address, user_agent, tabla_afectada, registro_id) 
            VALUES (?, ?, ?, ?, 'usuarios', ?, ?, ?, 'users', ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$admin_id, $admin_nombre, $admin_rol, $accion, $details, $ip_address, $user_agent, $usuario_a_eliminar_id]);
    
    // ==============================================
    // REALIZAR LA ELIMINACIÓN O DESACTIVACIÓN
    // ==============================================
    
    if ($total_dependencies > 0) {
        // Desactivar cuenta (eliminación lógica)
        $nuevo_correo = 'deleted_' . time() . '_' . $usuario_info['correo'];
        
        $sql = "UPDATE users 
                SET is_active = FALSE,
                    correo = ?,
                    telefono = NULL,
                    last_login = NOW()
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$nuevo_correo, $usuario_a_eliminar_id]);
        
        $action = 'deactivated';
        $message = 'El usuario tenía registros asociados, por lo que se ha desactivado su cuenta.';
        
    } else {
        // Eliminar físicamente
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$usuario_a_eliminar_id]);
        
        $action = 'deleted';
        $message = 'Usuario eliminado permanentemente del sistema.';
    }
    
    $db->commit();
    
    // ==============================================
    // ENVIAR RESPUESTA
    // ==============================================
    
    $response = [
        'success' => true,
        'message' => $message,
        'action' => $action,
        'usuario' => [
            'id' => $usuario_info['id'],
            'nombre' => $usuario_info['nombre'],
            'correo' => $usuario_info['correo']
        ],
        'admin' => [
            'id' => $admin_id,
            'nombre' => $admin_nombre
        ]
    ];
    
    if (!empty($dependencies)) {
        $response['dependencies'] = $dependencies;
    }
    
    auditoriaRegistrar('eliminar_usuario', 'usuarios', "Usuario {$usuario_info['nombre']} ({$usuario_info['correo']}) - Acción: $action");
    echo json_encode($response);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
    exit;
}
?>