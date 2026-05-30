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

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        $userData = json_encode([
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'rol' => $user['rol'],
            'user_table' => $userTable
        ]);

        $pdo->prepare("UPDATE qr_login_sessions SET estado = 'approved', user_id = ?, user_table = ?, user_data = ? WHERE token = ?")
            ->execute([$user['id'], $userTable, $userData, $token]);

        echo json_encode(['success' => true, 'message' => 'Inicio de sesión confirmado. Vuelve a tu computadora.']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
    exit;
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
