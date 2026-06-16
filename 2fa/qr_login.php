<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/totp.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    jsonResponse(['success' => false, 'message' => 'Token inválido'], 400);
    exit;
}

function iniciarSesionUsuario(array $userData): string {
    if (session_status() === PHP_SESSION_NONE) {
        if ($userData['user_table'] === 'admin_users') {
            session_name(ini_get('session.name'));
        } else {
            session_name('CLIENTSESSID');
        }
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false,
            'httponly' => true, 'samesite' => 'Lax'
        ]);
        session_start();
    }
    $_SESSION = array();
    session_regenerate_id(true);

    $_SESSION['_ultimo_acceso'] = time();
    $_SESSION['_regenerado_en'] = time();
    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_nombre'] = $userData['nombre'];
    $_SESSION['user_correo'] = $userData['correo'];
    $_SESSION['user_rol'] = $userData['rol'];
    $_SESSION['tabla_origen'] = $userData['user_table'];
    $_SESSION['2fa_verified'] = true;
    $_SESSION['2fa_verified_at'] = time();

    if ($userData['user_table'] === 'admin_users') {
        $_SESSION['es_admin'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['is_cliente'] = false;
        $_SESSION['user_tipo'] = 'admin';
        $redirect = BASE_URL . '/panel_admin/panel_admin.php';
    } else {
        $_SESSION['es_admin'] = false;
        $_SESSION['is_admin'] = false;
        $_SESSION['is_cliente'] = true;
        $_SESSION['user_tipo'] = 'cliente';
        $redirect = BASE_URL . '/interfaz_usuario/pagina_modernizada.php';
    }

    $token_data = $userData['id'] . '|' . $userData['nombre'] . '|' . $userData['user_table'];
    $token_sig = hash_hmac('sha256', $token_data, BASE_URL);
    $token_value = base64_encode($token_data . '|' . $token_sig);
    setcookie('persist_token', $token_value, time() + 86400 * 30, '/', '', false, true);

    session_write_close();
    return $redirect;
}

function verificarColumnas2FA(PDO $pdo, string $table): bool {
    $ck = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '2fa_enabled'");
    return $ck->rowCount() > 0;
}

function obtenerUser2FA(PDO $pdo, int $userId, string $table): ?array {
    $stmt = $pdo->prepare("SELECT 2fa_secret, 2fa_backup_codes FROM `$table` WHERE id = ?");
    $stmt->execute([$userId]);
    $r = $stmt->fetch();
    return $r ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = trim($_POST['action'] ?? '');

    try {
        $pdo = Database::getConnection();

        // --- Single-step: validate email + password (+ TOTP code if 2FA enabled) ---
        if ($action === 'login_with_code') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $code = trim($_POST['code'] ?? '');

            if (empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Correo y contraseña requeridos']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, estado, expires_at FROM qr_login_sessions WHERE token = ?");
            $stmt->execute([$token]);
            $row = $stmt->fetch();

            if (!$row || $row['estado'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'QR inválido o ya procesado']);
                exit;
            }

            if (strtotime($row['expires_at']) < time()) {
                $pdo->prepare("UPDATE qr_login_sessions SET estado = 'expired' WHERE token = ?")->execute([$token]);
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
                exit;
            }

            // Validate credentials
            $user = null;
            $userTable = null;

            $stmt = $pdo->prepare("SELECT id, nombre, correo, contrasena as password, rol FROM admin_users WHERE correo = ? AND activo = 1");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $u = $stmt->fetch();
                if (password_verify($password, $u['password']) || hash_equals(hash('sha256', $password), $u['password']) || hash_equals(strtoupper(hash('sha256', $password)), $u['password'])) {
                    $user = $u;
                    $userTable = 'admin_users';
                }
            }

            if (!$user) {
                $stmt = $pdo->prepare("SELECT id, nombre, correo, password, rol FROM users WHERE correo = ? AND is_active = 1 AND estado = 'activo'");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $u = $stmt->fetch();
                    if (password_verify($password, $u['password'])) {
                        $user = $u;
                        $userTable = 'users';
                    }
                }
            }

            if (!$user) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
                exit;
            }

            // Verificar si 2FA está configurado
            $has2FA = false;
            $tableName = ($userTable === 'admin_users') ? 'admin_users' : 'users';
            if (verificarColumnas2FA($pdo, $tableName)) {
                $user2fa = obtenerUser2FA($pdo, $user['id'], $tableName);
                $has2FA = ($user2fa && !empty($user2fa['2fa_secret']));
            }

            if ($has2FA) {
                if (empty($code)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Código de Google Authenticator requerido']);
                    exit;
                }

                $isValid = verificarTOTP($pdo, $user['id'], $tableName, $user2fa['2fa_secret'], $code);
                if (!$isValid) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Código de Google Authenticator inválido']);
                    exit;
                }
            }

            // Approve and set session directly
            $userDataArr = [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol'],
                'user_table' => $userTable
            ];

            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'approved', user_id = ?, user_table = ?, user_data = ? WHERE token = ?")
                ->execute([$user['id'], $userTable, json_encode($userDataArr), $token]);

            $redirect = iniciarSesionUsuario($userDataArr);
            $pdo->prepare("DELETE FROM qr_login_sessions WHERE token = ?")->execute([$token]);

            echo json_encode([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'redirect_url' => $redirect
            ]);
            exit;
        }

        // --- Step 2: Verify Google Authenticator code (phone flow) ---
        if ($action === 'verify_2fa') {
            $code = trim($_POST['code'] ?? '');
            if (empty($code)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Código requerido']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, estado, user_data, expires_at FROM qr_login_sessions WHERE token = ?");
            $stmt->execute([$token]);
            $row = $stmt->fetch();

            if (!$row || $row['estado'] !== 'scanned') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Primero debes iniciar sesión con correo y contraseña']);
                exit;
            }

            if (strtotime($row['expires_at']) < time()) {
                $pdo->prepare("UPDATE qr_login_sessions SET estado = 'expired' WHERE token = ?")->execute([$token]);
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
                exit;
            }

            $userData = json_decode($row['user_data'], true);
            $userId = $userData['id'];
            $table = $userData['user_table'];

            $secretCol = ($table === 'admin_users') ? 'admin_users' : 'users';
            $user2fa = obtenerUser2FA($pdo, $userId, $secretCol);

            if (!$user2fa || empty($user2fa['2fa_secret'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '2FA no configurado']);
                exit;
            }

            $isValid = verificarTOTP($pdo, $userId, $secretCol, $user2fa['2fa_secret'], $code);

            if (!$isValid) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Código inválido']);
                exit;
            }

            // Approve and set session
            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'approved', user_id = ?, user_table = ?, user_data = ? WHERE token = ?")
                ->execute([$userData['id'], $userData['user_table'], json_encode($userData), $token]);

            $redirect = iniciarSesionUsuario($userData);
            $pdo->prepare("DELETE FROM qr_login_sessions WHERE token = ?")->execute([$token]);

            echo json_encode([
                'success' => true,
                'message' => '2FA verificado. Inicio de sesión confirmado.',
                'redirect_url' => $redirect
            ]);
            exit;
        }

        // --- Step 1: Validate credentials (phone flow - no action) ---
        $stmt = $pdo->prepare("SELECT id, estado, expires_at FROM qr_login_sessions WHERE token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row || !in_array($row['estado'], ['pending', 'scanned'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'QR inválido o ya procesado']);
            exit;
        }

        if (strtotime($row['expires_at']) < time()) {
            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'expired' WHERE token = ?")->execute([$token]);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'QR expirado']);
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Correo y contraseña requeridos']);
            exit;
        }

        $user = null;
        $userTable = null;

        $stmt = $pdo->prepare("SELECT id, nombre, correo, contrasena as password, rol FROM admin_users WHERE correo = ? AND activo = 1");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $u = $stmt->fetch();
            if (password_verify($password, $u['password']) || hash_equals(hash('sha256', $password), $u['password']) || hash_equals(strtoupper(hash('sha256', $password)), $u['password'])) {
                $user = $u;
                $userTable = 'admin_users';
            }
        }

        if (!$user) {
            $stmt = $pdo->prepare("SELECT id, nombre, correo, password, rol FROM users WHERE correo = ? AND is_active = 1 AND estado = 'activo'");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $u = $stmt->fetch();
                if (password_verify($password, $u['password'])) {
                    $user = $u;
                    $userTable = 'users';
                }
            }
        }

        if (!$user) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
            exit;
        }

        $tableName = ($userTable === 'admin_users') ? 'admin_users' : 'users';
        $has2FA = false;

        if (verificarColumnas2FA($pdo, $tableName)) {
            $user2fa = obtenerUser2FA($pdo, $user['id'], $tableName);
            $has2FA = ($user2fa && !empty($user2fa['2fa_secret']));
        }

        $userDataArr = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'rol' => $user['rol'],
            'user_table' => $userTable
        ];

        if ($has2FA) {
            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'scanned', user_data = ? WHERE token = ?")
                ->execute([json_encode($userDataArr), $token]);
            echo json_encode(['success' => true, 'needs_2fa' => true, 'message' => 'Credenciales válidas. Ingresa el código de Google Authenticator.']);
        } else {
            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'approved', user_id = ?, user_table = ?, user_data = ? WHERE token = ?")
                ->execute([$user['id'], $userTable, json_encode($userDataArr), $token]);
            echo json_encode(['success' => true, 'message' => 'Inicio de sesión confirmado. Vuelve a tu computadora.']);
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
    exit;
}

require_once __DIR__ . '/totp.php';

function verificarTOTP(PDO $pdo, int $userId, string $table, string $secret, string $code): bool {
    $code = trim($code);
    if (strlen($code) !== 6 || !ctype_digit($code)) return false;

    $timeSlice = floor(time() / 30);
    for ($i = -2; $i <= 2; $i++) {
        if (hash_equals(generarTOTP($secret, $timeSlice + $i), $code)) return true;
    }

    $tableName = ($table === 'admin_users') ? 'admin_users' : 'users';
    $stmtBackup = $pdo->prepare("SELECT 2fa_backup_codes FROM `$tableName` WHERE id = ?");
    $stmtBackup->execute([$userId]);
    $user = $stmtBackup->fetch();
    if ($user && !empty($user['2fa_backup_codes'])) {
        $codes = json_decode($user['2fa_backup_codes'], true);
        if (is_array($codes)) {
            $idx = array_search($code, $codes);
            if ($idx !== false) {
                unset($codes[$idx]);
                $pdo->prepare("UPDATE `$tableName` SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $userId]);
                return true;
            }
        }
    }
    return false;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar inicio de sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; }
        .card { max-width: 400px; width: 100%; border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .card-header { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; border-radius: 16px 16px 0 0 !important; padding: 20px; text-align: center; }
        .card-header i { font-size: 2rem; margin-bottom: 8px; }
        .card-body { padding: 24px; }
        .btn-primary { background: linear-gradient(135deg, #1e3c72, #2a5298); border: none; border-radius: 10px; padding: 10px; }
        .btn-primary:hover { opacity: 0.9; }
        .form-control { border-radius: 10px; padding: 10px 14px; border: 1.5px solid #e2e8f0; }
        .form-control:focus { border-color: #1e3c72; box-shadow: 0 0 0 3px rgba(30,60,114,0.1); }
        .alert { border-radius: 10px; font-size: 0.85rem; }
        .laptop-icon { font-size: 3rem; color: #2a5298; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-qrcode"></i>
            <h5 class="mb-0">Confirmar inicio de sesión</h5>
            <small style="opacity:0.8">Escaneaste un código QR desde tu teléfono</small>
        </div>
        <div class="card-body text-center" id="confirmPage">
            <div class="laptop-icon">
                <i class="fas fa-laptop"></i>
            </div>
            <p style="color:#666;font-size:0.9rem">Ingresa tus credenciales para iniciar sesión en el navegador de escritorio.</p>

            <div id="loginError" class="alert alert-danger" style="display:none"></div>
            <div id="loginSuccess" class="alert alert-success" style="display:none"></div>

            <form id="qrLoginForm" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label style="font-size:0.85rem;font-weight:600;color:#333">Correo electrónico</label>
                    <input type="email" class="form-control" name="email" required placeholder="tu@correo.com" autocomplete="email">
                </div>
                <div class="form-group">
                    <label style="font-size:0.85rem;font-weight:600;color:#333">Contraseña</label>
                    <input type="password" class="form-control" name="password" required placeholder="••••••••" autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="qrLoginBtn">
                    Iniciar sesión en el escritorio
                </button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        document.getElementById('qrLoginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('qrLoginBtn');
            const errorDiv = document.getElementById('loginError');
            const successDiv = document.getElementById('loginSuccess');
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

            try {
                const formData = new FormData(this);
                const res = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    successDiv.textContent = data.message;
                    successDiv.style.display = 'block';
                    document.getElementById('qrLoginForm').style.display = 'none';
                } else {
                    errorDiv.textContent = data.message || 'Error al iniciar sesión';
                    errorDiv.style.display = 'block';
                }
            } catch(e) {
                errorDiv.textContent = 'Error de conexión';
                errorDiv.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Iniciar sesión en el escritorio';
            }
        });
    </script>
</body>
</html>
