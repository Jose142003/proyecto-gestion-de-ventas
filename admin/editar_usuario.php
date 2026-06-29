<?php
session_start();

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../config/i18n_helpers.php';
requerirAdmin();

$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
$localesValidos = ['es', 'en'];
if (!in_array($locale, $localesValidos)) $locale = 'es';
\I18n::load($locale);

// Obtener ID del usuario
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    echo "<div class='alert alert-danger'>" . __('invalid_user_id') . "</div>";
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
    try {
        $pdo = conectarDB();
        
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $telefono = $_POST['telefono'];
        $cedula = $_POST['cedula'];
        $rol = $_POST['rol'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $check_sql = "SELECT id FROM users WHERE correo = ? AND id != ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$correo, $id]);
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception(__('email_already_registered'));
        }
        
        $check_cedula_sql = "SELECT id FROM users WHERE cedula = ? AND id != ?";
        $check_cedula_stmt = $pdo->prepare($check_cedula_sql);
        $check_cedula_stmt->execute([$cedula, $id]);
        
        if ($check_cedula_stmt->rowCount() > 0) {
            throw new Exception(__('id_already_registered'));
        }
        
        $update_sql = "UPDATE users SET 
                      nombre = ?, correo = ?, telefono = ?, cedula = ?, rol = ?, is_active = ?
                      WHERE id = ?";
        
        $update_stmt = $pdo->prepare($update_sql);
        
        if ($update_stmt->execute([$nombre, $correo, $telefono, $cedula, $rol, $is_active, $id])) {
            $mensaje = __('user_updated');
            $tipo_mensaje = "success";
            auditoriaRegistrar('editar_usuario', 'usuarios', "Usuario ID $id editado: $nombre ($correo)");
        } else {
            throw new Exception(__('error_updating_user'));
        }
        
    } catch (Exception $e) {
        $mensaje = __('internal_server_error');
        $tipo_mensaje = "error";
    }
}

try {
    $pdo = conectarDB();

    $sql = "SELECT 
                id, nombre, correo, telefono, rol, cedula, is_active,
                DATE_FORMAT(created_at, '%d/%m/%Y %H:%i:%s') as fecha_registro,
                DATE_FORMAT(last_login, '%d/%m/%Y %H:%i:%s') as ultimo_login
            FROM users WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='alert alert-warning'>" . __('user_not_found') . "</div>";
        exit();
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . __('error_loading_details') . "</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('edit_user_title'); ?> - <?php echo htmlspecialchars($user['nombre']); ?></title>
    <style>
        :root {
            --primary: #4e54c8;
            --primary-dark: #3b41b8;
            --bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: #ffffff;
            --text-color: #333;
            --section-bg: #f8f9fa;
            --border-color: #e0e0e0;
            --label-color: #555;
            --info-color: #6c757d;
            --shadow: rgba(0, 0, 0, 0.2);
        }
        body.dark-mode {
            --primary: #5a7fd4;
            --primary-dark: #4a6fc4;
            --bg: linear-gradient(135deg, #1a1f3a 0%, #2a3050 100%);
            --card-bg: #1e2436;
            --text-color: #e4e6eb;
            --section-bg: #252b3d;
            --border-color: #2c3348;
            --label-color: #aaa;
            --info-color: #999;
            --shadow: rgba(0, 0, 0, 0.4);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            padding: 20px;
            color: var(--text-color);
        }
        .container { max-width: 800px; margin: 0 auto; background: var(--card-bg); border-radius: 15px; box-shadow: 0 10px 30px var(--shadow); overflow: hidden; }
        .header { background: linear-gradient(135deg, #4e54c8, #8f94fb); color: white; padding: 25px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; font-weight: 600; }
        .header .subtitle { opacity: 0.9; font-size: 16px; }
        .user-id { display: inline-block; background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 14px; margin-top: 10px; }
        .toggle-theme-btn { position: fixed; top: 20px; right: 20px; z-index: 999; background: rgba(255,255,255,0.2); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; backdrop-filter: blur(4px); transition: .3s; }
        .toggle-theme-btn:hover { background: rgba(255,255,255,0.35); transform: scale(1.1); }
        .content { padding: 30px; }
        .message { padding: 15px; margin-bottom: 25px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease-out; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
        .form-section { background: var(--section-bg); border-radius: 10px; padding: 25px; margin-bottom: 25px; border-left: 4px solid var(--primary); }
        .section-title { font-size: 18px; color: var(--primary); margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .section-title i { font-size: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--label-color); font-size: 14px; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 15px; background: var(--card-bg); transition: all 0.3s; color: var(--text-color); }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(78,84,200,0.1); }
        select.form-control { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234e54c8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 15px center; background-size: 16px; padding-right: 40px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 5px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }
        .form-info { font-size: 13px; color: var(--info-color); margin-top: 5px; font-style: italic; }
        .readonly-field { background: var(--section-bg) !important; cursor: not-allowed; }
        .actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 25px; border-top: 1px solid var(--border-color); }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; flex: 1; justify-content: center; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(78,84,200,0.3); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .btn-back { background: transparent; color: var(--primary); border: 2px solid var(--primary); }
        .btn-back:hover { background: var(--primary); color: white; }
        .user-info { display: flex; align-items: center; gap: 15px; margin-top: 15px; padding: 15px; background: var(--section-bg); border-radius: 8px; }
        .user-avatar { width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), #8f94fb); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold; }
        .user-details { flex: 1; }
        .user-name { font-weight: 600; color: var(--text-color); margin-bottom: 5px; }
        .user-meta { font-size: 13px; color: var(--info-color); }
        @media (max-width: 768px) { .container { margin: 10px; border-radius: 10px; } .header { padding: 20px; } .content { padding: 20px; } .form-grid { grid-template-columns: 1fr; } .actions { flex-direction: column; } .btn { width: 100%; } .user-info { flex-direction: column; text-align: center; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <button class="toggle-theme-btn" id="themeToggle" title="Toggle theme"><i class="fas fa-moon"></i></button>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> <?php echo __('edit_user_title'); ?></h1>
            <div class="subtitle"><?php echo __('edit_user_subtitle'); ?></div>
            <div class="user-id">ID: <?php echo $user['id']; ?></div>
        </div>
        <div class="content">
            <?php if ($mensaje): ?>
                <div class="message <?php echo htmlspecialchars($tipo_mensaje ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-<?php echo htmlspecialchars($tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'error' ? 'exclamation-circle' : 'exclamation-triangle'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                    <?php echo htmlspecialchars($mensaje ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($user['nombre'], 0, 1)); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['nombre']); ?></div>
                    <div class="user-meta">
                        <i class="fas fa-calendar"></i> <?php echo __('registered_label'); ?> <?php echo $user['fecha_registro']; ?>
                        <?php if ($user['ultimo_login']): ?>
                            | <i class="fas fa-sign-in-alt"></i> <?php echo __('last_login_label'); ?> <?php echo $user['ultimo_login']; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <form method="POST" action="">
                <?php echo campoCSRF(); ?>
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-user-cog"></i> <?php echo __('basic_info'); ?></div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre"><?php echo __('full_name_required'); ?></label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="correo"><?php echo __('email_required'); ?></label>
                            <input type="email" id="correo" name="correo" class="form-control" value="<?php echo htmlspecialchars($user['correo']); ?>" required>
                            <div class="form-info"><?php echo __('valid_unique_email'); ?></div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="cedula"><?php echo __('id_required'); ?></label>
                            <input type="text" id="cedula" name="cedula" class="form-control" value="<?php echo htmlspecialchars($user['cedula']); ?>" required pattern="[0-9]{6,10}" title="Solo números, entre 6 y 10 dígitos">
                            <div class="form-info"><?php echo __('only_numbers_hint'); ?></div>
                        </div>
                        <div class="form-group">
                            <label for="telefono"><?php echo __('phone'); ?></label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['telefono']); ?>">
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <div class="section-title"><i class="fas fa-shield-alt"></i> <?php echo __('access_config'); ?></div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="rol"><?php echo __('user_role'); ?></label>
                            <select id="rol" name="rol" class="form-control" required>
                                <option value="usuario" <?php echo $user['rol'] === 'usuario' ? 'selected' : ''; ?>><?php echo __('normal_user'); ?></option>
                                <option value="admin" <?php echo $user['rol'] === 'admin' ? 'selected' : ''; ?>><?php echo __('admin'); ?></option>
                                <option value="vendedor" <?php echo $user['rol'] === 'vendedor' ? 'selected' : ''; ?>><?php echo __('seller'); ?></option>
                            </select>
                            <div class="form-info"><?php echo __('determine_permissions'); ?></div>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('account_status'); ?></label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active"><?php echo __('active_account'); ?></label>
                            </div>
                            <div class="form-info"><?php echo __('inactive_account_hint'); ?></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="user_id"><?php echo __('user_id_field'); ?></label>
                        <input type="text" id="user_id" class="form-control readonly-field" value="<?php echo $user['id']; ?>" readonly>
                        <div class="form-info"><?php echo __('unique_system_id'); ?></div>
                    </div>
                </div>
                <div class="actions">
                    <a href='<?= url('/panel_admin/panel_admin.php') ?>' class="btn btn-back"><i class="fas fa-arrow-left"></i> <?php echo __('cancel'); ?></a>
                    <button type="reset" class="btn btn-secondary"><i class="fas fa-redo"></i> <?php echo __('reset'); ?></button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo __('save_changes'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const themeToggle = document.getElementById('themeToggle');
        function applyTheme(isDark) { document.body.classList.toggle('dark-mode', isDark); themeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>'; }
        const saved = localStorage.getItem('darkMode');
        if (saved === 'enabled') applyTheme(true);
        themeToggle.addEventListener('click', () => { const isDark = !document.body.classList.contains('dark-mode'); localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled'); applyTheme(isDark); });
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            document.getElementById('cedula').addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
            document.getElementById('telefono').addEventListener('input', function() { this.value = this.value.replace(/[^0-9\s]/g, ''); });
            document.getElementById('correo').addEventListener('blur', function() {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                this.style.borderColor = re.test(this.value) ? '#28a745' : '#dc3545';
            });
            form.addEventListener('submit', function(e) {
                const n = document.getElementById('nombre').value.trim();
                const c = document.getElementById('correo').value.trim();
                const d = document.getElementById('cedula').value.trim();
                if (!n || !c || !d) { e.preventDefault(); alert('<?php echo __('please_fill_required'); ?>'); return; }
                if (!confirm('<?php echo __('confirm_save_user'); ?>')) e.preventDefault();
            });
            document.querySelectorAll('.btn').forEach(b => {
                b.addEventListener('mouseenter', function() { this.style.transform = 'translateY(-2px)'; });
                b.addEventListener('mouseleave', function() { this.style.transform = 'translateY(0)'; });
            });
        });
    </script>
</body>
</html>