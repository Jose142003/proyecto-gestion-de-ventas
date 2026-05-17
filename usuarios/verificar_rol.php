<?php
// /proyecto/usuarios/verificar_rol.php
// VERIFICADOR UNIFICADO DE ROL - VERSIÓN DEFINITIVA

session_start();

header('Content-Type: application/json');

error_log("=== verificar_rol.php - INICIO ===");
error_log("SESSION: " . print_r($_SESSION, true));

// Verificar si hay sesión activa
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    error_log("No hay sesión activa - Usuario invitado");
    echo json_encode([
        'success' => false,
        'message' => 'No autenticado',
        'role' => 'guest',
        'is_guest' => true,
        'redirect' => null
    ]);
    exit;
}

// Obtener datos de sesión
$user_id = $_SESSION['user_id'] ?? null;
$user_nombre = $_SESSION['user_nombre'] ?? 'Usuario';
$user_correo = $_SESSION['user_correo'] ?? '';
$tabla_origen = $_SESSION['tabla_origen'] ?? null;
$user_rol = $_SESSION['user_rol'] ?? null;

error_log("Datos: user_id=$user_id, tabla_origen=$tabla_origen, user_rol=$user_rol");

require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de conexión', 'role' => 'error']);
    exit;
}

// ========== CASO 1: ADMINISTRADOR (tabla admin_users) ==========
if ($tabla_origen === 'admin_users') {
    error_log("✅ ADMINISTRADOR detectado por tabla_origen");
    
    // Verificar que el admin existe y está activo en BD
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, activo FROM admin_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && $admin['activo'] == 1) {
        echo json_encode([
            'success' => true,
            'role' => 'admin',
            'is_admin' => true,
            'is_cliente' => false,
            'user' => [
                'id' => $admin['id'],
                'nombre' => $admin['nombre'],
                'correo' => $admin['correo'],
                'rol' => 'admin'
            ],
            'redirect' => '/proyecto/admin-panel/panel_admin.php',
            'message' => 'Acceso como administrador'
        ]);
        exit;
    } else {
        // Admin no existe o está inactivo - destruir sesión
        session_destroy();
        echo json_encode([
            'success' => false,
            'role' => 'invalid',
            'message' => 'Usuario administrador inválido',
            'redirect' => '/proyecto/usuario/login.html'
        ]);
        exit;
    }
}

// ========== CASO 2: CLIENTE (tabla users) ==========
if ($tabla_origen === 'users') {
    error_log("✅ CLIENTE detectado por tabla_origen");
    
    // Verificar que el cliente existe y está activo en BD
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, estado, is_active FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['estado'] === 'activo' && $user['is_active'] == 1) {
        echo json_encode([
            'success' => true,
            'role' => 'cliente',
            'is_admin' => false,
            'is_cliente' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol']
            ],
            'redirect' => null,
            'can_purchase' => true
        ]);
        exit;
    } else {
        // Cliente inactivo - destruir sesión
        session_destroy();
        echo json_encode([
            'success' => false,
            'role' => 'inactive',
            'message' => 'Usuario inactivo',
            'redirect' => '/proyecto/usuario/login.html'
        ]);
        exit;
    }
}

// ========== CASO 3: Sesión sin tabla_origen (legacy) ==========
if ($tabla_origen === null && isset($_SESSION['user_id'])) {
    error_log("⚠️ Sesión legacy detectada - Intentando determinar tipo");
    
    // Buscar primero en admin_users
    $stmt = $pdo->prepare("SELECT id, nombre, correo, 'admin' as tipo FROM admin_users WHERE id = ? AND activo = 1");
    $stmt->execute([$user_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        error_log("✅ Sesión legacy - Usuario es ADMINISTRADOR");
        $_SESSION['tabla_origen'] = 'admin_users';
        $_SESSION['es_admin'] = true;
        
        echo json_encode([
            'success' => true,
            'role' => 'admin',
            'is_admin' => true,
            'is_cliente' => false,
            'user' => [
                'id' => $admin['id'],
                'nombre' => $admin['nombre'],
                'correo' => $admin['correo'],
                'rol' => 'admin'
            ],
            'redirect' => '/proyecto/admin-panel/panel_admin.php'
        ]);
        exit;
    }
    
    // Buscar en users
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol FROM users WHERE id = ? AND estado = 'activo' AND is_active = 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        error_log("✅ Sesión legacy - Usuario es CLIENTE");
        $_SESSION['tabla_origen'] = 'users';
        $_SESSION['es_admin'] = false;
        
        echo json_encode([
            'success' => true,
            'role' => 'cliente',
            'is_admin' => false,
            'is_cliente' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol']
            ],
            'redirect' => null,
            'can_purchase' => true
        ]);
        exit;
    }
    
    // No encontrado en ninguna tabla - destruir sesión
    session_destroy();
    echo json_encode([
        'success' => false,
        'role' => 'invalid',
        'message' => 'Usuario no encontrado',
        'redirect' => '/proyecto/usuario/login.html'
    ]);
    exit;
}

// ========== CASO 4: Cualquier otro caso ==========
error_log("⚠️ Sesión no reconocida - Cerrando sesión");
session_destroy();
echo json_encode([
    'success' => false,
    'role' => 'invalid',
    'message' => 'Sesión inválida',
    'redirect' => '/proyecto/usuario/login.html'
]);
exit;
?>