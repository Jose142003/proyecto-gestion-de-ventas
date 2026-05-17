<?php
// /proyecto/admin/verificar_sesion.php
session_start();

header('Content-Type: application/json');

error_log("=== verificar_sesion.php (ADMIN) - INICIO ===");
error_log("SESSION: " . print_r($_SESSION, true));

// Verificar si hay sesión activa
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    error_log("No hay sesión activa");
    echo json_encode([
        'success' => false,
        'message' => 'No hay sesión activa',
        'redirect' => '/proyecto/interfaz usuario/login.html'
    ]);
    exit;
}

// Obtener el origen de la sesión
$tabla_origen = $_SESSION['tabla_origen'] ?? null;

// CASO 1: Es administrador (tabla admin_users)
if ($tabla_origen === 'admin_users' && isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true) {
    error_log("Admin válido ID: " . ($_SESSION['user_id'] ?? 'unknown'));
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_nombre'] ?? $_SESSION['nombre'] ?? 'Administrador',
            'email' => $_SESSION['user_correo'] ?? $_SESSION['correo'] ?? $_SESSION['user_email'] ?? '',
            'rol' => $_SESSION['user_rol'] ?? 'admin'
        ]
    ]);
    exit;
}

// CASO 2: Es cliente (NO debe acceder al panel admin)
if ($tabla_origen === 'users') {
    error_log("Usuario cliente detectado - NO puede acceder al panel admin");
    echo json_encode([
        'success' => false,
        'message' => 'Área restringida a administradores',
        'redirect' => '/proyecto/interfaz usuario/pagina_modernizada.html'
    ]);
    exit;
}

// CASO 3: Sesión sin tabla_origen pero con user_id (legacy)
if ($tabla_origen === null && isset($_SESSION['user_id'])) {
    // Intentar determinar si es admin por otro medio
    if (isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true) {
        error_log("Sesión legacy detectada como admin");
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'nombre' => $_SESSION['user_nombre'] ?? 'Administrador',
                'email' => $_SESSION['user_correo'] ?? '',
                'rol' => 'admin'
            ]
        ]);
        exit;
    } else {
        error_log("Sesión legacy detectada como cliente");
        echo json_encode([
            'success' => false,
            'message' => 'Área restringida a administradores',
            'redirect' => '/proyecto/interfaz usuario/pagina_modernizada.html'
        ]);
        exit;
    }
}

// CASO 4: Sin acceso
error_log("Acceso denegado al panel admin");
echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos de administrador',
    'redirect' => '/proyecto/interfaz usuario/login.html'
]);
?>