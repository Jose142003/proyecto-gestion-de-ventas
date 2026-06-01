<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    die('Token inválido');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = trim($_POST['action'] ?? '');

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // --- Single-step: validate email + password + TOTP code ---
        if ($action === 'login_with_code') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $code = trim($_POST['code'] ?? '');

            if (empty($email) || empty($password) || empty($code)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
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

            // Validate TOTP code
            $tableName = ($userTable === 'admin_users') ? 'admin_users' : 'users';
            $stmt2fa = $pdo->prepare("SELECT 2fa_secret, 2fa_backup_codes FROM $tableName WHERE id = ?");
            $stmt2fa->execute([$user['id']]);
            $user2fa = $stmt2fa->fetch();

            if (!$user2fa || empty($user2fa['2fa_secret'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '2FA no configurado']);
                exit;
            }

            $isValid = verificarTOTP($pdo, $user['id'], $userTable, $user2fa['2fa_secret'], $code);

            if (!$isValid) {
                $codes = json_decode($user2fa['2fa_backup_codes'] ?? '[]', true);
                if (is_array($codes)) {
                    $idx = array_search($code, $codes);
                    if ($idx !== false) {
                        unset($codes[$idx]);
                        $pdo->prepare("UPDATE $tableName SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $user['id']]);
                        $isValid = true;
                    }
                }
            }

            if (!$isValid) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Código de Google Authenticator inválido']);
                exit;
            }

            // Approve
            $userDataArr = [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol'],
                'user_table' => $userTable
            ];
            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'approved', user_id = ?, user_table = ?, user_data = ? WHERE token = ?")
                ->execute([$user['id'], $userTable, json_encode($userDataArr), $token]);

            echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso']);
            exit;
        }

        // --- Step 2: Verify Google Authenticator code ---
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
            $stmt = $pdo->prepare("SELECT 2fa_secret, 2fa_backup_codes FROM $secretCol WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || empty($user['2fa_secret'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '2FA no configurado']);
                exit;
            }

            $isValid = verificarTOTP($pdo, $userId, $table, $user['2fa_secret'], $code);

            if (!$isValid) {
                $codes = json_decode($user['2fa_backup_codes'] ?? '[]', true);
                if (is_array($codes)) {
                    $idx = array_search($code, $codes);
                    if ($idx !== false) {
                        unset($codes[$idx]);
                        $pdo->prepare("UPDATE $secretCol SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $userId]);
                        $isValid = true;
                    }
                }
            }

            if (!$isValid) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Código inválido']);
                exit;
            }

            // Approve the session
            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'approved', user_id = ?, user_table = ?, user_data = ? WHERE token = ?")
                ->execute([$userData['id'], $userData['user_table'], json_encode($userData), $token]);

            echo json_encode(['success' => true, 'message' => '2FA verificado. Inicio de sesión confirmado.']);
            exit;
        }

        // --- Step 1: Validate credentials ---
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

        // Check if the user has 2FA (Google Authenticator) enabled
        $tableName = ($userTable === 'admin_users') ? 'admin_users' : 'users';
        $has2FA = false;

        $checkCol = $pdo->query("SHOW COLUMNS FROM $tableName LIKE '2fa_enabled'");
        if ($checkCol->rowCount() > 0) {
            $stmt2fa = $pdo->prepare("SELECT 2fa_enabled, 2fa_secret FROM $tableName WHERE id = ?");
            $stmt2fa->execute([$user['id']]);
            $user2fa = $stmt2fa->fetch();
            $has2FA = ($user2fa && $user2fa['2fa_enabled'] && !empty($user2fa['2fa_secret']));
        }

        $userDataArr = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'rol' => $user['rol'],
            'user_table' => $userTable
        ];

        if ($has2FA) {
            // Store validated user data but keep estado = 'scanned' (waiting for TOTP)
            $pdo->prepare("UPDATE qr_login_sessions SET estado = 'scanned', user_data = ? WHERE token = ?")
                ->execute([json_encode($userDataArr), $token]);
            echo json_encode(['success' => true, 'needs_2fa' => true, 'message' => 'Credenciales válidas. Ingresa el código de Google Authenticator.']);
        } else {
            // No 2FA — approve immediately
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

// ================================================================
// TOTP Helper Functions
// ================================================================

function verificarTOTP($pdo, int $userId, string $table, string $secret, string $code): bool {
    $code = trim($code);
    if (strlen($code) !== 6 || !ctype_digit($code)) return false;

    $timeSlice = floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) {
        if (hash_equals(generarTOTP($secret, $timeSlice + $i), $code)) return true;
    }

    $tableName = ($table === 'admin_users') ? 'admin_users' : 'users';
    $stmtBackup = $pdo->prepare("SELECT 2fa_backup_codes FROM $tableName WHERE id = ?");
    $stmtBackup->execute([$userId]);
    $user = $stmtBackup->fetch();
    if ($user && !empty($user['2fa_backup_codes'])) {
        $codes = json_decode($user['2fa_backup_codes'], true);
        if (is_array($codes)) {
            $idx = array_search($code, $codes);
            if ($idx !== false) {
                unset($codes[$idx]);
                $pdo->prepare("UPDATE $tableName SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $userId]);
                return true;
            }
        }
    }
    return false;
}

function generarTOTP(string $secret, int $timeSlice): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(str_replace('=', '', $secret));
    $bits = '';
    for ($i = 0; $i < strlen($secret); $i++) {
        $pos = strpos($chars, $secret[$i]);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $decoded = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) $decoded .= chr(bindec(substr($bits, $i, 8)));

    $timeBytes = pack('J', $timeSlice);
    $hash = hash_hmac('sha1', $timeBytes, $decoded, true);
    $offset = ord($hash[19]) & 0x0F;
    $code = (((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16) | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF)) % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
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
