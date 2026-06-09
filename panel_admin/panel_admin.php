<?php
// panel_admin.php - Verificación de sesión ADMIN

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../conexion/seguridad.php';
require_once __DIR__ . '/../conexion/conexion.php';

seguridadConfigurarCookies();
seguridadEnviarHeaders();

// Intentar restaurar sesión desde persist_token si no hay sesión activa
session_start();
seguridadVerificarTimeoutSesion();
seguridadRegenerarSesion();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_COOKIE['persist_token'])) {
        $token_value = base64_decode($_COOKIE['persist_token']);
        if ($token_value !== false) {
            $parts = explode('|', $token_value);
            if (count($parts) >= 4) {
                $token_id = $parts[0];
                $token_nombre = $parts[1];
                $token_tabla = $parts[2];
                $token_sig = $parts[3];
                $expected_sig = hash_hmac('sha256', $parts[0] . '|' . $parts[1] . '|' . $parts[2], BASE_URL);
                if (hash_equals($expected_sig, $token_sig) && $token_tabla === 'admin_users') {
                    try {
                        $pdo = conectarDB();
                        $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, activo FROM admin_users WHERE id = ? AND activo = 1");
                        $stmt->execute([$token_id]);
                        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($admin) {
                            session_regenerate_id(true);
                            $_SESSION['loggedin'] = true;
                            $_SESSION['user_id'] = $admin['id'];
                            $_SESSION['user_nombre'] = $admin['nombre'];
                            $_SESSION['user_correo'] = $admin['correo'];
                            $_SESSION['user_rol'] = $admin['rol'];
                            $_SESSION['tabla_origen'] = 'admin_users';
                            $_SESSION['es_admin'] = true;
                            $_SESSION['is_admin'] = true;
                            $_SESSION['user_tipo'] = 'admin';
                            $_SESSION['_ultimo_acceso'] = time();
                            $_SESSION['_regenerado_en'] = time();
                            $_SESSION['2fa_verified'] = true;
                            $_SESSION['2fa_verified_at'] = time();
                        }
                    } catch (PDOException $e) {
                        error_log("panel_admin.php: Error restaurando persist_token: " . $e->getMessage());
                    }
                }
            }
        }
    }
}

error_log("panel_admin.php - Admin ID: " . ($_SESSION['user_id'] ?? 'none') . ", Rol: " . ($_SESSION['user_rol'] ?? 'none'));

// Generar CSRF token para la sesion
if (function_exists('generarTokenCSRF')) {
    generarTokenCSRF();
}

// ========== VERIFICACIÓN ESTRICTA DE ADMIN ==========

// 1. Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    error_log("panel_admin.php: Check 1 FALLÓ - loggedin=" . (isset($_SESSION['loggedin']) ? ($_SESSION['loggedin'] ? 'true' : 'false') : 'NOT SET'));
    header('Location: /proyecto/interfaz_usuario/login.html');
    exit;
}

// 2. Verificar tabla_origen - Debe ser 'admin_users'
$tabla_origen = $_SESSION['tabla_origen'] ?? null;
if ($tabla_origen !== 'admin_users') {
    error_log("panel_admin.php: Acceso denegado - tabla_origen incorrecta: " . $tabla_origen);
    
    // Si es cliente, destruir sesión y redirigir a login
    if ($tabla_origen === 'users') {
        session_destroy();
        header('Location: /proyecto/interfaz_usuario/login.html?error=cliente_accediendo_admin');
        exit;
    }
    
    header('Location: /proyecto/interfaz_usuario/login.html?error=invalid_access');
    exit;
}

// 3. Verificar bandera es_admin
if (!isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    error_log("panel_admin.php: Acceso denegado - es_admin = false");
    header('Location: /proyecto/interfaz_usuario/login.html?error=not_admin');
    exit;
}

// 4. Verificar user_rol
$user_rol = $_SESSION['user_rol'] ?? '';
$roles_admin = ['admin', 'superadmin', 'vendedor', 'administrador'];
if (!in_array(strtolower($user_rol), $roles_admin)) {
    error_log("panel_admin.php: Acceso denegado - rol incorrecto: " . $user_rol);
    header('Location: /proyecto/interfaz_usuario/login.html?error=invalid_role');
    exit;
}

// 5. Verificar en base de datos que el admin existe y está activo
require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, activo FROM admin_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_user || $admin_user['activo'] == 0) {
        error_log("panel_admin.php: Admin no encontrado o inactivo en BD");
        session_destroy();
        header('Location: /proyecto/interfaz_usuario/login.html?error=invalid_admin');
        exit;
    }
    
    $user_nombre = $admin_user['nombre'];
    $user_rol = $admin_user['rol'];
    $_SESSION['user_nombre'] = $admin_user['nombre'];
    $_SESSION['user_rol'] = $admin_user['rol'];
    
} catch (PDOException $e) {
    error_log("Error en panel_admin: " . $e->getMessage());
    header('Location: /proyecto/interfaz_usuario/login.html?error=db_error');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="/proyecto/img/pic.png">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['_csrf_token'] ?? ''); ?>">
    <link rel="shortcut icon" href="/proyecto/img/pic.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
    <style>
       :root {
            --primary-color: #0a0e1a;
            --secondary-color: #1a1f2e;
            --accent-color: #3C91ED;
            --light-color: #5aa9e6;
            --bg-color: #0f1219;
            --text-color: #e4e6eb;
            --card-bg: #1e2436;
            --header-bg: linear-gradient(135deg, #0a0e1a, #1a1f2e);
            --shadow-color: rgba(0, 0, 0, 0.3);
            --sidebar-bg: #0a0e1a;
            --success: #2ed573;
            --warning: #ffa502;
            --danger: #ff4757;
            --info: #3498db;
            --purple: #9B59B6;
            --orange: #E67E22;
            --teal: #1abc9c;
            --border-color: #2c3348;
            --table-hover: rgba(60, 145, 237, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--primary-color);
            color: white;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 10px rgba(0,0,0,0.3);
            position: relative;
            z-index: 100;
        }

        .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid var(--secondary-color);
            margin-bottom: 20px;
        }

        .logo h1 {
            font-size: 2rem;
            color: var(--light-color);
        }

        .logo p {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .user-info {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            margin: 0 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-info:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color), var(--light-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            font-size: 1.4rem;
            position: relative;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .user-details h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .user-details p {
            font-size: 0.8rem;
            color: var(--light-color);
            background-color: rgba(62, 145, 237, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .menu {
            flex: 1;
            padding: 0 15px;
            overflow-y: auto;
        }

        .menu-section-title {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
            padding: 10px 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .menu-item {
            padding: 12px 20px;
            margin-bottom: 5px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background-color: rgba(41, 78, 144, 0.3);
            color: white;
            border-left-color: var(--accent-color);
            transform: translateX(5px);
        }

        .menu-item.active {
            background-color: rgba(41, 78, 144, 0.5);
            color: white;
            border-left-color: var(--light-color);
        }

        .menu-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .logout {
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            gap: 10px;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .main-content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            background-color: var(--bg-color);
        }

        .header {
            background: var(--header-bg);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .header h2 {
            font-size: 1.5rem;
            margin: 0;
        }

        .date-display {
            background-color: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--light-color));
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .card-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .card-content {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .card-footer {
            font-size: 0.8rem;
            color: #aaa;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 1px solid var(--border-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-color);
        }

        .stat-label {
            font-size: 0.85rem;
            color: #aaa;
            margin-top: 5px;
        }

        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }

        .table-header {
            padding: 15px 20px;
            background: var(--header-bg);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .table-header h3 {
            font-size: 1.1rem;
            margin: 0;
        }

        .table-content {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .data-table th {
            padding: 12px 15px;
            text-align: left;
            background: linear-gradient(135deg, var(--accent-color), var(--light-color));
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
            font-size: 0.85rem;
        }

        .data-table tr:hover td {
            background-color: var(--table-hover);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-action {
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            font-size: 0.75rem;
            transition: all 0.2s ease;
        }

        .btn-view { background: var(--info); }
        .btn-edit { background: var(--success); }
        .btn-delete { background: var(--danger); }
        .btn-pdf { background: var(--danger); }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), var(--light-color));
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary {
            background-color: #2c3348;
            color: white;
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .btn-danger {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .form-control {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.85rem;
            background: var(--bg-color);
            color: var(--text-color);
        }
        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
        }
        select.form-control option {
            background: var(--card-bg);
            color: var(--text-color);
        }
        .data-table td small {
            color: var(--text-color);
            opacity: 0.7;
        }

        .filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-active { background: var(--success); color: white; }
        .badge-inactive { background: var(--danger); color: white; }
        .badge-pending { background: var(--warning); color: white; }
        .badge-completed { background: var(--success); color: white; }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            max-width: 900px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #aaa;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .resumen-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .resumen-card {
            background: var(--bg-color);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .resumen-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-color);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .notification-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .notification-message.success { background: var(--success); }
        .notification-message.error { background: var(--danger); }
        .notification-message.warning { background: var(--warning); }
        .notification-message.info { background: var(--info); }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .profile-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 1px solid var(--border-color);
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 30px;
            text-align: center;
            color: white;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--light-color);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
        }

        .profile-avatar-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            font-size: 0.7rem;
            padding: 5px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .profile-avatar:hover .profile-avatar-overlay {
            opacity: 1;
        }

        .profile-name {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .profile-role {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .profile-body {
            padding: 25px;
        }

        .profile-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        .profile-info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-info-label {
            width: 120px;
            font-weight: 600;
            color: #aaa;
        }

        .profile-info-value {
            flex: 1;
            color: var(--text-color);
        }

        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        .profile-tab {
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 20px;
            transition: all 0.3s;
        }
        .profile-tab.active {
            background: var(--accent-color);
            color: white;
        }
        .profile-tab-pane {
            display: none;
        }
        .profile-tab-pane.active {
            display: block;
        }

        .factura-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .factura-info, .cliente-info {
            background: var(--bg-color);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid var(--border-color);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed var(--border-color);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .info-value {
            color: var(--text-color);
        }
        
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .productos-table th {
            background: linear-gradient(135deg, var(--accent-color), var(--light-color));
            color: white;
            padding: 10px;
            text-align: left;
        }
        
        .productos-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .totales {
            text-align: right;
            padding: 15px;
            background: var(--bg-color);
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            padding: 5px 0;
        }
        
        .total-grande {
            font-size: 1.2rem;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid var(--accent-color);
        }
        
        .metodo-pago-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .metodo-efectivo { background: #2ed573; color: white; }
        .metodo-transferencia { background: #3498db; color: white; }
        .metodo-pago-movil { background: #9b59b6; color: white; }
        .metodo-mixto { background: #f39c12; color: white; }
        .metodo-tarjeta { background: #e74c3c; color: white; }

        @media (max-width: 992px) {
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1100;
                background: var(--accent-color);
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 5px;
                font-size: 1.2rem;
            }

            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                z-index: 1050;
                width: 280px;
                transition: 0.3s;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0 !important;
                padding-top: 60px;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
            }
            .sidebar-overlay.active { display: block; }
        }

        @media (min-width: 993px) {
            .mobile-menu-toggle { display: none; }
        }

        .chart-container {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: 1px solid var(--border-color);
        }

        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--accent-color);
            border-left: 4px solid var(--accent-color);
            padding-left: 12px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .kpi-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .kpi-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.7;
        }

        .filtros-avanzados {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            border: 1px solid var(--border-color);
        }

        .filtro-group {
            display: flex;
            flex-direction: column;
        }

        .filtro-group label {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .top-list {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
        }

        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        }

        .top-item:hover {
            background: var(--table-hover);
        }

        .top-number {
            width: 30px;
            height: 30px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .top-name {
            flex: 1;
            margin-left: 15px;
            font-weight: 500;
        }

        .top-value {
            font-weight: 700;
            color: var(--accent-color);
        }

        .tendencia-up {
            color: var(--success);
        }

        .tendencia-down {
            color: var(--danger);
        }

        @media print {
            .no-print {
                display: none;
            }
            .kpi-card {
                break-inside: avoid;
            }
        }
        /* Estilos adicionales para productos ocultos */
        .btn-ocultar {
            background: #ffa502;
            color: white;
        }
        .btn-ocultar:hover {
            background: #e67e22;
        }
        .btn-mostrar {
            background: #2ed573;
            color: white;
        }
        .btn-mostrar:hover {
            background: #1abc9c;
        }
        .producto-oculto-row {
            background-color: rgba(255, 165, 2, 0.1);
        }
        .producto-oculto-row td {
            color: #aaa;
        }
        .badge-oculto {
            background: #95a5a6;
            color: white;
        }
        .badge-oculto i {
            margin-right: 5px;
        }
        .filtro-botones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filtro-botones .btn-filtro {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .filtro-botones .btn-filtro-active {
            background: #3C91ED;
            color: white;
        }
        .filtro-botones .btn-filtro-inactive {
            background: #2c3348;
            color: #aaa;
        }
        
        /* Estilos para la tabla de vendedores y clientes */
        .tabla-ventas th, .tabla-ventas td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .tabla-ventas th {
            background: var(--accent-color);
            color: white;
        }
        /* ========== RESPONSIVE DESIGN PARA PANEL ADMIN ========== */

/* Tablets y pantallas medianas (max-width: 1200px) */
@media (max-width: 1200px) {
    .dashboard {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 15px;
    }
    
    .dashboard-stats {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    
    .kpi-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    
    .header h2 {
        font-size: 1.3rem;
    }
}

/* Pantallas pequeñas (max-width: 992px) */
@media (max-width: 992px) {
    .sidebar {
        width: 260px;
    }
    
    .main-content {
        padding: 15px;
    }
    
    .header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .table-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .table-header > div {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .filtros {
        flex-wrap: wrap;
    }
    
    .filtros-avanzados {
        grid-template-columns: 1fr;
    }
    
    .resumen-cards {
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-action {
        width: 100%;
        text-align: center;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 10px;
        font-size: 0.75rem;
    }
    
    .kpi-card {
        padding: 15px;
    }
    
    .kpi-value {
        font-size: 1.5rem;
    }
    
    .modal-content {
        width: 95%;
        padding: 15px;
        margin: 10px;
    }
    
    .profile-header {
        padding: 20px;
    }
    
    .profile-avatar {
        width: 90px;
        height: 90px;
        font-size: 2rem;
    }
    
    .profile-name {
        font-size: 1.2rem;
    }
}

/* Móviles (max-width: 768px) */
@media (max-width: 768px) {
    .main-content {
        padding: 10px;
        padding-top: 60px;
    }
    
    .header {
        padding: 12px 15px;
    }
    
    .header h2 {
        font-size: 1.1rem;
    }
    
    .date-display {
        font-size: 0.75rem;
        padding: 5px 10px;
    }
    
    .dashboard {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .card {
        padding: 15px;
    }
    
    .card-content {
        font-size: 1.4rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .table-content {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .data-table {
        min-width: 500px;
    }
    
    .menu-item {
        padding: 10px 15px;
        font-size: 0.85rem;
    }
    
    .menu-section-title {
        font-size: 0.7rem;
        padding: 8px 12px;
    }
    
    .resumen-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .resumen-card {
        padding: 10px;
    }
    
    .resumen-value {
        font-size: 1.2rem;
    }
    
    .resumen-label {
        font-size: 0.7rem;
    }
    
    .chart-container {
        padding: 12px;
    }
    
    .chart-title {
        font-size: 0.9rem;
    }
    
    .filtro-botones {
        justify-content: center;
    }
    
    .filtro-botones .btn-filtro {
        padding: 4px 8px;
        font-size: 0.7rem;
    }
    
    .btn-primary, .btn-secondary, .btn-danger {
        padding: 6px 12px;
        font-size: 0.75rem;
    }
    
    .form-control {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
    
    .profile-tabs {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .profile-tab {
        padding: 6px 12px;
        font-size: 0.8rem;
    }
    
    .profile-info-row {
        flex-direction: column;
    }
    
    .profile-info-label {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .kpi-card {
        padding: 12px;
    }
    
    .kpi-value {
        font-size: 1.2rem;
    }
    
    .kpi-icon {
        font-size: 1.5rem;
    }
    
    .kpi-label {
        font-size: 0.7rem;
    }
    
    .top-item {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .top-name {
        flex: 1 1 100%;
        margin-left: 0;
    }
}

/* Móviles muy pequeños (max-width: 480px) */
@media (max-width: 480px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .resumen-cards {
        grid-template-columns: 1fr;
    }
    
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-header {
        padding: 15px;
    }
    
    .profile-avatar {
        width: 70px;
        height: 70px;
        font-size: 1.5rem;
    }
    
    .profile-name {
        font-size: 1rem;
    }
    
    .modal-header h3 {
        font-size: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions button {
        width: 100%;
    }
    
    .factura-header h3 {
        font-size: 1rem;
    }
    
    .info-row {
        flex-direction: column;
        gap: 5px;
    }
}

/* Orientación landscape en móviles */
@media (max-width: 900px) and (orientation: landscape) {
    .sidebar {
        width: 240px;
    }
    
    .main-content {
        padding: 10px;
    }
    
    .dashboard {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .kpi-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Mejora de touch targets en móvil */
@media (max-width: 768px) {
    button, 
    .menu-item,
    .btn-action,
    .btn-primary,
    .btn-secondary,
    .btn-danger,
    .logout-btn,
    .filter-badge .close {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-action {
        min-height: 36px;
    }
    
    input, select, textarea {
        font-size: 16px !important;
    }
}

/* ========== OFFLINE DETECTION STYLES ========== */
.offline-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #ff4757;
    color: white;
    text-align: center;
    padding: 8px;
    font-size: 0.85rem;
    z-index: 10000;
    transform: translateY(-100%);
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.offline-banner.show {
    transform: translateY(0);
}

.online-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #2ed573;
    color: white;
    text-align: center;
    padding: 8px;
    font-size: 0.85rem;
    z-index: 10000;
    transform: translateY(-100%);
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.online-banner.show {
    transform: translateY(0);
}

.offline-badge {
    background: #ff4757;
    color: white;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    margin-left: 10px;
}

.online-badge {
    background: #2ed573;
    color: white;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    margin-left: 10px;
}

.offline-mode-actions {
    background: rgba(255, 71, 87, 0.1);
    border: 1px solid #ff4757;
    border-radius: 8px;
    padding: 10px 15px;
    margin-bottom: 15px;
    display: none;
}

.offline-mode-actions.show {
    display: block;
}

.offline-mode-actions h4 {
    color: #ff4757;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.offline-mode-actions p {
    font-size: 0.8rem;
    margin-bottom: 10px;
    color: #aaa;
}

/* Deshabilitar botones cuando offline */
.btn-offline-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Banners de conexión -->
    <div id="offlineBanner" class="offline-banner">
        <i class="fas fa-wifi-slash"></i>
        <span>📡 Sin conexión a internet - Modo offline activado</span>
        <i class="fas fa-database"></i>
    </div>
    <div id="onlineBanner" class="online-banner">
        <i class="fas fa-wifi"></i>
        <span>🟢 Conexión restablecida</span>
    </div>
    
    <div class="container">
        <aside class="sidebar">
            <div class="logo"><h1>PIC</h1><p>Panel de Administración</p></div>
            <div class="user-info" id="userInfo">
                <div class="user-avatar" id="userAvatar">
                    <span id="avatarInitial">A</span>
                    <img id="avatarImage" style="display:none" alt="Foto perfil">
                </div>
                <div class="user-details">
                    <h3 id="userName">Administrador</h3>
                    <p id="userRole">Administrador</p>
                </div>
            </div>
            <nav class="menu" id="mainMenu">
                <div class="menu-item active" data-section="dashboardSection"><i class="fas fa-tachometer-alt"></i> Dashboard</div>
                <div class="menu-section-title">Mi Cuenta</div>
                <div class="menu-item" data-section="perfilSection"><i class="fas fa-user-circle"></i> Mi Perfil</div>
                <div class="menu-section-title">Gestion</div>
                <div class="menu-item" data-section="usersSection"><i class="fas fa-users"></i> Usuarios</div>
                <div class="menu-item" data-section="productsSection"><i class="fas fa-boxes"></i> Productos</div>
                <div class="menu-item" data-section="proveedoresSection"><i class="fas fa-truck"></i> Proveedores</div>
                <div class="menu-item" data-section="comprasSection"><i class="fas fa-shopping-cart"></i> Compras</div>
                <div class="menu-section-title">Ventas</div>
                <div class="menu-item" data-section="pedidosSection"><i class="fas fa-clipboard-list"></i> Pedidos</div>
                <div class="menu-item" data-section="cotizacionesSection"><i class="fas fa-file-signature"></i> Cotizaciones</div>
                <div class="menu-item" data-section="crmSection"><i class="fas fa-handshake"></i> CRM</div>
                <div class="menu-item" data-section="facturacionSection"><i class="fas fa-file-invoice-dollar"></i> Facturacion</div>
                <div class="menu-item" data-section="cajaSection"><i class="fas fa-cash-register"></i> Caja / Arqueo</div>
                <div class="menu-section-title">Herramientas</div>
                <a href="/proyecto/admin/asistente_tecnico.php" class="menu-item" style="display:flex;text-decoration:none;color:inherit"><i class="fas fa-robot"></i> Asistente Técnico</a>
                <div class="menu-item" data-section="prediccionesSection"><i class="fas fa-brain"></i> IA Predictiva</div>
                <div class="menu-item" data-section="biDashboardSection"><i class="fas fa-chart-pie"></i> BI Dashboard</div>
                <div class="menu-section-title">Reportes</div>
                <div class="menu-item" data-section="ventasClienteSection"><i class="fas fa-chart-line"></i> Ventas por Cliente</div>
                <div class="menu-item" data-section="ventasVendedorSection"><i class="fas fa-user-tie"></i> Ventas por Vendedor</div>
                <div class="menu-item" data-section="productosVendidosSection"><i class="fas fa-chart-bar"></i> Productos mas Vendidos</div>
                <div class="menu-item" data-section="historialComprasSection"><i class="fas fa-history"></i> Historial de Compras</div>
                <div class="menu-item" data-section="auditoriaSection"><i class="fas fa-clipboard-list"></i> Auditoria</div>
                <div class="menu-item" data-section="reporteGeneralSection"><i class="fas fa-chart-pie"></i> Reporte General Ejecutivo</div>
                <div class="menu-item" data-section="reporteEspecificoSection"><i class="fas fa-sliders-h"></i> Reporte Específico</div>
                <div class="menu-section-title">Sistema</div>
                <div class="menu-item" data-section="ceoSection"><i class="fas fa-crown"></i> Panel CEO</div>
                <div class="menu-item" data-section="configuracionSection"><i class="fas fa-cog"></i> Configuracion</div>
                <div class="menu-item" data-section="telegramSection"><i class="fab fa-telegram"></i> Telegram</div>
                <div class="menu-item" data-section="backupSection"><i class="fas fa-database"></i> Backup</div>
                <div class="menu-item" data-section="marketingSection"><i class="fas fa-bullhorn"></i> Marketing</div>
                <div class="menu-item" data-section="reporteStockSection"><i class="fas fa-chart-simple"></i> Reporte de Stock</div>
                <div class="menu-section-title">Seguridad</div>
                <div class="menu-item" data-section="seguridad2faSection"><i class="fas fa-shield-alt"></i> Autenticación 2FA</div>
            </nav>
            <div class="logout"><button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesion</button></div>
        </aside>

        <main class="main-content">
            <div class="header"><h2 id="dashboardTitle">Dashboard</h2><div class="date-display"><i class="fas fa-calendar-alt"></i> <span id="currentDate"></span></div></div>

            <!-- Dashboard Section -->
            <div id="dashboardSection" class="content-section active">
                <div class="dashboard">
                    <div class="card"><div class="card-header"><h3 class="card-title">Usuarios</h3><div class="card-icon" style="background: var(--info);"><i class="fas fa-users"></i></div></div><div class="card-content" id="totalUsers">0</div><div class="card-footer">Usuarios registrados</div></div>
                    <div class="card"><div class="card-header"><h3 class="card-title">Productos</h3><div class="card-icon" style="background: var(--success);"><i class="fas fa-boxes"></i></div></div><div class="card-content" id="totalProducts">0</div><div class="card-footer">Productos en inventario</div></div>
                    <div class="card"><div class="card-header"><h3 class="card-title">Pedidos</h3><div class="card-icon" style="background: var(--purple);"><i class="fas fa-shopping-cart"></i></div></div><div class="card-content" id="totalPedidos">0</div><div class="card-footer">Pendientes: <span id="pedidosPendientes">0</span></div></div>
                    <div class="card"><div class="card-header"><h3 class="card-title">Facturas Emitidas</h3><div class="card-icon" style="background: var(--orange);"><i class="fas fa-file-invoice"></i></div></div><div class="card-content" id="facturasHoy">0</div><div class="card-footer">Facturas emitidas hoy</div></div>
                    <div class="card"><div class="card-header"><h3 class="card-title">Cotizaciones</h3><div class="card-icon" style="background: var(--teal);"><i class="fas fa-file-signature"></i></div></div><div class="card-content" id="totalCotizaciones">0</div><div class="card-footer">Pendientes: <span id="cotizacionesPendientes">0</span></div></div>
                </div>
                <div class="dashboard-stats">
                    <div class="stat-card"><div class="stat-value" id="totalVentas">Bs. 0</div><div class="stat-label">Ventas Totales</div></div>
                    <div class="stat-card"><div class="stat-value" id="totalClientes">0</div><div class="stat-label">Clientes</div></div>
                    <div class="stat-card"><div class="stat-value" id="stockBajo">0</div><div class="stat-label">Stock Bajo</div></div>
                    <div class="stat-card"><div class="stat-value" id="cajaHoy">Bs. 0</div><div class="stat-label">Caja del Dia</div></div>
                </div>
            </div>

            <!-- Perfil Section -->
            <div id="perfilSection" class="content-section">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar" id="profileAvatar" onclick="document.getElementById('fotoPerfilInput').click()">
                            <span id="profileAvatarInitial">A</span>
                            <img id="profileAvatarImage" style="display:none" alt="Foto perfil">
                            <div class="profile-avatar-overlay"><i class="fas fa-camera"></i> Cambiar</div>
                        </div>
                        <input type="file" id="fotoPerfilInput" style="display:none" accept="image/jpeg,image/png,image/jpg">
                        <div class="profile-name" id="profileName">Cargando...</div>
                        <div class="profile-role" id="profileRole">Cargando...</div>
                    </div>
                    <div class="profile-body">
                        <div class="profile-tabs">
                            <div class="profile-tab active" data-tab="Info">Información Personal</div>
                            <div class="profile-tab" data-tab="Security">Seguridad</div>
                            <div class="profile-tab" data-tab="Danger">Zona Peligrosa</div>
                        </div>
                        <div class="profile-tab-pane active" id="tabInfo">
                            <div class="profile-section">
                                <h3 class="profile-section-title"><i class="fas fa-user"></i> Datos Personales</h3>
                                <div id="profileInfoDisplay">
                                    <div class="profile-info-row"><div class="profile-info-label">Nombre completo:</div><div class="profile-info-value" id="displayNombre">-</div></div>
                                    <div class="profile-info-row"><div class="profile-info-label">Correo electrónico:</div><div class="profile-info-value" id="displayEmail">-</div></div>
                                    <div class="profile-info-row"><div class="profile-info-label">Teléfono:</div><div class="profile-info-value" id="displayTelefono">-</div></div>
                                    <div class="profile-info-row"><div class="profile-info-label">Rol:</div><div class="profile-info-value" id="displayRol">-</div></div>
                                    <div class="profile-info-row"><div class="profile-info-label">Fecha de registro:</div><div class="profile-info-value" id="displayFechaRegistro">-</div></div>
                                </div>
                                <div id="profileInfoEdit" style="display:none">
                                    <div class="form-group"><label class="form-label">Nombre completo</label><input type="text" id="editNombre" class="form-control" style="width:100%"></div>
                                    <div class="form-group"><label class="form-label">Correo electrónico</label><input type="email" id="editEmail" class="form-control" style="width:100%"></div>
                                    <div class="form-group"><label class="form-label">Teléfono</label><input type="text" id="editTelefono" class="form-control" style="width:100%"></div>
                                    <div class="form-actions"><button class="btn-secondary" onclick="cancelarEdicionPerfil()">Cancelar</button><button class="btn-primary" onclick="guardarPerfil()">Guardar cambios</button></div>
                                </div>
                                <div class="form-actions" style="margin-top:15px"><button class="btn-primary" id="btnEditarPerfil" onclick="habilitarEdicionPerfil()"><i class="fas fa-edit"></i> Editar perfil</button></div>
                            </div>
                        </div>
                        <div class="profile-tab-pane" id="tabSecurity">
                            <div class="profile-section">
                                <h3 class="profile-section-title"><i class="fas fa-shield-alt"></i> Autenticación en Dos Pasos (2FA)</h3>
                                <div id="perfil2faStatus" style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--card-bg);border-radius:8px;margin-bottom:10px">
                                    <div style="font-size:2rem;opacity:0.5"><i class="fas fa-qrcode"></i></div>
                                    <div style="flex:1">
                                        <div style="font-weight:600;font-size:0.95rem" id="perfil2faLabel">Verificando...</div>
                                        <div style="font-size:0.8rem;opacity:0.7;margin-top:2px" id="perfil2faDesc">Consultando estado...</div>
                                    </div>
                                    <div>
                                        <span class="badge" id="perfil2faBadge">...</span>
                                    </div>
                                </div>
                                <div class="form-actions" style="margin-top:10px">
                                    <button class="btn-primary" onclick="switchSection('seguridad2faSection')"><i class="fas fa-cog"></i> Gestionar 2FA</button>
                                </div>
                            </div>
                            <div class="profile-section">
                                <h3 class="profile-section-title"><i class="fas fa-key"></i> Cambiar Contraseña</h3>
                                <form id="cambiarPasswordForm" onsubmit="cambiarContrasena(event)">
                                    <div class="form-group"><label class="form-label">Contraseña actual</label><input type="password" id="currentPassword" class="form-control" required></div>
                                    <div class="form-group"><label class="form-label">Nueva contraseña</label><input type="password" id="newPassword" class="form-control" required><small>Mínimo 6 caracteres</small></div>
                                    <div class="form-group"><label class="form-label">Confirmar nueva contraseña</label><input type="password" id="confirmPassword" class="form-control" required></div>
                                    <div class="form-actions"><button type="submit" class="btn-primary">Cambiar contraseña</button></div>
                                </form>
                            </div>
                            <div class="profile-section">
                                <h3 class="profile-section-title"><i class="fas fa-envelope"></i> Recuperación de Cuenta</h3>
                                <p>Si olvidaste tu contraseña, puedes solicitar un token de recuperación.</p>
                                <div class="form-actions" style="margin-top:10px"><button class="btn-primary" id="btnSolicitarToken" type="button"><i class="fas fa-paper-plane"></i> Solicitar token de recuperación</button></div>
                            </div>
                        </div>
                        <div class="profile-tab-pane" id="tabDanger">
                            <div class="profile-section">
                                <h3 class="profile-section-title" style="color:var(--danger)"><i class="fas fa-exclamation-triangle"></i> Eliminar Cuenta</h3>
                                <p style="color:var(--danger); margin-bottom:15px">Esta acción es irreversible. Se eliminarán todos tus datos del sistema.</p>
                                <div class="form-group"><label class="form-label">Escribe tu contraseña para confirmar</label><input type="password" id="deleteAccountPassword" class="form-control" placeholder="Contraseña actual"></div>
                                <div class="form-actions"><button class="btn-danger" id="btnEliminarCuenta" type="button"><i class="fas fa-trash"></i> Eliminar mi cuenta</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- USUARIOS Section -->
            <div id="usersSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-users"></i> Gestión de Usuarios</h3>
                        <div style="display: flex; gap: 8px; align-items:center;">
                            <input type="text" id="searchUsers" placeholder="Buscar usuario..." class="form-control" style="width: 200px;">
                            <button class="btn-primary" onclick="imprimirTabla('usersList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('usersList','Usuarios')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-primary" id="addUserBtn" style="margin-left:0"><i class="fas fa-plus"></i> Nuevo</button>
                        </div>
                    </div>
                    <div class="table-content"><table class="data-table"><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Telefono</th><th>Rol</th><th>Estado</th><th>Acciones</th><tr></thead><tbody id="usersList"><tr><td colspan="7" style="text-align:center">Cargando...</tbody></table></div>
                </div>
            </div>

            <!-- PRODUCTOS Section -->
            <div id="productsSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-boxes"></i> Gestión de Productos</h3>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items:center;">
                            <input type="text" id="searchProducts" placeholder="Buscar producto..." class="form-control" style="width: 180px;">
                            <div class="filtro-botones">
                                <button class="btn-filtro btn-filtro-active" id="filtroTodos" data-filtro="todos"><i class="fas fa-list"></i> Todos</button>
                                <button class="btn-filtro btn-filtro-inactive" id="filtroVisibles" data-filtro="visibles"><i class="fas fa-eye"></i> Visibles</button>
                                <button class="btn-filtro btn-filtro-inactive" id="filtroOcultos" data-filtro="ocultos"><i class="fas fa-eye-slash"></i> Ocultos</button>
                            </div>
                            <button class="btn-primary" onclick="imprimirTabla('productsList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('productsList','Productos')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-primary" id="addProductBtn"><i class="fas fa-plus"></i> Nuevo Producto</button>
                            <button class="btn-primary" id="btnActualizarProductos" style="background: var(--info);"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead><tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
                            <tbody id="productsList"><tr><td colspan="7" style="text-align:center; padding: 40px;"><div class="loading-spinner" style="margin: 0 auto;"></div><p>Cargando productos...</p></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PROVEEDORES Section -->
            <div id="proveedoresSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-truck"></i> Proveedores</h3>
                        <div style="display: flex; gap: 8px; align-items:center;">
                            <input type="text" id="searchProveedores" placeholder="Buscar proveedor..." class="form-control" style="width: 200px;">
                            <button class="btn-primary" onclick="imprimirTabla('proveedoresList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('proveedoresList','Proveedores')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-primary" id="addProveedorBtn"><i class="fas fa-plus"></i> Nuevo</button>
                        </div>
                    </div>
                    <div class="table-content"><table class="data-table"><thead><tr><th>ID</th><th>Codigo</th><th>Nombre</th><th>RUC</th><th>Telefono</th><th>Email</th><th>Contacto</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="proveedoresList"></td><td colspan="9" style="text-align:center">Cargando......</tbody></table></div>
                </div>
            </div>

            <!-- COMPRAS Section - CORREGIDA (Estructura fija) -->
            <div id="comprasSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-shopping-cart"></i> Ordenes de Compra</h3>
                        <div style="display: flex; gap: 8px; align-items:center;">
                            <input type="text" id="searchCompras" placeholder="Buscar compra..." class="form-control" style="width: 200px;">
                            <button class="btn-primary" onclick="imprimirTabla('comprasList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('comprasList','Compras')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-primary" id="addCompraBtn"><i class="fas fa-plus"></i> Nueva Compra</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>N° Orden</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="comprasList">
                                <tr><td colspan="8" style="text-align:center">Cargando...</tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pedidos Section -->
            <div id="pedidosSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-clipboard-list"></i> Pedidos</h3>
                        <div class="filtros" style="display:flex;gap:8px;align-items:center">
                            <select id="filtroEstadoPedido" class="form-control" style="width:auto">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendientes</option>
                                <option value="facturado">Facturados</option>
                                <option value="completado">Completados</option>
                                <option value="cancelado">Cancelados</option>
                            </select>
                            <button class="btn-primary" onclick="imprimirTabla('pedidosList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('pedidosList','Pedidos')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-primary" id="btnFacturarPedido" style="background:var(--success)"><i class="fas fa-file-invoice"></i> Facturar Seleccionado</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead>
                                <tr><th><input type="checkbox" id="selectAllPedidos"></th><th>ID</th><th>N° Pedido</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Método Pago</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="pedidosList"><tr><td colspan="9" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- COTIZACIONES Section -->
            <div id="cotizacionesSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-file-signature"></i> Cotizaciones - CRM</h3>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <input type="text" id="buscarCotizacion" placeholder="Buscar cotización..." class="form-control" style="width:200px">
                            <select id="filtroEstadoCotizacion" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobada">Aprobada</option>
                                <option value="rechazada">Rechazada</option>
                                <option value="vencida">Vencida</option>
                                <option value="convertida">Convertida</option>
                            </select>
                            <input type="date" id="cotizacionFechaDesde" class="form-control">
                            <input type="date" id="cotizacionFechaHasta" class="form-control">
                            <button class="btn-primary" id="btnFiltrarCotizaciones" style="background:var(--info)"><i class="fas fa-filter"></i> Filtrar</button>
                            <button class="btn-primary" id="btnNuevaCotizacion"><i class="fas fa-plus"></i> Nueva Cotización</button>
                            <button class="btn-primary" onclick="imprimirTabla('cotizacionesBody')" style="background:var(--purple)"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('cotizacionesBody','Cotizaciones')" style="background:var(--success)"><i class="fas fa-file-excel"></i></button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead>
                                <tr><th>N° Cotización</th><th>Cliente</th><th>Email</th><th>Teléfono</th><th>Total</th><th>Estado</th><th>Vendedor</th><th>Fecha</th><th>Vencimiento</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="cotizacionesBody"><tr><td colspan="10" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CRM Section -->
            <div id="crmSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-handshake"></i> CRM - Interacciones con Clientes</h3>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <input type="text" id="buscarClienteCRM" placeholder="Buscar cliente..." class="form-control" style="width:200px">
                            <button class="btn-primary" onclick="abrirModalCRM(0)" style="background:var(--success)"><i class="fas fa-plus"></i> Nueva Interacción</button>
                            <button class="btn-primary" onclick="cargarInteraccionesCRM()" style="background:var(--info)"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead>
                                <tr><th>Fecha</th><th>Cliente</th><th>Tipo</th><th>Título</th><th>Descripción</th><th>Registrado por</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="crmInteraccionesBody"><tr><td colspan="7" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- FACTURACION Section -->
            <div id="facturacionSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Facturas</h3>
                        <div style="display: flex; gap: 8px; align-items:center; flex-wrap:wrap;">
                            <input type="text" id="searchFacturas" placeholder="Buscar factura..." class="form-control" style="width: 180px;">
                            <button class="btn-primary" onclick="imprimirTabla('facturasList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('facturasList','Facturas')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button>
                            <div style="display:flex;gap:5px;flex-wrap:wrap">
                                <button class="btn-primary" id="btnListarFacturas" style="background: var(--info);"><i class="fas fa-list"></i> Listar</button>
                                <button class="btn-primary" id="btnNuevaFactura"><i class="fas fa-plus"></i> Nueva</button>
                                <button class="btn-primary" id="btnActualizarFacturas" style="margin-left:0"><i class="fas fa-sync"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead><tr><th>ID</th><th>N° Factura</th><th>Cliente</th><th>Fecha</th><th>Método Pago</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
                            <tbody id="facturasList"><tr><td colspan="8" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Caja Section -->
            <div id="cajaSection" class="content-section">
                <div class="resumen-cards">
                    <div class="resumen-card"><div class="resumen-value" id="cajaEstado">Cerrada</div><div class="resumen-label">Estado</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="cajaMontoInicial">Bs. 0</div><div class="resumen-label">Monto Inicial</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="cajaIngresos">Bs. 0</div><div class="resumen-label">Ingresos</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="cajaEgresos">Bs. 0</div><div class="resumen-label">Egresos</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="cajaTotal">Bs. 0</div><div class="resumen-label">Total</div></div>
                </div>
                <div class="table-container">
                    <div class="table-header"><h3><i class="fas fa-cash-register"></i> Movimientos</h3><div style="display:flex;gap:5px;flex-wrap:wrap"><button class="btn-primary" onclick="imprimirTabla('cajaMovimientosList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button><button class="btn-primary" onclick="exportarExcel('cajaMovimientosList','Caja_Movimientos')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button><button class="btn-primary" id="btnAbrirCaja" style="background:var(--success)"><i class="fas fa-unlock"></i> Abrir Caja</button><button class="btn-primary" id="btnCerrarCaja" style="background:var(--danger)"><i class="fas fa-lock"></i> Cerrar Caja</button><button class="btn-primary" id="btnRegistrarMovimiento" style="background:var(--info)"><i class="fas fa-plus"></i> Movimiento</button></div></div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead><tr><th>Fecha</th><th>Tipo</th><th>Categoria</th><th>Monto</th><th>Descripcion</th><th>Usuario</th></tr></thead>
                            <tbody id="cajaMovimientosList"><tr><td colspan="6" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ventas por Cliente Section -->
            <div id="ventasClienteSection" class="content-section">
                <div class="resumen-cards"><div class="resumen-card"><div class="resumen-value" id="clientesTotal">0</div><div class="resumen-label">Total Clientes</div></div><div class="resumen-card"><div class="resumen-value" id="ventasClienteTotal">0</div><div class="resumen-label">Total Ventas</div></div><div class="resumen-card"><div class="resumen-value" id="montoClienteTotal">Bs. 0</div><div class="resumen-label">Monto Total</div></div></div>
                <div class="table-container">
                    <div class="table-header"><h3>Ventas por Cliente</h3><div style="display:flex;gap:8px"><input type="text" id="buscarCliente" placeholder="Buscar..." class="form-control"><button class="btn-primary" onclick="abrirModalCRM(0)" title="Añadir interacción CRM" style="background:var(--purple)"><i class="fas fa-handshake"></i> CRM</button><button class="btn-primary" onclick="imprimirTabla('ventasClienteBody')" style="background:var(--purple)"><i class="fas fa-print"></i></button><button class="btn-primary" onclick="exportarExcel('ventasClienteBody','Ventas_por_Cliente')" style="background:var(--success)"><i class="fas fa-file-excel"></i></button></div></div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead><tr><th>ID</th><th>Cliente</th><th>Email</th><th>Telefono</th><th>Ventas</th><th>Productos</th><th>Monto</th><th>Ultima Compra</th><th>Acciones</th></tr></thead>
                            <tbody id="ventasClienteBody"><tr><td colspan="9" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ventas por Vendedor Section -->
            <div id="ventasVendedorSection" class="content-section">
                <div class="resumen-cards">
                    <div class="resumen-card"><div class="resumen-value" id="vendedoresTotal">0</div><div class="resumen-label">Total Vendedores</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="ventasVendedorTotal">0</div><div class="resumen-label">Total Ventas</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="montoVendedorTotal">Bs. 0</div><div class="resumen-label">Monto Total</div></div>
                </div>
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-user-tie"></i> Ventas por Vendedor</h3>
                        <div>
                            <select id="filtroVendedor" class="form-control">
                                <option value="">Todos los vendedores</option>
                            </select>
                            <button class="btn-primary" onclick="imprimirTabla('ventasVendedorBody')" style="background:var(--purple)"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('ventasVendedorBody','Ventas_por_Vendedor')" style="background:var(--success)"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-primary" id="btnActualizarVendedores" style="margin-left:10px; background: var(--info);"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table tabla-ventas">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Vendedor</th>
                                    <th>Email</th>
                                    <th>Ventas realizadas</th>
                                    <th>Productos vendidos</th>
                                    <th>Monto total</th>
                                    <th>Ticket promedio</th>
                                    <th>Última venta</th>
                                </tr>
                            </thead>
                            <tbody id="ventasVendedorBody">
                                <tr><td colspan="8" style="text-align:center">Cargando datos de vendedores...</tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Productos mas Vendidos Section -->
            <div id="productosVendidosSection" class="content-section">
                <div class="table-container">
                    <div class="table-header"><h3><i class="fas fa-chart-bar"></i> Productos mas Vendidos</h3><div><button class="btn-primary" onclick="imprimirTabla('productosVendidosBody')" style="background:var(--purple)"><i class="fas fa-print"></i></button><button class="btn-primary" onclick="exportarExcel('productosVendidosBody','Productos_mas_Vendidos')" style="background:var(--success)"><i class="fas fa-file-excel"></i></button></div></div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead><tr><th>ID</th><th>Producto</th><th>Categoria</th><th>Veces Vendido</th><th>Unidades</th><th>Ingresos</th><th>Stock</th></tr></thead>
                            <tbody id="productosVendidosBody"><tr><td colspan="7" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reporte de Stock Section -->
            <div id="reporteStockSection" class="content-section">
                <div class="resumen-cards" id="stockStatsCards">
                    <div class="resumen-card"><div class="resumen-value" id="totalProductosStock">0</div><div class="resumen-label">Total Productos</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="stockCritico" style="color: #ff4757;">0</div><div class="resumen-label">Stock Crítico (≤5)</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="stockBajoResumen" style="color: #ffa502;">0</div><div class="resumen-label">Stock Bajo (≤10)</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="stockMedio" style="color: #3498db;">0</div><div class="resumen-label">Stock Medio (≤20)</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="stockAlto" style="color: #2ed573;">0</div><div class="resumen-label">Stock Alto (>20)</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="agotadosResumen" style="color: #ff4757;">0</div><div class="resumen-label">Agotados</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="valorInventario">Bs. 0</div><div class="resumen-label">Valor del Inventario</div></div>
                </div>
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-chart-simple"></i> Reporte de Inventario</h3>
                        <div class="filtros">
                            <select id="filtroCategoriaStock" class="form-control"><option value="">Todas las categorías</option></select>
                            <select id="filtroEstadoStock" class="form-control"><option value="">Todos los estados</option><option value="critico">Stock Crítico (≤5)</option><option value="bajo">Stock Bajo (≤10)</option><option value="normal">Stock Normal (>10)</option><option value="agotado">Agotados</option></select>
                            <button class="btn-primary" id="btnFiltrarStock"><i class="fas fa-filter"></i> Filtrar</button>
                            <button class="btn-primary" onclick="imprimirTabla('reporteStockBody')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" id="btnExportarStock" style="background: var(--success);"><i class="fas fa-file-excel"></i> Exportar</button>
                            <button class="btn-primary" id="btnActualizarStock" style="background: var(--info);"><i class="fas fa-sync-alt"></i> Actualizar</button>
                            <button class="btn-primary" id="btnNotificarTelegramStock" style="background: var(--danger);"><i class="fab fa-telegram"></i> Notificar por Telegram</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead><tr><th>ID</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock Actual</th><th>Estado</th><th>Veces Vendido</th><th>Acciones</th></tr></thead>
                            <tbody id="reporteStockBody"><tr><td colspan="8" style="text-align:center">Cargando...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- HISTORIAL DE COMPRAS POR CLIENTE Section -->
            <div id="historialComprasSection" class="content-section">
                <div class="resumen-cards">
                    <div class="resumen-card"><div class="resumen-value" id="totalClientesHistorial">0</div><div class="resumen-label">Total Clientes</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="totalPedidosHistorial">0</div><div class="resumen-label">Total Pedidos</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="totalMontoHistorial">Bs. 0</div><div class="resumen-label">Monto Total</div></div>
                </div>
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-history"></i> Historial de Compras por Cliente</h3>
                        <div class="filtros">
                            <input type="text" id="buscarClienteHistorial" placeholder="Buscar cliente..." class="form-control" style="width:200px">
                            <input type="date" id="fechaDesdeHistorial" class="form-control">
                            <input type="date" id="fechaHastaHistorial" class="form-control">
                            <select id="estadoHistorial" class="form-control"><option value="">Todos los estados</option><option value="pendiente">Pendiente</option><option value="completado">Completado</option><option value="facturado">Facturado</option><option value="cancelado">Cancelado</option></select>
                            <button class="btn-primary" id="btnFiltrarHistorial"><i class="fas fa-filter"></i> Filtrar</button>
                            <button class="btn-primary" onclick="imprimirTabla('historialComprasBody')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" id="btnExportarHistorial" style="background:var(--success)"><i class="fas fa-file-excel"></i> Exportar</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID Pedido</th>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Fecha</th>
                                    <th>Método Pago</th>
                                    <th>Total</th>
                                    <th>Productos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="historialComprasBody">
                                <tr><td colspan="10" style="text-align:center">Cargando...</tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reporte General Section -->
            <div id="reporteGeneralSection" class="content-section">
                <div class="kpi-grid">
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div class="kpi-value" id="kpiVentasTotales">Bs. 0</div><div class="kpi-label">Ventas Totales</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-trend-up"></i></div><div class="kpi-value" id="kpiVentasMes">Bs. 0</div><div class="kpi-label">Ventas del Mes</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-calendar-week"></i></div><div class="kpi-value" id="kpiVentasSemana">Bs. 0</div><div class="kpi-label">Ventas de la Semana</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-percent"></i></div><div class="kpi-value" id="kpiCrecimiento">0%</div><div class="kpi-label">Crecimiento vs Mes Anterior</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div><div class="kpi-value" id="kpiTicketPromedio">Bs. 0</div><div class="kpi-label">Ticket Promedio</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-value" id="kpiClientesActivos">0</div><div class="kpi-label">Clientes Activos</div></div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 25px;">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-line"></i> Ventas por Mes</div><canvas id="ventasPorMesChart" style="max-height: 300px; width: 100%;"></canvas></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-pie"></i> Distribución por Método de Pago</div><canvas id="metodoPagoChart" style="max-height: 300px; width: 100%;"></canvas></div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-trophy"></i> Top 5 Productos Más Vendidos</div><div id="topProductosList" class="top-list"><div style="text-align:center; padding:20px;">Cargando...</div></div></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-star"></i> Top 5 Clientes</div><div id="topClientesList" class="top-list"><div style="text-align:center; padding:20px;">Cargando...</div></div></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-user-tie"></i> Top 5 Vendedores</div><div id="topVendedoresList" class="top-list"><div style="text-align:center; padding:20px;">Cargando...</div></div></div>
                </div>
                <div class="form-actions no-print" style="margin-top: 20px; justify-content: center;">
                    <button class="btn-primary" onclick="exportarReporteGeneralPDF()"><i class="fas fa-file-pdf"></i> Exportar a PDF</button>
                    <button class="btn-primary" onclick="exportarReporteGeneralExcel()" style="background: var(--success);"><i class="fas fa-file-excel"></i> Exportar a Excel</button>
                    <button class="btn-primary" onclick="window.print()" style="background: var(--info);"><i class="fas fa-print"></i> Imprimir</button>
                </div>
            </div>

            <!-- Reporte Especifico Section -->
            <div id="reporteEspecificoSection" class="content-section">
                <div class="filtros-avanzados">
                    <div class="filtro-group"><label><i class="fas fa-calendar-alt"></i> Fecha Desde</label><input type="date" id="espFechaDesde" class="form-control"></div>
                    <div class="filtro-group"><label><i class="fas fa-calendar-alt"></i> Fecha Hasta</label><input type="date" id="espFechaHasta" class="form-control"></div>
                    <div class="filtro-group"><label><i class="fas fa-tag"></i> Tipo de Reporte</label><select id="espTipoReporte" class="form-control"><option value="ventas">Ventas</option><option value="compras">Compras</option><option value="pedidos">Pedidos</option><option value="clientes">Clientes</option><option value="productos">Productos</option></select></div>
                    <div class="filtro-group"><label><i class="fas fa-filter"></i> Estado</label><select id="espEstado" class="form-control"><option value="">Todos</option><option value="completado">Completado</option><option value="pendiente">Pendiente</option><option value="cancelado">Cancelado</option><option value="facturado">Facturado</option></select></div>
                    <div class="filtro-group"><label><i class="fas fa-search"></i> Buscar</label><input type="text" id="espBuscar" class="form-control" placeholder="Cliente, producto, vendedor..."></div>
                    <div class="filtro-group" style="justify-content: flex-end;"><button class="btn-primary" id="btnAplicarFiltrosEspecificos" style="margin-top: auto;"><i class="fas fa-search"></i> Aplicar Filtros</button></div>
                </div>
                <div class="table-container">
                    <div class="table-header"><h3><i class="fas fa-table"></i> Resultados del Reporte <span id="espTituloReporte">- Ventas</span></h3><div style="display:flex;gap:5px"><button class="btn-primary" onclick="imprimirTabla('espTablaBody')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button><button class="btn-primary" id="btnExportarEspecificoPDF" style="background: var(--danger);"><i class="fas fa-file-pdf"></i> PDF</button><button class="btn-primary" id="btnExportarEspecificoExcel" style="background: var(--success);"><i class="fas fa-file-excel"></i> Excel</button></div></div>
                    <div class="table-content"><table class="data-table"><thead id="espTablaHeaders"><tr><th>ID</th><th>Fecha</th><th>Cliente/Vendedor</th><th>Total</th><th>Estado</th><th>Método Pago</th></tr></thead><tbody id="espTablaBody"><tr><td colspan="6" style="text-align:center">Selecciona filtros y presiona "Aplicar Filtros"</tbody></table></div>
                </div>
                <div class="resumen-cards" id="espResumenCards" style="margin-top: 20px; display: none;">
                    <div class="resumen-card"><div class="resumen-value" id="espTotalRegistros">0</div><div class="resumen-label">Total Registros</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="espTotalMonto">Bs. 0</div><div class="resumen-label">Monto Total</div></div>
                    <div class="resumen-card"><div class="resumen-value" id="espPromedioMonto">Bs. 0</div><div class="resumen-label">Promedio</div></div>
                </div>
            </div>

            <!-- Modal de Detalle de Pedido para Historial -->
            <div id="detallePedidoModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 800px;">
                    <div class="modal-header"><h3 class="modal-title"><i class="fas fa-shopping-cart"></i> Detalle del Pedido</h3><button class="modal-close" onclick="cerrarDetallePedidoModal()">&times;</button></div>
                    <div id="detallePedidoContent"><div style="text-align:center; padding: 40px;"><div class="loading-spinner"></div><p>Cargando detalles...</p></div></div>
                    <div class="form-actions"><button class="btn-primary" onclick="imprimirDetallePedido()"><i class="fas fa-print"></i> Imprimir</button><button class="btn-secondary" onclick="cerrarDetallePedidoModal()">Cerrar</button></div>
                </div>
            </div>

            <!-- Auditoria Section -->
            <div id="auditoriaSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-clipboard-list"></i> Auditoria</h3>
                        <div class="filtros">
                            <input type="date" id="auditoriaFechaDesde" class="form-control">
                            <input type="date" id="auditoriaFechaHasta" class="form-control">
                            <select id="auditoriaModulo" class="form-control">
                                <option value="">Todos</option>
                                <option value="usuarios">Usuarios</option>
                                <option value="productos">Productos</option>
                                <option value="pedidos">Pedidos</option>
                                <option value="facturacion">Facturacion</option>
                            </select>
                            <button class="btn-primary" id="btnFiltrarAuditoria"><i class="fas fa-filter"></i> Filtrar</button>
                            <button class="btn-primary" onclick="imprimirTabla('auditoriaBody')" style="background:var(--purple)"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('auditoriaBody','Auditoria')" style="background:var(--success)"><i class="fas fa-file-excel"></i></button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Modulo</th>
                                    <th>Accion</th>
                                    <th>Descripcion</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody id="auditoriaBody">  <!-- ← AGREGAR EL ID AQUÍ -->
                                <tr><td colspan="7" style="text-align:center">Cargando...</tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CEO Section -->
            <div id="ceoSection" class="content-section">
                <div class="kpi-grid" style="margin-bottom: 25px;">
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-value" id="ceoUsuarios">0</div><div class="kpi-label">Total Usuarios</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-user-friends"></i></div><div class="kpi-value" id="ceoClientes">0</div><div class="kpi-label">Clientes Activos</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-boxes"></i></div><div class="kpi-value" id="ceoProductos">0</div><div class="kpi-label">Productos en Inventario</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-truck"></i></div><div class="kpi-value" id="ceoProveedores">0</div><div class="kpi-label">Proveedores</div></div>
                </div>
                <div class="kpi-grid" style="margin-bottom: 25px;">
                    <div class="kpi-card" style="background: linear-gradient(135deg, #2ed573, #1abc9c);"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div class="kpi-value" id="ceoVentasMes">Bs. 0</div><div class="kpi-label">Ventas del Mes</div></div>
                    <div class="kpi-card" style="background: linear-gradient(135deg, #ffa502, #e67e22);"><div class="kpi-icon"><i class="fas fa-clock"></i></div><div class="kpi-value" id="ceoPedidosPendientes">0</div><div class="kpi-label">Pedidos Pendientes</div></div>
                    <div class="kpi-card" style="background: linear-gradient(135deg, #ff4757, #e74c3c);"><div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="kpi-value" id="ceoStockBajo">0</div><div class="kpi-label">Productos Stock Bajo</div></div>
                    <div class="kpi-card" style="background: linear-gradient(135deg, #3498db, #2980b9);"><div class="kpi-icon"><i class="fas fa-shopping-cart"></i></div><div class="kpi-value" id="ceoComprasMes">Bs. 0</div><div class="kpi-label">Compras del Mes</div></div>
                </div>
                <div class="kpi-grid" style="margin-bottom: 25px;">
                    <div class="kpi-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);"><div class="kpi-icon"><i class="fas fa-chart-simple"></i></div><div class="kpi-value" id="ceoUtilidad">Bs. 0</div><div class="kpi-label">Utilidad Estimada</div></div>
                    <div class="kpi-card" style="background: linear-gradient(135deg, #1abc9c, #16a085);"><div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div><div class="kpi-value" id="ceoTicketPromedio">Bs. 0</div><div class="kpi-label">Ticket Promedio</div></div>
                    <div class="kpi-card" style="background: linear-gradient(135deg, #e056fd, #be2edd);"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div class="kpi-value" id="ceoCrecimiento">0%</div><div class="kpi-label">Crecimiento vs Mes Anterior</div></div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 25px;">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-line"></i> Ventas por Mes</div><canvas id="ceoVentasMesChart" style="max-height: 300px; width: 100%;"></canvas></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-pie"></i> Distribución por Método de Pago</div><canvas id="ceoMetodoPagoChart" style="max-height: 300px; width: 100%;"></canvas></div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-trophy"></i> Top 5 Productos Más Vendidos</div><div id="ceoTopProductosList" class="top-list"><div style="text-align:center; padding:20px;">Cargando...</div></div></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-star"></i> Top 5 Clientes</div><div id="ceoTopClientesList" class="top-list"><div style="text-align:center; padding:20px;">Cargando...</div></div></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-user-tie"></i> Top 5 Vendedores</div><div id="ceoTopVendedoresList" class="top-list"><div style="text-align:center; padding:20px;">Cargando...</div></div></div>
                </div>
            </div>

            <!-- Configuracion Section -->
            <div id="configuracionSection" class="content-section">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-cog"></i> Configuración del Sistema</h3>
                        <div style="display:flex;gap:8px">
                            <button class="btn-primary" onclick="imprimirTabla('configuracionBody')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button>
                            <button class="btn-primary" onclick="exportarExcel('configuracionBody','Configuracion')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button>
                            <button class="btn-primary" id="btnGuardarConfig"><i class="fas fa-save"></i> Guardar Cambios</button>
                        </div>
                    </div>
                    <div class="table-content">
                        <table class="data-table">
                            <thead><tr><th width="30%">Clave</th><th width="40%">Valor</th><th width="30%">Descripción</th></tr></thead>
                            <tbody id="configuracionBody"><tr><td colspan="3" style="text-align:center">Cargando configuración...</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Backup Section -->
            <div id="backupSection" class="content-section">
                <div class="table-container">
                    <div class="table-header"><h3><i class="fas fa-database"></i> Copias de Seguridad</h3><div style="display:flex;gap:8px"><button class="btn-primary" onclick="imprimirTabla('backupsList')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button><button class="btn-primary" onclick="exportarExcel('backupsList','Backups')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button><button class="btn-primary" id="btnCrearBackup"><i class="fas fa-plus"></i> Crear Backup</button></div></div>
                    <div class="table-content"><table class="data-table"><thead><tr><th>ID</th><th>Archivo</th><th>Tamaño</th><th>Tipo</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="backupsList"><tr><td colspan="7" style="text-align:center">Cargando...</tbody></table></div>
                </div>
            </div>

            <!-- Marketing / Recomendaciones Section -->
            <div id="marketingSection" class="content-section">
                <div class="header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <h3><i class="fas fa-bullhorn"></i> Marketing - Recomendaciones a Clientes</h3>
                    <div style="display:flex;gap:8px">
                        <button class="btn-primary" id="btnEnviarRecomendaciones" style="background:var(--success)"><i class="fas fa-paper-plane"></i> Enviar Recomendaciones</button>
                        <button class="btn-primary" id="btnHistorialEnvios" style="background:var(--info)"><i class="fas fa-sync-alt"></i> Actualizar</button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:20px">
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-envelope"></i></div><div class="kpi-value" id="marketingTotalEnvios">0</div><div class="kpi-label">Total Envíos</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-star"></i></div><div class="kpi-value" id="marketingRecomendaciones">0</div><div class="kpi-label">Recomendaciones</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-box"></i></div><div class="kpi-value" id="marketingNuevosProductos">0</div><div class="kpi-label">Nuevos Productos</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-poll"></i></div><div class="kpi-value" id="marketingEncuestas">0</div><div class="kpi-label">Encuestas</div></div>
                </div>
                <div class="table-container">
                    <div class="table-header"><h3><i class="fas fa-history"></i> Historial de Envíos</h3>
                    <div style="display:flex;gap:8px"><button class="btn-primary" onclick="imprimirTabla('enviosRecomendacionesBody')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button><button class="btn-primary" onclick="exportarExcel('enviosRecomendacionesBody','Historial_Marketing')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button></div></div>
                    <div class="table-content"><table class="data-table"><thead><tr><th>ID</th><th>Email</th><th>Tipo</th><th>Asunto</th><th>Fecha</th></tr></thead><tbody id="enviosRecomendacionesBody"><tr><td colspan="5" style="text-align:center">Cargando...</tbody></table></div>
                </div>
            </div>

            <!-- IA Predictiva Section -->
            <div id="prediccionesSection" class="content-section">
                <div class="header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <h3><i class="fas fa-brain"></i> IA Predictiva - Pronóstico de Ventas e Inventario</h3>
                    <div>
                        <button class="btn-primary" id="btnGenerarPredicciones" style="background:var(--info)"><i class="fas fa-sync-alt"></i> Generar Predicciones</button>
                    </div>
                </div>
                <div class="kpi-grid" id="prediccionesResumen">
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div class="kpi-value" id="predPrecision">0%</div><div class="kpi-label">Precisión Promedio</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-check-circle"></i></div><div class="kpi-value" id="predConfianza">0%</div><div class="kpi-label">Nivel de Confianza</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-arrow-up" style="color:#2ed573"></i></div><div class="kpi-value" id="predSubiendo">0</div><div class="kpi-label">Productos en Aumento</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-arrow-down" style="color:#ff4757"></i></div><div class="kpi-value" id="predBajando">0</div><div class="kpi-label">Productos en Descenso</div></div>
                </div>
                <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-bar"></i> Pronóstico vs Real (Este Mes)</div><canvas id="prediccionesChart" style="max-height:300px;width:100%"></canvas></div>
                <div class="table-container" style="margin-top:20px">
                    <div class="table-header"><h3><i class="fas fa-boxes"></i> Predicciones por Producto</h3><div class="filtros" style="display:flex;gap:8px;align-items:center"><input type="text" id="searchPredicciones" placeholder="Buscar producto..." class="form-control" style="width:200px"><button class="btn-primary" onclick="imprimirTabla('prediccionesBody')" style="background:var(--purple);padding:6px 10px;font-size:0.8rem"><i class="fas fa-print"></i></button><button class="btn-primary" onclick="exportarExcel('prediccionesBody','Predicciones')" style="background:var(--success);padding:6px 10px;font-size:0.8rem"><i class="fas fa-file-excel"></i></button></div></div>
                    <div class="table-content"><table class="data-table"><thead><tr><th>Producto</th><th>SKU</th><th>Categoría</th><th>Stock Actual</th><th>Ventas Esperadas</th><th>Stock Sugerido</th><th>Tendencia</th><th>Confianza</th><th>Días para Agotar</th><th>Estado</th></tr></thead><tbody id="prediccionesBody"><tr><td colspan="10" style="text-align:center">Generando predicciones...</tbody></table></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;margin-top:20px">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-exclamation-triangle" style="color:#ffa502"></i> Alertas de Stock</div><div id="alertasStockList" class="top-list"><div style="text-align:center;padding:20px">Cargando alertas...</div></div></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-lightbulb" style="color:#2ed573"></i> Recomendaciones</div><div id="recomendacionesList" class="top-list"><div style="text-align:center;padding:20px">Cargando recomendaciones...</div></div></div>
                </div>
            </div>

            <!-- BI Dashboard Section -->
            <div id="biDashboardSection" class="content-section">
                <div class="header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <h3><i class="fas fa-chart-pie"></i> Business Intelligence - Analítica Avanzada</h3>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <label style="color:var(--text-color);font-size:0.85rem">Desde:</label>
                        <input type="date" id="biFechaDesde" class="form-control" style="width:auto;padding:6px 10px;background:var(--card-bg);color:var(--text-color);border:1px solid var(--border-color);border-radius:6px">
                        <label style="color:var(--text-color);font-size:0.85rem">Hasta:</label>
                        <input type="date" id="biFechaHasta" class="form-control" style="width:auto;padding:6px 10px;background:var(--card-bg);color:var(--text-color);border:1px solid var(--border-color);border-radius:6px">
                        <button class="btn-primary" id="btnFiltrarBI" style="background:var(--accent-color)"><i class="fas fa-filter"></i> Filtrar</button>
                        <button class="btn-primary" id="btnActualizarBI" style="background:var(--info)"><i class="fas fa-sync-alt"></i> Actualizar Datos</button>
                    </div>
                </div>
                <div class="kpi-grid" id="biKpiGrid">
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-file-invoice"></i></div><div class="kpi-value" id="biFacturasHoy">0</div><div class="kpi-label">Facturas Hoy</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-chart-line"></i></div><div class="kpi-value" id="biVentasHoy">Bs. 0</div><div class="kpi-label">Ventas Hoy</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-calendar-week"></i></div><div class="kpi-value" id="biVentasMes">Bs. 0</div><div class="kpi-label">Ventas del Mes</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-percent"></i></div><div class="kpi-value" id="biCrecimiento">0%</div><div class="kpi-label">Crecimiento</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div><div class="kpi-value" id="biTicketPromedio">Bs. 0</div><div class="kpi-label">Ticket Promedio</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-users"></i></div><div class="kpi-value" id="biClientesActivos">0</div><div class="kpi-label">Clientes Activos</div></div>
                    <div class="kpi-card"><div class="kpi-icon"><i class="fas fa-box"></i></div><div class="kpi-value" id="biStockBajo">0</div><div class="kpi-label">Stock Bajo</div></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;margin-bottom:20px">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-line"></i> Tendencia de Ventas (12 meses)</div><canvas id="biVentasChart" style="max-height:300px;width:100%"></canvas></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-pie"></i> Distribución por Método de Pago</div><canvas id="biMetodosPagoChart" style="max-height:300px;width:100%"></canvas></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;margin-bottom:20px">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-chart-bar"></i> Ventas por Categoría</div><canvas id="biCategoriasChart" style="max-height:300px;width:100%"></canvas></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-users"></i> Nuevos Clientes por Mes</div><canvas id="biClientesChart" style="max-height:300px;width:100%"></canvas></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:20px">
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-trophy"></i> Top 10 Productos Más Vendidos</div><div id="biTopProductos" class="top-list"><div style="text-align:center;padding:20px">Cargando...</div></div></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-star"></i> Top 10 Clientes</div><div id="biTopClientes" class="top-list"><div style="text-align:center;padding:20px">Cargando...</div></div></div>
                    <div class="chart-container"><div class="chart-title"><i class="fas fa-layer-group"></i> Stock por Categoría</div><canvas id="biStockCategoriasChart" style="max-height:250px;width:100%"></canvas></div>
                </div>
            </div>


            <!-- Telegram Section -->
            <div id="telegramSection" class="content-section">
                <div class="header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <h3><i class="fab fa-telegram"></i> Integración Telegram</h3>
                </div>
                <div class="card" style="margin-bottom:20px">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-cog"></i> Configuración de API</h3></div>
                    <div class="card-body" style="padding:20px">
                        <div class="filtros-avanzados" style="grid-template-columns:1fr 1fr">
                            <div class="filtro-group"><label>Token del Bot</label><input type="password" id="telegramToken" class="form-control" placeholder="7234567890:AAHdskfjsdfkj234234..."></div>
                            <div class="filtro-group"><label>Chat ID</label><input type="text" id="telegramChatId" class="form-control" placeholder="123456789"></div>
                        </div>
                        <div class="form-actions" style="margin-top:15px">
                            <button class="btn-primary" id="btnGuardarTelegram"><i class="fas fa-save"></i> Guardar Configuración</button>
                            <button class="btn-primary" id="btnProbarTelegram" style="background:var(--success);margin-left:10px"><i class="fab fa-telegram"></i> Enviar Mensaje de Prueba</button>
                            <button class="btn-primary" id="btnProbarTelegramStock" style="background:var(--danger);margin-left:10px"><i class="fas fa-box"></i> Notificar Stock Bajo</button>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle"></i> Estado de la Integración</h3></div>
                    <div class="card-body" style="padding:20px">
                        <div class="profile-info-row"><div class="profile-info-label">Estado</div><div class="profile-info-value" id="telegramEstado"><span class="badge badge-pending"><i class="fas fa-clock"></i> No configurado</span></div></div>
                        <div class="profile-info-row"><div class="profile-info-label">Chat ID</div><div class="profile-info-value" id="telegramChatIdDisplay">-</div></div>
                        <div class="profile-info-row"><div class="profile-info-label">Bot</div><div class="profile-info-value" id="telegramBotDisplay">-</div></div>
                        <div class="profile-info-row" style="border-bottom:none"><div class="profile-info-label">Notificaciones</div><div class="profile-info-value"><span style="color:var(--success)"><i class="fas fa-check-circle"></i> Pedidos + Stock</span></div></div>
                    </div>
                </div>
            </div>

            <!-- 2FA Section -->
            <div id="seguridad2faSection" class="content-section">
                <div class="header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <h3><i class="fas fa-shield-alt"></i> Autenticación de Dos Factores (2FA)</h3>
                </div>
                <div class="card" style="max-width:600px;margin:0 auto">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-lock"></i> Seguridad de la Cuenta</h3></div>
                    <div class="card-body" style="padding:25px;text-align:center">
                        <div style="font-size:4rem;color:var(--accent-color);margin-bottom:20px" id="2faIcon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 style="margin-bottom:10px;color:var(--text-color)" id="2faTitulo">Protege tu cuenta</h3>
                        <p style="color:#aaa;margin-bottom:20px" id="2faDescripcion">
                            La autenticación de dos factores añade una capa adicional de seguridad a tu cuenta.
                            Cada vez que inicies sesión, necesitarás tu contraseña y un código generado por tu aplicación de autenticación.
                        </p>
                        <div id="2faEstado" class="profile-info-row" style="justify-content:center;margin-bottom:20px">
                            <span class="badge badge-pending" id="2faEstadoBadge"><i class="fas fa-clock"></i> Verificando estado...</span>
                        </div>
                        <div id="2faSetupSection" style="display:none">
                            <div style="background:var(--bg-color);border-radius:10px;padding:20px;margin-bottom:20px;text-align:left">
                                <h4 style="color:var(--accent-color);margin-bottom:15px"><i class="fas fa-cog"></i> Paso 1: Escanea el código QR</h4>
                                <p style="color:#aaa;font-size:0.85rem;margin-bottom:15px">Abre Google Authenticator (o cualquier app compatible) y escanea este código:</p>
                                <div id="2faQRCode" style="text-align:center;margin-bottom:15px;background:white;padding:15px;border-radius:10px;display:inline-block;width:100%">
                                    <div id="2faQRContainer" style="width:200px;height:200px;margin:0 auto;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999">
                                        <i class="fas fa-qrcode" style="font-size:4rem"></i>
                                    </div>
                                </div>
                                <p style="color:#aaa;font-size:0.8rem;text-align:center">O ingresa manualmente: <code id="2faSecretDisplay" style="background:#0f1219;padding:4px 8px;border-radius:4px;color:var(--accent-color);font-size:0.9rem"></code></p>
                                <h4 style="color:var(--accent-color);margin:20px 0 15px"><i class="fas fa-key"></i> Paso 2: Verifica el código</h4>
                                <p style="color:#aaa;font-size:0.85rem;margin-bottom:15px">Ingresa el código de 6 dígitos que aparece en tu app:</p>
                                <div style="display:flex;gap:8px;justify-content:center;margin-bottom:15px" id="2faCodeInput">
                                    <input type="text" maxlength="1" class="code-digit-2fa form-control" style="width:45px;height:55px;text-align:center;font-size:1.3rem;font-weight:700" id="2fa1">
                                    <input type="text" maxlength="1" class="code-digit-2fa form-control" style="width:45px;height:55px;text-align:center;font-size:1.3rem;font-weight:700" id="2fa2">
                                    <input type="text" maxlength="1" class="code-digit-2fa form-control" style="width:45px;height:55px;text-align:center;font-size:1.3rem;font-weight:700" id="2fa3">
                                    <input type="text" maxlength="1" class="code-digit-2fa form-control" style="width:45px;height:55px;text-align:center;font-size:1.3rem;font-weight:700" id="2fa4">
                                    <input type="text" maxlength="1" class="code-digit-2fa form-control" style="width:45px;height:55px;text-align:center;font-size:1.3rem;font-weight:700" id="2fa5">
                                    <input type="text" maxlength="1" class="code-digit-2fa form-control" style="width:45px;height:55px;text-align:center;font-size:1.3rem;font-weight:700" id="2fa6">
                                </div>
                                <div class="form-actions" style="justify-content:center">
                                    <button class="btn-primary" id="btnVerificar2FA"><i class="fas fa-check-circle"></i> Verificar y Activar</button>
                                </div>
                            </div>
                            <div style="background:rgba(255,165,2,0.1);border:1px solid rgba(255,165,2,0.3);border-radius:10px;padding:15px;margin-bottom:20px">
                                <h4 style="color:var(--warning);margin-bottom:10px"><i class="fas fa-exclamation-triangle"></i> Códigos de Respaldo</h4>
                                <p style="color:#aaa;font-size:0.8rem;margin-bottom:10px">Guarda estos códigos en un lugar seguro. Puedes usarlos si pierdes acceso a tu app de autenticación.</p>
                                <div id="2faBackupCodes" style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center"></div>
                            </div>
                        </div>
                        <div id="2faActiveSection" style="display:none">
                            <div style="background:rgba(46,213,115,0.1);border:1px solid rgba(46,213,115,0.3);border-radius:10px;padding:20px;margin-bottom:20px">
                                <i class="fas fa-check-circle" style="font-size:3rem;color:var(--success);margin-bottom:10px"></i>
                                <h4 style="color:var(--success)">2FA Activado</h4>
                                <p style="color:#aaa;font-size:0.85rem">Tu cuenta está protegida con autenticación de dos factores.</p>
                            </div>
                            <div class="form-actions" style="justify-content:center">
                                <button class="btn-danger" id="btnDesactivar2FA"><i class="fas fa-shield-slash"></i> Desactivar 2FA</button>
                            </div>
                        </div>
                        <div id="2faDisabledSection">
                            <div class="form-actions" style="justify-content:center">
                                <button class="btn-primary" id="btnConfigurar2FA"><i class="fas fa-shield-alt"></i> Configurar 2FA</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL CRM -->
    <div id="crmModal" class="modal" style="display:none">
        <div class="modal-content" style="max-width:500px">
            <div class="modal-header">
                <h3><i class="fas fa-handshake"></i> Nueva Interacción</h3>
                <span class="modal-close" onclick="cerrarModalCRM()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="crmClienteId">
                <div style="margin-bottom:12px">
                    <label>Cliente *</label>
                    <select id="crmClienteSelect" class="form-control" required>
                        <option value="">Seleccionar cliente...</option>
                    </select>
                </div>
                <div style="margin-bottom:12px">
                    <label>Tipo de Interacción *</label>
                    <select id="crmTipo" class="form-control">
                        <option value="llamada">📞 Llamada</option>
                        <option value="correo">✉️ Correo</option>
                        <option value="reunion">🤝 Reunión</option>
                        <option value="nota">📝 Nota</option>
                        <option value="seguimiento">🔄 Seguimiento</option>
                        <option value="recordatorio">⏰ Recordatorio</option>
                    </select>
                </div>
                <div style="margin-bottom:12px">
                    <label>Título *</label>
                    <input type="text" id="crmTitulo" class="form-control" placeholder="Ej: Llamada de seguimiento" required>
                </div>
                <div style="margin-bottom:12px">
                    <label>Descripción</label>
                    <textarea id="crmDescripcion" class="form-control" rows="3" placeholder="Detalles de la interacción..."></textarea>
                </div>
                <div style="margin-bottom:12px">
                    <label>Fecha de interacción</label>
                    <input type="datetime-local" id="crmFecha" class="form-control">
                </div>
                <button class="btn-primary" id="btnGuardarInteraccion" style="width:100%"><i class="fas fa-save"></i> Guardar Interacción</button>
            </div>
        </div>
    </div>

    <!-- MODALES -->
    <div id="verDetalleModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header"><h3 class="modal-title" id="detalleModalTitle">Detalle de Factura</h3><button class="modal-close" onclick="cerrarModalDetalle()">&times;</button></div>
            <div id="detalleModalContent"><div style="text-align:center; padding: 40px;"><div class="loading-spinner"></div><p>Cargando detalles...</p></div></div>
            <div class="form-actions"><button class="btn-primary" onclick="imprimirDetalle()"><i class="fas fa-print"></i> Imprimir</button><button class="btn-secondary" onclick="cerrarModalDetalle()">Cerrar</button></div>
        </div>
    </div>

    <div id="verificarPinModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header"><h3 class="modal-title">Verificar PIN de Recuperación</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
            <form id="verificarPinForm"><div class="form-group"><label class="form-label">Ingresa el PIN que recibiste en tu correo</label><input type="text" id="pinToken" class="form-control" placeholder="Código de 6 dígitos" required></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="cerrarModales()">Cancelar</button><button type="submit" class="btn-primary">Verificar</button></div></form>
        </div>
    </div>

    <div id="recuperacionModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header"><h3 class="modal-title"><i class="fas fa-key"></i> Recuperar Contraseña</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
            <form id="recuperacionForm"><div class="form-group"><label class="form-label">Correo electrónico registrado</label><input type="email" id="recuperacionEmail" class="form-control" placeholder="ejemplo@correo.com" required><small>Te enviaremos un código de verificación a este correo</small></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="cerrarModales()">Cancelar</button><button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Enviar código</button></div></form>
        </div>
    </div>

    <div id="cambiarPasswordRecuperacionModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header"><h3 class="modal-title"><i class="fas fa-lock"></i> Cambiar Contraseña</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
            <form id="cambiarPasswordRecuperacionForm"><div class="form-group"><label class="form-label">Nueva contraseña</label><input type="password" id="nuevaPasswordRecuperacion" class="form-control" required><small>Mínimo 6 caracteres</small></div><div class="form-group"><label class="form-label">Confirmar nueva contraseña</label><input type="password" id="confirmarPasswordRecuperacion" class="form-control" required></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="cerrarModales()">Cancelar</button><button type="submit" class="btn-primary"><i class="fas fa-save"></i> Cambiar contraseña</button></div></form>
        </div>
    </div>

    <div id="addUserModal" class="modal">
        <div class="modal-content"><div class="modal-header"><h3 class="modal-title">Nuevo Usuario</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
        <form id="addUserForm"><div class="form-group"><label class="form-label">Nombre</label><input type="text" id="addNombre" class="form-control" required></div><div class="form-group"><label class="form-label">Email</label><input type="email" id="addEmail" class="form-control" required></div><div class="form-group"><label class="form-label">Contraseña</label><input type="password" id="addPassword" class="form-control" required></div><div class="form-group"><label class="form-label">Teléfono</label><input type="text" id="addTelefono" class="form-control"></div><div class="form-group"><label class="form-label">Rol</label><select id="addRol" class="form-control"><option value="admin">Administrador</option><option value="vendedor">Vendedor</option><option value="contador">Contador</option></select></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="cerrarModales()">Cancelar</button><button type="submit" class="btn-primary">Guardar</button></div></form></div>
    </div>

    <div id="addProveedorModal" class="modal">
        <div class="modal-content"><div class="modal-header"><h3 class="modal-title">Nuevo Proveedor</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
        <form id="addProveedorForm"><div class="form-group"><label class="form-label">Nombre</label><input type="text" id="provNombre" class="form-control" required></div><div class="form-group"><label class="form-label">RUC</label><input type="text" id="provRuc" class="form-control"></div><div class="form-group"><label class="form-label">Teléfono</label><input type="text" id="provTelefono" class="form-control"></div><div class="form-group"><label class="form-label">Email</label><input type="email" id="provEmail" class="form-control"></div><div class="form-group"><label class="form-label">Contacto</label><input type="text" id="provContacto" class="form-control"></div><div class="form-group"><label class="form-label">Dirección</label><textarea id="provDireccion" class="form-control" rows="2"></textarea></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="cerrarModales()">Cancelar</button><button type="submit" class="btn-primary">Guardar</button></div></form></div>
    </div>

    <div id="registrarMovimientoModal" class="modal">
        <div class="modal-content"><div class="modal-header"><h3 class="modal-title">Registrar Movimiento</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
        <form id="movimientoForm"><div class="form-group"><label class="form-label">Tipo</label><select id="movTipo" class="form-control" required><option value="ingreso">Ingreso</option><option value="egreso">Egreso</option></select></div><div class="form-group"><label class="form-label">Categoría</label><input type="text" id="movCategoria" class="form-control" required></div><div class="form-group"><label class="form-label">Monto</label><input type="number" id="movMonto" class="form-control" step="0.01" required></div><div class="form-group"><label class="form-label">Descripción</label><textarea id="movDescripcion" class="form-control" rows="2"></textarea></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="cerrarModales()">Cancelar</button><button type="submit" class="btn-primary">Registrar</button></div></form></div>
    </div>

    <div id="abrirCajaModal" class="modal">
        <div class="modal-content"><div class="modal-header"><h3 class="modal-title">Abrir Caja</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
        <form id="abrirCajaForm"><div class="form-group"><label class="form-label">Monto Inicial</label><input type="number" id="montoInicial" class="form-control" step="0.01" required></div><div class="form-group"><label class="form-label">Observaciones</label><textarea id="cajaObservaciones" class="form-control" rows="2"></textarea></div><div class="form-actions"><button type="button" class="btn-secondary" onclick="cerrarModales()">Cancelar</button><button type="submit" class="btn-primary">Abrir Caja</button></div></form></div>
    </div>

    <!-- Modal: Re-verificación 2FA periódica -->
    <div id="reverificar2faModal" class="modal">
        <div class="modal-content" style="max-width:400px">
            <div class="modal-header"><h3 class="modal-title"><i class="fas fa-shield-alt"></i> Verificación 2FA</h3><button class="modal-close" onclick="cerrarModales()">&times;</button></div>
            <div class="modal-body text-center" style="padding:20px">
                <i class="fab fa-google" style="font-size:3rem;color:#4285F4;margin-bottom:10px"></i>
                <p style="font-size:0.9rem;margin-bottom:4px">Tu sesión requiere verificación adicional</p>
                <p style="font-size:0.85rem;opacity:0.7;margin-bottom:16px">Abre <strong>Google Authenticator</strong> e ingresa el código de 6 dígitos</p>
                <form id="reverificar2faForm">
                    <div class="form-group">
                        <input type="text" id="reverificar2faCode" class="form-control" inputmode="numeric" maxlength="6" placeholder="000000" style="text-align:center;font-size:1.5rem;letter-spacing:8px;font-weight:600;padding:12px">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;padding:10px;font-size:0.9rem"><i class="fas fa-check-circle"></i> Verificar código</button>
                </form>
                <div id="reverificar2faMessage" style="display:none;margin-top:0.8rem;padding:8px 12px;border-radius:8px;font-size:0.85rem"></div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <p style="color: white; margin-top: 15px;">Cargando...</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // ====================================================================
        // VARIABLES GLOBALES
        // ====================================================================
        let usersData = [];
        let productsData = [];
        let proveedoresData = [];
        let comprasData = [];
        let pedidosData = [];
        let facturasData = [];
        let cajaData = null;
        let configuracionData = {};
        let usuarioActual = null;
        let currentDetalleData = null;
        let emailRecuperacion = null;
        let historialData = [];
        let currentDetallePedido = null;
        let ventasClienteData = [];
        let ventasVendedorData = [];
        let productosVendidosData = [];
        let auditoriaData = [];
        let backupsData = [];
        let reporteStockData = [];
        let currentEditPedidoProductos = [];
        let ceoVentasMesChart = null;
        let ceoMetodoPagoChart = null;
        let filtroProductosActual = 'todos';
        
        window.ceoVentasMesChart = null;
        window.ceoMetodoPagoChart = null;

        let biVentasChartInstance = null;
        let biMetodosPagoChartInstance = null;
        let biCategoriasChartInstance = null;
        let biClientesChartInstance = null;
        let biStockCategoriasChartInstance = null;
        let prediccionesChartInstance = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // ====================================================================
        // FUNCIONES DE BÚSQUEDA EN TIEMPO REAL
        // ====================================================================
        function initSearchUsers() {
            const searchInput = document.getElementById('searchUsers');
            if (!searchInput) return;
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tbody = document.getElementById('usersList');
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        function initSearchProducts() {
            const searchInput = document.getElementById('searchProducts');
            if (!searchInput) return;
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tbody = document.getElementById('productsList');
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        function initSearchProveedores() {
            const searchInput = document.getElementById('searchProveedores');
            if (!searchInput) return;
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tbody = document.getElementById('proveedoresList');
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        function initSearchCompras() {
            const searchInput = document.getElementById('searchCompras');
            if (!searchInput) return;
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tbody = document.getElementById('comprasList');
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        function initSearchFacturas() {
            const searchInput = document.getElementById('searchFacturas');
            if (!searchInput) return;
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tbody = document.getElementById('facturasList');
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        // ====================================================================
        // FUNCIONES AUXILIARES
        // ====================================================================
        function escapeHtml(text) { if(!text) return ''; return String(text).replace(/[&<>]/g, function(m){if(m==='&')return'&amp;';if(m==='<')return'&lt;';if(m==='>')return'&gt;';return m;}); }
        
        function formatDate(dateStr) { 
            if(!dateStr || dateStr === 'null' || dateStr === 'NULL' || dateStr === '' || dateStr === 'N/A') return 'N/A';
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return 'N/A';
                if (date.getFullYear() < 1900 || date.getFullYear() > 2100) return 'N/A';
                return date.toLocaleDateString('es-ES');
            } catch(e) { return 'N/A'; }
        }
        
        function formatDateTime(dateStr) { 
            if(!dateStr || dateStr === 'null' || dateStr === 'NULL' || dateStr === '') return 'N/A'; 
            try {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return 'N/A';
                return date.toLocaleString('es-ES');
            } catch(e) { return 'N/A'; }
        }
        
        function formatMoney(value) { return `Bs. ${parseFloat(value || 0).toLocaleString('es-ES', {minimumFractionDigits: 2})}`; }
        
        function imprimirTabla(tbodyId) {
            const tbody = document.getElementById(tbodyId);
            if (!tbody || !tbody.rows.length) return;
            const table = tbody.closest('table') || tbody.parentElement;
            const win = window.open('', '_blank');
            win.document.write('<html><head><title>Reporte</title><style>body{font-family:Arial,sans-serif;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:8px;text-align:left}th{background:#1a1f2e;color:white}</style></head><body>');
            win.document.write(table.outerHTML);
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }
        
        function exportarExcel(tbodyId, nombre) {
            const tbody = document.getElementById(tbodyId);
            if (!tbody || !tbody.rows.length) return;
            const table = tbody.closest('table') || tbody.parentElement;
            let csv = [];
            const thead = table.querySelector('thead');
            if (thead) {
                const headers = [];
                thead.querySelectorAll('th').forEach(th => headers.push('"' + (th.textContent.trim() || '') + '"'));
                csv.push(headers.join(','));
            }
            tbody.querySelectorAll('tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    let text = td.textContent.trim().replace(/"/g, '""');
                    if (td.querySelector('i,button,a')) text = td.innerText.trim().replace(/"/g, '""');
                    row.push('"' + text + '"');
                });
                csv.push(row.join(','));
            });
            const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = (nombre || 'reporte') + '_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        }
        
        function mostrarLoading(texto) { 
            const el = document.getElementById('loadingOverlay'); 
            if(el) {
                el.style.display = 'flex'; 
                if(texto && el.querySelector('p')) el.querySelector('p').textContent = texto;
            } 
        }
        
        function ocultarLoading() { 
            const el = document.getElementById('loadingOverlay'); 
            if(el) el.style.display = 'none'; 
        }
        
        function mostrarNotificacion(mensaje, tipo) { 
            const existing = document.querySelectorAll('.notification-message'); 
            existing.forEach(n => n.remove()); 
            const notif = document.createElement('div'); 
            notif.className = `notification-message ${tipo || 'success'}`; 
            const icon = tipo === 'error' ? 'fa-exclamation-circle' : (tipo === 'warning' ? 'fa-exclamation-triangle' : (tipo === 'info' ? 'fa-info-circle' : 'fa-check-circle')); 
            notif.innerHTML = `<i class="fas ${icon}"></i><span>${mensaje}</span>`; 
            document.body.appendChild(notif); 
            setTimeout(() => notif.remove(), 4000); 
        }
        
        function cerrarModales() { document.querySelectorAll('.modal').forEach(m => m.style.display = 'none'); }
        function cerrarModalDetalle() { document.getElementById('verDetalleModal').style.display = 'none'; }
        function cerrarDetallePedidoModal() {
            document.getElementById('detallePedidoModal').style.display = 'none';
            currentDetallePedido = null;
            window.currentProductosList = [];
        }

        function getEstadoBadge(estado) {
            const estados = {
                'pendiente': '<span class="badge badge-pending"><i class="fas fa-clock"></i> Pendiente</span>',
                'facturado': '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Facturado</span>',
                'completado': '<span class="badge badge-completed"><i class="fas fa-check-double"></i> Completado</span>',
                'cancelado': '<span class="badge badge-inactive"><i class="fas fa-times-circle"></i> Cancelado</span>',
                'pagada': '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Pagada</span>',
                'anulada': '<span class="badge badge-inactive"><i class="fas fa-ban"></i> Anulada</span>'
            };
            return estados[estado] || `<span class="badge">${estado}</span>`;
        }

        function getMetodoPagoBadge(metodo) {
            if (!metodo) return '<span class="metodo-pago-badge" style="background: #6c757d; color: white;"><i class="fas fa-question-circle"></i> No especificado</span>';
            let metodoStr = String(metodo);
            let metodoNormalizado = metodoStr.toLowerCase().trim();
            if (metodoNormalizado === 'efectivo' || metodoNormalizado === 'cash' || metodoNormalizado.includes('efectivo')) return '<span class="metodo-pago-badge metodo-efectivo"><i class="fas fa-money-bill-wave"></i> Efectivo</span>';
            if (metodoNormalizado === 'mixto' || metodoNormalizado === 'mixed' || metodoNormalizado.includes('mixto')) return '<span class="metodo-pago-badge metodo-mixto"><i class="fas fa-sync-alt"></i> Pago Mixto</span>';
            if (metodoNormalizado === 'transferencia' || metodoNormalizado === 'transferencia bancaria' || metodoNormalizado.includes('transferencia')) return '<span class="metodo-pago-badge metodo-transferencia"><i class="fas fa-university"></i> Transferencia</span>';
            if (metodoNormalizado === 'pago_movil' || metodoNormalizado === 'pago movil' || metodoNormalizado.includes('pago_movil') || metodoNormalizado.includes('pago movil')) return '<span class="metodo-pago-badge metodo-pago-movil"><i class="fas fa-mobile-alt"></i> Pago Móvil</span>';
            if (metodoNormalizado === 'tarjeta' || metodoNormalizado === 'credito' || metodoNormalizado === 'debito' || metodoNormalizado.includes('tarjeta')) return '<span class="metodo-pago-badge metodo-tarjeta"><i class="fas fa-credit-card"></i> Tarjeta</span>';
            return `<span class="metodo-pago-badge" style="background: #6c757d; color: white;"><i class="fas fa-question-circle"></i> ${escapeHtml(metodoStr)}</span>`;
        }

        // ====================================================================
        // FUNCIONES DE PERFIL
        // ====================================================================
        async function cargarDatosPerfil() {
    try {
        const response = await fetch('/proyecto/usuarios/obtener_usuario.php', { credentials: 'include' });
        if(response.ok) {
            const data = await response.json();
            let user = null;
            if (data.success && data.usuario) user = data.usuario;
            else if (data.success && data.user) user = data.user;
            else if (data.usuario) user = data.usuario;
            else if (data.user) user = data.user;
            else if (data.data) user = data.data;
            else if (data.id || data.nombre) user = data;
            
            if (user && (user.id || user.nombre)) {
                usuarioActual = user;
                document.getElementById('userName').textContent = usuarioActual.nombre || usuarioActual.name || 'Administrador';
                document.getElementById('userRole').textContent = usuarioActual.rol || usuarioActual.role || 'admin';
                const inicial = (usuarioActual.nombre || usuarioActual.name || 'A').charAt(0).toUpperCase();
                document.getElementById('avatarInitial').textContent = inicial;
                document.getElementById('profileName').textContent = usuarioActual.nombre || usuarioActual.name || 'Sin nombre';
                document.getElementById('profileRole').textContent = usuarioActual.rol || usuarioActual.role || 'Sin rol';
                document.getElementById('profileAvatarInitial').textContent = inicial;
                document.getElementById('displayNombre').textContent = usuarioActual.nombre || usuarioActual.name || '-';
                document.getElementById('displayEmail').textContent = usuarioActual.email || usuarioActual.correo || '-';
                
                // 🔧 MODIFICAR ESTA LÍNEA - Mostrar teléfono si existe, sino mostrar "No registrado"
                const telefonoValue = (usuarioActual.telefono || usuarioActual.phone || usuarioActual.telefono_contacto || '');
                document.getElementById('displayTelefono').textContent = telefonoValue || 'No registrado';
                
                document.getElementById('displayRol').textContent = usuarioActual.rol || usuarioActual.role || '-';
                
                // También actualizar el campo de edición si existe
                const editTelefonoInput = document.getElementById('editTelefono');
                if (editTelefonoInput && telefonoValue) {
                    editTelefonoInput.value = telefonoValue;
                }
                
                let fechaRegistro = usuarioActual.fecha_registro || usuarioActual.created_at;
                if (!fechaRegistro || fechaRegistro === 'null' || fechaRegistro === 'NULL') {
                    fechaRegistro = 'No disponible';
                } else {
                    try {
                        const fechaObj = new Date(fechaRegistro);
                        if (!isNaN(fechaObj.getTime())) {
                            fechaRegistro = fechaObj.toLocaleDateString('es-ES');
                        } else {
                            fechaRegistro = 'No disponible';
                        }
                    } catch(e) {
                        fechaRegistro = 'No disponible';
                    }
                }
                document.getElementById('displayFechaRegistro').textContent = fechaRegistro;
                await cargarFotoPerfil();
                await cargarEstado2FAenPerfil();
                return usuarioActual;
            }
        }
    } catch(e) { console.error('Error cargando perfil:', e); }
    return null;
}
        
        async function cargarEstado2FAenPerfil() {
            try {
                const response = await fetch('/proyecto/2fa/configurar.php?action=estado', { credentials: 'include' });
                const data = await response.json();
                const label = document.getElementById('perfil2faLabel');
                const desc = document.getElementById('perfil2faDesc');
                const badge = document.getElementById('perfil2faBadge');
                if (data.migracion_pendiente) {
                    label.textContent = 'Migración pendiente';
                    desc.textContent = 'Ejecute la migración para activar 2FA';
                    badge.className = 'badge badge-pending';
                    badge.innerHTML = '<i class="fas fa-clock"></i> Pendiente';
                } else if (data.enabled) {
                    label.textContent = '✅ 2FA Activado';
                    desc.textContent = 'Tu cuenta está protegida con autenticación en dos pasos.';
                    badge.className = 'badge badge-active';
                    badge.innerHTML = '<i class="fas fa-check-circle"></i> Activado';
                } else {
                    label.textContent = '2FA Desactivado';
                    desc.textContent = 'Activa la autenticación en dos pasos para mayor seguridad.';
                    badge.className = 'badge badge-pending';
                    badge.innerHTML = '<i class="fas fa-clock"></i> Desactivado';
                }
            } catch (e) {
                console.error('Error cargando estado 2FA en perfil:', e);
            }
        }
        
        async function cargarFotoPerfil() {
            try {
                const response = await fetch('/proyecto/usuarios/obtener_foto_perfil.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    const avatarImg = document.getElementById('avatarImage');
                    const profileImg = document.getElementById('profileAvatarImage');
                    const avatarInitial = document.getElementById('avatarInitial');
                    const profileInitial = document.getElementById('profileAvatarInitial');
                    if(data.success && data.photo_url) {
                        let fotoUrl = data.photo_url;
                        if(avatarImg) { avatarImg.src = fotoUrl; avatarImg.style.display = 'block'; avatarImg.style.width = '100%'; avatarImg.style.height = '100%'; avatarImg.style.objectFit = 'cover'; }
                        if(profileImg) { profileImg.src = fotoUrl; profileImg.style.display = 'block'; profileImg.style.width = '100%'; profileImg.style.height = '100%'; profileImg.style.objectFit = 'cover'; }
                        if(avatarInitial) avatarInitial.style.display = 'none';
                        if(profileInitial) profileInitial.style.display = 'none';
                    } else {
                        if(avatarImg) avatarImg.style.display = 'none';
                        if(profileImg) profileImg.style.display = 'none';
                        if(avatarInitial) avatarInitial.style.display = 'flex';
                        if(profileInitial) profileInitial.style.display = 'flex';
                    }
                }
            } catch(e) { console.error('Error cargando foto:', e); }
        }

        async function subirFotoPerfil(file) {
            const formData = new FormData();
            formData.append('foto', file);
            formData.append('_csrf_token', csrfToken);
            
            mostrarLoading('Subiendo foto...');
            try {
                const response = await fetch('/proyecto/usuarios/subir_foto_perfil.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        mostrarNotificacion('Foto de perfil actualizada correctamente', 'success');
                        await cargarFotoPerfil();
                        await cargarDatosPerfil();
                        cerrarModales();
                    } else {
                        mostrarNotificacion(data.message || 'Error al subir foto', 'error');
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    mostrarNotificacion('Error del servidor', 'error');
                }
            } catch (error) {
                console.error('Error en subirFotoPerfil:', error);
                mostrarNotificacion('Error al conectar con el servidor', 'error');
            } finally {
                ocultarLoading();
            }
        }
        
        function habilitarEdicionPerfil() {
            document.getElementById('profileInfoDisplay').style.display = 'none';
            document.getElementById('profileInfoEdit').style.display = 'block';
            document.getElementById('btnEditarPerfil').style.display = 'none';
            document.getElementById('editNombre').value = usuarioActual?.nombre || usuarioActual?.name || '';
            document.getElementById('editEmail').value = usuarioActual?.email || '';
            document.getElementById('editTelefono').value = usuarioActual?.telefono || '';
        }
        
        function cancelarEdicionPerfil() {
            document.getElementById('profileInfoDisplay').style.display = 'block';
            document.getElementById('profileInfoEdit').style.display = 'none';
            document.getElementById('btnEditarPerfil').style.display = 'inline-flex';
        }
        
      async function guardarPerfil() {
    const data = { 
        nombre: document.getElementById('editNombre').value, 
        email: document.getElementById('editEmail').value, 
        telefono: document.getElementById('editTelefono').value || ''  // Asegurar que se envía
    };
    mostrarLoading('Guardando cambios...');
    try {
        const response = await fetch('/proyecto/usuarios/actualizar_perfil.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(data), 
            credentials: 'include' 
        });
        const result = await response.json();
        if(result.success) { 
            mostrarNotificacion('Perfil actualizado correctamente', 'success'); 
            await cargarDatosPerfil(); 
            cancelarEdicionPerfil(); 
        } else { 
            mostrarNotificacion(result.message || 'Error al actualizar', 'error'); 
        }
    } catch(e) { 
        mostrarNotificacion('Error al guardar cambios', 'error'); 
    }
    finally { 
        ocultarLoading(); 
    }
}
        
        async function cambiarContrasena(event) {
            event.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value.trim();
            const newPassword = document.getElementById('newPassword').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();
            
            if (!currentPassword) { mostrarNotificacion('Debes ingresar tu contraseña actual', 'warning'); return; }
            if (!newPassword) { mostrarNotificacion('Debes ingresar una nueva contraseña', 'warning'); return; }
            if (!confirmPassword) { mostrarNotificacion('Debes confirmar tu nueva contraseña', 'warning'); return; }
            if (newPassword !== confirmPassword) { mostrarNotificacion('Las contraseñas nuevas no coinciden', 'warning'); return; }
            if (newPassword.length < 6) { mostrarNotificacion('La nueva contraseña debe tener al menos 6 caracteres', 'warning'); return; }
            
            mostrarLoading('Cambiando contraseña...');
            
            try {
                const response = await fetch('/proyecto/usuarios/cambiar_contraseña.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ current_password: currentPassword, new_password: newPassword, confirm_new_password: confirmPassword }), 
                    credentials: 'include' 
                });
                
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) { 
                        mostrarNotificacion('Contraseña cambiada correctamente', 'success'); 
                        document.getElementById('cambiarPasswordForm').reset(); 
                    } else { 
                        mostrarNotificacion(data.message || 'Error al cambiar contraseña', 'error'); 
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    mostrarNotificacion('Error del servidor', 'error');
                }
            } catch(e) { 
                console.error('Error:', e); 
                mostrarNotificacion('Error de conexión con el servidor', 'error'); 
            } finally { 
                ocultarLoading(); 
            }
        }

        // ====================================================================
        // FUNCIONES DE CARGA DE DATOS
        // ====================================================================
        async function cargarUsuarios() {
            try {
                const response = await fetch('/proyecto/admin/obtener_todos_los_usuarios.php', { credentials: 'include' });
                if(response.ok) { usersData = await response.json(); if(!Array.isArray(usersData)) usersData = usersData.usuarios || usersData.data || []; }
                else { throw new Error('Error en la respuesta del servidor'); }
            } catch(e) { console.error('Error cargando usuarios:', e); usersData = []; }
            renderUsuarios();
        }

        function renderUsuarios() {
            const tbody = document.getElementById('usersList');
            if(!tbody) return;
            if(!usersData || usersData.length === 0) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center">No hay usuarios registrados</tbody>'; return; }
            let html = '';
            usersData.forEach(u => {
                html += `<tr>
                    <td>${u.id}</td>
                    <td><strong>${escapeHtml(u.nombre)}</strong></td>
                    <td>${escapeHtml(u.email || u.correo)}</td>
                    <td>${escapeHtml(u.telefono || 'N/A')}</td>
                    <td><span class="badge badge-active">${u.rol}</span></td>
                    <td><span class="badge ${u.estado === 'activo' ? 'badge-active' : 'badge-inactive'}">${u.estado || 'activo'}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-view" onclick="verUsuario(${u.id})"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editarUsuario(${u.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="eliminarUsuario(${u.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        async function cargarDashboard() {
            try {
                const response = await fetch('/proyecto/admin/obtener_dashboard.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    document.getElementById('totalUsers').innerHTML = data.total_usuarios || 0;
                    document.getElementById('totalProducts').innerHTML = data.total_productos || 0;
                    document.getElementById('totalPedidos').innerHTML = data.total_pedidos || 0;
                    document.getElementById('pedidosPendientes').innerHTML = data.pedidos_pendientes || 0;
                    document.getElementById('facturasHoy').innerHTML = data.facturas_hoy || 0;
                    document.getElementById('totalCotizaciones').innerHTML = data.total_cotizaciones || 0;
                    document.getElementById('cotizacionesPendientes').innerHTML = data.cotizaciones_pendientes || 0;
                    document.getElementById('totalVentas').innerHTML = formatMoney(data.total_ventas);
                    document.getElementById('totalClientes').innerHTML = data.total_clientes || 0;
                    document.getElementById('stockBajo').innerHTML = data.productos_stock_bajo || 0;
                    document.getElementById('cajaHoy').innerHTML = formatMoney(data.caja_hoy);
                }
            } catch(e) { console.error('Error cargando dashboard:', e); }
        }

        // ====================================================================
        // NOTIFICACIONES DE PAGOS EN TIEMPO REAL
        // ====================================================================
        let ultimoPagoMovilId = 0;

        async function verificarPagosMoviles() {
            try {
                const response = await fetch('/proyecto/admin/verificar_pagos_pendientes.php?ultimo_id=' + ultimoPagoMovilId, { credentials: 'include' });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.nuevos && data.nuevos.length > 0) {
                        data.nuevos.forEach(p => {
                            mostrarNotificacion(p.mensaje || `Nuevo pago de ${p.cliente}`, 'info');
                        });
                    }
                    if (data.max_id > ultimoPagoMovilId) ultimoPagoMovilId = data.max_id;
                }
            } catch(e) { /* Silencio */ }
        }

        async function cargarProveedores() {
            try {
                const response = await fetch('/proyecto/proveedores/obtener_proveedores.php', { credentials: 'include' });
                if(response.ok) { proveedoresData = await response.json(); if(!Array.isArray(proveedoresData)) proveedoresData = proveedoresData.proveedores || proveedoresData.data || []; }
                else { throw new Error('Error en la respuesta del servidor'); }
            } catch(e) { console.error('Error cargando proveedores:', e); proveedoresData = []; }
            renderProveedores();
        }

        function renderProveedores() {
            const tbody = document.getElementById('proveedoresList');
            if(!tbody) return;
            if(!proveedoresData || proveedoresData.length === 0) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center">No hay proveedores registrados</tbody>'; return; }
            let html = '';
            proveedoresData.forEach(p => {
                const id = p.id || 'N/A';
                const codigo = p.codigo || 'N/A';
                const nombre = p.nombre_comercial || p.nombre || p.razon_social || 'N/A';
                const ruc = p.ruc || p.rif || 'N/A';
                const telefono = p.telefono_principal || p.telefono || 'N/A';
                const email = p.email_principal || p.email || 'N/A';
                const contacto = p.contacto_nombre || p.contacto || 'N/A';
                const estado = p.estado || 'activo';
                html += `<tr>
                    <td>${id}</td>
                    <td>${escapeHtml(codigo)}</td>
                    <td><strong>${escapeHtml(nombre)}</strong></td>
                    <td>${escapeHtml(ruc)}</td>
                    <td>${escapeHtml(telefono)}</td>
                    <td>${escapeHtml(email)}</td>
                    <td>${escapeHtml(contacto)}</td>
                    <td><span class="badge ${estado === 'activo' ? 'badge-active' : 'badge-inactive'}">${estado}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-edit" onclick="editarProveedor(${id})"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="eliminarProveedor(${id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

// ====================================================================
// FUNCIONES DE COMPRAS (MANTENIDAS SIN CAMBIOS FUNCIONALES)
// ====================================================================
async function cargarCompras() {
            console.log('🔄 Cargando compras...');
    mostrarLoading('Cargando compras...');
    try {
        const response = await fetch('/proyecto/compras/obtener_compras.php', { 
            credentials: 'include',
            headers: { 'Cache-Control': 'no-cache' }
        });
        
        if(!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        comprasData = [];
        
        if (data && data.success === true && Array.isArray(data.compras)) {
            comprasData = data.compras;
        }
        else if (data && data.success === true && Array.isArray(data.data)) {
            comprasData = data.data;
        } 
        else if (Array.isArray(data)) {
            comprasData = data;
        }
        else if (data && data.compras && Array.isArray(data.compras)) {
            comprasData = data.compras;
        }
        else if (data && data.data && Array.isArray(data.data)) {
            comprasData = data.data;
        }
        else {
            comprasData = [];
        }
        
        if (data && data.error) {
            mostrarNotificacion(data.error, 'error');
        }
        
    } catch(e) { 
        console.error('Error cargando compras:', e); 
        comprasData = []; 
        mostrarNotificacion('Error al cargar compras: ' + e.message, 'error');
    }
    
    renderCompras();
    ocultarLoading();
}

function renderCompras() {
    console.log('🎨 Renderizando compras, datos:', comprasData);
    
    const tbody = document.getElementById('comprasList');
    if(!tbody) {
        console.error('❌ No se encuentra el elemento comprasList');
        return;
    }
    
    if(!comprasData || comprasData.length === 0) { 
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 40px;"><i class="fas fa-shopping-cart" style="font-size: 2rem; color: #ccc; margin-bottom: 10px; display: block;"></i>No hay órdenes de compra disponibles</tbody>';
        return; 
    }
    
    let html = '';
    for(let i = 0; i < comprasData.length; i++) {
        const c = comprasData[i];
        if (!c) continue;
        
        let fechaFormateada = 'N/A';
        const fechaRaw = c.fecha_orden || c.fecha || c.created_at || c.fecha_creacion;
        if (fechaRaw && fechaRaw !== 'null' && fechaRaw !== 'NULL' && fechaRaw !== '0000-00-00') {
            try {
                const fechaObj = new Date(fechaRaw);
                if (!isNaN(fechaObj.getTime())) {
                    fechaFormateada = fechaObj.toLocaleDateString('es-ES');
                }
            } catch(e) {
                console.warn('Error al formatear fecha:', fechaRaw);
                fechaFormateada = 'N/A';
            }
        }
        
        const proveedorNombre = c.proveedor_nombre || c.proveedor || c.nombre_proveedor || 'N/A';
        
        let estado = c.estado || 'pendiente';
        let estadoLower = estado.toLowerCase();
        let estadoBadge = '';
        
        if (estadoLower === 'recibida_total' || estadoLower === 'completado' || estadoLower === 'completada') {
            estadoBadge = '<span class="badge badge-completed"><i class="fas fa-check-double"></i> Completada</span>';
        } else if (estadoLower === 'recibida_parcial' || estadoLower === 'parcial') {
            estadoBadge = '<span class="badge badge-pending"><i class="fas fa-clock"></i> Recibida Parcial</span>';
        } else if (estadoLower === 'pendiente') {
            estadoBadge = '<span class="badge badge-pending"><i class="fas fa-clock"></i> Pendiente</span>';
        } else if (estadoLower === 'cancelada' || estadoLower === 'cancelado') {
            estadoBadge = '<span class="badge badge-inactive"><i class="fas fa-times-circle"></i> Cancelada</span>';
        } else {
            estadoBadge = `<span class="badge">${escapeHtml(estado)}</span>`;
        }
        
        const total = parseFloat(c.total || c.total_compra || c.monto_total || 0);
        const subtotal = parseFloat(c.subtotal || 0);
        
        html += `<tr>
            <td style="padding: 12px 15px;">${c.id || 'N/A'}</td>
            <td style="padding: 12px 15px;"><strong>${escapeHtml(c.numero_orden || c.order_number || c.nro_orden || 'N/A')}</strong></td>
            <td style="padding: 12px 15px;">${escapeHtml(proveedorNombre)}</td>
            <td style="padding: 12px 15px;">${fechaFormateada}</td>
            <td style="padding: 12px 15px;">${formatMoney(subtotal)}</td>
            <td style="padding: 12px 15px;"><strong>${formatMoney(total)}</strong></td>
            <td style="padding: 12px 15px;">${estadoBadge}</td>
            <td class="action-buttons" style="padding: 12px 15px;">
                <button class="btn-action btn-view" onclick="verCompra(${c.id})" title="Ver detalles"><i class="fas fa-eye"></i></button>
                <button class="btn-action btn-pdf" onclick="exportarCompraPDF(${c.id})" title="Exportar PDF"><i class="fas fa-file-pdf"></i></button>
            </td>
        </tr>`;
    }
    
    tbody.innerHTML = html;
}

        async function cargarPedidos() {
            mostrarLoading('Cargando pedidos...');
            try {
                const response = await fetch('/proyecto/proceso_compra/obtener_todos_los_pedidos.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success) pedidosData = data.pedidos || [];
                    else if(Array.isArray(data)) pedidosData = data;
                    else pedidosData = [];
                } else { throw new Error('Error en la respuesta del servidor'); }
            } catch(e) { console.error('Error cargando pedidos:', e); pedidosData = []; }
            renderPedidos();
            ocultarLoading();
        }

        function renderPedidos() {
            const tbody = document.getElementById('pedidosList');
            if(!tbody) return;
            if(!pedidosData || pedidosData.length === 0) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center">No hay pedidos registrados</tbody>'; return; }
            let html = '';
            for(let i = 0; i < pedidosData.length; i++) {
                const p = pedidosData[i];
                let fechaFormateada = 'N/A';
                let fechaStr = p.fecha || p.created_at || p.fecha_creacion;
                if (fechaStr && fechaStr !== 'null' && fechaStr !== 'NULL' && fechaStr !== '0000-00-00') {
                    try { 
                        const fecha = new Date(fechaStr); 
                        if (!isNaN(fecha.getTime())) fechaFormateada = fecha.toLocaleDateString('es-ES'); 
                    } catch(e) { fechaFormateada = 'N/A'; }
                }
                html += `<tr>
                    <td><input type="checkbox" class="pedidoCheckbox" value="${p.id}"></td>
                    <td>${p.id}</td>
                    <td><strong>${escapeHtml(p.numero_pedido || 'N/A')}</strong></td>
                    <td>${escapeHtml(p.cliente_nombre || 'Cliente ID: ' + (p.usuario_id || 'N/A'))}</td>
                    <td>${fechaFormateada}</td>
                    <td>${formatMoney(p.total)}</td>
                    <td>${getMetodoPagoBadge(p.metodo_pago)}</td>
                    <td>${getEstadoBadge(p.estado || 'pendiente')}</td>
                    <td class="action-buttons">
                        <button class="btn-action btn-view" onclick="verDetallePedido(${p.id})"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editarPedido(${p.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-pdf" onclick="exportarPedidoPDF(${p.id})"><i class="fas fa-file-pdf"></i></button>
                    </td>
                </tr>`;
            }
            tbody.innerHTML = html;
            const selectAll = document.getElementById('selectAllPedidos');
            if(selectAll) selectAll.onclick = function() { document.querySelectorAll('.pedidoCheckbox').forEach(cb => cb.checked = this.checked); };
        }

        // ====================================================================
        // COTIZACIONES CRM - FUNCIONES
        // ====================================================================
        let cotizacionesItems = [];

        async function cargarCotizaciones() {
            try {
                const busqueda = document.getElementById('buscarCotizacion')?.value || '';
                const estado = document.getElementById('filtroEstadoCotizacion')?.value || '';
                const fd = document.getElementById('cotizacionFechaDesde')?.value || '';
                const fh = document.getElementById('cotizacionFechaHasta')?.value || '';
                const params = new URLSearchParams({ busqueda, estado, fecha_desde: fd, fecha_hasta: fh });
                const res = await fetch('/proyecto/cotizaciones/obtener_cotizaciones.php?' + params.toString(), { credentials: 'include' });
                const data = await res.json();
                const tbody = document.getElementById('cotizacionesBody');
                if (!tbody) return;
                if (!data.success || !data.data || !data.data.length) {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center">No hay cotizaciones registradas</td></tr>';
                    return;
                }
                tbody.innerHTML = data.data.map(c => {
                    const estados = { pendiente: 'Pendiente', aprobada: 'Aprobada', rechazada: 'Rechazada', vencida: 'Vencida', convertida: 'Convertida' };
                    const colores = { pendiente: '#ffa502', aprobada: '#2ed573', rechazada: '#ff4757', vencida: '#95a5a6', convertida: '#3498db' };
                    return `<tr>
                        <td><strong>${escapeHtml(c.numero_cotizacion)}</strong></td>
                        <td>${escapeHtml(c.cliente_nombre)}</td>
                        <td>${escapeHtml(c.cliente_email || '')}</td>
                        <td>${escapeHtml(c.cliente_telefono || '')}</td>
                        <td>${formatMoney(c.total)}</td>
                        <td><span style="background:${colores[c.estado]||'#95a5a6'};color:white;padding:2px 8px;border-radius:4px;font-size:0.75rem">${estados[c.estado]||c.estado}</span></td>
                        <td>${escapeHtml(c.usuario_nombre || '')}</td>
                        <td>${c.fecha_creacion ? new Date(c.fecha_creacion).toLocaleDateString('es-ES') : ''}</td>
                        <td>${c.fecha_vencimiento ? new Date(c.fecha_vencimiento).toLocaleDateString('es-ES') : 'N/A'}</td>
                        <td>
                            <button class="btn-pdf" onclick="verCotizacion(${c.id})" title="Ver"><i class="fas fa-eye"></i></button>
                            <button class="btn-pdf" onclick="editarCotizacion(${c.id})" title="Editar" style="background:var(--info)"><i class="fas fa-edit"></i></button>
                            <button class="btn-pdf" onclick="seguimientoCotizacion(${c.id})" title="Seguimiento" style="background:var(--purple)"><i class="fas fa-history"></i></button>
                            <button class="btn-pdf" onclick="window.open('/proyecto/cotizaciones/generar_pdf_cotizacion.php?id=${c.id}')" title="PDF" style="background:var(--danger)"><i class="fas fa-file-pdf"></i></button>
                            <button class="btn-pdf" onclick="eliminarCotizacion(${c.id})" title="Eliminar" style="background:#e74c3c"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                }).join('');
            } catch(e) { console.error('Error cargando cotizaciones:', e); }
        }

        async function abrirModalCotizacion(id) {
            document.getElementById('cotizacionModalTitle').textContent = id ? 'Editar Cotización' : 'Nueva Cotización';
            document.getElementById('editCotizacionId').value = id || '';

            // Cargar productos en el select
            try {
                const res = await fetch('/proyecto/admin/obtener_inventario.php', { credentials: 'include' });
                const data = await res.json();
                const sel = document.getElementById('cotProductoSelect');
                if (Array.isArray(data)) {
                    sel.innerHTML = '<option value="">Seleccionar producto...</option>' + data.map(p => `<option value="${p.id}" data-nombre="${escapeHtml(p.name)}" data-precio="${p.price || 0}">${escapeHtml(p.name)} - Bs. ${parseFloat(p.price || 0).toFixed(2)}</option>`).join('');
                } else if (data.data && Array.isArray(data.data)) {
                    sel.innerHTML = '<option value="">Seleccionar producto...</option>' + data.data.map(p => `<option value="${p.id}" data-nombre="${escapeHtml(p.name)}" data-precio="${p.price || 0}">${escapeHtml(p.name)} - Bs. ${parseFloat(p.price || 0).toFixed(2)}</option>`).join('');
                }
            } catch(e) { console.error('Error cargando productos:', e); }

            if (id) {
                try {
                    const res = await fetch('/proyecto/cotizaciones/obtener_cotizacion.php?id=' + id, { credentials: 'include' });
                    const data = await res.json();
                    if (data.success && data.data) {
                        const c = data.data;
                        document.getElementById('cotClienteNombre').value = c.cliente_nombre || '';
                        document.getElementById('cotClienteEmail').value = c.cliente_email || '';
                        document.getElementById('cotClienteTelefono').value = c.cliente_telefono || '';
                        document.getElementById('cotClienteDireccion').value = c.cliente_direccion || '';
                        document.getElementById('cotFechaVencimiento').value = c.fecha_vencimiento || '';
                        document.getElementById('cotNotas').value = c.notas || '';
                        cotizacionesItems = (c.detalles || []).map(d => ({
                            producto_id: d.producto_id,
                            producto_nombre: d.producto_nombre,
                            cantidad: parseInt(d.cantidad) || 1,
                            precio_unitario: parseFloat(d.precio_unitario) || 0
                        }));
                        renderCotizacionItems();
                    }
                } catch(e) { console.error('Error cargando cotización:', e); }
            } else {
                document.getElementById('cotClienteNombre').value = '';
                document.getElementById('cotClienteEmail').value = '';
                document.getElementById('cotClienteTelefono').value = '';
                document.getElementById('cotClienteDireccion').value = '';
                document.getElementById('cotFechaVencimiento').value = '';
                document.getElementById('cotNotas').value = '';
                cotizacionesItems = [];
                renderCotizacionItems();
            }
            document.getElementById('cotizacionModal').style.display = 'flex';
        }

        function cerrarModalCotizacion() {
            document.getElementById('cotizacionModal').style.display = 'none';
        }

        function agregarItemCotizacion() {
            const sel = document.getElementById('cotProductoSelect');
            const nombreInput = document.getElementById('cotProductoNombre');
            const cantidad = parseInt(document.getElementById('cotProductoCantidad').value) || 1;
            const precio = parseFloat(document.getElementById('cotProductoPrecio').value) || 0;
            let nombre = '';
            let producto_id = null;

            if (sel.value) {
                const opt = sel.options[sel.selectedIndex];
                nombre = opt.dataset.nombre || opt.textContent.split(' - ')[0];
                producto_id = parseInt(sel.value);
                if (!precio) document.getElementById('cotProductoPrecio').value = opt.dataset.precio;
            } else if (nombreInput.value.trim()) {
                nombre = nombreInput.value.trim();
            } else {
                mostrarNotificacion('Selecciona un producto o escribe el nombre', 'error');
                return;
            }

            const precioFinal = parseFloat(document.getElementById('cotProductoPrecio').value) || precio;
            if (precioFinal <= 0) {
                mostrarNotificacion('Ingresa un precio válido', 'error');
                return;
            }

            cotizacionesItems.push({ producto_id, producto_nombre: nombre, cantidad, precio_unitario: precioFinal });
            renderCotizacionItems();
            nombreInput.value = '';
            document.getElementById('cotProductoCantidad').value = '1';
            document.getElementById('cotProductoPrecio').value = '';
            sel.value = '';
        }

        function renderCotizacionItems() {
            const tbody = document.getElementById('cotizacionItemsBody');
            if (!tbody) return;
            let subtotal = 0;
            tbody.innerHTML = cotizacionesItems.map((item, i) => {
                const st = item.cantidad * item.precio_unitario;
                subtotal += st;
                return `<tr>
                    <td>${escapeHtml(item.producto_nombre)}</td>
                    <td><input type="number" value="${item.cantidad}" min="1" style="width:60px;padding:4px;background:var(--card-bg);color:var(--text-color);border:1px solid var(--border-color);border-radius:4px" onchange="actualizarItemCot(${i}, 'cantidad', parseInt(this.value)||1)"></td>
                    <td><input type="number" value="${item.precio_unitario}" step="0.01" min="0" style="width:100px;padding:4px;background:var(--card-bg);color:var(--text-color);border:1px solid var(--border-color);border-radius:4px" onchange="actualizarItemCot(${i}, 'precio_unitario', parseFloat(this.value)||0)"></td>
                    <td>Bs. ${st.toFixed(2)}</td>
                    <td><button class="btn-pdf" onclick="cotizacionesItems.splice(${i},1);renderCotizacionItems()" style="background:#e74c3c"><i class="fas fa-times"></i></button></td>
                </tr>`;
            }).join('');
            if (!cotizacionesItems.length) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999">Agrega productos a la cotización</td></tr>';

            const ivaPorc = 16;
            const totalSt = cotizacionesItems.reduce((s, it) => s + (it.cantidad * it.precio_unitario), 0);
            const iva = totalSt * (ivaPorc / 100);
            const total = totalSt + iva;
            document.getElementById('cotSubtotal').textContent = formatMoney(totalSt);
            document.getElementById('cotIva').textContent = formatMoney(iva);
            document.getElementById('cotTotal').textContent = formatMoney(total);
        }

        function actualizarItemCot(idx, campo, valor) {
            if (cotizacionesItems[idx]) {
                cotizacionesItems[idx][campo] = valor;
                renderCotizacionItems();
            }
        }

        async function guardarCotizacion() {
            const id = document.getElementById('editCotizacionId').value;
            const data = {
                id: id || null,
                cliente_nombre: document.getElementById('cotClienteNombre').value.trim(),
                cliente_email: document.getElementById('cotClienteEmail').value.trim(),
                cliente_telefono: document.getElementById('cotClienteTelefono').value.trim(),
                cliente_direccion: document.getElementById('cotClienteDireccion').value.trim(),
                fecha_vencimiento: document.getElementById('cotFechaVencimiento').value || null,
                notas: document.getElementById('cotNotas').value.trim(),
                items: cotizacionesItems
            };

            if (!data.cliente_nombre) {
                mostrarNotificacion('El nombre del cliente es requerido', 'error');
                return;
            }
            if (!data.items.length) {
                mostrarNotificacion('Agrega al menos un producto', 'error');
                return;
            }

            try {
                const url = id ? '/proyecto/cotizaciones/editar_cotizacion.php' : '/proyecto/cotizaciones/crear_cotizacion.php';
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                    credentials: 'include'
                });
                const result = await res.json();
                if (result.success) {
                    mostrarNotificacion(result.message, 'success');
                    cerrarModalCotizacion();
                    cargarCotizaciones();
                } else {
                    mostrarNotificacion(result.message || 'Error al guardar', 'error');
                }
            } catch(e) {
                mostrarNotificacion('Error de conexión', 'error');
            }
        }

        function eliminarCotizacion(id) {
            if (!confirm('¿Eliminar esta cotización?')) return;
            fetch('/proyecto/cotizaciones/eliminar_cotizacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
                credentials: 'include'
            }).then(r => r.json()).then(d => {
                mostrarNotificacion(d.message, d.success ? 'success' : 'error');
                if (d.success) cargarCotizaciones();
            });
        }

        async function verCotizacion(id) {
            try {
                const res = await fetch('/proyecto/cotizaciones/obtener_cotizacion.php?id=' + id, { credentials: 'include' });
                const data = await res.json();
                if (!data.success || !data.data) return;
                const c = data.data;
                const estados = { pendiente: 'Pendiente', aprobada: 'Aprobada', rechazada: 'Rechazada', vencida: 'Vencida', convertida: 'Convertida' };
                const colores = { pendiente: '#ffa502', aprobada: '#2ed573', rechazada: '#ff4757', vencida: '#95a5a6', convertida: '#3498db' };
                let detallesHtml = c.detalles.map((d, i) => `<tr><td>${i+1}</td><td>${escapeHtml(d.producto_nombre)}</td><td>${d.cantidad}</td><td>${formatMoney(d.precio_unitario)}</td><td>${formatMoney(d.subtotal)}</td></tr>`).join('');

                const html = `
                    <div style="padding:20px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                            <h2 style="margin:0">${escapeHtml(c.numero_cotizacion)}</h2>
                            <span style="background:${colores[c.estado]||'#95a5a6'};color:white;padding:4px 12px;border-radius:4px">${estados[c.estado]||c.estado}</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;padding:15px;background:var(--card-bg);border-radius:8px">
                            <div><strong>Cliente:</strong> ${escapeHtml(c.cliente_nombre)}</div>
                            <div><strong>Email:</strong> ${escapeHtml(c.cliente_email||'')}</div>
                            <div><strong>Teléfono:</strong> ${escapeHtml(c.cliente_telefono||'')}</div>
                            <div><strong>Vendedor:</strong> ${escapeHtml(c.usuario_nombre||'')}</div>
                            <div><strong>Fecha:</strong> ${c.fecha_creacion ? new Date(c.fecha_creacion).toLocaleDateString('es-ES') : ''}</div>
                            <div><strong>Vencimiento:</strong> ${c.fecha_vencimiento ? new Date(c.fecha_vencimiento).toLocaleDateString('es-ES') : 'N/A'}</div>
                        </div>
                        <table class="data-table" style="margin-bottom:15px">
                            <thead><tr><th>#</th><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr></thead>
                            <tbody>${detallesHtml}</tbody>
                        </table>
                        <div style="text-align:right">
                            <div>Subtotal: ${formatMoney(c.subtotal)}</div>
                            <div>IVA: ${formatMoney(c.iva)}</div>
                            <div style="font-size:1.2rem;font-weight:bold">Total: ${formatMoney(c.total)}</div>
                        </div>
                        ${c.notas ? `<div style="margin-top:15px;padding:10px;background:var(--card-bg);border-radius:6px"><strong>Notas:</strong><br>${escapeHtml(c.notas)}</div>` : ''}
                        ${c.seguimiento ? `<div style="margin-top:10px;padding:10px;background:var(--card-bg);border-radius:6px"><strong>Seguimiento:</strong><pre style="margin:5px 0 0;font-size:0.8rem;white-space:pre-wrap">${escapeHtml(c.seguimiento)}</pre></div>` : ''}
                        <div style="margin-top:20px;text-align:center">
                            <button class="btn-primary" onclick="window.open('/proyecto/cotizaciones/generar_pdf_cotizacion.php?id=${c.id}')" style="background:var(--danger)"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
                        </div>
                    </div>`;

                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.style.cssText = 'display:flex;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center';
                modal.innerHTML = `<div class="modal-content" style="max-width:700px;max-height:90vh;overflow-y:auto"><div class="modal-header"><h3>Detalle de Cotización</h3><span class="modal-close" onclick="this.closest('.modal').remove()">&times;</span></div><div class="modal-body">${html}</div></div>`;
                document.body.appendChild(modal);
            } catch(e) { console.error(e); }
        }

        async function editarCotizacion(id) {
            await abrirModalCotizacion(id);
        }

        async function seguimientoCotizacion(id) {
            document.getElementById('segCotizacionId').value = id;
            document.getElementById('segNuevoEstado').value = 'pendiente';
            document.getElementById('segNota').value = '';
            try {
                const res = await fetch('/proyecto/cotizaciones/obtener_cotizacion.php?id=' + id, { credentials: 'include' });
                const data = await res.json();
                if (data.success && data.data) {
                    const c = data.data;
                    document.getElementById('segNuevoEstado').value = c.estado || 'pendiente';
                    document.getElementById('segHistorial').innerHTML = c.seguimiento
                        ? '<pre style="margin:0;font-size:0.8rem;white-space:pre-wrap">' + escapeHtml(c.seguimiento) + '</pre>'
                        : '<div style="color:#999;text-align:center">Sin seguimiento registrado</div>';
                }
            } catch(e) { console.error(e); }
            document.getElementById('seguimientoModal').style.display = 'flex';
        }

        async function guardarSeguimiento() {
            const id = document.getElementById('segCotizacionId').value;
            const estado = document.getElementById('segNuevoEstado').value;
            const nota = document.getElementById('segNota').value.trim();
            if (!id) return;
            try {
                const res = await fetch('/proyecto/cotizaciones/cambiar_estado.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: parseInt(id), estado, seguimiento: nota }),
                    credentials: 'include'
                });
                const data = await res.json();
                mostrarNotificacion(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    document.getElementById('seguimientoModal').style.display = 'none';
                    cargarCotizaciones();
                }
            } catch(e) { mostrarNotificacion('Error de conexión', 'error'); }
        }

        // ====================================================================
        // CRM INTERACCIONES - FUNCIONES
        // ====================================================================
        let crmInteraccionesData = [];

        async function cargarInteraccionesCRM() {
            try {
                const busqueda = document.getElementById('buscarClienteCRM')?.value || '';
                const urlSimple = '/proyecto/reportes/ventas_por_cliente.php?simple=1' + (busqueda ? '&buscar=' + encodeURIComponent(busqueda) : '');
                const resClientes = await fetch(urlSimple, { credentials: 'include' });
                if (!resClientes.ok) { document.getElementById('crmInteraccionesBody').innerHTML = '<tr><td colspan="7" style="text-align:center">Error al cargar clientes</td></tr>'; return; }
                const dataClientes = await resClientes.json();
                const clientes = Array.isArray(dataClientes) ? dataClientes : (dataClientes.clientes || []);

                if (clientes.length === 0) {
                    document.getElementById('crmInteraccionesBody').innerHTML = '<tr><td colspan="7" style="text-align:center">No se encontraron clientes</td></tr>';
                    return;
                }

                let todas = [];
                const lote = clientes.slice(0, 20);
                for (const cli of lote) {
                    try {
                        const res = await fetch('/proyecto/clientes/obtener_interacciones.php?cliente_id=' + cli.id, { credentials: 'include' });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.success && data.interacciones) {
                                data.interacciones.forEach(int => {
                                    if (!int.cliente_nombre) int.cliente_nombre = cli.nombre || cli.cliente || 'Cliente #' + cli.id;
                                    todas.push(int);
                                });
                            }
                        }
                    } catch(e) { /* ignorar error individual */ }
                }

                todas.sort((a, b) => new Date(b.fecha_interaccion) - new Date(a.fecha_interaccion));
                const maxMostrar = 50;
                const mostrar = todas.slice(0, maxMostrar);
                crmInteraccionesData = mostrar;

                const tbody = document.getElementById('crmInteraccionesBody');
                if (!tbody) return;
                if (mostrar.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center">No hay interacciones registradas</td></tr>';
                    return;
                }
                const iconos = { llamada: 'fa-phone', correo: 'fa-envelope', reunion: 'fa-handshake', nota: 'fa-sticky-note', seguimiento: 'fa-redo', recordatorio: 'fa-bell' };
                const colores = { llamada: '#3498db', correo: '#2ed573', reunion: '#9b59b6', nota: '#f39c12', seguimiento: '#1abc9c', recordatorio: '#e74c3c' };
                tbody.innerHTML = mostrar.map(int => `<tr>
                    <td style="white-space:nowrap">${new Date(int.fecha_interaccion).toLocaleString('es-ES')}</td>
                    <td><strong>${escapeHtml(int.cliente_nombre || 'Cliente')}</strong></td>
                    <td><span style="background:${colores[int.tipo]||'#95a5a6'};color:white;padding:2px 8px;border-radius:4px;font-size:0.75rem"><i class="fas ${iconos[int.tipo]||'fa-comment'}"></i> ${int.tipo}</span></td>
                    <td>${escapeHtml(int.titulo || '')}</td>
                    <td>${escapeHtml((int.descripcion || '').substring(0, 80))}${(int.descripcion || '').length > 80 ? '...' : ''}</td>
                    <td>${escapeHtml(int.usuario_nombre || 'Sistema')}</td>
                    <td><button class="btn-pdf" onclick="eliminarInteraccionCRM(${int.id})" title="Eliminar" style="background:#e74c3c"><i class="fas fa-trash"></i></button></td>
                </tr>`).join('');
            } catch(e) { console.error('Error cargando CRM:', e); }
        }

        async function abrirModalCRM(clienteId) {
            document.getElementById('crmClienteId').value = clienteId || '';
            document.getElementById('crmTitulo').value = '';
            document.getElementById('crmDescripcion').value = '';
            document.getElementById('crmFecha').value = new Date().toISOString().slice(0, 16);

            try {
                const res = await fetch('/proyecto/reportes/ventas_por_cliente.php?simple=1', { credentials: 'include' });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                const clientes = Array.isArray(data) ? data : (data.clientes || []);
                const sel = document.getElementById('crmClienteSelect');
                sel.innerHTML = '<option value="">Seleccionar cliente...</option>' + clientes.map(c => `<option value="${c.id}" ${c.id == clienteId ? 'selected' : ''}>${escapeHtml(c.nombre || c.cliente || 'Cliente #' + c.id)}</option>`).join('');
            } catch(e) { console.error('Error cargando clientes CRM:', e); }
            document.getElementById('crmModal').style.display = 'flex';
        }

        function cerrarModalCRM() {
            document.getElementById('crmModal').style.display = 'none';
        }

        async function guardarInteraccionCRM() {
            const clienteId = document.getElementById('crmClienteSelect')?.value || document.getElementById('crmClienteId')?.value;
            const tipo = document.getElementById('crmTipo')?.value;
            const titulo = document.getElementById('crmTitulo')?.value.trim();
            const descripcion = document.getElementById('crmDescripcion')?.value.trim();
            const fecha = document.getElementById('crmFecha')?.value;

            if (!clienteId || !tipo || !titulo) {
                mostrarNotificacion('Complete los campos requeridos (Cliente, Tipo, Título)', 'error');
                return;
            }

            try {
                const res = await fetch('/proyecto/clientes/agregar_interaccion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cliente_id: parseInt(clienteId), tipo, titulo, descripcion, fecha_interaccion: fecha || new Date().toISOString().slice(0, 19).replace('T', ' ') }),
                    credentials: 'include'
                });
                const data = await res.json();
                mostrarNotificacion(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    cerrarModalCRM();
                    cargarInteraccionesCRM();
                }
            } catch(e) { mostrarNotificacion('Error de conexión', 'error'); }
        }

        async function eliminarInteraccionCRM(id) {
            if (!confirm('¿Eliminar esta interacción?')) return;
            try {
                const res = await fetch('/proyecto/clientes/eliminar_interaccion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id }),
                    credentials: 'include'
                });
                const data = await res.json();
                mostrarNotificacion(data.message, data.success ? 'success' : 'error');
                if (data.success) cargarInteraccionesCRM();
            } catch(e) { mostrarNotificacion('Error de conexión', 'error'); }
        }

        async function cargarFacturas() {
            mostrarLoading('Cargando facturas...');
            try {
                const response = await fetch('/proyecto/facturacion/obtener_factura.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success === true && Array.isArray(data.facturas)) facturasData = data.facturas;
                    else if(Array.isArray(data)) facturasData = data;
                    else if(data.data && Array.isArray(data.data)) facturasData = data.data;
                    else facturasData = [];
                } else { facturasData = []; }
            } catch(e) { console.error('Error cargando facturas:', e); facturasData = []; }
            renderFacturas();
            ocultarLoading();
        }

        function renderFacturas() {
            const tbody = document.getElementById('facturasList');
            if(!tbody) return;
            if(!facturasData || facturasData.length === 0) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center">No hay facturas registradas</tbody>'; return; }
            let html = '';
            facturasData.forEach(f => {
                let fechaFormateada = 'N/A';
                const fecha = f.fecha_emision || f.created_at || f.fecha;
                if(fecha && fecha !== '0000-00-00') { const fechaObj = new Date(fecha); if(!isNaN(fechaObj.getTime())) fechaFormateada = fechaObj.toLocaleDateString('es-ES'); }
                html += `<tr>
                    <td>${f.id}</td>
                    <td><strong>${escapeHtml(f.numero_factura)}</strong></td>
                    <td>${escapeHtml(f.cliente_nombre || f.nombre_cliente || 'Cliente')}</td>
                    <td>${fechaFormateada}</td>
                    <td>${getMetodoPagoBadge(f.metodo_pago)}</td>
                    <td>${formatMoney(f.total)}</td>
                    <td>${getEstadoBadge(f.estado)}</td>
                    <td class="action-buttons">
                        <button class="btn-action btn-view" onclick="verFactura(${f.id})"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-pdf" onclick="exportarFacturaPDF(${f.id})"><i class="fas fa-file-pdf"></i></button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        async function cargarCaja() {
            try {
                const response = await fetch('/proyecto/caja/obtener_estado_caja.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success) {
                        cajaData = data;
                        document.getElementById('cajaEstado').innerHTML = data.estado === 'abierta' ? '<span style="color:#2ed573">Abierta</span>' : '<span style="color:#ff4757">Cerrada</span>';
                        document.getElementById('cajaMontoInicial').innerHTML = formatMoney(data.monto_inicial);
                        document.getElementById('cajaIngresos').innerHTML = formatMoney(data.total_ingresos);
                        document.getElementById('cajaEgresos').innerHTML = formatMoney(data.total_egresos);
                        document.getElementById('cajaTotal').innerHTML = formatMoney(data.total || data.saldo_actual);
                    }
                }
            } catch(e) { console.error('Error cargando caja:', e); }
            
            try {
                const response = await fetch('/proyecto/caja/obtener_movimientos.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    const movimientos = Array.isArray(data) ? data : (data.movimientos || []);
                    const tbody = document.getElementById('cajaMovimientosList');
                    if(!tbody) return;
                    if(movimientos.length === 0) { 
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">No hay movimientos disponibles</tbody>'; 
                        return; 
                    }
                    let html = '';
                    for(let i = 0; i < movimientos.length; i++) {
                        const m = movimientos[i];
                        let fechaFormateada = 'N/A';
                        const fechaRaw = m.fecha || m.created_at;
                        if(fechaRaw && fechaRaw !== 'null') {
                            try {
                                const fechaObj = new Date(fechaRaw);
                                if(!isNaN(fechaObj.getTime())) {
                                    fechaFormateada = fechaObj.toLocaleString('es-ES');
                                }
                            } catch(e) {}
                        }
                        html += `<tr>
                            <td>${fechaFormateada}</td>
                            <td><span class="badge ${m.tipo === 'ingreso' ? 'badge-active' : 'badge-inactive'}">${m.tipo === 'ingreso' ? 'Ingreso' : 'Egreso'}</span></td>
                            <td>${escapeHtml(m.categoria || 'General')}</td>
                            <td><strong>${formatMoney(m.monto)}</strong></td>
                            <td>${escapeHtml(m.descripcion || 'Sin descripción')}</td>
                            <td>${escapeHtml(m.usuario_nombre || 'Sistema')}</td>
                        </tr>`;
                    }
                    tbody.innerHTML = html;
                }
            } catch(e) { console.error('Error cargando movimientos:', e); }
        }

        async function imprimirCierreCaja() {
            try {
                const response = await fetch('/proyecto/caja/obtener_estado_caja.php', { credentials: 'include' });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.estado === 'abierta') {
                        if (cajaData && cajaData.caja_id) {
                            window.open('/proyecto/caja/imprimir_cierre_pdf.php?id=' + cajaData.caja_id, '_blank');
                        } else {
                            window.open('/proyecto/caja/imprimir_cierre_pdf.php', '_blank');
                        }
                    } else {
                        window.open('/proyecto/caja/imprimir_cierre_pdf.php', '_blank');
                    }
                }
            } catch(e) { console.error('Error imprimiendo cierre:', e); }
        }

        async function limpiarCaja() {
            if (!confirm('¿Estás seguro de limpiar todos los movimientos de la caja actual?\n\nEsta acción eliminará todos los movimientos del día y reiniciará los montos.')) return;
            if (!confirm('⚠️ CONFIRMACIÓN: ¿Estás completamente seguro? Esta operación no se puede deshacer.')) return;
            try {
                const response = await fetch('/proyecto/caja/limpiar_caja.php', { method: 'POST', body: JSON.stringify({ accion: 'limpiar' }), headers: { 'Content-Type': 'application/json' }, credentials: 'include' });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion(data.message, 'success');
                    await cargarCaja();
                } else {
                    mostrarNotificacion(data.message, 'error');
                }
            } catch(e) { console.error('Error limpiando caja:', e); mostrarNotificacion('Error al limpiar caja', 'error'); }
        }

        async function cargarVentasCliente() {
            mostrarLoading('Cargando ventas por cliente...');
            try {
                const buscar = document.getElementById('buscarCliente')?.value || '';
                let url = '/proyecto/reportes/ventas_por_cliente.php';
                if(buscar) url += `?buscar=${encodeURIComponent(buscar)}`;
                const response = await fetch(url, { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    ventasClienteData = Array.isArray(data) ? data : (data.clientes || []);
                    document.getElementById('clientesTotal').innerHTML = ventasClienteData.length;
                    const totalVentas = ventasClienteData.reduce((sum, c) => sum + (c.total_ventas || 0), 0);
                    document.getElementById('ventasClienteTotal').innerHTML = totalVentas;
                    const montoTotal = ventasClienteData.reduce((sum, c) => sum + parseFloat(c.monto_total || 0), 0);
                    document.getElementById('montoClienteTotal').innerHTML = formatMoney(montoTotal);
                    renderVentasCliente();
                } else {
                    ventasClienteData = [];
                    renderVentasCliente();
                }
            } catch(e) { 
                console.error('Error cargando ventas por cliente:', e); 
                ventasClienteData = []; 
                renderVentasCliente();
                mostrarNotificacion('Error al cargar ventas por cliente', 'error');
            }
            finally { ocultarLoading(); }
        }

        function renderVentasCliente() {
            const tbody = document.getElementById('ventasClienteBody');
            if(!tbody) {
                console.error('No se encontró el elemento ventasClienteBody');
                return;
            }
            if(!ventasClienteData || ventasClienteData.length === 0) { 
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center">No hay datos disponibles</tbody>'; 
                return; 
            }
            let html = '';
            for(let i = 0; i < ventasClienteData.length; i++) {
                const c = ventasClienteData[i];
                let ultimaCompra = (c.ultima_compra && c.ultima_compra !== '' && c.ultima_compra !== 'null') ? formatDate(c.ultima_compra) : 'N/A';
                html += `<tr>
                    <td>${c.id}</td>
                    <td><strong>${escapeHtml(c.nombre)}</strong></td>
                    <td>${escapeHtml(c.email)}</td>
                    <td>${escapeHtml(c.telefono)}</td>
                    <td>${c.total_ventas || 0}</td>
                    <td>${c.total_productos || 0}</td>
                    <td>${formatMoney(c.monto_total)}</td>
                    <td>${ultimaCompra}</td>
                    <td class="action-buttons">
                        <button class="btn-action btn-view" onclick="verDetalleCliente(${c.id})" title="Ver cliente"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="abrirModalCRM(${c.id})" title="Añadir interacción CRM" style="background:var(--purple)"><i class="fas fa-handshake"></i></button>
                    </td>
                </tr>`;
            }
            tbody.innerHTML = html;
        }

        async function cargarVentasVendedor() {
            mostrarLoading('Cargando ventas por vendedor...');
            try {
                const vendedorId = document.getElementById('filtroVendedor')?.value || '';
                let url = '/proyecto/reportes/ventas_por_vendedor.php';
                if(vendedorId) url += `?vendedor_id=${vendedorId}`;
                
                const response = await fetch(url, { credentials: 'include' });
                
                if(!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                ventasVendedorData = [];
                
                if(data.error) { 
                    mostrarNotificacion(data.error, 'error'); 
                    renderVentasVendedor();
                    ocultarLoading();
                    return;
                }
                
                if(Array.isArray(data)) {
                    ventasVendedorData = data;
                } else if(data.vendedores && Array.isArray(data.vendedores)) {
                    ventasVendedorData = data.vendedores;
                } else if(data.data && Array.isArray(data.data)) {
                    ventasVendedorData = data.data;
                } else {
                    console.warn('Estructura de datos inesperada:', data);
                    ventasVendedorData = [];
                }
                
                const totalVendedoresSpan = document.getElementById('vendedoresTotal');
                const totalVentasSpan = document.getElementById('ventasVendedorTotal');
                const montoTotalSpan = document.getElementById('montoVendedorTotal');
                
                if(totalVendedoresSpan) {
                    totalVendedoresSpan.innerHTML = ventasVendedorData.length;
                }
                
                const sumaVentas = ventasVendedorData.reduce((sum, v) => sum + (parseInt(v.total_ventas) || 0), 0);
                if(totalVentasSpan) {
                    totalVentasSpan.innerHTML = sumaVentas;
                }
                
                const sumaMonto = ventasVendedorData.reduce((sum, v) => sum + (parseFloat(v.monto_total) || 0), 0);
                if(montoTotalSpan) {
                    montoTotalSpan.innerHTML = formatMoney(sumaMonto);
                }
                
                renderVentasVendedor();
                
                const selectVendedor = document.getElementById('filtroVendedor');
                if(selectVendedor && selectVendedor.options.length <= 1 && ventasVendedorData.length > 0) {
                    while(selectVendedor.options.length > 1) {
                        selectVendedor.remove(1);
                    }
                    for(let i = 0; i < ventasVendedorData.length; i++) {
                        const v = ventasVendedorData[i];
                        if(v && v.id) {
                            const option = document.createElement('option'); 
                            option.value = v.id; 
                            option.textContent = v.nombre || v.name || `Vendedor ${v.id}`; 
                            selectVendedor.appendChild(option); 
                        }
                    }
                }
                
            } catch(e) { 
                console.error('Error cargando ventas por vendedor:', e); 
                ventasVendedorData = []; 
                renderVentasVendedor();
                mostrarNotificacion('Error al cargar ventas por vendedor: ' + e.message, 'error');
            }
            finally { 
                ocultarLoading(); 
            }
        }

        function renderVentasVendedor() {
            const tbody = document.getElementById('ventasVendedorBody');
            
            if(!tbody) {
                console.error('ERROR: No se encontró el elemento con id "ventasVendedorBody"');
                const tablaVentas = document.querySelector('#ventasVendedorSection .data-table');
                if(tablaVentas) {
                    const newTbody = document.createElement('tbody');
                    newTbody.id = 'ventasVendedorBody';
                    tablaVentas.appendChild(newTbody);
                    console.log('✅ Creado nuevo tbody con id ventasVendedorBody');
                    return renderVentasVendedor();
                }
                return;
            }
            
            if(!ventasVendedorData || ventasVendedorData.length === 0) { 
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center">No hay datos disponibles para vendedores</tbody>'; 
                return; 
            }
            
            let html = '';
            for(let i = 0; i < ventasVendedorData.length; i++) {
                const v = ventasVendedorData[i];
                if(!v) continue;
                
                let ultimaVenta = 'N/A';
                const fechaUltimaVenta = v.ultima_venta || v.fecha_ultima_venta;
                if(fechaUltimaVenta && fechaUltimaVenta !== '' && fechaUltimaVenta !== 'null') { 
                    try { 
                        const fechaObj = new Date(fechaUltimaVenta); 
                        if(!isNaN(fechaObj.getTime())) {
                            ultimaVenta = fechaObj.toLocaleDateString('es-ES');
                        }
                    } catch(e) { 
                        ultimaVenta = 'N/A'; 
                    } 
                }
                
                let promedio = v.promedio;
                if(!promedio && v.monto_total && v.total_ventas) {
                    promedio = parseFloat(v.monto_total) / parseInt(v.total_ventas);
                }
                
                html += `<tr>
                    <td style="padding: 12px 15px;">${v.id || 'N/A'}</td>
                    <td style="padding: 12px 15px;"><strong>${escapeHtml(v.nombre || v.name || 'N/A')}</strong></td>
                    <td style="padding: 12px 15px;">${escapeHtml(v.email || 'N/A')}</td>
                    <td style="padding: 12px 15px;">${v.total_ventas || 0}</td>
                    <td style="padding: 12px 15px;">${v.total_productos || 0}</td>
                    <td style="padding: 12px 15px;">${formatMoney(v.monto_total || 0)}</td>
                    <td style="padding: 12px 15px;">${formatMoney(promedio || 0)}</td>
                    <td style="padding: 12px 15px;">${ultimaVenta}</td>
                </tr>`;
            }
            tbody.innerHTML = html;
        }

        async function cargarProductosVendidos() {
            try {
                const response = await fetch('/proyecto/reportes/productos_mas_vendidos.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    productosVendidosData = Array.isArray(data) ? data : (data.productos || []);
                    renderProductosVendidos();
                }
            } catch(e) { console.error('Error cargando productos más vendidos:', e); }
        }

        function renderProductosVendidos() {
            const tbody = document.getElementById('productosVendidosBody');
            if(!tbody) return;
            if(!productosVendidosData || productosVendidosData.length === 0) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center">No hay datos disponibles</tbody>'; return; }
            let html = '';
            for(let i = 0; i < productosVendidosData.length; i++) {
                const p = productosVendidosData[i];
                html += `<tr>
                    <td>${p.id}</td>
                    <td><strong>${escapeHtml(p.nombre)}</strong></td>
                    <td>${escapeHtml(p.categoria)}</td>
                    <td>${p.veces_vendido || 0}</td>
                    <td>${p.unidades_vendidas || 0}</td>
                    <td>${formatMoney(p.ingresos)}</td>
                    <td>${p.stock_actual || 0}</td>
                </tr>`;
            }
            tbody.innerHTML = html;
        }

        async function cargarReporteStock() {
            mostrarLoading('Cargando reporte de stock...');
            try {
                const categoria = document.getElementById('filtroCategoriaStock')?.value || '';
                const estado = document.getElementById('filtroEstadoStock')?.value || '';
                let url = '/proyecto/reportes/reporte_stock.php?';
                const params = [];
                if(categoria) params.push(`categoria=${encodeURIComponent(categoria)}`);
                if(estado === 'critico') params.push('estado=critico');
                else if(estado === 'bajo') params.push('estado=bajo');
                else if(estado === 'normal') params.push('estado=normal');
                else if(estado === 'agotado') params.push('estado=agotado');
                url += params.join('&');
                const response = await fetch(url, { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success) {
                        reporteStockData = data.data;
                        if(data.stats) {
                            document.getElementById('totalProductosStock').innerHTML = data.stats.total_productos;
                            document.getElementById('stockCritico').innerHTML = data.stats.stock_critico;
                            document.getElementById('stockBajoResumen').innerHTML = data.stats.stock_bajo;
                            document.getElementById('stockMedio').innerHTML = data.stats.stock_medio;
                            document.getElementById('stockAlto').innerHTML = data.stats.stock_alto;
                            document.getElementById('agotadosResumen').innerHTML = data.stats.agotados;
                            document.getElementById('valorInventario').innerHTML = formatMoney(data.stats.valor_inventario);
                        }
                        renderReporteStock();
                        cargarCategoriasStock();
                    } else { mostrarNotificacion(data.message || 'Error al cargar reporte', 'error'); }
                }
            } catch(e) { console.error('Error cargando reporte stock:', e); mostrarNotificacion('Error al cargar reporte de stock', 'error'); }
            finally { ocultarLoading(); }
        }

        async function cargarCategoriasStock() {
            const selectCategoria = document.getElementById('filtroCategoriaStock');
            if (!selectCategoria) return;
            
            try {
                const response = await fetch('/proyecto/producto/obtener_categorias.php', { credentials: 'include' });
                if (response.ok) {
                    const data = await response.json();
                    let categorias = [];
                    
                    if (Array.isArray(data)) {
                        categorias = data;
                    } else if (data.categorias && Array.isArray(data.categorias)) {
                        categorias = data.categorias;
                    } else if (data.data && Array.isArray(data.data)) {
                        categorias = data.data;
                    } else if (data.success && data.categorias) {
                        categorias = data.categorias;
                    }
                    
                    while (selectCategoria.options.length > 1) {
                        selectCategoria.remove(1);
                    }
                    
                    for(let i = 0; i < categorias.length; i++) {
                        const cat = categorias[i];
                        const nombre = cat.nombre || cat.name || cat.categoria || cat;
                        const id = cat.id || cat.nombre || cat;
                        if (nombre && nombre !== '') {
                            const option = document.createElement('option');
                            option.value = nombre;
                            option.textContent = nombre;
                            selectCategoria.appendChild(option);
                        }
                    }
                }
            } catch (e) {
                console.error('Error cargando categorías:', e);
            }
        }

        function renderReporteStock() {
            const tbody = document.getElementById('reporteStockBody');
            if(!tbody) return;
            if(!reporteStockData || reporteStockData.length === 0) { tbody.innerHTML = '</tr><td colspan="8" style="text-align:center">No hay productos registrados'; return; }
            let html = '';
            for(let i = 0; i < reporteStockData.length; i++) {
                const p = reporteStockData[i];
                let estadoClass = '', estadoText = '';
                if(p.stock === 0) { estadoClass = 'badge-inactive'; estadoText = 'Agotado'; }
                else if(p.stock <= 5) { estadoClass = 'badge-danger'; estadoText = 'Crítico'; }
                else if(p.stock <= 10) { estadoClass = 'badge-pending'; estadoText = 'Bajo'; }
                else if(p.stock <= 20) { estadoClass = 'badge-info'; estadoText = 'Medio'; }
                else { estadoClass = 'badge-active'; estadoText = 'Alto'; }
                const rowStyle = p.stock <= 5 ? 'style="background-color: rgba(255, 71, 87, 0.1);"' : '';
                html += `<tr ${rowStyle}>
                    <td>${p.id}</td>
                    <td><strong>${escapeHtml(p.nombre)}</strong></td>
                    <td>${escapeHtml(p.categoria)}</td>
                    <td>${formatMoney(p.precio)}</td>
                    <td><strong style="color: ${p.stock <= 5 ? '#ff4757' : (p.stock <= 10 ? '#ffa502' : '#2ed573')};">${p.stock}</strong></td>
                    <td><span class="badge ${estadoClass}">${estadoText}</span></td>
                    <td>${p.veces_vendido || 0} veces (${p.unidades_vendidas || 0} uds)</td>
                    <td class="action-buttons"><button class="btn-action btn-edit" onclick="editarProducto(${p.id})"><i class="fas fa-edit"></i></button></td>
                </tr>`;
            }
            tbody.innerHTML = html;
        }

        // ====================================================================
        // HISTORIAL DE COMPRAS - FUNCIÓN CORREGIDA
        // ====================================================================
        async function cargarHistorialCompras() {
            console.log('Cargando historial de compras...');
            
            const tbody = document.getElementById('historialComprasBody');
            if (!tbody) {
                console.error('No se encuentra el elemento historialComprasBody');
                return;
            }
            
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center"><div class="loading-spinner" style="margin:0 auto 10px;"></div>Cargando pedidos...</tbody>';
            
            try {
                const cliente = document.getElementById('buscarClienteHistorial')?.value || '';
                const fechaDesde = document.getElementById('fechaDesdeHistorial')?.value || '';
                const fechaHasta = document.getElementById('fechaHastaHistorial')?.value || '';
                const estado = document.getElementById('estadoHistorial')?.value || '';
                
                let url = '/proyecto/proceso_compra/obtener_todos_los_pedidos.php';
                const params = [];
                if (cliente) params.push(`cliente=${encodeURIComponent(cliente)}`);
                if (fechaDesde) params.push(`desde=${fechaDesde}`);
                if (fechaHasta) params.push(`hasta=${fechaHasta}`);
                if (estado) params.push(`estado=${estado}`);
                if (params.length > 0) url += '?' + params.join('&');
                
                console.log('Fetching:', url);
                
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();
                
                if (!data || !data.success || !data.pedidos) {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; color:red;">Error al cargar los datos</tbody>';
                    return;
                }
                
                const pedidos = data.pedidos;
                
                if (pedidos.length === 0) {
                    tbody.innerHTML = '<td><td colspan="10" style="text-align:center;">No hay pedidos registrados</tbody>';
                    document.getElementById('totalClientesHistorial').innerHTML = '0';
                    document.getElementById('totalPedidosHistorial').innerHTML = '0';
                    document.getElementById('totalMontoHistorial').innerHTML = 'Bs. 0,00';
                    return;
                }
                
                historialData = pedidos;
                
                let html = '';
                let totalMontoGeneral = 0;
                const clientesUnicos = new Set();
                
                for(let i = 0; i < pedidos.length; i++) {
                    const pedido = pedidos[i];
                    
                    let fecha = 'N/A';
                    let fechaRaw = pedido.fecha_creacion || pedido.created_at || pedido.fecha || pedido.fecha_pedido;
                    
                    if (fechaRaw && fechaRaw !== 'null' && fechaRaw !== 'NULL') {
                        try {
                            const d = new Date(fechaRaw);
                            if (!isNaN(d.getTime())) {
                                fecha = d.toLocaleDateString('es-ES');
                            }
                        } catch (e) {}
                    }
                    
                    const id = pedido.id || '?';
                    const nombreCliente = pedido.cliente_nombre || pedido.nombre || 'N/A';
                    const emailCliente = pedido.cliente_email || pedido.email || 'N/A';
                    const telefonoCliente = pedido.cliente_telefono || pedido.telefono || 'N/A';
                    const total = parseFloat(pedido.total) || 0;
                    const numProductos = (pedido.productos && pedido.productos.length) || pedido.total_productos || 0;
                    
                    totalMontoGeneral += total;
                    const idCliente = pedido.usuario_id || pedido.cliente_id;
                    if (idCliente) clientesUnicos.add(idCliente);
                    
                    html += `<tr>
                        <td style="padding: 12px 15px;">${escapeHtml(String(id))}</td>
                        <td style="padding: 12px 15px;"><strong>${escapeHtml(nombreCliente)}</strong></td>
                        <td style="padding: 12px 15px;">${escapeHtml(emailCliente)}</td>
                        <td style="padding: 12px 15px;">${escapeHtml(telefonoCliente)}</td>
                        <td style="padding: 12px 15px;">${fecha}</td>
                        <td style="padding: 12px 15px;">${getMetodoPagoBadge(pedido.metodo_pago)}</td>
                        <td style="padding: 12px 15px;">${formatMoney(total)}</td>
                        <td style="padding: 12px 15px;">${numProductos} producto(s)</td>
                        <td style="padding: 12px 15px;">${getEstadoBadge(pedido.estado || 'pendiente')}</td>
                        <td class="action-buttons" style="padding: 12px 15px;">
                            <button class="btn-action btn-view" onclick="verDetallePedido(${id})"><i class="fas fa-eye"></i></button>
                            <button class="btn-action btn-pdf" onclick="exportarPedidoPDF(${id})"><i class="fas fa-file-pdf"></i></button>
                        </td>
                    </tr>`;
                }
                
                tbody.innerHTML = html;
                
                document.getElementById('totalClientesHistorial').innerHTML = clientesUnicos.size;
                document.getElementById('totalPedidosHistorial').innerHTML = pedidos.length;
                document.getElementById('totalMontoHistorial').innerHTML = formatMoney(totalMontoGeneral);
                
            } catch (error) {
                console.error('Error:', error);
                const tbody = document.getElementById('historialComprasBody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:red;">Error: ${error.message}</tbody>`;
                }
                mostrarNotificacion('Error al cargar el historial: ' + error.message, 'error');
            }
        }

async function cargarAuditoria() {
    console.log('🔄 Cargando auditoría...');
    
    const tbody = document.getElementById('auditoriaBody');
    if (!tbody) {
        console.error('No se encontró el elemento auditoriaBody');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center"><div class="loading-spinner" style="margin:0 auto 10px;"></div>Cargando registros de auditoría...</tbody>';
    
    try {
        const desde = document.getElementById('auditoriaFechaDesde')?.value || '';
        const hasta = document.getElementById('auditoriaFechaHasta')?.value || '';
        const modulo = document.getElementById('auditoriaModulo')?.value || '';
        
        let url = '/proyecto/reportes/obtener_auditoria.php';
        const params = [];
        if (desde) params.push(`desde=${encodeURIComponent(desde)}`);
        if (hasta) params.push(`hasta=${encodeURIComponent(hasta)}`);
        if (modulo) params.push(`modulo=${encodeURIComponent(modulo)}`);
        if (params.length > 0) url += '?' + params.join('&');
        
        console.log('Fetching auditoría desde:', url);
        
        const response = await fetch(url, { 
            credentials: 'include',
            headers: { 'Cache-Control': 'no-cache' }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Datos recibidos:', data);
        
        // ADAPTADO: Manejar diferentes formatos de respuesta
        if (data && !data.error) {
            // Si es un array directo (como devuelve tu PHP)
            if (Array.isArray(data)) {
                auditoriaData = data;
            }
            // Si es un objeto con propiedad auditoria
            else if (data.auditoria && Array.isArray(data.auditoria)) {
                auditoriaData = data.auditoria;
            }
            // Si es un objeto con propiedad data
            else if (data.data && Array.isArray(data.data)) {
                auditoriaData = data.data;
            }
            // Si es un objeto con propiedad success y datos
            else if (data.success === true && data.auditoria) {
                auditoriaData = data.auditoria;
            }
            else {
                auditoriaData = [];
            }
        } else {
            console.warn('Error en respuesta:', data?.error);
            auditoriaData = [];
            if (data?.error) {
                mostrarNotificacion(data.error, 'error');
            }
        }
        
        console.log('Auditoría cargada:', auditoriaData.length, 'registros');
        renderAuditoria();
        
    } catch (error) {
        console.error('Error cargando auditoría:', error);
        
        // Mostrar mensaje amigable en la tabla
        const tbodyActual = document.getElementById('auditoriaBody');
        if (tbodyActual) {
            tbodyActual.innerHTML = `<tr><td colspan="7" style="text-align:center; color: #ffa502;">
                <i class="fas fa-exclamation-triangle"></i> Error al cargar auditoría: ${escapeHtml(error.message)}
            </td></tr>`;
        }
        mostrarNotificacion('Error al cargar auditoría: ' + error.message, 'error');
    }
}

function renderAuditoria() {
    console.log('🎨 Renderizando auditoría, datos:', auditoriaData);
    
    const tbody = document.getElementById('auditoriaBody');
    if (!tbody) {
        console.error('No se encontró el elemento auditoriaBody');
        return;
    }
    
    if (!auditoriaData || auditoriaData.length === 0) {
        tbody.innerHTML = '<table><td colspan="7" style="text-align:center"><i class="fas fa-info-circle"></i> No hay registros de auditoría disponibles</tbody>';
        return;
    }
    
    let html = '';
    for (let i = 0; i < auditoriaData.length; i++) {
        const a = auditoriaData[i];
        
        // Formatear fecha - compatible con diferentes nombres de campo
        let fechaFormateada = 'N/A';
        const fecha = a.fecha || a.fecha_creacion || a.created_at || a.fecha_hora || a.fecha_creacion;
        if (fecha && fecha !== 'null' && fecha !== 'NULL' && fecha !== '') {
            try {
                const dateObj = new Date(fecha);
                if (!isNaN(dateObj.getTime())) {
                    fechaFormateada = dateObj.toLocaleString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                } else {
                    fechaFormateada = fecha;
                }
            } catch(e) {
                fechaFormateada = fecha;
            }
        }
        
        // Obtener datos con diferentes posibles nombres de campo
        const usuario = a.usuario_nombre || a.usuario || a.username || 'Sistema';
        const rol = a.usuario_rol || a.rol || 'sistema';
        const modulo = a.modulo || 'sistema';
        let accionTexto = (a.accion || 'N/A').toUpperCase();
        
        let accionBadge = '';
        if (accionTexto === 'CREAR' || accionTexto === 'CREATE' || accionTexto === 'INSERT') {
            accionBadge = '<span class="badge" style="background:#2ed573; color:white;"><i class="fas fa-plus-circle"></i> CREAR</span>';
        } else if (accionTexto === 'ACTUALIZAR' || accionTexto === 'UPDATE' || accionTexto === 'EDITAR' || accionTexto === 'EDIT') {
            accionBadge = '<span class="badge" style="background:#ffa502; color:white;"><i class="fas fa-edit"></i> ACTUALIZAR</span>';
        } else if (accionTexto === 'ELIMINAR' || accionTexto === 'DELETE' || accionTexto === 'REMOVE') {
            accionBadge = '<span class="badge" style="background:#ff4757; color:white;"><i class="fas fa-trash-alt"></i> ELIMINAR</span>';
        } else if (accionTexto === 'LOGIN' || accionTexto === 'INICIAR_SESION' || accionTexto === 'LOGIN_EXITOSO') {
            accionBadge = '<span class="badge" style="background:#3498db; color:white;"><i class="fas fa-sign-in-alt"></i> LOGIN</span>';
        } else if (accionTexto === 'LOGOUT' || accionTexto === 'CERRAR_SESION') {
            accionBadge = '<span class="badge" style="background:#95a5a6; color:white;"><i class="fas fa-sign-out-alt"></i> LOGOUT</span>';
        } else {
            accionBadge = `<span class="badge" style="background:#6c757d; color:white;">${escapeHtml(accionTexto)}</span>`;
        }
        
        const descripcion = a.descripcion || a.details || 'Sin descripción';
        const ip = a.ip_address || a.ip || '0.0.0.0';
        
        html += `<tr>
            <td style="padding: 12px 15px; white-space: nowrap;">${escapeHtml(fechaFormateada)}</td>
            <td style="padding: 12px 15px;"><strong>${escapeHtml(usuario)}</strong></td>
            <td style="padding: 12px 15px;"><span class="badge" style="background:#9b59b6; color:white;">${escapeHtml(rol)}</span></td>
            <td style="padding: 12px 15px;"><span class="badge" style="background:#3498db; color:white;">${escapeHtml(modulo)}</span></td>
            <td style="padding: 12px 15px;">${accionBadge}</td>
            <td style="padding: 12px 15px; max-width: 300px; word-wrap: break-word;">${escapeHtml(descripcion)}</td>
            <td style="padding: 12px 15px;"><code>${escapeHtml(ip)}</code></td>
        </tr>`;
    }
    
    tbody.innerHTML = html;
    console.log('✅ Auditoría renderizada,', auditoriaData.length, 'filas');
}

        async function cargarCEO() {
            mostrarLoading('Cargando Panel Ejecutivo...');
            try {
                const response = await fetch('/proyecto/reportes/obtener_datos_ceo.php', { credentials: 'include' });
                const data = await response.json();
                if(data.success) {
                    document.getElementById('ceoUsuarios').innerHTML = data.total_usuarios || 0;
                    document.getElementById('ceoClientes').innerHTML = data.clientes_activos || data.total_clientes || 0;
                    document.getElementById('ceoProductos').innerHTML = data.total_productos || 0;
                    document.getElementById('ceoProveedores').innerHTML = data.total_proveedores || 0;
                    document.getElementById('ceoVentasMes').innerHTML = formatMoney(data.ventas_mes);
                    document.getElementById('ceoPedidosPendientes').innerHTML = data.pedidos_pendientes || 0;
                    document.getElementById('ceoStockBajo').innerHTML = data.productos_stock_bajo || 0;
                    document.getElementById('ceoComprasMes').innerHTML = formatMoney(data.compras_mes);
                    document.getElementById('ceoUtilidad').innerHTML = formatMoney(data.utilidad_estimada);
                    document.getElementById('ceoTicketPromedio').innerHTML = formatMoney(data.ticket_promedio);
                    const crecimiento = data.crecimiento || 0;
                    const crecEl = document.getElementById('ceoCrecimiento');
                    if(crecimiento > 0) crecEl.innerHTML = `<span class="tendencia-up">+${crecimiento}% <i class="fas fa-arrow-up"></i></span>`;
                    else if(crecimiento < 0) crecEl.innerHTML = `<span class="tendencia-down">${crecimiento}% <i class="fas fa-arrow-down"></i></span>`;
                    else crecEl.innerHTML = `0%`;
                    
                    const meses = (data.ventas_por_mes || []).map(d => d.mes_nombre || d.mes);
                    const montos = (data.ventas_por_mes || []).map(d => parseFloat(d.total) || 0);
                    if(window.ceoVentasMesChart) window.ceoVentasMesChart.destroy();
                    const ctxVentas = document.getElementById('ceoVentasMesChart');
                    if(ctxVentas && meses.length > 0) {
                        window.ceoVentasMesChart = new Chart(ctxVentas, {
                            type: 'line', data: { labels: meses, datasets: [{ label: 'Ventas (Bs.)', data: montos, borderColor: '#3C91ED', backgroundColor: 'rgba(60,145,237,0.1)', borderWidth: 3, fill: true, tension: 0.4 }] },
                            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: (ctx) => `Bs. ${ctx.raw.toLocaleString()}` } } }, scales: { y: { beginAtZero: true, ticks: { callback: (value) => `Bs. ${value.toLocaleString()}` } } } }
                        });
                    }
                    
                    const metodos = (data.metodos_pago || []).map(d => d.metodo_pago);
                    const montosMetodos = (data.metodos_pago || []).map(d => parseFloat(d.total) || 0);
                    if(window.ceoMetodoPagoChart) window.ceoMetodoPagoChart.destroy();
                    const ctxMetodos = document.getElementById('ceoMetodoPagoChart');
                    if(ctxMetodos && metodos.length > 0) {
                        window.ceoMetodoPagoChart = new Chart(ctxMetodos, {
                            type: 'doughnut', data: { labels: metodos, datasets: [{ data: montosMetodos, backgroundColor: ['#2ed573','#3498db','#9b59b6','#f39c12','#e74c3c','#1abc9c'], borderWidth: 0 }] },
                            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${formatMoney(ctx.raw)}` } } } }
                        });
                    }
                    
                    const topProductosDiv = document.getElementById('ceoTopProductosList');
                    if(topProductosDiv && data.top_productos?.length > 0) {
                        let topHtml = '';
                        for(let idx = 0; idx < data.top_productos.length; idx++) {
                            const p = data.top_productos[idx];
                            topHtml += `<div class="top-item"><div class="top-number">${idx+1}</div><div class="top-name">${escapeHtml(p.nombre)}</div><div class="top-value">${p.unidades_vendidas || 0} unidades</div><div style="margin-left:10px; font-size:0.8rem;">${formatMoney(p.ingresos)}</div></div>`;
                        }
                        topProductosDiv.innerHTML = topHtml;
                    } else if(topProductosDiv) { topProductosDiv.innerHTML = '<div style="text-align:center; padding:20px;">No hay datos disponibles</div>'; }
                    
                    const topClientesDiv = document.getElementById('ceoTopClientesList');
                    if(topClientesDiv && data.top_clientes?.length > 0) {
                        let topHtml = '';
                        for(let idx = 0; idx < data.top_clientes.length; idx++) {
                            const c = data.top_clientes[idx];
                            topHtml += `<div class="top-item"><div class="top-number">${idx+1}</div><div class="top-name">${escapeHtml(c.nombre)}</div><div class="top-value">${c.total_compras || 0} compras</div><div style="margin-left:10px; font-size:0.8rem;">${formatMoney(c.monto_total)}</div></div>`;
                        }
                        topClientesDiv.innerHTML = topHtml;
                    } else if(topClientesDiv) { topClientesDiv.innerHTML = '<div style="text-align:center; padding:20px;">No hay datos disponibles</div>'; }
                    
                    const topVendedoresDiv = document.getElementById('ceoTopVendedoresList');
                    if(topVendedoresDiv && data.top_vendedores?.length > 0) {
                        let topHtml = '';
                        for(let idx = 0; idx < data.top_vendedores.length; idx++) {
                            const v = data.top_vendedores[idx];
                            topHtml += `<div class="top-item"><div class="top-number">${idx+1}</div><div class="top-name">${escapeHtml(v.nombre)}</div><div class="top-value">${v.total_ventas || 0} ventas</div><div style="margin-left:10px; font-size:0.8rem;">${formatMoney(v.monto_total)}</div></div>`;
                        }
                        topVendedoresDiv.innerHTML = topHtml;
                    } else if(topVendedoresDiv) { topVendedoresDiv.innerHTML = '<div style="text-align:center; padding:20px;">No hay datos disponibles</div>'; }
                } else { mostrarNotificacion(data.message || 'Error al cargar datos del CEO', 'error'); }
            } catch(e) { console.error('Error en cargarCEO:', e); mostrarNotificacion('Error al cargar el Panel Ejecutivo', 'error'); }
            finally { ocultarLoading(); }
        }

        async function cargarConfiguracion() {
            console.log('🔄 Iniciando carga de configuración...');
            mostrarLoading('Cargando configuración...');
            
            try {
                const response = await fetch('/proyecto/admin/obtener_configuracion.php', { 
                    credentials: 'include', 
                    headers: { 'Cache-Control': 'no-cache' } 
                });
                
                console.log('📡 Response status:', response.status);
                
                if(!response.ok) {
                    throw new Error('HTTP error: ' + response.status);
                }
                
                const data = await response.json();
                console.log('📦 Datos recibidos:', data);
                
                if (data && data.success === true && Array.isArray(data.data)) {
                    configuracionData = data.data;
                    console.log('✅ Configuración cargada:', configuracionData.length, 'items');
                    renderConfiguracion();
                } else if (Array.isArray(data)) {
                    configuracionData = data;
                    console.log('✅ Configuración cargada (array):', configuracionData.length, 'items');
                    renderConfiguracion();
                } else {
                    console.warn('⚠️ Formato inesperado:', data);
                    configuracionData = [];
                    renderConfiguracion();
                }
                
            } catch(e) { 
                console.error('❌ Error cargando configuración:', e); 
                configuracionData = []; 
                renderConfiguracion();
                mostrarNotificacion('Error al cargar configuración: ' + e.message, 'error');
            }
            finally { 
                ocultarLoading();
                console.log('🏁 Finalizada carga de configuración');
            }
        }

function renderConfiguracion() {
    console.log('🎨 Renderizando configuración...');
    
    let tbody = document.getElementById('configuracionBody');
    
    if(!tbody) {
        console.warn('⚠️ No se encuentra configuracionBody, intentando crearlo...');
        const tableContainer = document.querySelector('#configuracionSection .data-table');
        if(tableContainer) {
            let existingTbody = tableContainer.querySelector('tbody');
            if(existingTbody) {
                existingTbody.id = 'configuracionBody';
                tbody = existingTbody;
            } else {
                tbody = document.createElement('tbody');
                tbody.id = 'configuracionBody';
                tableContainer.appendChild(tbody);
            }
        } else {
            console.error('❌ No se encuentra la tabla de configuración');
            return;
        }
    }
    
    let configs = [];
    if (Array.isArray(configuracionData) && configuracionData.length > 0) {
        configs = configuracionData;
    } else if (configuracionData && typeof configuracionData === 'object') {
        configs = Object.values(configuracionData);
    }
    
    console.log('📋 Configuraciones a renderizar:', configs.length);
    
    if(!configs || configs.length === 0) { 
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:40px;"><i class="fas fa-cog" style="font-size:2rem; color:#ccc;"></i><br>No hay configuración disponible</td></tr>'; 
        return; 
    }
    
    let html = '';
    let lastGrupo = '';
    
    for(let i = 0; i < configs.length; i++) {
        const c = configs[i];
        const clave = c.clave || '';
        const valor = c.valor || '';
        const descripcion = c.descripcion || '';
        const grupo = c.grupo || 'general';
        const isEditable = c.editable !== false;
        const inputDisabled = isEditable ? '' : 'disabled';
        
        if (!clave) continue;
        
        // Mostrar encabezado de grupo cuando cambia
        if (grupo !== lastGrupo) {
            // Siempre mostrar el encabezado del grupo (incluyendo el primero)
            let icono = '';
            let nombreGrupo = '';
            
            // Asignar iconos según el grupo
            switch(grupo.toLowerCase()) {
                case 'sistema':
                    icono = '<i class="fas fa-server"></i>';
                    nombreGrupo = 'SISTEMA';
                    break;
                case 'notificaciones':
                    icono = '<i class="fas fa-bell"></i>';
                    nombreGrupo = 'NOTIFICACIONES';
                    break;
                case 'inventario':
                    icono = '<i class="fas fa-boxes"></i>';
                    nombreGrupo = 'INVENTARIO';
                    break;
                case 'facturacion':
                    icono = '<i class="fas fa-file-invoice-dollar"></i>';
                    nombreGrupo = 'FACTURACIÓN';
                    break;
                default:
                    icono = '<i class="fas fa-folder-open"></i>';
                    nombreGrupo = grupo.toUpperCase();
            }
            
            html += `<tr style="background: linear-gradient(135deg, #1a1f2e, #0a0e1a);">
                        <td colspan="3" style="padding: 12px 15px;">
                            <strong style="color: #3C91ED; font-size: 1rem;">${icono} ${escapeHtml(nombreGrupo)}</strong>
                        </td>
                     </tr>`;
            lastGrupo = grupo;
        }
        
        let inputHtml = '';
        if (c.tipo === 'boolean') {
            inputHtml = `<select class="form-control config-valor" data-key="${escapeHtml(clave)}" ${inputDisabled} style="width:100%; background: var(--bg-color); color: var(--text-color); border-color: var(--border-color);">
                <option value="1" ${valor == 1 ? 'selected' : ''}>✅ Activado</option>
                <option value="0" ${valor == 0 ? 'selected' : ''}>❌ Desactivado</option>
            </select>`;
        } else if (c.tipo === 'number') {
            inputHtml = `<input type="number" class="form-control config-valor" data-key="${escapeHtml(clave)}" value="${escapeHtml(String(valor))}" style="width:100%; background: var(--bg-color); color: var(--text-color); border-color: var(--border-color);" ${inputDisabled}>`;
        } else if (c.tipo === 'textarea') {
            inputHtml = `<textarea class="form-control config-valor" data-key="${escapeHtml(clave)}" rows="2" style="width:100%; background: var(--bg-color); color: var(--text-color); border-color: var(--border-color);" ${inputDisabled}>${escapeHtml(String(valor))}</textarea>`;
        } else {
            inputHtml = `<input type="text" class="form-control config-valor" data-key="${escapeHtml(clave)}" value="${escapeHtml(String(valor))}" style="width:100%; background: var(--bg-color); color: var(--text-color); border-color: var(--border-color);" ${inputDisabled}>`;
        }
        
        html += `<tr style="border-bottom: 1px solid var(--border-color);">
            <td style="padding: 12px 15px; vertical-align: top; width: 30%;">
                <strong style="color: var(--text-color);">${escapeHtml(clave.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}</strong>
                <br><small style="color: var(--text-color); opacity: 0.6; font-size: 0.7rem;">${escapeHtml(grupo)}</small>
            </td>
            <td style="padding: 12px 15px; vertical-align: top; width: 40%;">
                ${inputHtml}
            </td>
            <td style="padding: 12px 15px; color: var(--text-color); opacity: 0.7; font-size:0.8rem; vertical-align: top; width: 30%;">
                ${escapeHtml(descripcion)}
            </td>
        </tr>`;
    }
    
    tbody.innerHTML = html;
    console.log('✅ Renderizado completo,', configs.length, 'filas generadas');
}

        async function guardarConfiguracion() {
            const valores = {};
            const inputs = document.querySelectorAll('.config-valor');
            if(inputs.length === 0) { mostrarNotificacion('No hay configuración para guardar', 'warning'); return; }
            for(let i = 0; i < inputs.length; i++) {
                const input = inputs[i];
                if(!input.disabled) { const key = input.getAttribute('data-key'); if(key) valores[key] = input.value; }
            }
            if(Object.keys(valores).length === 0) { mostrarNotificacion('No hay cambios para guardar', 'warning'); return; }
            mostrarLoading('Guardando configuración...');
            try {
                const response = await fetch('/proyecto/admin/guardar_configuracion.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(valores), credentials: 'include' });
                if(!response.ok) throw new Error('Error HTTP: ' + response.status);
                const data = await response.json();
                if(data.success) { mostrarNotificacion(data.message || 'Configuración guardada correctamente', 'success'); await cargarConfiguracion(); }
                else { mostrarNotificacion(data.error || data.message || 'Error al guardar configuración', 'error'); }
            } catch(e) { console.error('Error al guardar:', e); mostrarNotificacion('Error al guardar configuración: ' + e.message, 'error'); }
            finally { ocultarLoading(); }
        }

        async function cargarBackups() {
            mostrarLoading('Cargando backups...');
            try {
                const response = await fetch('/proyecto/backups/obtener_backups.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(Array.isArray(data)) backupsData = data;
                    else if(data.backups && Array.isArray(data.backups)) backupsData = data.backups;
                    else backupsData = [];
                    renderBackups();
                } else { backupsData = []; renderBackups(); }
            } catch(e) { console.error('Error cargando backups:', e); backupsData = []; renderBackups(); }
            finally { ocultarLoading(); }
        }

        function renderBackups() {
            const tbody = document.getElementById('backupsList');
            if(!tbody) return;
            if(!backupsData || backupsData.length === 0) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center">No hay copias de seguridad</tbody>'; return; }
            let html = '';
            for(let i = 0; i < backupsData.length; i++) {
                const b = backupsData[i];
                html += `<tr>
                    <td>${b.id}</td>
                    <td>${escapeHtml(b.archivo)}</td>
                    <td>${b.tamaño || 'N/A'}</td>
                    <td>${escapeHtml(b.tipo)}</td>
                    <td>${formatDate(b.fecha)}</td>
                    <td><span class="badge ${b.estado === 'completado' ? 'badge-active' : 'badge-pending'}">${b.estado || 'completado'}</span></td>
                    <td class="action-buttons"><button class="btn-action btn-view" onclick="descargarBackup(${b.id})"><i class="fas fa-download"></i></button><button class="btn-action btn-delete" onclick="eliminarBackup(${b.id})"><i class="fas fa-trash"></i></button>
                </tr>`;
            }
            tbody.innerHTML = html;
        }

        // ====================================================================
        // FUNCIONES DE PRODUCTOS - OCULTAR/MOSTRAR
        // ====================================================================
        async function cargarProductos() {
            mostrarLoading('Cargando productos...');
            try {
                let url = '/proyecto/producto/obtener_productos.php?';
                if (filtroProductosActual === 'ocultos') url += 'solo_ocultos=true';
                else if (filtroProductosActual === 'visibles') url += 'solo_visibles=true';
                else url += 'incluir_ocultos=true';
                
                const response = await fetch(url, { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success && data.productos) productsData = data.productos;
                    else if(Array.isArray(data)) productsData = data;
                    else if(data.productos && Array.isArray(data.productos)) productsData = data.productos;
                    else if(data.data && Array.isArray(data.data)) productsData = data.data;
                    else productsData = [];
                    renderProductos();
                } else { throw new Error('Error en la respuesta del servidor'); }
            } catch(e) { console.error('Error cargando productos:', e); productsData = []; renderProductos(); mostrarNotificacion('Error al cargar productos: ' + e.message, 'error'); }
            finally { ocultarLoading(); }
        }

        function renderProductos() {
            const tbody = document.getElementById('productsList');
            if(!tbody) return;
            if(!productsData || productsData.length === 0) { 
                let mensaje = filtroProductosActual === 'ocultos' ? 'No hay productos ocultos' : (filtroProductosActual === 'visibles' ? 'No hay productos visibles' : 'No hay productos registrados');
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center">${mensaje}</tbody>`;
                return; 
            }
            let html = '';
            for(let i = 0; i < productsData.length; i++) {
                const p = productsData[i];
                const estaOculto = p.active === 0;
                const rowClass = estaOculto ? 'producto-oculto-row' : '';
                let estadoText = '', estadoClass = '';
                if (estaOculto) { estadoText = 'OCULTO'; estadoClass = 'badge-oculto'; }
                else if (p.stock === 0) { estadoText = 'Agotado'; estadoClass = 'badge-inactive'; }
                else if (p.stock < 10) { estadoText = 'Stock Bajo'; estadoClass = 'badge-pending'; }
                else { estadoText = 'Disponible'; estadoClass = 'badge-active'; }
                let stockColor = estaOculto ? '#95a5a6' : (p.stock === 0 ? '#ff4757' : (p.stock < 10 ? '#ffa502' : '#2ed573'));
                html += `<tr class="${rowClass}">
                    <td style="padding: 12px 15px;">${p.id}</td>
                    <td style="padding: 12px 15px;"><strong ${estaOculto ? 'style="color:#888;"' : ''}>${escapeHtml(p.name || p.nombre)}</strong>${estaOculto ? '<br><small style="color:#aaa;"><i class="fas fa-eye-slash"></i> Oculto</small>' : ''}
                    <td style="padding: 12px 15px;">${escapeHtml(p.category || p.categoria || 'General')}
                    <td style="padding: 12px 15px;">${formatMoney(p.price || p.precio)}
                    <td style="padding: 12px 15px;"><strong style="color: ${stockColor};">${p.stock}</strong>
                    <td style="padding: 12px 15px;"><span class="badge ${estadoClass}">${estaOculto ? '<i class="fas fa-eye-slash"></i>' : (p.stock === 0 ? '<i class="fas fa-times-circle"></i>' : (p.stock < 10 ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-check-circle"></i>'))} ${estadoText}</span>
                    <td class="action-buttons" style="padding: 12px 15px;">
                        <button class="btn-action btn-view" onclick="verProducto(${p.id})" title="Ver detalles"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editarProducto(${p.id})" title="Editar"><i class="fas fa-edit"></i></button>
                        <button class="btn-action ${estaOculto ? 'btn-mostrar' : 'btn-ocultar'}" onclick="toggleOcultarProducto(${p.id}, ${estaOculto})" title="${estaOculto ? 'Mostrar producto' : 'Ocultar producto'}"><i class="fas ${estaOculto ? 'fa-eye' : 'fa-eye-slash'}"></i></button>
                    
                </tr>`;
            }
            tbody.innerHTML = html;
        }

        async function toggleOcultarProducto(id, estaOculto) {
            const accion = estaOculto ? 'mostrar' : 'ocultar';
            if(!confirm(`¿${accion === 'ocultar' ? 'Ocultar' : 'Mostrar'} este producto? Los productos ocultos no se mostrarán en la tienda.`)) return;
            mostrarLoading(`${accion === 'ocultar' ? 'Ocultando' : 'Mostrando'} producto...`);
            try {
                const response = await fetch('/proyecto/admin/eliminar_producto.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, ocultar: !estaOculto }), credentials: 'include'
                });
                const result = await response.json();
                if(result.success) { mostrarNotificacion(result.message, 'success'); await cargarProductos(); }
                else { mostrarNotificacion(result.message || 'Error al procesar', 'error'); }
            } catch(e) { console.error('Error:', e); mostrarNotificacion('Error al procesar la solicitud', 'error'); }
            finally { ocultarLoading(); }
        }

        function cambiarFiltroProductos(filtro) {
            filtroProductosActual = filtro;
            const btnTodos = document.getElementById('filtroTodos');
            const btnVisibles = document.getElementById('filtroVisibles');
            const btnOcultos = document.getElementById('filtroOcultos');
            if (btnTodos) btnTodos.className = 'btn-filtro ' + (filtro === 'todos' ? 'btn-filtro-active' : 'btn-filtro-inactive');
            if (btnVisibles) btnVisibles.className = 'btn-filtro ' + (filtro === 'visibles' ? 'btn-filtro-active' : 'btn-filtro-inactive');
            if (btnOcultos) btnOcultos.className = 'btn-filtro ' + (filtro === 'ocultos' ? 'btn-filtro-active' : 'btn-filtro-inactive');
            cargarProductos();
        }

        function mostrarTodosProductos() { cambiarFiltroProductos('todos'); }
        function mostrarProductosVisibles() { cambiarFiltroProductos('visibles'); }
        function mostrarProductosOcultos() { cambiarFiltroProductos('ocultos'); }

        // ====================================================================
        // FUNCIONES DE REPORTE GENERAL Y ESPECÍFICO
        // ====================================================================
        let ventasPorMesChart = null;
        let metodoPagoChart = null;

        async function cargarReporteGeneral() {
            mostrarLoading('Generando reporte ejecutivo...');
            try {
                const response = await fetch('/proyecto/reportes/reporte_general_ejecutivo.php', { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success) {
                        document.getElementById('kpiVentasTotales').innerHTML = formatMoney(data.ventas_totales);
                        document.getElementById('kpiVentasMes').innerHTML = formatMoney(data.ventas_mes);
                        document.getElementById('kpiVentasSemana').innerHTML = formatMoney(data.ventas_semana);
                        document.getElementById('kpiTicketPromedio').innerHTML = formatMoney(data.ticket_promedio);
                        document.getElementById('kpiClientesActivos').innerHTML = data.clientes_activos || 0;
                        const crecimientoEl = document.getElementById('kpiCrecimiento');
                        if(data.crecimiento > 0) crecimientoEl.innerHTML = `<span class="tendencia-up">+${data.crecimiento}% <i class="fas fa-arrow-up"></i></span>`;
                        else if(data.crecimiento < 0) crecimientoEl.innerHTML = `<span class="tendencia-down">${data.crecimiento}% <i class="fas fa-arrow-down"></i></span>`;
                        else crecimientoEl.innerHTML = `0%`;
                        
                        const meses = (data.ventas_por_mes || []).map(d => d.mes);
                        const montos = (data.ventas_por_mes || []).map(d => d.total);
                        const ctxVentas = document.getElementById('ventasPorMesChart');
                        if(ventasPorMesChart) ventasPorMesChart.destroy();
                        if(ctxVentas && meses.length > 0) {
                            ventasPorMesChart = new Chart(ctxVentas, {
                                type: 'line', data: { labels: meses, datasets: [{ label: 'Ventas (Bs.)', data: montos, borderColor: '#3C91ED', backgroundColor: 'rgba(60,145,237,0.1)', borderWidth: 3, fill: true, tension: 0.4 }] },
                                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: (ctx) => `Bs. ${ctx.raw.toLocaleString()}` } } }, scales: { y: { beginAtZero: true, ticks: { callback: (value) => `Bs. ${value.toLocaleString()}` } } } }
                            });
                        }
                        
                        const metodos = (data.metodos_pago || []).map(d => d.metodo_pago);
                        const montosMetodos = (data.metodos_pago || []).map(d => d.total);
                        const ctxMetodos = document.getElementById('metodoPagoChart');
                        if(metodoPagoChart) metodoPagoChart.destroy();
                        if(ctxMetodos && metodos.length > 0) {
                            metodoPagoChart = new Chart(ctxMetodos, {
                                type: 'doughnut', data: { labels: metodos, datasets: [{ data: montosMetodos, backgroundColor: ['#2ed573','#3498db','#9b59b6','#f39c12','#e74c3c','#1abc9c'], borderWidth: 0 }] },
                                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${formatMoney(ctx.raw)}` } } } }
                            });
                        }
                        
                        const topProductosDiv = document.getElementById('topProductosList');
                        if(topProductosDiv && data.top_productos?.length > 0) {
                            let topHtml = '';
                            for(let idx = 0; idx < data.top_productos.length; idx++) {
                                const p = data.top_productos[idx];
                                topHtml += `<div class="top-item"><div class="top-number">${idx+1}</div><div class="top-name">${escapeHtml(p.nombre)}</div><div class="top-value">${p.unidades || 0} unidades</div><div style="margin-left:10px; font-size:0.8rem;">${formatMoney(p.ingresos)}</div></div>`;
                            }
                            topProductosDiv.innerHTML = topHtml;
                        } else if(topProductosDiv) { topProductosDiv.innerHTML = '<div style="text-align:center; padding:20px;">No hay datos disponibles</div>'; }
                        
                        const topClientesDiv = document.getElementById('topClientesList');
                        if(topClientesDiv && data.top_clientes?.length > 0) {
                            let topHtml = '';
                            for(let idx = 0; idx < data.top_clientes.length; idx++) {
                                const c = data.top_clientes[idx];
                                topHtml += `<div class="top-item"><div class="top-number">${idx+1}</div><div class="top-name">${escapeHtml(c.nombre)}</div><div class="top-value">${c.total_compras || 0} compras</div><div style="margin-left:10px; font-size:0.8rem;">${formatMoney(c.monto_total)}</div></div>`;
                            }
                            topClientesDiv.innerHTML = topHtml;
                        } else if(topClientesDiv) { topClientesDiv.innerHTML = '<div style="text-align:center; padding:20px;">No hay datos disponibles</div>'; }
                        
                        const topVendedoresDiv = document.getElementById('topVendedoresList');
                        if(topVendedoresDiv && data.top_vendedores?.length > 0) {
                            let topHtml = '';
                            for(let idx = 0; idx < data.top_vendedores.length; idx++) {
                                const v = data.top_vendedores[idx];
                                topHtml += `<div class="top-item"><div class="top-number">${idx+1}</div><div class="top-name">${escapeHtml(v.nombre)}</div><div class="top-value">${v.total_ventas || 0} ventas</div><div style="margin-left:10px; font-size:0.8rem;">${formatMoney(v.monto_total)}</div></div>`;
                            }
                            topVendedoresDiv.innerHTML = topHtml;
                        } else if(topVendedoresDiv) { topVendedoresDiv.innerHTML = '<div style="text-align:center; padding:20px;">No hay datos disponibles</div>'; }
                    } else { mostrarNotificacion(data.message || 'Error al cargar reporte', 'error'); }
                }
            } catch(e) { console.error('Error cargando reporte general:', e); mostrarNotificacion('Error al cargar reporte general', 'error'); }
            finally { ocultarLoading(); }
        }

        async function cargarReporteEspecifico() {
            const fechaDesde = document.getElementById('espFechaDesde').value;
            const fechaHasta = document.getElementById('espFechaHasta').value;
            const tipo = document.getElementById('espTipoReporte').value;
            const estado = document.getElementById('espEstado').value;
            const buscar = document.getElementById('espBuscar').value;
            
            if(!fechaDesde || !fechaHasta) { mostrarNotificacion('Selecciona ambas fechas', 'warning'); return; }
            
            mostrarLoading('Cargando reporte específico...');
            try {
                const params = new URLSearchParams();
                params.append('desde', fechaDesde); 
                params.append('hasta', fechaHasta);
                if(tipo) params.append('tipo', tipo);
                if(estado) params.append('estado', estado);
                if(buscar) params.append('buscar', buscar);
                
                const response = await fetch(`/proyecto/reportes/reporte_especifico.php?${params.toString()}`, { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success) {
                        const datos = data.data || [];
                        const tbody = document.getElementById('espTablaBody');
                        const thead = document.getElementById('espTablaHeaders');
                        if(!tbody || !thead) return;
                        
                        if(datos.length === 0) { 
                            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center">No se encontraron resultados</tbody>'; 
                            return; 
                        }
                        
                        let headers = '';
                        let rows = '';
                        
                        thead.innerHTML = '';
                        
                        if(tipo === 'ventas') {
                            headers = '<th>ID</th><th>Fecha</th><th>Factura</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Estado</th><th>Método Pago</th>';
                            for(let i = 0; i < datos.length; i++) {
                                const d = datos[i];
                                rows += `<tr>
                                    <td style="padding: 12px 15px;">${d.id}</td>
                                    <td style="padding: 12px 15px;">${formatDate(d.fecha)}</td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.numero_factura || '-')}</td>
                                    <td style="padding: 12px 15px;"><strong>${escapeHtml(d.cliente_nombre || d.cliente || 'N/A')}</strong></td>
                                    <td style="padding: 12px 15px;"><span class="badge" style="background:#9b59b6;">${escapeHtml(d.vendedor_nombre || 'Administrador')}</span></td>
                                    <td style="padding: 12px 15px;">${formatMoney(d.total)}</td>
                                    <td style="padding: 12px 15px;">${getEstadoBadge(d.estado)}</td>
                                    <td style="padding: 12px 15px;">${getMetodoPagoBadge(d.metodo_pago)}</td>
                                </tr>`;
                            }
                        } else if(tipo === 'pedidos') {
                            headers = '<th>ID</th><th>Fecha</th><th>N° Pedido</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Método Pago</th><th>Productos</th>';
                            for(let i = 0; i < datos.length; i++) {
                                const d = datos[i];
                                rows += `<tr>
                                    <td style="padding: 12px 15px;">${d.id}</td>
                                    <td style="padding: 12px 15px;">${formatDate(d.fecha)}</td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.numero_pedido)}</td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.cliente_nombre || 'N/A')}</td>
                                    <td style="padding: 12px 15px;">${formatMoney(d.total)}</td>
                                    <td style="padding: 12px 15px;">${getEstadoBadge(d.estado)}</td>
                                    <td style="padding: 12px 15px;">${getMetodoPagoBadge(d.metodo_pago)}</td>
                                    <td style="padding: 12px 15px;">${d.total_productos || 0}</td>
                                </tr>`;
                            }
                        } else if(tipo === 'compras') {
                            headers = '<th>ID</th><th>Fecha</th><th>N° Orden</th><th>Proveedor</th><th>Total</th><th>Estado</th>';
                            for(let i = 0; i < datos.length; i++) {
                                const d = datos[i];
                                rows += `<tr>
                                    <td style="padding: 12px 15px;">${d.id}</td>
                                    <td style="padding: 12px 15px;">${formatDate(d.fecha)}</td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.numero_orden)}</td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.proveedor_nombre)}</td>
                                    <td style="padding: 12px 15px;">${formatMoney(d.total)}</td>
                                    <td style="padding: 12px 15px;">${getEstadoBadge(d.estado)}</td>
                                </tr>`;
                            }
                        } else if(tipo === 'clientes') {
                            headers = '<th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Compras</th><th>Monto Total</th>';
                            for(let i = 0; i < datos.length; i++) {
                                const d = datos[i];
                                rows += `<tr>
                                    <td style="padding: 12px 15px;">${d.id}</td>
                                    <td style="padding: 12px 15px;"><strong>${escapeHtml(d.nombre)}</strong></td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.email)}</td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.telefono || 'N/A')}</td>
                                    <td style="padding: 12px 15px;">${d.total_compras || 0}</td>
                                    <td style="padding: 12px 15px;">${formatMoney(d.total_monto || 0)}</td>
                                </tr>`;
                            }
                        } else if(tipo === 'productos') {
                            headers = '<th>ID</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Unidades Vendidas</th><th>Ingresos</th>';
                            for(let i = 0; i < datos.length; i++) {
                                const d = datos[i];
                                rows += `<tr>
                                    <td style="padding: 12px 15px;">${d.id}</td>
                                    <td style="padding: 12px 15px;"><strong>${escapeHtml(d.nombre)}</strong></td>
                                    <td style="padding: 12px 15px;">${escapeHtml(d.categoria || 'General')}</td>
                                    <td style="padding: 12px 15px;">${formatMoney(d.precio)}</td>
                                    <td style="padding: 12px 15px;">${d.stock_actual || 0}</td>
                                    <td style="padding: 12px 15px;">${d.unidades_vendidas || 0}</td>
                                    <td style="padding: 12px 15px;">${formatMoney(d.ingresos || 0)}</td>
                                </tr>`;
                            }
                        }
                        
                        thead.innerHTML = `<tr>${headers}<table>`;
                        tbody.innerHTML = rows;
                        
                        document.getElementById('espTotalRegistros').innerHTML = data.total_registros || 0;
                        document.getElementById('espTotalMonto').innerHTML = formatMoney(data.total_monto || 0);
                        document.getElementById('espPromedioMonto').innerHTML = formatMoney(data.promedio || 0);
                        document.getElementById('espResumenCards').style.display = 'grid';
                        
                        let tituloTipo = { ventas: 'Ventas', pedidos: 'Pedidos', compras: 'Compras', clientes: 'Clientes', productos: 'Productos' }[tipo] || tipo;
                        document.getElementById('espTituloReporte').innerHTML = `- ${tituloTipo}`;
                    } else { mostrarNotificacion(data.message || 'Error al cargar reporte', 'error'); }
                } else { throw new Error('Error en la respuesta del servidor'); }
            } catch(e) { console.error('Error cargando reporte específico:', e); mostrarNotificacion('Error al cargar reporte', 'error'); }
            finally { ocultarLoading(); }
        }

        // ====================================================================
        // FUNCIONES DE ACCIÓN Y UTILIDADES
        // ====================================================================
        function verProducto(id) { window.location.href = `/proyecto/producto/detalles_producto.php?id=${id}`; }
        function editarProducto(id) { window.location.href = `/proyecto/producto/editar_producto.php?id=${id}`; }
        function eliminarProducto(id) { if(confirm('¿Eliminar este producto?')) fetch('/proyecto/admin/eliminar_producto.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }), credentials: 'include' }).then(() => { mostrarNotificacion('Producto eliminado', 'success'); cargarProductos(); }).catch(() => mostrarNotificacion('Error al eliminar', 'error')); }
        function verUsuario(id) { window.location.href = `/proyecto/admin/detalles_usuarios.php?id=${id}`; }
        function editarUsuario(id) { window.location.href = `/proyecto/admin/editar_usuario.php?id=${id}`; }
        function eliminarUsuario(id) { if(confirm('¿Eliminar este usuario?')) fetch('/proyecto/admin/eliminar_usuario.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }), credentials: 'include' }).then(() => { mostrarNotificacion('Usuario eliminado', 'success'); cargarUsuarios(); }).catch(() => mostrarNotificacion('Error al eliminar', 'error')); }
        function editarProveedor(id) { window.location.href = `/proyecto/proveedores/editar_proveedor.php?id=${id}`; }
        function eliminarProveedor(id) { if(confirm('¿Eliminar este proveedor?')) fetch('/proyecto/proveedores/eliminar_proveedor.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }), credentials: 'include' }).then(() => { mostrarNotificacion('Proveedor eliminado', 'success'); cargarProveedores(); }).catch(() => mostrarNotificacion('Error al eliminar', 'error')); }
        function verCompra(id) { window.location.href = `/proyecto/compras/ver_compra.php?id=${id}`; }
        function exportarCompraPDF(id) { window.open(`/proyecto/compras/generar_pdf_compra.php?id=${id}`, '_blank'); }
        function verFactura(id) { window.open(`/proyecto/facturacion/ver_factura.php?id=${id}`, '_blank'); }
        function exportarFacturaPDF(id) { window.open(`/proyecto/facturacion/generar_pdf_factura.php?id=${id}`, '_blank'); }
        function verDetalleCliente(id) { window.location.href = `/proyecto/clientes/detalle_cliente.php?id=${id}`; }
        function editarPedido(pedidoId) { window.location.href = `/proyecto/proceso_compra/editar_pedido.php?id=${pedidoId}`; }
        function exportarPedidoPDF(pedidoId) { if(pedidoId) window.open(`/proyecto/producto/generar_pdf_pedido.php?id=${pedidoId}`, '_blank'); }
        function nuevaFactura() { window.location.href = '/proyecto/facturacion/nueva_factura.php'; }
        function nuevaCompra() { window.location.href = '/proyecto/compras/nueva_compra.php'; }
        function exportarReporteGeneralPDF() { window.open('/proyecto/reportes/exportar_reporte_general_pdf.php', '_blank'); }
        function exportarReporteGeneralExcel() { window.open('/proyecto/reportes/exportar_reporte_general_excel.php', '_blank'); }
        
        async function crearBackup() {
            mostrarLoading('Creando copia de seguridad...');
            try {
                const response = await fetch('/proyecto/backups/crear_backup.php', { method: 'POST', credentials: 'include' });
                const data = await response.json();
                if(data.success) { mostrarNotificacion('Backup creado correctamente', 'success'); cargarBackups(); }
                else { mostrarNotificacion(data.message || 'Error al crear backup', 'error'); }
            } catch(e) { mostrarNotificacion('Error al crear backup', 'error'); }
            finally { ocultarLoading(); }
        }

        async function enviarRecomendacionesMasivo() {
            if (!confirm('¿Enviar recomendaciones a todos los clientes con compras previas?')) return;
            mostrarLoading('Enviando recomendaciones...');
            try {
                const response = await fetch('/proyecto/admin/enviar_recomendaciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ accion: 'recomendar_masivo' })
                });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion(data.message || 'Recomendaciones enviadas', 'success');
                    cargarMarketing();
                } else {
                    mostrarNotificacion(data.message || 'Error al enviar', 'error');
                }
            } catch (e) {
                mostrarNotificacion('Error al enviar recomendaciones', 'error');
            } finally { ocultarLoading(); }
        }

        function descargarBackup(id) { window.open(`/proyecto/backups/descargar_backup.php?id=${id}`, '_blank'); }
        
        async function eliminarBackup(id) {
            if(!confirm('¿Eliminar esta copia de seguridad?')) return;
            try {
                const response = await fetch('/proyecto/backups/eliminar_backup.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }), credentials: 'include' });
                const data = await response.json();
                if(data.success) { mostrarNotificacion('Backup eliminado', 'success'); cargarBackups(); }
                else { mostrarNotificacion(data.message || 'Error al eliminar', 'error'); }
            } catch(e) { mostrarNotificacion('Error al eliminar backup', 'error'); }
        }
        
        async function verDetallePedido(pedidoId) {
            currentDetallePedido = null;
            window.currentProductosList = [];
            mostrarLoading('Cargando detalles del pedido...');
            try {
                const response = await fetch(`/proyecto/proceso_compra/obtener_detalles_pedido.php?id=${pedidoId}`, { credentials: 'include' });
                if(response.ok) {
                    const data = await response.json();
                    if(data.success) {
                        currentDetallePedido = data.pedido;
                        if (data.productos && Array.isArray(data.productos)) {
                            currentDetallePedido.productos = data.productos;
                            window.currentProductosList = data.productos;
                        } else {
                            currentDetallePedido.productos = [];
                            window.currentProductosList = [];
                        }
                        renderDetallePedidoModal();
                        document.getElementById('detallePedidoModal').style.display = 'flex';
                    } else { mostrarNotificacion(data.message || 'Error al cargar detalles del pedido', 'error'); }
                } else { throw new Error('Error en la respuesta del servidor'); }
            } catch(e) { console.error('Error:', e); mostrarNotificacion('Error al cargar detalles del pedido', 'error'); }
            finally { ocultarLoading(); }
        }
        
        function renderDetallePedidoModal() {
            const container = document.getElementById('detallePedidoContent');
            if(!container || !currentDetallePedido) return;
            const pedido = currentDetallePedido;
            let productos = pedido.productos || window.currentProductosList || pedido.data || [];
            if (!Array.isArray(productos)) productos = [];
            let productosHtml = '';
            if(productos.length === 0) productosHtml = '<tr><td colspan="4" style="text-align:center; padding:40px;">No hay productos registrados</td></tr>';
            else {
                for(let idx = 0; idx < productos.length; idx++) {
                    const prod = productos[idx];
                    if (!prod) continue;
                    let nombre = prod.nombre || prod.producto_nombre || prod.product_name || prod.name || `Producto #${idx+1}`;
                    let cantidad = parseInt(prod.cantidad) || 1;
                    let precio = parseFloat(prod.precio_unitario || prod.precio || 0);
                    let subtotal = parseFloat(prod.subtotal || (precio * cantidad) || 0);
                    productosHtml += `<tr><td style="padding:12px;"><strong>${escapeHtml(nombre)}</strong>${prod.sku ? `<br><small>SKU: ${escapeHtml(prod.sku)}</small>` : ''}</td><td style="padding:12px; text-align:center;"><span style="background:#3C91ED; color:white; padding:4px 12px; border-radius:20px;">${cantidad}</span></td><td style="padding:12px; text-align:right;">${formatMoney(precio)}</td><td style="padding:12px; text-align:right;"><strong>${formatMoney(subtotal)}</strong></td></tr>`;
                }
            }
            const fechaFormateada = pedido.fecha_creacion_formateada || formatDateTime(pedido.created_at);
            const estado = (pedido.estado || 'pendiente').toLowerCase();
            let estadoBadge = estado === 'pendiente' ? '<span class="badge badge-pending"><i class="fas fa-clock"></i> Pendiente</span>' : (estado === 'completado' ? '<span class="badge badge-completed"><i class="fas fa-check-double"></i> Completado</span>' : (estado === 'cancelado' ? '<span class="badge badge-inactive"><i class="fas fa-times-circle"></i> Cancelado</span>' : `<span class="badge">${escapeHtml(estado)}</span>`));
            const html = `<div class="factura-header"><i class="fas fa-shopping-cart" style="font-size:2rem;"></i><h3>Detalle del Pedido</h3><p><strong>N° Pedido:</strong> ${escapeHtml(pedido.numero_pedido)}</p><p><strong>Fecha:</strong> ${fechaFormateada}</p></div>
            <div class="cliente-info"><h4><i class="fas fa-user"></i> Información del Cliente</h4><div class="info-row"><span class="info-label">Nombre:</span><span>${escapeHtml(pedido.cliente_nombre || 'N/A')}</span></div><div class="info-row"><span class="info-label">Email:</span><span>${escapeHtml(pedido.cliente_email || 'N/A')}</span></div><div class="info-row"><span class="info-label">Teléfono:</span><span>${escapeHtml(pedido.cliente_telefono || 'N/A')}</span></div></div>
            <h4><i class="fas fa-boxes"></i> Productos</h4><div style="overflow-x:auto;"><table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#3C91ED; color:white;"><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr></thead><tbody>${productosHtml}</tbody></table></div>
            <div style="text-align:right; padding:20px; background:#f8f9fa; margin-top:15px; border-radius:8px;"><div style="padding:5px 0; color:#050C18;"><strong>Subtotal:</strong> ${formatMoney(pedido.subtotal)}</div><div style="padding:5px 0; color:#050C18;"><strong>IVA (16%):</strong> ${formatMoney(pedido.iva)}</div><div style="font-size:1.2rem; border-top:2px solid #3C91ED; margin-top:10px; padding-top:10px; color:#050C18; font-weight:bold;"><strong>TOTAL:</strong> ${formatMoney(pedido.total)}</div></div>
            <div style="margin-top:15px;"><h4><i class="fas fa-info-circle"></i> Información Adicional</h4><div class="info-row"><span class="info-label">Estado:</span><span>${estadoBadge}</span></div><div class="info-row"><span class="info-label">Método de Pago:</span><span>${getMetodoPagoBadge(pedido.metodo_pago)}</span></div></div>`;
            container.innerHTML = html;
        }
        
        function imprimirDetallePedido() {
            const content = document.getElementById('detallePedidoContent');
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`<html><head><title>Detalle del Pedido</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>body{font-family:Arial;margin:20px}.factura-header{background:#050C18;color:white;padding:20px;border-radius:10px;text-align:center}.cliente-info{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:10px}.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #eee}.badge{padding:4px 10px;border-radius:20px;font-size:0.7rem;font-weight:600;display:inline-block}.badge-pending{background:#ffa502}.badge-completed{background:#2ed573}.badge-inactive{background:#ff4757}@media print{body{margin:0}.no-print{display:none}}</style></head><body>${content.innerHTML}<div class="no-print" style="text-align:center;margin-top:20px;"><button onclick="window.print()">Imprimir</button><button onclick="window.close()">Cerrar</button></div><script>window.onload=function(){setTimeout(function(){window.print();},500)}<\/script></body></html>`);
            printWindow.document.close();
        }
        
        function imprimirDetalle() {
            const content = document.getElementById('detalleModalContent');
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`<html><head><title>Detalle</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>body{font-family:Arial;margin:20px}.factura-header{background:#050C18;color:#fff;padding:20px;border-radius:10px;text-align:center}.cliente-info,.factura-info{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:10px}.productos-table{width:100%;border-collapse:collapse}.productos-table th,.productos-table td{padding:10px;border-bottom:1px solid #ddd;text-align:left}.totales{text-align:right;padding:15px;background:#f5f5f5;border-radius:8px}.total-grande{font-size:1.2em;margin-top:10px;padding-top:10px;border-top:2px solid #3C91ED}</style></head><body>${content.innerHTML}<div style="text-align:center;margin-top:20px"><button onclick="window.print()">Imprimir</button><button onclick="window.close()">Cerrar</button></div><script>window.onload=function(){setTimeout(function(){window.print();},500)}<\/script></body></html>`);
            printWindow.document.close();
        }
        
        function exportarHistorialExcel() {
            if(!historialData || historialData.length === 0) { mostrarNotificacion('No hay datos para exportar', 'warning'); return; }
            const headers = ['ID Pedido','Cliente','Email','Teléfono','Fecha','Método Pago','Total','Productos','Estado'];
            const rows = [];
            for(let i = 0; i < historialData.length; i++) {
                const p = historialData[i];
                rows.push([p.id, p.cliente_nombre || 'N/A', p.cliente_email || 'N/A', p.cliente_telefono || 'N/A', formatDate(p.fecha_raw), p.metodo_pago || 'No especificado', p.total || 0, p.total_productos || 0, p.estado || 'pendiente']);
            }
            const csvContent = [headers, ...rows].map(row => row.join(',')).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `historial_compras_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link); link.click(); document.body.removeChild(link); URL.revokeObjectURL(url);
            mostrarNotificacion('Exportación completada', 'success');
        }
        
        function exportarStockExcel() {
            if(!reporteStockData || reporteStockData.length === 0) { mostrarNotificacion('No hay datos para exportar', 'warning'); return; }
            const headers = ['ID', 'Producto', 'Categoría', 'Precio', 'Stock Actual', 'Estado', 'Veces Vendido', 'Unidades Vendidas'];
            const rows = [];
            for(let i = 0; i < reporteStockData.length; i++) {
                const p = reporteStockData[i];
                rows.push([p.id, p.nombre, p.categoria, p.precio, p.stock, p.stock === 0 ? 'Agotado' : (p.stock <= 5 ? 'Crítico' : (p.stock <= 10 ? 'Bajo' : (p.stock <= 20 ? 'Medio' : 'Alto'))), p.veces_vendido || 0, p.unidades_vendidas || 0]);
            }
            const csvContent = [headers, ...rows].map(row => row.join(',')).join('\n');
            const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `reporte_stock_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link); link.click(); document.body.removeChild(link); URL.revokeObjectURL(url);
            mostrarNotificacion('Reporte exportado correctamente', 'success');
        }
        
        function exportarReporteEspecificoPDF() { 
            const params = new URLSearchParams(); 
            params.append('desde', document.getElementById('espFechaDesde').value); 
            params.append('hasta', document.getElementById('espFechaHasta').value); 
            params.append('tipo', document.getElementById('espTipoReporte').value); 
            params.append('estado', document.getElementById('espEstado').value); 
            params.append('buscar', document.getElementById('espBuscar').value); 
            window.open(`/proyecto/reportes/exportar_especifico_pdf.php?${params.toString()}`, '_blank'); 
        }
        
        function exportarReporteEspecificoExcel() { 
            const params = new URLSearchParams(); 
            params.append('desde', document.getElementById('espFechaDesde').value); 
            params.append('hasta', document.getElementById('espFechaHasta').value); 
            params.append('tipo', document.getElementById('espTipoReporte').value); 
            params.append('estado', document.getElementById('espEstado').value); 
            params.append('buscar', document.getElementById('espBuscar').value); 
            window.open(`/proyecto/reportes/exportar_especifico_excel.php?${params.toString()}`, '_blank'); 
        }
        
        async function facturarPedidosSeleccionados() {
            const seleccionados = [];
            document.querySelectorAll('.pedidoCheckbox:checked').forEach(cb => seleccionados.push(cb.value));
            if(seleccionados.length === 0) { mostrarNotificacion('Selecciona al menos un pedido', 'warning'); return; }
            mostrarLoading('Facturando pedidos...');
            try {
                const response = await fetch('/proyecto/facturacion/facturar_pedidos.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ pedidos: seleccionados }), credentials: 'include' });
                const result = await response.json();
                if(result.success) { mostrarNotificacion('Pedidos facturados', 'success'); cargarPedidos(); cargarFacturas(); }
                else { mostrarNotificacion(result.message || 'Error al facturar', 'error'); }
            } catch(e) { mostrarNotificacion('Error al facturar', 'error'); }
            finally { ocultarLoading(); }
        }
        
        async function abrirCaja(data) {
            try {
                const response = await fetch('/proyecto/caja/abrir_caja.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data), credentials: 'include' });
                const result = await response.json();
                if(result.success) { mostrarNotificacion('Caja abierta', 'success'); cerrarModales(); cargarCaja(); }
                else { mostrarNotificacion(result.message || 'Error al abrir caja', 'error'); }
            } catch(e) { mostrarNotificacion('Error al abrir caja', 'error'); }
        }
        
        async function cerrarCaja() {
            if(!confirm('¿Cerrar caja? Se generará un arqueo.')) return;
            try {
                const response = await fetch('/proyecto/caja/cerrar_caja.php', { method: 'POST', credentials: 'include' });
                const result = await response.json();
                if(result.success) { mostrarNotificacion('Caja cerrada', 'success'); cargarCaja(); }
                else { mostrarNotificacion(result.message || 'Error al cerrar caja', 'error'); }
            } catch(e) { mostrarNotificacion('Error al cerrar caja', 'error'); }
        }
        
        async function registrarMovimiento(data) {
            try {
                const response = await fetch('/proyecto/caja/registrar_movimiento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data), credentials: 'include' });
                const result = await response.json();
                if(result.success) { mostrarNotificacion('Movimiento registrado', 'success'); cerrarModales(); cargarCaja(); }
                else { mostrarNotificacion(result.message || 'Error al registrar movimiento', 'error'); }
            } catch(e) { mostrarNotificacion('Error al registrar movimiento', 'error'); }
        }
        
        async function crearUsuario(data) {
            try {
                const response = await fetch('/proyecto/admin/crear_usuario.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data), credentials: 'include' });
                if(response.ok) { mostrarNotificacion('Usuario creado', 'success'); cerrarModales(); cargarUsuarios(); }
                else throw new Error();
            } catch(e) { mostrarNotificacion('Error al crear usuario', 'error'); }
        }
        
        async function crearProveedor(data) {
            try {
                const response = await fetch('/proyecto/proveedores/crear_proveedor.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data), credentials: 'include' });
                if(response.ok) { mostrarNotificacion('Proveedor creado', 'success'); cerrarModales(); cargarProveedores(); }
                else throw new Error();
            } catch(e) { mostrarNotificacion('Error al crear proveedor', 'error'); }
        }
        
        async function solicitarTokenRecuperacion(event) {
            event.preventDefault();
            const email = document.getElementById('recuperacionEmail').value.trim();
            if(!email) { mostrarNotificacion('Ingresa tu correo electrónico', 'warning'); return; }
            mostrarLoading('Enviando código de verificación...');
            try {
                const response = await fetch('/proyecto/usuarios/solicitar_token.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email: email }), credentials: 'include' });
                const data = await response.json();
                if(data.success) { emailRecuperacion = email; mostrarNotificacion(data.message || 'Código enviado a tu correo', 'success'); cerrarModales(); document.getElementById('verificarPinModal').style.display = 'flex'; document.getElementById('pinToken').value = ''; }
                else { mostrarNotificacion(data.message || 'Error al solicitar código', 'error'); }
            } catch(e) { console.error('Error:', e); mostrarNotificacion('Error al solicitar código de recuperación', 'error'); }
            finally { ocultarLoading(); }
        }
        
async function verificarPin(event) {
    event.preventDefault();
    const pin = document.getElementById('pinToken').value.trim();
    if(!pin) { mostrarNotificacion('Ingresa el código de verificación', 'warning'); return; }
    if(!emailRecuperacion) { mostrarNotificacion('Correo no encontrado. Solicita un nuevo código', 'error'); cerrarModales(); document.getElementById('recuperacionModal').style.display = 'flex'; return; }
    mostrarLoading('Verificando código...');
    try {
        const response = await fetch('/proyecto/usuarios/verificar_pin.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email: emailRecuperacion, pin: pin }), credentials: 'include' });
        const data = await response.json();
        if(data.success) { 
            mostrarNotificacion('Código verificado correctamente', 'success'); 
            cerrarModales(); 
            document.getElementById('cambiarPasswordRecuperacionModal').style.display = 'flex'; 
            document.getElementById('nuevaPasswordRecuperacion').value = ''; 
            document.getElementById('confirmarPasswordRecuperacion').value = ''; 
        } else { 
            mostrarNotificacion(data.message || 'Código inválido o expirado', 'error'); 
        }
    } catch(e) { 
        console.error('Error:', e); 
        mostrarNotificacion('Error al verificar el código', 'error'); 
    } finally { 
        ocultarLoading(); 
    }
}
        
async function cambiarPasswordRecuperacion(event) {
    event.preventDefault();
    const nuevaPassword = document.getElementById('nuevaPasswordRecuperacion').value.trim();
    const confirmarPassword = document.getElementById('confirmarPasswordRecuperacion').value.trim();
    if(!nuevaPassword) { mostrarNotificacion('Ingresa una nueva contraseña', 'warning'); return; }
    if(!confirmarPassword) { mostrarNotificacion('Confirma tu nueva contraseña', 'warning'); return; }
    if(nuevaPassword !== confirmarPassword) { mostrarNotificacion('Las contraseñas no coinciden', 'warning'); return; }
    if(nuevaPassword.length < 6) { mostrarNotificacion('La contraseña debe tener al menos 6 caracteres', 'warning'); return; }
    if(!emailRecuperacion) { mostrarNotificacion('Error: Correo no encontrado', 'error'); cerrarModales(); return; }
    mostrarLoading('Cambiando contraseña...');
    try {
        const response = await fetch('/proyecto/usuarios/recuperacion_contraseña.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email: emailRecuperacion, newPassword: nuevaPassword }), credentials: 'include' });
        const data = await response.json();
        if(data.success) { 
            mostrarNotificacion('Contraseña cambiada exitosamente', 'success'); 
            cerrarModales(); 
            emailRecuperacion = null; 
            setTimeout(() => { 
                if(confirm('Contraseña cambiada correctamente. ¿Deseas iniciar sesión?')) 
                    window.location.href = '/proyecto/interfaz_usuario/login.html'; 
            }, 1000); 
        } else { 
            mostrarNotificacion(data.message || 'Error al cambiar la contraseña', 'error'); 
        }
    } catch(e) { 
        console.error('Error:', e); 
        mostrarNotificacion('Error al cambiar la contraseña', 'error'); 
    } finally { 
        ocultarLoading(); 
    }
}
        
        async function eliminarCuenta() {
            const password = document.getElementById('deleteAccountPassword').value;
            if(!password) { mostrarNotificacion('Ingresa tu contraseña para confirmar', 'warning'); return; }
            if(!confirm('¡ADVERTENCIA! Esta acción es irreversible. ¿Estás seguro de eliminar tu cuenta?')) return;
            mostrarLoading('Eliminando cuenta...');
            try {
                const response = await fetch('/proyecto/usuarios/eliminar_cuenta.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ password }), credentials: 'include' });
                const data = await response.json();
                if(data.success) { mostrarNotificacion('Cuenta eliminada. Cerrando sesión...', 'success'); setTimeout(() => { window.location.href = '/proyecto/interfaz_usuario/login.html'; }, 2000); }
                else { mostrarNotificacion(data.message || 'Error al eliminar cuenta', 'error'); }
            } catch(e) { mostrarNotificacion('Error al eliminar cuenta', 'error'); }
            finally { ocultarLoading(); }
        }

        // ====================================================================
        // IA PREDICTIVA - FUNCIONES
        // ====================================================================
        async function cargarPredicciones() {
            try {
                const response = await fetch('/proyecto/predicciones/obtener_predicciones.php?tipo=general', { credentials: 'include' });
                const data = await response.json();
                if (!data.success) throw new Error('Error al cargar predicciones');

                if (data.migracion_pendiente) {
                    document.getElementById('prediccionesBody').innerHTML = `<tr><td colspan="10" style="text-align:center;padding:30px">
                        <i class="fas fa-database" style="font-size:2rem;color:var(--warning);display:block;margin-bottom:10px"></i>
                        <p style="color:var(--warning)">${data.mensaje_migracion || 'Migración pendiente'}</p>
                        <small style="color:#aaa">Ejecute el archivo sql/migracion_nuevas_funcionalidades.sql en su base de datos</small>
                    </td></tr>`;
                    document.getElementById('predPrecision').textContent = '--';
                    document.getElementById('predConfianza').textContent = '--';
                    document.getElementById('predSubiendo').textContent = '--';
                    document.getElementById('predBajando').textContent = '--';
                    document.getElementById('alertasStockList').innerHTML = '<div style="text-align:center;padding:20px;color:#aaa">Migración pendiente</div>';
                    document.getElementById('recomendacionesList').innerHTML = '<div style="text-align:center;padding:20px;color:#aaa">Migración pendiente</div>';
                    return;
                }

                if (data.resumen) {
                    document.getElementById('predPrecision').textContent = (data.resumen.precision_general || 0) + '%';
                    document.getElementById('predConfianza').textContent = (data.resumen.confianza_general || 0) + '%';
                    document.getElementById('predSubiendo').textContent = data.resumen.productos_subiendo || 0;
                    document.getElementById('predBajando').textContent = data.resumen.productos_bajando || 0;
                }

                const alertas = data.conteo_alertas || {};
                const totalAlertas = alertas.total_alertas || 0;
                const criticas = alertas.criticas || 0;
                if (criticas > 0) {
                    document.getElementById('predSubiendo').parentElement.style.borderLeft = '4px solid var(--danger)';
                }

                const tbody = document.getElementById('prediccionesBody');
                if (data.productos && data.productos.length > 0) {
                    tbody.innerHTML = data.productos.map(p => {
                        const tendenciaIcon = p.tendencia === 'subiendo' ? '<i class="fas fa-arrow-up" style="color:#2ed573"></i>' :
                            p.tendencia === 'bajando' ? '<i class="fas fa-arrow-down" style="color:#ff4757"></i>' :
                            '<i class="fas fa-minus" style="color:#ffa502"></i>';
                        const estadoClass = p.estado_stock === 'agotado' || p.estado_stock === 'critico' ? 'badge-inactive' :
                            p.estado_stock === 'bajo' ? 'badge-pending' : 'badge-active';
                        const estadoLabel = p.estado_stock === 'agotado' ? 'Agotado' :
                            p.estado_stock === 'critico' ? 'Crítico' :
                            p.estado_stock === 'bajo' ? 'Bajo' :
                            p.estado_stock === 'exceso' ? 'Exceso' : 'Normal';
                        return `<tr>
                            <td>${escapeHtml(p.name)}</td>
                            <td>${escapeHtml(p.sku)}</td>
                            <td>${escapeHtml(p.category)}</td>
                            <td><strong>${p.stock}</strong></td>
                            <td>${(+p.ventas_esperadas).toFixed(0)}</td>
                            <td>${p.stock_sugerido}</td>
                            <td>${tendenciaIcon} ${p.tendencia}</td>
                            <td>${p.confianza}%</td>
                            <td>${p.dias_para_agotar > 0 ? p.dias_para_agotar + ' días' : 'N/A'}</td>
                            <td><span class="badge ${estadoClass}">${estadoLabel}</span></td>
                        </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center">No hay datos de predicción. Haz clic en "Generar Predicciones".</td></tr>';
                }

                initSearchPredicciones();

                const alertasList = document.getElementById('alertasStockList');
                if (data.alertas && data.alertas.length > 0) {
                    alertasList.innerHTML = data.alertas.map(a => `
                        <div class="top-item">
                            <div>
                                <span class="${a.tipo === 'critico' ? 'badge-inactive' : 'badge-pending'}" style="padding:2px 8px;border-radius:4px;font-size:0.7rem">${a.tipo.toUpperCase()}</span>
                                <span style="margin-left:8px">${escapeHtml(a.producto_nombre)}</span>
                            </div>
                            <div>
                                <small style="color:#aaa">Stock: ${a.nivel_actual} | Sug: ${a.nivel_sugerido}</small>
                                <button class="btn-action" style="background:var(--success);margin-left:8px" onclick="resolverAlerta(${a.id})"><i class="fas fa-check"></i></button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    alertasList.innerHTML = '<div style="text-align:center;padding:20px;color:#aaa"><i class="fas fa-check-circle" style="color:var(--success);font-size:2rem;display:block;margin-bottom:10px"></i>No hay alertas pendientes</div>';
                }

                const recomList = document.getElementById('recomendacionesList');
                if (data.recomendaciones && data.recomendaciones.length > 0) {
                    const prioridadColors = { alta: 'var(--danger)', media: 'var(--warning)', baja: 'var(--info)' };
                    recomList.innerHTML = data.recomendaciones.map(r => `
                        <div class="top-item">
                            <div>
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${prioridadColors[r.prioridad] || '#aaa'};margin-right:8px"></span>
                                <strong>${escapeHtml(r.name)}</strong>
                                <small style="color:#aaa;display:block;margin-left:18px">Stock: ${r.stock} | Sugerido: ${r.stock_sugerido} | Demanda: ${r.demanda_esperada}</small>
                            </div>
                            <div><small style="color:${r.prioridad === 'alta' ? 'var(--danger)' : 'var(--warning)'}">${escapeHtml(r.accion_recomendada)}</small></div>
                        </div>
                    `).join('');
                } else {
                    recomList.innerHTML = '<div style="text-align:center;padding:20px;color:#aaa"><i class="fas fa-thumbs-up" style="color:var(--success);font-size:2rem;display:block;margin-bottom:10px"></i>Sin recomendaciones pendientes</div>';
                }

                renderPrediccionesChart(data.tendencias || []);
            } catch (e) {
                console.error('Error cargarPredicciones:', e);
                document.getElementById('prediccionesBody').innerHTML = '<tr><td colspan="10" style="text-align:center">Error al cargar predicciones</td></tr>';
            }
        }

        function initSearchPredicciones() {
            const input = document.getElementById('searchPredicciones');
            if (!input) return;
            input.addEventListener('keyup', function() {
                const term = this.value.toLowerCase();
                const rows = document.getElementById('prediccionesBody').querySelectorAll('tr');
                rows.forEach(r => { r.style.display = r.textContent.toLowerCase().includes(term) ? '' : 'none'; });
            });
        }

        function renderPrediccionesChart(tendencias) {
            if (prediccionesChartInstance) prediccionesChartInstance.destroy();
            const canvas = document.getElementById('prediccionesChart');
            if (!canvas || !tendencias.length) return;
            const ctx = canvas.getContext('2d');
            const labels = tendencias.map(t => `${t.mes}/${t.anio}`);
            prediccionesChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Ventas Reales', data: tendencias.map(t => t.ventas_reales), backgroundColor: 'rgba(60,145,237,0.7)', borderRadius: 4 },
                        { label: 'Pronóstico', data: tendencias.map(t => t.ventas_predichas), backgroundColor: 'rgba(46,213,115,0.7)', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#e4e6eb' } } },
                    scales: { y: { beginAtZero: true, ticks: { color: '#aaa' } }, x: { ticks: { color: '#aaa' } } }
                }
            });
        }

        async function generarPredicciones() {
            const btn = document.getElementById('btnGenerarPredicciones');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            try {
                const response = await fetch('/proyecto/predicciones/generar_predicciones.php', { method: 'POST', credentials: 'include' });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion(data.message || 'Predicciones generadas', 'success');
                    await cargarPredicciones();
                } else {
                    mostrarNotificacion(data.message || 'Error al generar', 'error');
                }
            } catch (e) {
                mostrarNotificacion('Error al generar predicciones', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Generar Predicciones';
            }
        }

        async function resolverAlerta(alertaId) {
            try {
                const response = await fetch('/proyecto/predicciones/resolver_alerta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ alerta_id: alertaId }),
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('Alerta resuelta', 'success');
                    await cargarPredicciones();
                } else {
                    mostrarNotificacion(data.message || 'Error', 'warning');
                }
            } catch (e) {
                mostrarNotificacion('Error al resolver alerta', 'error');
            }
        }

        // ====================================================================
        // BI DASHBOARD - FUNCIONES
        // ====================================================================
        async function cargarBIDashboard() {
            const btns = ['btnActualizarBI', 'btnFiltrarBI'].map(id => document.getElementById(id)).filter(Boolean);
            btns.forEach(b => { b.disabled = true; b.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Cargando...'; });
            try {
                const fechaDesde = document.getElementById('biFechaDesde')?.value || '';
                const fechaHasta = document.getElementById('biFechaHasta')?.value || '';
                const params = new URLSearchParams();
                if (fechaDesde) params.append('fecha_desde', fechaDesde);
                if (fechaHasta) params.append('fecha_hasta', fechaHasta);
                const url = '/proyecto/bi/obtener_datos_bi.php?' + params.toString();
                const response = await fetch(url, { credentials: 'include' });
                if (!response.ok) throw new Error('Error HTTP: ' + response.status);
                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'Error al cargar BI');

                if (data.kpis) {
                    document.getElementById('biFacturasHoy').textContent = data.kpis.facturas_hoy || 0;
                    document.getElementById('biVentasHoy').textContent = formatMoney(data.kpis.ventas_hoy);
                    document.getElementById('biVentasMes').textContent = formatMoney(data.kpis.ventas_mes);
                    document.getElementById('biCrecimiento').textContent = (data.kpis.crecimiento >= 0 ? '+' : '') + data.kpis.crecimiento + '%';
                    document.getElementById('biTicketPromedio').textContent = formatMoney(data.kpis.ticket_promedio);
                    document.getElementById('biClientesActivos').textContent = data.kpis.clientes_activos;
                    document.getElementById('biStockBajo').textContent = data.kpis.stock_bajo;
                }

                renderBiVentasChart(data.ventas_por_mes || []);
                renderBiMetodosPagoChart(data.metodos_pago || []);
                renderBiCategoriasChart(data.ventas_por_categoria || []);
                renderBiClientesChart(data.tendencia_clientes || []);
                renderBiStockCategoriasChart(data.stock_por_categoria || []);

                const topProd = document.getElementById('biTopProductos');
                if (data.top_productos && data.top_productos.length > 0) {
                    topProd.innerHTML = data.top_productos.map((p, i) => `
                        <div class="top-item">
                            <span class="top-number">${i + 1}</span>
                            <span class="top-name">${escapeHtml(p.name)}</span>
                            <span class="top-value">${p.total_vendido} vendidos</span>
                        </div>
                    `).join('');
                }

                const topClient = document.getElementById('biTopClientes');
                if (data.top_clientes && data.top_clientes.length > 0) {
                    topClient.innerHTML = data.top_clientes.map((c, i) => `
                        <div class="top-item">
                            <span class="top-number">${i + 1}</span>
                            <span class="top-name">${escapeHtml(c.nombre)}</span>
                            <span class="top-value">${formatMoney(c.monto_total)}</span>
                        </div>
                    `).join('');
                }

                mostrarNotificacion('Datos del BI actualizados correctamente', 'success');
            } catch (e) {
                console.error('Error BI:', e);
                mostrarNotificacion('Error al actualizar BI: ' + e.message, 'error');
            } finally {
                btns.forEach(b => { b.disabled = false; b.innerHTML = b.id === 'btnActualizarBI' ? '<i class=\"fas fa-sync-alt\"></i> Actualizar Datos' : '<i class=\"fas fa-filter\"></i> Filtrar'; });
            }
        }

        function renderBiVentasChart(ventasPorMes) {
            if (biVentasChartInstance) biVentasChartInstance.destroy();
            const canvas = document.getElementById('biVentasChart');
            if (!canvas || !ventasPorMes.length) return;
            const ctx = canvas.getContext('2d');
            biVentasChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ventasPorMes.map(v => v.mes_nombre || v.mes),
                    datasets: [{
                        label: 'Ventas',
                        data: ventasPorMes.map(v => v.total_ventas),
                        borderColor: '#3C91ED',
                        backgroundColor: 'rgba(60,145,237,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3C91ED'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#e4e6eb' } } },
                    scales: { y: { beginAtZero: true, ticks: { color: '#aaa', callback: v => 'Bs. ' + v.toLocaleString() } }, x: { ticks: { color: '#aaa' } } }
                }
            });
        }

        function renderBiMetodosPagoChart(metodosPago) {
            if (biMetodosPagoChartInstance) biMetodosPagoChartInstance.destroy();
            const canvas = document.getElementById('biMetodosPagoChart');
            if (!canvas || !metodosPago.length) return;
            const ctx = canvas.getContext('2d');
            const colors = ['#3C91ED', '#2ed573', '#ffa502', '#ff4757', '#9B59B6'];
            biMetodosPagoChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: metodosPago.map(m => m.metodo_pago),
                    datasets: [{ data: metodosPago.map(m => m.monto), backgroundColor: colors.slice(0, metodosPago.length) }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#e4e6eb', padding: 15 } } }
                }
            });
        }

        function renderBiCategoriasChart(ventasCategoria) {
            if (biCategoriasChartInstance) biCategoriasChartInstance.destroy();
            const canvas = document.getElementById('biCategoriasChart');
            if (!canvas || !ventasCategoria.length) return;
            const ctx = canvas.getContext('2d');
            const colors = ['#3C91ED', '#2ed573', '#ffa502', '#ff4757', '#9B59B6', '#E67E22', '#1abc9c', '#3498db'];
            biCategoriasChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ventasCategoria.map(c => c.category),
                    datasets: [{
                        label: 'Ingresos',
                        data: ventasCategoria.map(c => c.ingresos),
                        backgroundColor: colors.slice(0, ventasCategoria.length),
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { color: '#aaa', callback: v => 'Bs. ' + v.toLocaleString() } }, y: { ticks: { color: '#aaa' } } }
                }
            });
        }

        function renderBiClientesChart(tendenciaClientes) {
            if (biClientesChartInstance) biClientesChartInstance.destroy();
            const canvas = document.getElementById('biClientesChart');
            if (!canvas || !tendenciaClientes.length) return;
            const ctx = canvas.getContext('2d');
            biClientesChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: tendenciaClientes.map(c => c.mes),
                    datasets: [{
                        label: 'Nuevos Clientes',
                        data: tendenciaClientes.map(c => c.nuevos_clientes),
                        borderColor: '#2ed573',
                        backgroundColor: 'rgba(46,213,117,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2ed573'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#e4e6eb' } } },
                    scales: { y: { beginAtZero: true, ticks: { color: '#aaa' } }, x: { ticks: { color: '#aaa' } } }
                }
            });
        }

        function renderBiStockCategoriasChart(stockCategoria) {
            if (biStockCategoriasChartInstance) biStockCategoriasChartInstance.destroy();
            const canvas = document.getElementById('biStockCategoriasChart');
            if (!canvas || !stockCategoria.length) return;
            const ctx = canvas.getContext('2d');
            const colors = ['#3C91ED', '#2ed573', '#ffa502', '#ff4757', '#9B59B6', '#E67E22', '#1abc9c', '#3498db'];
            biStockCategoriasChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: stockCategoria.map(c => c.category),
                    datasets: [{ data: stockCategoria.map(c => c.stock_total), backgroundColor: colors.slice(0, stockCategoria.length) }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#e4e6eb', padding: 10, font: { size: 10 } } } }
                }
            });
        }

        // ====================================================================
        // TELEGRAM - FUNCIONES
        // ====================================================================
        async function cargarTelegramConfig() {
            try {
                const response = await fetch('/proyecto/admin/obtener_configuracion.php', { credentials: 'include' });
                const data = await response.json();
                if (data.success && data.data) {
                    const config = data.data;
                    const getVal = (key) => { const item = config.find(c => c.clave === key); return item ? item.valor : ''; };
                    const token = getVal('telegram_token');
                    const chatId = getVal('telegram_chat_id');
                    document.getElementById('telegramToken').value = token;
                    document.getElementById('telegramChatId').value = chatId;
                    document.getElementById('telegramChatIdDisplay').textContent = chatId || '-';
                    document.getElementById('telegramBotDisplay').textContent = token ? token.substring(0, 20) + '...' : '-';
                    if (token && chatId) {
                        document.getElementById('telegramEstado').innerHTML = '<span class="badge badge-active"><i class="fas fa-check-circle"></i> Configurado</span>';
                    } else {
                        document.getElementById('telegramEstado').innerHTML = '<span class="badge badge-pending"><i class="fas fa-clock"></i> No configurado</span>';
                    }
                }
            } catch (e) {
                console.error('Error al cargar config Telegram', e);
            }
        }

        async function guardarTelegram() {
            const token = document.getElementById('telegramToken').value.trim();
            const chatId = document.getElementById('telegramChatId').value.trim();
            if (!token) { mostrarNotificacion('❌ Ingresa el Token del Bot', 'error'); return; }
            if (!chatId) { mostrarNotificacion('❌ Ingresa el Chat ID', 'error'); return; }
            try {
                const response = await fetch('/proyecto/admin/guardar_configuracion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ telegram_token: token, telegram_chat_id: chatId }),
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('✅ Configuración de Telegram guardada', 'success');
                    cargarTelegramConfig();
                } else {
                    mostrarNotificacion('❌ ' + (data.message || 'Error al guardar'), 'error');
                }
            } catch (e) {
                mostrarNotificacion('Error al guardar configuración de Telegram', 'error');
            }
        }

        async function probarTelegram() {
            const mensaje = prompt('Ingresa el mensaje de prueba:', '🧪 Mensaje de prueba desde PIC - Sistema de Gestión Comercial');
            if (!mensaje) return;
            try {
                const response = await fetch('/proyecto/telegram/enviar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tipo: 'prueba', mensaje }),
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('✅ Mensaje de prueba enviado por Telegram', 'success');
                } else {
                    mostrarNotificacion('❌ ' + (data.message || 'Error al enviar'), 'error');
                }
            } catch (e) {
                mostrarNotificacion('Error al enviar mensaje de prueba', 'error');
            }
        }

        async function probarTelegramStock() {
            try {
                mostrarNotificacion('🔍 Verificando stock bajo...', 'info');
                const response = await fetch('/proyecto/telegram/notificar_stock.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('✅ ' + (data.message || 'Stock notificado por Telegram'), 'success');
                } else {
                    mostrarNotificacion('❌ ' + (data.message || 'Error al notificar stock'), 'error');
                }
            } catch (e) {
                mostrarNotificacion('Error al notificar stock por Telegram', 'error');
            }
        }

        // ====================================================================
        // 2FA - FUNCIONES
        let current2faSecret = '';
        let current2faBackupCodes = [];

        async function cargar2FA() {
            try {
                const response = await fetch('/proyecto/2fa/configurar.php?action=estado', { credentials: 'include' });
                const data = await response.json();
                if (data.migracion_pendiente) {
                    document.getElementById('2faEstadoBadge').className = 'badge badge-pending';
                    document.getElementById('2faEstadoBadge').innerHTML = '<i class="fas fa-clock"></i> Migración pendiente';
                    document.getElementById('2faTitulo').textContent = '2FA no disponible';
                    document.getElementById('2faDescripcion').textContent = 'Ejecute sql/migracion_nuevas_funcionalidades.sql para activar la autenticación de dos factores.';
                    document.getElementById('2faSetupSection').style.display = 'none';
                    document.getElementById('2faActiveSection').style.display = 'none';
                    document.getElementById('2faDisabledSection').style.display = 'none';
                    return;
                }
                const enabled = data.enabled;
                const badge = document.getElementById('2faEstadoBadge');
                const setupSection = document.getElementById('2faSetupSection');
                const activeSection = document.getElementById('2faActiveSection');
                const disabledSection = document.getElementById('2faDisabledSection');

                if (enabled) {
                    badge.className = 'badge badge-active';
                    badge.innerHTML = '<i class="fas fa-check-circle"></i> 2FA Activado';
                    setupSection.style.display = 'none';
                    activeSection.style.display = 'block';
                    disabledSection.style.display = 'none';
                    document.getElementById('2faTitulo').textContent = '✅ Cuenta Protegida';
                    document.getElementById('2faDescripcion').textContent = 'La autenticación en dos pasos está activa en tu cuenta.';
                } else {
                    badge.className = 'badge badge-pending';
                    badge.innerHTML = '<i class="fas fa-clock"></i> 2FA Desactivado';
                    setupSection.style.display = 'none';
                    activeSection.style.display = 'none';
                    disabledSection.style.display = 'block';
                    document.getElementById('2faTitulo').textContent = 'Protege tu cuenta';
                    document.getElementById('2faDescripcion').textContent = 'La autenticación de dos factores añade una capa adicional de seguridad a tu cuenta.';
                }
            } catch (e) {
                console.error('Error cargar2FA:', e);
            }
        }

        async function cargarMarketing() {
            mostrarLoading('Cargando historial de marketing...');
            try {
                const res = await fetch('/proyecto/admin/enviar_recomendaciones.php?accion=historial');
                const data = await res.json();
                if (data.success) {
                    document.getElementById('marketingTotalEnvios').textContent = data.total || 0;
                    document.getElementById('marketingRecomendaciones').textContent = data.recomendaciones || 0;
                    document.getElementById('marketingNuevosProductos').textContent = data.nuevos_productos || 0;
                    document.getElementById('marketingEncuestas').textContent = data.encuestas || 0;

                    const tbody = document.getElementById('enviosRecomendacionesBody');
                    if (data.envios && data.envios.length) {
                        tbody.innerHTML = data.envios.map(e =>
                            `<tr><td>${e.id}</td><td>${e.cliente_email || '-'}</td><td><span class="badge badge-${e.tipo === 'recomendacion' ? 'success' : e.tipo === 'nuevo_producto' ? 'danger' : 'info'}">${e.tipo}</span></td><td>${e.asunto}</td><td>${e.fecha_envio}</td></tr>`
                        ).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay envíos registrados</td></tr>';
                    }
                } else {
                    mostrarNotificacion(data.message || 'Error al cargar historial', 'error');
                }
            } catch (e) {
                console.error('Error cargarMarketing:', e);
                mostrarNotificacion('Error de conexión al cargar marketing', 'error');
            } finally { ocultarLoading(); }
        }

        async function configurar2FA() {
            try {
                const response = await fetch('/proyecto/2fa/configurar.php?action=generar_secreto', {
                    method: 'POST',
                    credentials: 'include'
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message);

                current2faSecret = data.secret;
                current2faBackupCodes = data.backup_codes || [];

                document.getElementById('2faSecretDisplay').textContent = data.secret;
                document.getElementById('2faSetupSection').style.display = 'block';
                document.getElementById('2faActiveSection').style.display = 'none';
                document.getElementById('2faDisabledSection').style.display = 'none';

                if (data.qr_content) {
                    const qrContainer = document.getElementById('2faQRContainer');
                    qrContainer.innerHTML = `<div style="text-align:center;padding:10px">
                        <canvas id="2faQRCanvas"></canvas>
                        <p style="color:#666;font-size:0.75rem;margin-top:8px">Escanea con Google Authenticator</p>
                    </div>`;
                    try {
                        new QRious({
                            element: document.getElementById('2faQRCanvas'),
                            value: data.qr_content,
                            size: 200,
                            level: 'M'
                        });
                    } catch(e) {
                        console.error('Error generando QR:', e);
                    }
                }

                const backupContainer = document.getElementById('2faBackupCodes');
                backupContainer.innerHTML = data.backup_codes.map(c =>
                    `<code style="background:#0f1219;padding:6px 12px;border-radius:4px;color:var(--accent-color);font-size:0.8rem">${c}</code>`
                ).join('');
            } catch (e) {
                mostrarNotificacion('Error al configurar 2FA: ' + e.message, 'error');
            }
        }

        async function verificar2FA() {
            const code = Array.from(document.querySelectorAll('.code-digit-2fa')).map(i => i.value).join('');
            if (code.length !== 6) {
                mostrarNotificacion('Ingresa el código de 6 dígitos', 'warning');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('code', code);

                const response = await fetch('/proyecto/2fa/configurar.php?action=verificar', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('✅ 2FA activado correctamente', 'success');
                    await cargar2FA();
                } else {
                    mostrarNotificacion(data.message || 'Código inválido', 'error');
                }
            } catch (e) {
                mostrarNotificacion('Error al verificar código', 'error');
            }
        }

        async function desactivar2FA() {
            const password = prompt('Ingresa tu contraseña para desactivar 2FA:');
            if (!password) return;

            try {
                const formData = new FormData();
                formData.append('password', password);

                const response = await fetch('/proyecto/2fa/configurar.php?action=desactivar', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('2FA desactivado', 'success');
                    await cargar2FA();
                } else {
                    mostrarNotificacion(data.message || 'Contraseña incorrecta', 'error');
                }
            } catch (e) {
                mostrarNotificacion('Error al desactivar 2FA', 'error');
            }
        }

        // ====================================================================
        // DETECCIÓN DE CONEXIÓN Y MODO OFFLINE
        // ====================================================================
        let isOnline = navigator.onLine;
        let offlineQueue = [];

        function showOfflineBanner(show) {
            const offlineBanner = document.getElementById('offlineBanner');
            if (show) {
                offlineBanner.classList.add('show');
                setTimeout(() => {
                    offlineBanner.classList.remove('show');
                }, 5000);
            }
        }

        function showOnlineBanner() {
            const onlineBanner = document.getElementById('onlineBanner');
            onlineBanner.classList.add('show');
            setTimeout(() => {
                onlineBanner.classList.remove('show');
            }, 3000);
        }

        function updateConnectionUI(online) {
            const actionButtons = document.querySelectorAll('.btn-primary:not(.offline-enabled), .btn-action:not(.offline-enabled)');
            
            if (!online) {
                document.body.classList.add('offline-mode');
                const headerTitle = document.querySelector('.header h2');
                if (headerTitle && !document.querySelector('.offline-badge')) {
                    headerTitle.innerHTML += '<span class="offline-badge"><i class="fas fa-wifi-slash"></i> OFFLINE</span>';
                }
                for(let i = 0; i < actionButtons.length; i++) {
                    const btn = actionButtons[i];
                    if (!btn.hasAttribute('data-offline-enabled')) {
                        btn.classList.add('btn-offline-disabled');
                    }
                }
                mostrarNotificacion('📡 Modo offline - Algunas funciones están limitadas', 'warning');
            } else {
                document.body.classList.remove('offline-mode');
                const badge = document.querySelector('.offline-badge');
                if (badge) badge.remove();
                for(let i = 0; i < actionButtons.length; i++) {
                    const btn = actionButtons[i];
                    btn.classList.remove('btn-offline-disabled');
                }
            }
        }

        function queueOfflineAction(action, data) {
            offlineQueue.push({
                id: Date.now(),
                action: action,
                data: data,
                timestamp: new Date().toISOString()
            });
            localStorage.setItem('offlineQueue', JSON.stringify(offlineQueue));
            mostrarNotificacion(`📦 Acción "${action}" guardada para cuando vuelva internet`, 'info');
        }

        async function processOfflineQueue() {
            if (offlineQueue.length === 0) {
                const stored = localStorage.getItem('offlineQueue');
                if (stored) offlineQueue = JSON.parse(stored);
            }
            if (offlineQueue.length === 0) return;
            
            mostrarLoading(`Procesando ${offlineQueue.length} acciones pendientes...`);
            for (let i = 0; i < offlineQueue.length; i++) {
                const item = offlineQueue[i];
                console.log(`Procesando acción pendiente: ${item.action}`);
                try {
                    if (item.action === 'crearProducto' && item.data) {
                        const response = await fetch('/proyecto/admin/crear_producto.php', {
                            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(item.data)
                        });
                        if (response.ok) mostrarNotificacion(`Producto "${item.data.nombre}" creado`, 'success');
                    }
                    offlineQueue.splice(i, 1);
                    i--;
                } catch(e) {
                    console.error(`Error al procesar ${item.action}:`, e);
                }
            }
            localStorage.setItem('offlineQueue', JSON.stringify(offlineQueue));
            ocultarLoading();
            if (offlineQueue.length === 0) mostrarNotificacion('✅ Todas las acciones pendientes fueron procesadas', 'success');
        }

        let lastOnlineStatus = navigator.onLine;
        setInterval(() => {
            const currentStatus = navigator.onLine;
            if (currentStatus !== lastOnlineStatus) {
                lastOnlineStatus = currentStatus;
                if (currentStatus) {
                    showOnlineBanner();
                    updateConnectionUI(true);
                    cargarDashboard();
                    cargarUsuarios();
                    cargarProductos();
                } else {
                    showOfflineBanner(true);
                    updateConnectionUI(false);
                }
            }
        }, 3000);

        window.addEventListener('online', () => {
            console.log('🟢 Conexión restablecida');
            showOnlineBanner();
            updateConnectionUI(true);
            cargarDashboard();
            cargarUsuarios();
            cargarProductos();
        });
        window.addEventListener('offline', () => {
            console.log('🔴 Sin conexión');
            showOfflineBanner(true);
            updateConnectionUI(false);
        });

        // ====================================================================
        // INICIALIZACIÓN Y EVENTOS
        // ====================================================================
        function initDate() { const dateSpan = document.getElementById('currentDate'); if(dateSpan) dateSpan.innerHTML = new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }); }
        function initProfileTabs() { document.querySelectorAll('.profile-tab').forEach(tab => { tab.addEventListener('click', function() { const tabId = this.getAttribute('data-tab'); document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active')); document.querySelectorAll('.profile-tab-pane').forEach(p => p.classList.remove('active')); this.classList.add('active'); const pane = document.getElementById(`tab${tabId}`); if(pane) pane.classList.add('active'); }); }); }
        function switchSection(sectionId) {
            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
            document.querySelectorAll(`[data-section="${sectionId}"]`).forEach(i => i.classList.add('active'));
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            const sec = document.getElementById(sectionId);
            if(sec) sec.classList.add('active');
            const loaders = {
                'dashboardSection': cargarDashboard, 
                'perfilSection': cargarDatosPerfil,
                'usersSection': cargarUsuarios, 
                'productsSection': cargarProductos,
                'proveedoresSection': cargarProveedores, 
                'comprasSection': cargarCompras,
                'pedidosSection': cargarPedidos,
                'cotizacionesSection': cargarCotizaciones,
                'crmSection': cargarInteraccionesCRM,
                'facturacionSection': cargarFacturas,
                'cajaSection': cargarCaja, 
                'ventasClienteSection': cargarVentasCliente,
                'ventasVendedorSection': cargarVentasVendedor,
                'productosVendidosSection': cargarProductosVendidos,
                'historialComprasSection': cargarHistorialCompras, 
                'auditoriaSection': cargarAuditoria,
                'ceoSection': cargarCEO, 
                'configuracionSection': cargarConfiguracion,
                'backupSection': cargarBackups, 
                'reporteStockSection': cargarReporteStock,
                'reporteGeneralSection': cargarReporteGeneral, 
                'reporteEspecificoSection': () => { cargarReporteEspecifico(); },
                'prediccionesSection': cargarPredicciones,
                'biDashboardSection': cargarBIDashboard,
                'telegramSection': cargarTelegramConfig,
                'seguridad2faSection': cargar2FA,
                'marketingSection': cargarMarketing
            };
            const loader = loaders[sectionId];
            if(loader) loader();
        }
        function cerrarSesion() { if(confirm('¿Cerrar sesión?')) fetch('/proyecto/usuarios/cerrar_sesion.php', { method: 'POST', credentials: 'include' }).finally(() => { window.location.href = '/proyecto/interfaz_usuario/login.html'; }); }
        function toggleMobileMenu() { document.querySelector('.sidebar').classList.toggle('active'); }
        function inicializarFechasReporte() { const hoy = new Date(); const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1); const fechaHastaInput = document.getElementById('espFechaHasta'); const fechaDesdeInput = document.getElementById('espFechaDesde'); if(fechaHastaInput && !fechaHastaInput.value) fechaHastaInput.value = hoy.toISOString().split('T')[0]; if(fechaDesdeInput && !fechaDesdeInput.value) fechaDesdeInput.value = primerDiaMes.toISOString().split('T')[0]; }

        document.addEventListener('DOMContentLoaded', async function() {
            initDate();
            mostrarLoading('Iniciando panel...');
            try {
                const response = await fetch('/proyecto/admin/verificar_sesion.php', { credentials: 'include' });
                if(!response.ok) throw new Error('No autenticado');
            } catch(e) {
                mostrarNotificacion('Error de autenticacion. Por favor inicie sesion nuevamente.', 'error');
                window.location.href = '/proyecto/interfaz_usuario/login.html';
                ocultarLoading();
                return;
            }
            initProfileTabs();
            document.getElementById('fotoPerfilInput')?.addEventListener('change', (e) => { if(e.target.files && e.target.files[0]) subirFotoPerfil(e.target.files[0]); });
            document.getElementById('cambiarPasswordForm')?.addEventListener('submit', cambiarContrasena);
            document.getElementById('btnSolicitarToken')?.addEventListener('click', () => { document.getElementById('recuperacionModal').style.display = 'flex'; });
            document.getElementById('recuperacionForm')?.addEventListener('submit', solicitarTokenRecuperacion);
            document.getElementById('verificarPinForm')?.addEventListener('submit', verificarPin);
            document.getElementById('cambiarPasswordRecuperacionForm')?.addEventListener('submit', cambiarPasswordRecuperacion);
            document.getElementById('btnEliminarCuenta')?.addEventListener('click', eliminarCuenta);
            document.getElementById('logoutBtn')?.addEventListener('click', cerrarSesion);
            document.getElementById('addUserBtn')?.addEventListener('click', () => document.getElementById('addUserModal').style.display = 'flex');
            document.getElementById('addProductBtn')?.addEventListener('click', () => window.location.href = '/proyecto/producto/crear_producto.php');
            document.getElementById('addUserForm')?.addEventListener('submit', async (e) => { e.preventDefault(); await crearUsuario({ nombre: document.getElementById('addNombre').value, email: document.getElementById('addEmail').value, password: document.getElementById('addPassword').value, telefono: document.getElementById('addTelefono').value, rol: document.getElementById('addRol').value }); });
            document.getElementById('addProveedorBtn')?.addEventListener('click', () => document.getElementById('addProveedorModal').style.display = 'flex');
            document.getElementById('addProveedorForm')?.addEventListener('submit', async (e) => { e.preventDefault(); await crearProveedor({ nombre: document.getElementById('provNombre').value, ruc: document.getElementById('provRuc').value, telefono: document.getElementById('provTelefono').value, email: document.getElementById('provEmail').value, contacto: document.getElementById('provContacto').value, direccion: document.getElementById('provDireccion').value }); });
            document.getElementById('btnAbrirCaja')?.addEventListener('click', () => document.getElementById('abrirCajaModal').style.display = 'flex');
            document.getElementById('abrirCajaForm')?.addEventListener('submit', async (e) => { e.preventDefault(); await abrirCaja({ monto_inicial: document.getElementById('montoInicial').value, observaciones: document.getElementById('cajaObservaciones').value }); });
            document.getElementById('btnCerrarCaja')?.addEventListener('click', cerrarCaja);
            document.getElementById('btnRegistrarMovimiento')?.addEventListener('click', () => document.getElementById('registrarMovimientoModal').style.display = 'flex');
            document.getElementById('movimientoForm')?.addEventListener('submit', async (e) => { e.preventDefault(); await registrarMovimiento({ tipo: document.getElementById('movTipo').value, categoria: document.getElementById('movCategoria').value, monto: document.getElementById('movMonto').value, descripcion: document.getElementById('movDescripcion').value }); });
            document.getElementById('btnFacturarPedido')?.addEventListener('click', facturarPedidosSeleccionados);
            document.getElementById('btnNuevaFactura')?.addEventListener('click', nuevaFactura);
            document.getElementById('btnActualizarFacturas')?.addEventListener('click', cargarFacturas);
            document.getElementById('btnListarFacturas')?.addEventListener('click', function() { window.location.href = '/proyecto/facturacion/listar_facturas.php'; });
            document.getElementById('btnFiltrarCotizaciones')?.addEventListener('click', cargarCotizaciones);
            document.getElementById('btnNuevaCotizacion')?.addEventListener('click', () => abrirModalCotizacion(0));
            document.getElementById('btnLimpiarCaja')?.addEventListener('click', limpiarCaja);
            document.getElementById('btnGuardarInteraccion')?.addEventListener('click', guardarInteraccionCRM);
            document.getElementById('buscarClienteCRM')?.addEventListener('keyup', (e) => { if(e.key === 'Enter') cargarInteraccionesCRM(); });
            document.getElementById('btnAgregarItemCotizacion')?.addEventListener('click', agregarItemCotizacion);
            document.getElementById('btnGuardarCotizacion')?.addEventListener('click', guardarCotizacion);
            document.getElementById('btnGuardarSeguimiento')?.addEventListener('click', guardarSeguimiento);
            document.getElementById('buscarCotizacion')?.addEventListener('keyup', (e) => { if(e.key === 'Enter') cargarCotizaciones(); });
            document.getElementById('filtroEstadoCotizacion')?.addEventListener('change', cargarCotizaciones);
            document.getElementById('addCompraBtn')?.addEventListener('click', nuevaCompra);
            document.getElementById('btnGuardarConfig')?.addEventListener('click', guardarConfiguracion);
            document.getElementById('btnCrearBackup')?.addEventListener('click', crearBackup);
            document.getElementById('btnEnviarRecomendaciones')?.addEventListener('click', enviarRecomendacionesMasivo);
            document.getElementById('btnHistorialEnvios')?.addEventListener('click', cargarMarketing);
            document.getElementById('btnFiltrarAuditoria')?.addEventListener('click', cargarAuditoria);
            document.getElementById('filtroVendedor')?.addEventListener('change', cargarVentasVendedor);
            document.getElementById('btnActualizarVendedores')?.addEventListener('click', cargarVentasVendedor);
            document.getElementById('buscarCliente')?.addEventListener('keyup', cargarVentasCliente);
            document.getElementById('btnFiltrarHistorial')?.addEventListener('click', cargarHistorialCompras);
            document.getElementById('btnExportarHistorial')?.addEventListener('click', exportarHistorialExcel);
            document.getElementById('buscarClienteHistorial')?.addEventListener('keypress', (e) => { if(e.key === 'Enter') cargarHistorialCompras(); });
            document.getElementById('fechaDesdeHistorial')?.addEventListener('change', cargarHistorialCompras);
            document.getElementById('fechaHastaHistorial')?.addEventListener('change', cargarHistorialCompras);
            document.getElementById('estadoHistorial')?.addEventListener('change', cargarHistorialCompras);
            document.querySelectorAll('.modal-close').forEach(btn => btn.addEventListener('click', cerrarModales));
            document.querySelectorAll('.menu-item').forEach(item => item.addEventListener('click', () => switchSection(item.getAttribute('data-section'))));
            document.getElementById('btnFiltrarStock')?.addEventListener('click', cargarReporteStock);
            document.getElementById('btnExportarStock')?.addEventListener('click', exportarStockExcel);
            document.getElementById('btnActualizarStock')?.addEventListener('click', cargarReporteStock);
            document.getElementById('btnAplicarFiltrosEspecificos')?.addEventListener('click', cargarReporteEspecifico);
            document.getElementById('btnExportarEspecificoPDF')?.addEventListener('click', exportarReporteEspecificoPDF);
            document.getElementById('btnExportarEspecificoExcel')?.addEventListener('click', exportarReporteEspecificoExcel);
            document.getElementById('filtroTodos')?.addEventListener('click', mostrarTodosProductos);
            document.getElementById('filtroVisibles')?.addEventListener('click', mostrarProductosVisibles);
            document.getElementById('filtroOcultos')?.addEventListener('click', mostrarProductosOcultos);
            document.getElementById('btnActualizarProductos')?.addEventListener('click', cargarProductos);
            document.getElementById('btnGenerarPredicciones')?.addEventListener('click', generarPredicciones);
            document.getElementById('btnActualizarBI')?.addEventListener('click', cargarBIDashboard);
            document.getElementById('btnFiltrarBI')?.addEventListener('click', cargarBIDashboard);
            document.getElementById('btnGuardarTelegram')?.addEventListener('click', guardarTelegram);
            document.getElementById('btnProbarTelegram')?.addEventListener('click', probarTelegram);
            document.getElementById('btnProbarTelegramStock')?.addEventListener('click', probarTelegramStock);
            document.getElementById('btnNotificarTelegramStock')?.addEventListener('click', probarTelegramStock);
            document.getElementById('btnConfigurar2FA')?.addEventListener('click', configurar2FA);
            document.getElementById('btnVerificar2FA')?.addEventListener('click', verificar2FA);
            document.getElementById('btnDesactivar2FA')?.addEventListener('click', desactivar2FA);

            document.querySelectorAll('.code-digit-2fa').forEach((input, idx, arr) => {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, 1);
                    if (this.value && idx < arr.length - 1) arr[idx + 1].focus();
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && idx > 0) { arr[idx - 1].focus(); arr[idx - 1].value = ''; }
                });
            });

            inicializarFechasReporte();
            initSearchUsers();
            initSearchProducts();
            initSearchProveedores();
            initSearchCompras();
            initSearchFacturas();
            updateConnectionUI(navigator.onLine);
            const stored = localStorage.getItem('offlineQueue');
            if (stored) {
                offlineQueue = JSON.parse(stored);
                if (offlineQueue.length > 0) {
                    mostrarNotificacion(`📦 Tienes ${offlineQueue.length} acciones pendientes sincronizar`, 'info');
                }
            }
            await cargarDashboard();
            await cargarUsuarios();
            await cargarProductos();
            await cargarDatosPerfil();
            ocultarLoading();
            mostrarNotificacion('Panel cargado correctamente', 'success');

            // Periodic 2FA re-verification (every 60 seconds)
            setInterval(verificarReverificacion2FA, 60000);

            // Verificar pagos móviles periódicamente
            setInterval(verificarPagosMoviles, 15000);
            setTimeout(verificarPagosMoviles, 2000);
        });

        async function verificarReverificacion2FA() {
            try {
                const res = await fetch('/proyecto/2fa/verificar_2fa_periodico.php?action=check&type=admin', {
                    credentials: 'include',
                    cache: 'no-store'
                });
                const data = await res.json();
                if (data.success && data.needs_2fa) {
                    const modal = document.getElementById('reverificar2faModal');
                    if (modal && modal.style.display !== 'flex') {
                        modal.style.display = 'flex';
                        document.getElementById('reverificar2faCode').value = '';
                        document.getElementById('reverificar2faMessage').style.display = 'none';
                    }
                }
            } catch(e) {
                console.error('Error verificando 2FA periódico:', e);
            }
        }

        document.getElementById('reverificar2faForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const code = document.getElementById('reverificar2faCode').value.trim();
            if (code.length !== 6) return;
            const msgDiv = document.getElementById('reverificar2faMessage');
            msgDiv.style.display = 'none';
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verificando...';

            try {
                const formData = new FormData();
                formData.append('action', 'verify');
                formData.append('code', code);
                formData.append('type', 'admin');
                const res = await fetch('/proyecto/2fa/verificar_2fa_periodico.php', { method: 'POST', body: formData, credentials: 'include' });
                const data = await res.json();
                if (data.success) {
                    msgDiv.style.cssText = 'display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;background:#d4edda;color:#155724';
                    msgDiv.textContent = '✅ Verificado correctamente';
                    setTimeout(() => { cerrarModales(); }, 800);
                } else {
                    msgDiv.style.cssText = 'display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;background:#f8d7da;color:#721c24';
                    msgDiv.textContent = data.message || 'Código inválido';
                    document.getElementById('reverificar2faCode').value = '';
                    document.getElementById('reverificar2faCode').focus();
                }
            } catch(e) {
                msgDiv.style.cssText = 'display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;background:#f8d7da;color:#721c24';
                msgDiv.textContent = 'Error de conexión';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Verificar código';
            }
        });

        document.getElementById('reverificar2faCode')?.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            if (this.value.length === 6) {
                document.getElementById('reverificar2faForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>