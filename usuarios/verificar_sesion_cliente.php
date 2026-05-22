<?php
// /proyecto/usuarios/verificar_sesion_cliente.php
// VERIFICACIÓN EXCLUSIVA PARA CLIENTES - NO REDIRIGE A ADMIN

session_name('CLIENTSESSID');
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: 0');

error_log("=== verificar_sesion_cliente.php - INICIO ===");

// ========== CONEXIÓN A BASE DE DATOS ==========
require_once __DIR__ . '/../conexion/conexion.php';
$pdo = conectarDB();

// ========== VERIFICAR SESIÓN ACTIVA ==========
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si tenía persist_token (sesión se perdió), redirigir al login
    if (isset($_COOKIE['persist_token'])) {
        error_log("Sesión perdida pero hay persist_token - Redirigiendo al login");
        echo json_encode([
            'success' => false,
            'is_authenticated' => false,
            'role' => 'guest',
            'redirect' => '/proyecto/interfaz_usuario/login.html',
            'message' => 'Sesión expirada'
        ]);
        exit;
    }
    
    error_log("No hay sesión activa - Invitado");
    echo json_encode([
        'success' => false,
        'is_authenticated' => false,
        'role' => 'guest',
        'is_admin' => false,
        'is_cliente' => false,
        'can_purchase' => false,
        'message' => 'No autenticado'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$tabla_origen = $_SESSION['tabla_origen'] ?? null;
$user_rol = $_SESSION['user_rol'] ?? '';

error_log("user_id: $user_id, tabla_origen: $tabla_origen, user_rol: $user_rol");

// ========== CASO 1: Es ADMINISTRADOR (NO PUEDE COMPRAR) ==========
if ($tabla_origen === 'admin_users') {
    error_log("⚠️ ADMINISTRADOR detectado - Modo invitado en tienda");
    
    echo json_encode([
        'success' => false,
        'is_authenticated' => false,
        'role' => 'guest',
        'is_admin' => false,
        'is_cliente' => false,
        'can_purchase' => false,
        'force_logout' => false,
        'message' => 'Los administradores no pueden comprar en la tienda'
    ]);
    exit;
}

// ========== CASO 2: Es CLIENTE VÁLIDO ==========
if ($tabla_origen === 'users' || ($tabla_origen === null && $user_rol === 'cliente')) {
    // Verificar en la base de datos que el cliente existe y está activo
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, estado, is_active FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['estado'] === 'activo' && $user['is_active'] == 1) {
        error_log("✅ CLIENTE válido: " . $user['nombre']);
        
        // Asegurar que la sesión tenga los valores correctos
        if ($tabla_origen !== 'users') {
            $_SESSION['tabla_origen'] = 'users';
            $_SESSION['es_admin'] = false;
            $_SESSION['is_cliente'] = true;
        }
        
        echo json_encode([
            'success' => true,
            'is_authenticated' => true,
            'role' => 'cliente',
            'is_admin' => false,
            'is_cliente' => true,
            'can_purchase' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol'] ?? 'cliente'
            ]
        ]);
        exit;
    } else {
        // Cliente no encontrado o inactivo
        error_log("⚠️ Cliente no encontrado o inactivo en BD");
        session_destroy();
        echo json_encode([
            'success' => false,
            'is_authenticated' => false,
            'role' => 'invalid',
            'message' => 'Usuario no válido',
            'redirect' => '/proyecto/interfaz_usuario/login.html'
        ]);
        exit;
    }
}

// ========== CASO 3: Sesión sin tipo definido ==========
if ($tabla_origen === null && isset($_SESSION['user_id'])) {
    error_log("⚠️ Sesión legacy detectada - Verificando en users");
    
    // Buscar en users
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, estado, is_active FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['estado'] === 'activo' && $user['is_active'] == 1) {
        error_log("✅ Cliente legacy validado");
        
        // Corregir la sesión
        $_SESSION['tabla_origen'] = 'users';
        $_SESSION['es_admin'] = false;
        $_SESSION['is_cliente'] = true;
        
        echo json_encode([
            'success' => true,
            'is_authenticated' => true,
            'role' => 'cliente',
            'is_admin' => false,
            'is_cliente' => true,
            'can_purchase' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol'] ?? 'cliente'
            ]
        ]);
        exit;
    }
}

// ========== CASO 4: Cualquier otro caso ==========
error_log("⚠️ Sesión no reconocida - Cerrando sesión");
session_destroy();
echo json_encode([
    'success' => false,
    'is_authenticated' => false,
    'role' => 'invalid',
    'message' => 'Sesión inválida',
    'redirect' => '/proyecto/interfaz_usuario/login.html'
]);
exit;
?>