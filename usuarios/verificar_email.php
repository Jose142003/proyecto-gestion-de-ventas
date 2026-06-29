<?php
session_name('CLIENTSESSID');
session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, correo, nombre, verification_token FROM users WHERE verification_token IS NOT NULL AND email_verified = 0");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userId = null;
    foreach ($usuarios as $u) {
        $data = json_decode($u['verification_token'], true);
        if ($data && isset($data['token']) && hash_equals($data['token'], $token) && isset($data['type']) && $data['type'] === 'email_verification') {
            if (isset($data['expires']) && strtotime($data['expires']) < time()) {
                $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '/proyecto', '/');
echo "<html><body style='font-family:Arial;text-align:center;padding:50px;'><h2 style='color:#dc3545;'>Enlace expirado</h2><p>El enlace de verificación ha expirado. <a href='{$baseUrl}/usuarios/solicitar_verificacion.php'>Solicita uno nuevo</a>.</p></body></html>";
                exit;
            }
            $userId = $u['id'];
            break;
        }
    }
    if (!$userId) {
        echo "<html><body style='font-family:Arial;text-align:center;padding:50px;'><h2 style='color:#dc3545;'>Token inválido</h2><p>El enlace de verificación no es válido o la cuenta ya fue verificada.</p></body></html>";
        exit;
    }
    $upd = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
    $upd->execute([$userId]);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_nombre'] = $u['nombre'];
    $_SESSION['user_correo'] = $u['correo'];
    $_SESSION['user_rol'] = 'usuario';
    $_SESSION['es_admin'] = false;
    $_SESSION['is_cliente'] = true;
    $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '/proyecto', '/');
echo "<html><body style='font-family:Arial;text-align:center;padding:50px;'><h2 style='color:#28a745;'>✓ Correo verificado</h2><p>Tu cuenta ha sido verificada exitosamente.</p><p><a href='{$baseUrl}/interfaz_usuario/pagina_modernizada.php' style='background:#294E90;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;'>Ir a la tienda</a></p></body></html>";
    exit;
}
echo "<html><body style='font-family:Arial;text-align:center;padding:50px;'><h2 style='color:#dc3545;'>Error</h2><p>Parámetros inválidos.</p></body></html>";
