<?php
require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

// Obtener ID del usuario
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    echo "<div class='alert alert-danger'>ID de usuario no válido</div>";
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
        echo "<div class='alert alert-warning'>Usuario no encontrado</div>";
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
    $ultimo_acceso = 'No disponible';
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
    echo "<div class='alert alert-danger'>Error al obtener detalles</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Usuario - <?php echo htmlspecialchars($user['nombre']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header .subtitle {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .user-id {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #4e54c8;
        }
        
        .section-title {
            font-size: 18px;
            color: #4e54c8;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            background: white;
            transition: all 0.3s;
            color: #333;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4e54c8;
            box-shadow: 0 0 0 3px rgba(78, 84, 200, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #4e54c8;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .badge-primary {
            background: #4e54c8;
            color: white;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #4e54c8;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3b41b8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: transparent;
            color: #4e54c8;
            border: 2px solid #4e54c8;
        }
        
        .btn-back:hover {
            background: #4e54c8;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }
        
        .admin-roles {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .role-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            flex: 1;
            min-width: 200px;
        }
        
        .role-title {
            font-weight: 600;
            color: #4e54c8;
            margin-bottom: 5px;
        }
        
        .role-detail {
            font-size: 14px;
            color: #666;
            margin-bottom: 3px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .content {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-circle"></i> Detalles del Usuario</h1>
            <div class="subtitle">Información completa del perfil de usuario</div>
            <div class="user-id">ID: <?php echo $user['id']; ?></div>
        </div>
        
        <div class="content">
            <!-- Sección: Información Personal -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-user"></i>
                    Información Personal
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nombre']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Cédula</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['cedula']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['telefono']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Rol Principal</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['rol']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Registro</label>
                        <input type="text" class="form-control" value="<?php echo $user['fecha_registro']; ?>" readonly>
                    </div>
                </div>
            </div>
            
            <!-- Sección: Estadísticas -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Estadísticas del Usuario
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $user['items_carrito']; ?></div>
                        <div class="stat-label">Productos en Carrito</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $user['total_facturas']; ?></div>
                        <div class="stat-label">Total Facturas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($user['total_comprado'] ?? 0, 2); ?> Bs</div>
                        <div class="stat-label">Total Comprado</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $ultimo_acceso; ?></div>
                        <div class="stat-label">Último Acceso</div>
                    </div>
                </div>
            </div>
            
            <!-- Sección: Roles Administrativos -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-user-shield"></i>
                    Roles Administrativos
                </div>
                <?php if (!empty($roles_admin)): ?>
                    <div class="admin-roles">
                        <?php foreach ($roles_admin as $role): ?>
                            <div class="role-card">
                                <div class="role-title">
                                    <i class="fas fa-user-tie"></i>
                                    <?php echo htmlspecialchars($role['role']); ?>
                                </div>
                                <div class="role-detail">
                                    <i class="fas fa-user"></i> Usuario: <?php echo htmlspecialchars($role['username']); ?>
                                </div>
                                <div class="role-detail">
                                    <i class="fas fa-circle" style="color: <?php echo $role['activo'] ? '#28a745' : '#dc3545'; ?>"></i>
                                    Estado: <?php echo $role['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </div>
                                <?php if (!empty($role['fecha_registro_admin'])): ?>
                                <div class="role-detail">
                                    <i class="fas fa-calendar"></i> Registro: <?php echo $role['fecha_registro_admin']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        Este usuario no tiene roles administrativos asignados
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sección: Acciones -->
            <div class="actions">
                <a href="javascript:history.back()" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <a href="editar_usuario.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Editar Usuario
                </a>
            </div>
        </div>
    </div>

    <script>
        // Efectos de interacción
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar animación de entrada a las tarjetas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
        
        // Agregar estilos para animación
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .fade-in {
                animation: fadeIn 0.5s ease-out forwards;
                opacity: 0;
            }
            
            .stat-card:hover .stat-value {
                transform: scale(1.1);
                transition: transform 0.3s;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>