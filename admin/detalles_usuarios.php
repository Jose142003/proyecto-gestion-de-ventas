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

try {
    $pdo = conectarDB();

    // Obtener datos del usuario
    $sql = "SELECT 
                id, 
                nombre, 
                correo as email, 
                telefono, 
                rol, 
                cedula,
                DATE_FORMAT(created_at, '%d/%m/%Y %H:%i:%s') as fecha_registro,
                (SELECT COUNT(*) FROM cart_items WHERE user_id = users.id) as items_carrito,
                (SELECT COUNT(*) FROM facturas WHERE usuario_id = users.id) as total_facturas,
                (SELECT SUM(total) FROM facturas WHERE usuario_id = users.id) as total_comprado
            FROM users 
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='alert alert-warning'>" . __('user_not_found') . "</div>";
        exit();
    }
    
    // Obtener información de administrador si existe
    $admin_sql = "SELECT 
                    rol as role, 
                    activo,
                    usuario as username,
                    DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i:%s') as fecha_registro_admin
                 FROM admin_users 
                 WHERE correo = ?";
    
    $admin_stmt = $pdo->prepare($admin_sql);
    $admin_stmt->execute([$user['email']]);
    
    $roles_admin = [];
    while ($admin_row = $admin_stmt->fetch(PDO::FETCH_ASSOC)) {
        $roles_admin[] = $admin_row;
    }
    
    // Obtener último login si existe
    $ultimo_acceso = __('not_available');
    try {
        $check_sql = "SHOW COLUMNS FROM users LIKE 'last_login'";
        $check_result = $pdo->query($check_sql);
        
        if ($check_result && $check_result->rowCount() > 0) {
            $last_login_sql = "SELECT last_login FROM users WHERE id = ?";
            $last_login_stmt = $pdo->prepare($last_login_sql);
            $last_login_stmt->execute([$id]);
            $last_login_row = $last_login_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($last_login_row && $last_login_row['last_login']) {
                $ultimo_acceso = date('d/m/Y H:i:s', strtotime($last_login_row['last_login']));
            }
        }
    } catch (Exception $e) {
        // Si hay error, mantener el valor por defecto
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
    <title><?php echo __('user_details'); ?> - <?php echo htmlspecialchars($user['nombre']); ?></title>
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
            --stat-label-color: #666;
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
            --stat-label-color: #999;
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
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--shadow);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; font-weight: 600; }
        .header .subtitle { opacity: 0.9; font-size: 16px; }
        .user-id { display: inline-block; background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 14px; margin-top: 10px; }
        .toggle-theme-btn {
            position: fixed; top: 20px; right: 20px; z-index: 999;
            background: rgba(255,255,255,0.2); border: none; color: white;
            width: 40px; height: 40px; border-radius: 50%; cursor: pointer;
            font-size: 18px; backdrop-filter: blur(4px); transition: .3s;
        }
        .toggle-theme-btn:hover { background: rgba(255,255,255,0.35); transform: scale(1.1); }
        .content { padding: 30px; }
        .section { margin-bottom: 30px; background: var(--section-bg); border-radius: 10px; padding: 20px; border-left: 4px solid var(--primary); }
        .section-title { font-size: 18px; color: var(--primary); margin-bottom: 15px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .section-title i { font-size: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--label-color); font-size: 14px; }
        .form-control {
            width: 100%; padding: 12px 15px; border: 2px solid var(--border-color);
            border-radius: 8px; font-size: 15px; background: var(--card-bg);
            transition: all 0.3s; color: var(--text-color);
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(78,84,200,0.1); }
        .form-control[readonly] { opacity: 0.8; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px; }
        .stat-card { background: var(--card-bg); border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 5px; }
        .stat-label { font-size: 14px; color: var(--stat-label-color); }
        .admin-roles { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .role-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; flex: 1; min-width: 200px; }
        .role-title { font-weight: 600; color: var(--primary); margin-bottom: 5px; }
        .role-detail { font-size: 14px; color: var(--stat-label-color); margin-bottom: 3px; }
        .empty-state { text-align: center; padding: 20px; color: var(--stat-label-color); font-style: italic; }
        .actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .btn-back { background: transparent; color: var(--primary); border: 2px solid var(--primary); }
        .btn-back:hover { background: var(--primary); color: white; }
        @media (max-width: 768px) {
            .container { margin: 10px; border-radius: 10px; }
            .header { padding: 20px; }
            .content { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <button class="toggle-theme-btn" id="themeToggle" title="Toggle theme"><i class="fas fa-moon"></i></button>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-circle"></i> <?php echo __('user_details'); ?></h1>
            <div class="subtitle"><?php echo __('full_profile_info'); ?></div>
            <div class="user-id">ID: <?php echo $user['id']; ?></div>
        </div>
        <div class="content">
            <div class="section">
                <div class="section-title"><i class="fas fa-user"></i> <?php echo __('personal_information'); ?></div>
                <div class="form-grid">
                    <div class="form-group"><label><?php echo __('full_name'); ?></label><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nombre']); ?>" readonly></div>
                    <div class="form-group"><label><?php echo __('email'); ?></label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly></div>
                    <div class="form-group"><label><?php echo __('id_number'); ?></label><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['cedula']); ?>" readonly></div>
                    <div class="form-group"><label><?php echo __('phone'); ?></label><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['telefono']); ?>" readonly></div>
                    <div class="form-group"><label><?php echo __('main_role'); ?></label><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['rol']); ?>" readonly></div>
                    <div class="form-group"><label><?php echo __('registration_date'); ?></label><input type="text" class="form-control" value="<?php echo $user['fecha_registro']; ?>" readonly></div>
                </div>
            </div>
            <div class="section">
                <div class="section-title"><i class="fas fa-chart-bar"></i> <?php echo __('user_statistics'); ?></div>
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-value"><?php echo $user['items_carrito']; ?></div><div class="stat-label"><?php echo __('cart_products'); ?></div></div>
                    <div class="stat-card"><div class="stat-value"><?php echo $user['total_facturas']; ?></div><div class="stat-label"><?php echo __('total_invoices'); ?></div></div>
                    <div class="stat-card"><div class="stat-value"><?php echo number_format($user['total_comprado'] ?? 0, 2); ?> Bs</div><div class="stat-label"><?php echo __('total_purchased'); ?></div></div>
                    <div class="stat-card"><div class="stat-value"><?php echo htmlspecialchars($ultimo_acceso ?? '', ENT_QUOTES, 'UTF-8'); ?></div><div class="stat-label"><?php echo __('last_access'); ?></div></div>
                </div>
            </div>
            <div class="section">
                <div class="section-title"><i class="fas fa-user-shield"></i> <?php echo __('admin_roles'); ?></div>
                <?php if (!empty($roles_admin)): ?>
                    <div class="admin-roles">
                        <?php foreach ($roles_admin as $role): ?>
                            <div class="role-card">
                                <div class="role-title"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($role['role']); ?></div>
                                <div class="role-detail"><i class="fas fa-user"></i> <?php echo __('user'); ?> <?php echo htmlspecialchars($role['username']); ?></div>
                                <div class="role-detail"><i class="fas fa-circle" style="color: <?php echo $role['activo'] ? '#28a745' : '#dc3545'; ?>"></i> <?php echo __('status'); ?> <?php echo $role['activo'] ? __('active') : __('inactive'); ?></div>
                                <?php if (!empty($role['fecha_registro_admin'])): ?>
                                <div class="role-detail"><i class="fas fa-calendar"></i> <?php echo __('registration'); ?> <?php echo $role['fecha_registro_admin']; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-info-circle"></i> <?php echo __('no_admin_roles'); ?></div>
                <?php endif; ?>
            </div>
            <div class="actions">
                <a href="javascript:history.back()" class="btn btn-back"><i class="fas fa-arrow-left"></i> <?php echo __('back'); ?></a>
                <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> <?php echo __('print'); ?></button>
                <a href="editar_usuario.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary"><i class="fas fa-edit"></i> <?php echo __('edit_user'); ?></a>
            </div>
        </div>
    </div>
    <script>
        const themeToggle = document.getElementById('themeToggle');
        function applyTheme(isDark) {
            document.body.classList.toggle('dark-mode', isDark);
            themeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        }
        const saved = localStorage.getItem('darkMode');
        if (saved === 'enabled') applyTheme(true);
        themeToggle.addEventListener('click', () => {
            const isDark = !document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            applyTheme(isDark);
        });
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.stat-card').forEach((card, i) => { card.style.animationDelay = i * 0.1 + 's'; card.classList.add('fade-in'); });
        });
        const style = document.createElement('style');
        style.textContent = '@keyframes fadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } } .fade-in { animation:fadeIn 0.5s ease-out forwards; opacity:0; } .stat-card:hover .stat-value { transform:scale(1.1); transition:transform 0.3s; }';
        document.head.appendChild(style);
    </script>
</body>
</html>