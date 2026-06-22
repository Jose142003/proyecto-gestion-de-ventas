<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
$cors_origin = getenv('CORS_ORIGIN') ?: (defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'http://localhost');
header("Access-Control-Allow-Origin: $cors_origin");
header('Access-Control-Allow-Credentials: true');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
});

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/totp.php';
requerirAdmin();

function columnaExiste($pdo, $tabla, $columna): bool {
    try { return (bool)$pdo->query("SHOW COLUMNS FROM `$tabla` LIKE " . $pdo->quote($columna))->fetch(); } catch (Throwable $e) { return false; }
}

try {
    $pdo = conectarDB();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'estado') {
        $userId = $_SESSION['user_id'];
        $enabled = false;
        if (columnaExiste($pdo, 'admin_users', '2fa_enabled')) {
            $stmt = $pdo->prepare("SELECT 2fa_enabled FROM admin_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            $enabled = (bool)($user['2fa_enabled'] ?? false);
        }
        echo json_encode(['success' => true, 'enabled' => $enabled, 'migracion_pendiente' => !columnaExiste($pdo, 'admin_users', '2fa_enabled')]);
        exit;
    }

    if (!columnaExiste($pdo, 'admin_users', '2fa_enabled')) {
        echo json_encode(['success' => false, 'message' => 'Migración pendiente. Ejecute sql/migracion_nuevas_funcionalidades.sql para activar 2FA.', 'migracion_pendiente' => true]);
        exit;
    }

    if ($action === 'generar_secreto') {
        $userId = $_SESSION['user_id'];
        
        // Requerir contraseña para habilitar 2FA
        $password_confirm = $_POST['password'] ?? '';
        if (empty($password_confirm)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Contraseña requerida para habilitar 2FA']);
            exit;
        }
        $stmt_pass = $pdo->prepare("SELECT contrasena FROM admin_users WHERE id = ?");
        $stmt_pass->execute([$userId]);
        $user_pass = $stmt_pass->fetch();
        if (!$user_pass || !password_verify($password_confirm, $user_pass['contrasena'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
            exit;
        }
        
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        for ($i = 0; $i < 32; $i++) $secret .= $chars[random_int(0, strlen($chars) - 1)];

        $email = $_SESSION['user_correo'] ?? 'admin@pic.com.ve';
        $issuer = 'PIC - Sistema de Gestion Comercial';
        $qrContent = generarOtpAuthUrl($secret, $email, $issuer);

        $backupCodes = [];
        for ($i = 0; $i < 8; $i++) $backupCodes[] = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2));

        $stmt = $pdo->prepare("UPDATE admin_users SET 2fa_secret = ?, 2fa_backup_codes = ? WHERE id = ?");
        $stmt->execute([$secret, json_encode($backupCodes), $userId]);

        echo json_encode(['success' => true, 'secret' => $secret, 'qr_content' => $qrContent, 'backup_codes' => $backupCodes, 'server_time' => time()], JSON_UNESCAPED_UNICODE);

    } elseif ($action === 'verificar') {
        $code = $_POST['code'] ?? '';
        $userId = $_SESSION['user_id'];
        if (empty($code)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Código requerido']); exit; }

        $stmt = $pdo->prepare("SELECT 2fa_secret FROM admin_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || empty($user['2fa_secret'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => '2FA no configurado']); exit; }

        $isValid = verificarTOTP($user['2fa_secret'], $code);
        if ($isValid) {
            $stmt = $pdo->prepare("UPDATE admin_users SET 2fa_enabled = TRUE, 2fa_verified_at = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'message' => '2FA activado correctamente']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Código inválido']);
        }

    } elseif ($action === 'desactivar') {
        $password = $_POST['password'] ?? '';
        $userId = $_SESSION['user_id'];
        if (empty($password)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Contraseña requerida']); exit; }

        $stmt = $pdo->prepare("SELECT contrasena FROM admin_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['contrasena'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']); exit; }

        $stmt = $pdo->prepare("UPDATE admin_users SET 2fa_enabled = FALSE, 2fa_secret = NULL, 2fa_backup_codes = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'message' => '2FA desactivado correctamente']);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar configuración 2FA']);
}

