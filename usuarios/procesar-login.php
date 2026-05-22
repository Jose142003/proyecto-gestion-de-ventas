<?php
// /proyecto/usuarios/procesar-login.php
// VERSIÓN CORREGIDA - SESIÓN PERSISTENTE CON SEPARACIÓN DE ROLES

// Desactivar errores en pantalla
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Configurar sesión para persistencia
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
    exit;
}

// Iniciar sesión temporal para rate limiting
if (session_status() === PHP_SESSION_NONE) {
    session_name('LOGIN_SESSID');
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// ========== RATE LIMITING ==========
$maxIntentos = 5;
$ventanaMinutos = 15;
$ahora = time();

if (!isset($_SESSION['_login_attempts'])) {
    $_SESSION['_login_attempts'] = ['count' => 0, 'first_attempt' => $ahora];
}

$attempts = &$_SESSION['_login_attempts'];

// Si pasó la ventana, reiniciar
if ($ahora - $attempts['first_attempt'] > $ventanaMinutos * 60) {
    $attempts = ['count' => 0, 'first_attempt' => $ahora];
}

if ($attempts['count'] >= $maxIntentos) {
    $espera = $ventanaMinutos - floor(($ahora - $attempts['first_attempt']) / 60);
    echo json_encode([
        "success" => false,
        "message" => "Demasiados intentos. Intenta de nuevo en $espera minuto(s)."
    ]);
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$tipo_usuario = trim($_POST['tipo_usuario'] ?? '');

if (empty($correo) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Correo y contraseña son obligatorios"]);
    exit;
}

try {
    $user = null;
    $tabla_origen = null;
    $es_admin = false;
    
    // Si se especificó tipo_usuario, buscar SOLO en esa tabla
    if ($tipo_usuario === 'admin') {
        // Buscar SOLO en admin_users
        $query_admin = "SELECT id, nombre, correo, contrasena as password, rol, activo as is_active 
                        FROM admin_users 
                        WHERE correo = :correo AND activo = 1";
        
        $stmt_admin = $pdo->prepare($query_admin);
        $stmt_admin->bindParam(":correo", $correo);
        $stmt_admin->execute();
        
        if ($stmt_admin->rowCount() > 0) {
            $user = $stmt_admin->fetch(PDO::FETCH_ASSOC);
            $tabla_origen = 'admin_users';
            $es_admin = true;
            
                if (!password_verify($password, $user['password'])) {
                    $attempts['count']++;
                    echo json_encode(["success" => false, "message" => "Credenciales de administrador incorrectas"]);
                    exit;
                }
        }
    } 
    elseif ($tipo_usuario === 'cliente') {
        // Buscar SOLO en users (clientes)
        $query_user = "SELECT id, nombre, correo, password, rol, is_active, estado
                       FROM users 
                       WHERE correo = :correo";
        
        $stmt_user = $pdo->prepare($query_user);
        $stmt_user->bindParam(":correo", $correo);
        $stmt_user->execute();
        
        if ($stmt_user->rowCount() > 0) {
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            $tabla_origen = 'users';
            
            if (!$user['is_active'] || $user['estado'] !== 'activo') {
                    $attempts['count']++;
                    echo json_encode(["success" => false, "message" => "Usuario inactivo"]);
                    exit;
                }
                
                if (!password_verify($password, $user['password'])) {
                    $attempts['count']++;
                    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
                    exit;
                }
                
                $es_admin = false;
            }
        }
    else {
        // Sin especificar - buscar en ambas (modo automático)
        // Buscar en admin_users primero
        $query_admin = "SELECT id, nombre, correo, contrasena as password, rol, activo as is_active 
                        FROM admin_users 
                        WHERE correo = :correo AND activo = 1";
        
        $stmt_admin = $pdo->prepare($query_admin);
        $stmt_admin->bindParam(":correo", $correo);
        $stmt_admin->execute();
        
        if ($stmt_admin->rowCount() > 0) {
            $user = $stmt_admin->fetch(PDO::FETCH_ASSOC);
            $tabla_origen = 'admin_users';
            $es_admin = true;
            
                if (!password_verify($password, $user['password'])) {
                    $attempts['count']++;
                    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
                    exit;
                }
    } else {
            // Buscar en users
            $query_user = "SELECT id, nombre, correo, password, rol, is_active, estado
                           FROM users 
                           WHERE correo = :correo";
            
            $stmt_user = $pdo->prepare($query_user);
            $stmt_user->bindParam(":correo", $correo);
            $stmt_user->execute();
            
            if ($stmt_user->rowCount() > 0) {
                $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
                $tabla_origen = 'users';
                
                if (!$user['is_active'] || $user['estado'] !== 'activo') {
                    echo json_encode(["success" => false, "message" => "Usuario inactivo"]);
                    exit;
                }
                
                if (!password_verify($password, $user['password'])) {
                    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
                    exit;
                }
                
                $es_admin = false;
            }
        }
    }
    
    if (!$user) {
        $attempts['count']++;
        echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
        exit;
    }
    
    // ========== VERIFICAR 2FA PARA ADMINISTRADORES ==========
    if ($tabla_origen === 'admin_users') {
        try {
            $stmt2fa = $pdo->query("SHOW COLUMNS FROM admin_users LIKE '2fa_enabled'");
            if ($stmt2fa->rowCount() > 0) {
                $stmt2fa = $pdo->prepare("SELECT 2fa_enabled, 2fa_secret FROM admin_users WHERE id = ?");
                $stmt2fa->execute([$user['id']]);
                $admin2fa = $stmt2fa->fetch();
                
                if ($admin2fa && $admin2fa['2fa_enabled'] && !empty($admin2fa['2fa_secret'])) {
                    $tokenVerificacion = bin2hex(random_bytes(32));
                    $expiracion = date('Y-m-d H:i:s', time() + 300);
                    
                    $stmtToken = $pdo->prepare("
                        INSERT INTO sesiones_2fa (admin_user_id, token_verificacion, expiracion)
                        VALUES (?, ?, ?)
                    ");
                    $stmtToken->execute([$user['id'], $tokenVerificacion, $expiracion]);
                    
                    echo json_encode([
                        "success" => true,
                        "require_2fa" => true,
                        "message" => "Verificación de dos factores requerida",
                        "token_2fa" => $tokenVerificacion,
                        "redirect_url" => BASE_URL . '/2fa/verificar.html?token=' . $tokenVerificacion
                    ]);
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log("2FA check skipped (columns may not exist): " . $e->getMessage());
        }
    }
    
    // ========== LIMPIAR Y REGENERAR SESIÓN (SEPARADA POR ROL) ==========
    // Cerrar sesión temporal de login
    session_write_close();

    // Usar nombre de sesión diferente según el rol para que coexistan
    if ($tabla_origen === 'admin_users') {
        session_name(ini_get('session.name')); // Restablecer a PHPSESSID
    } else {
        session_name('CLIENTSESSID'); // Sesión separada para clientes
    }
    session_start();
    $_SESSION = array();
    session_regenerate_id(true);

    // ========== VARIABLES OBLIGATORIAS ==========
    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_correo'] = $user['correo'];
    $_SESSION['user_rol'] = $user['rol'];
    $_SESSION['tabla_origen'] = $tabla_origen; // CRÍTICO: 'users' o 'admin_users'
    $_SESSION['user_tipo_login'] = $tipo_usuario ?: ($es_admin ? 'admin' : 'cliente');
    $_SESSION['2fa_verified'] = true;

    // ========== BANDERAS CLARAS ==========
    if ($tabla_origen === 'admin_users') {
        $_SESSION['es_admin'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['is_cliente'] = false;
        $_SESSION['user_tipo'] = 'admin';
    } else {
        $_SESSION['es_admin'] = false;
        $_SESSION['is_admin'] = false;
        $_SESSION['is_cliente'] = true;
        $_SESSION['user_tipo'] = 'cliente';
    }
    
    // Cookie persistente como respaldo por si la sesión se pierde
    $token_data = $user['id'] . '|' . $user['nombre'] . '|' . $tabla_origen;
    $token_sig = hash_hmac('sha256', $token_data, BASE_URL);
    $token_value = base64_encode($token_data . '|' . $token_sig);
    setcookie('persist_token', $token_value, time() + 86400 * 30, '/', '', false, true);
    
    // Cerrar sesión para asegurar que los datos se escriban antes de continuar
    session_write_close();
    
    // Actualizar último login
    if ($tabla_origen === 'users') {
        $update = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt_update = $pdo->prepare($update);
        $stmt_update->bindParam(":id", $user['id']);
        $stmt_update->execute();
    } else {
        $update = "UPDATE admin_users SET ultimo_login = NOW() WHERE id = :id";
        $stmt_update = $pdo->prepare($update);
        $stmt_update->bindParam(":id", $user['id']);
        $stmt_update->execute();
    }
    
    // Redirección usando BASE_URL
    $redirect_url = ($tabla_origen === 'admin_users')
        ? BASE_URL . '/panel_admin/panel_admin.php'
        : BASE_URL . '/interfaz_usuario/pagina_modernizada.html';
    
    echo json_encode([
        "success" => true,
        "message" => $es_admin ? "Bienvenido administrador" : "Bienvenido",
        "redirect_url" => $redirect_url,
        "user_tipo" => $es_admin ? "admin" : "cliente",
        "is_admin" => $es_admin,
        "is_cliente" => !$es_admin,
        "user_id" => $user['id'],
        "user_nombre" => $user['nombre'],
        "tabla_origen" => $tabla_origen
    ]);
    
} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error de base de datos"]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error en el servidor"]);
}
?>
