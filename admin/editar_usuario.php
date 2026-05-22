<?php
require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

// Obtener ID del usuario
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    echo "<div class='alert alert-danger'>ID de usuario no válido</div>";
    exit();
}

$mensaje = '';
$tipo_mensaje = ''; // success, error, warning

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        // Obtener datos del formulario
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $telefono = $_POST['telefono'];
        $cedula = $_POST['cedula'];
        $rol = $_POST['rol'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Verificar si el correo ya existe en otro usuario
        $check_sql = "SELECT id FROM users WHERE correo = ? AND id != ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$correo, $id]);
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception("El correo electrónico ya está registrado por otro usuario");
        }
        
        // Verificar si la cédula ya existe en otro usuario
        $check_cedula_sql = "SELECT id FROM users WHERE cedula = ? AND id != ?";
        $check_cedula_stmt = $pdo->prepare($check_cedula_sql);
        $check_cedula_stmt->execute([$cedula, $id]);
        
        if ($check_cedula_stmt->rowCount() > 0) {
            throw new Exception("La cédula ya está registrada por otro usuario");
        }
        
        // Actualizar usuario
        $update_sql = "UPDATE users SET 
                      nombre = ?,
                      correo = ?,
                      telefono = ?,
                      cedula = ?,
                      rol = ?,
                      is_active = ?
                      WHERE id = ?";
        
        $update_stmt = $pdo->prepare($update_sql);
        
        if ($update_stmt->execute([$nombre, $correo, $telefono, $cedula, $rol, $is_active, $id])) {
            $mensaje = "Usuario actualizado correctamente";
            $tipo_mensaje = "success";
            auditoriaRegistrar('editar_usuario', 'usuarios', "Usuario ID $id editado: $nombre ($correo)");
        } else {
            throw new Exception("Error al actualizar el usuario");
        }
        
    } catch (Exception $e) {
        $mensaje = 'Error interno del servidor';
        $tipo_mensaje = "error";
    }
}

try {
    $pdo = conectarDB();

    // Obtener datos del usuario
    $sql = "SELECT 
                id, 
                nombre, 
                correo, 
                telefono, 
                rol, 
                cedula,
                is_active,
                DATE_FORMAT(created_at, '%d/%m/%Y %H:%i:%s') as fecha_registro,
                DATE_FORMAT(last_login, '%d/%m/%Y %H:%i:%s') as ultimo_login
            FROM users 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='alert alert-warning'>Usuario no encontrado</div>";
        exit();
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
    <title>Editar Usuario - <?php echo htmlspecialchars($user['nombre']); ?></title>
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
            max-width: 800px;
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
        
        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #4e54c8;
        }
        
        .section-title {
            font-size: 18px;
            color: #4e54c8;
            margin-bottom: 20px;
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
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
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
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234e54c8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4e54c8;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .form-info {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }
        
        .readonly-field {
            background: #f5f5f5 !important;
            cursor: not-allowed;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
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
            flex: 1;
            justify-content: center;
        }
        
        .btn-primary {
            background: #4e54c8;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3b41b8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
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
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 8px;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .user-meta {
            font-size: 13px;
            color: #6c757d;
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
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Editar Usuario</h1>
            <div class="subtitle">Modifique la información del usuario según sea necesario</div>
            <div class="user-id">ID: <?php echo $user['id']; ?></div>
        </div>
        
        <div class="content">
            <?php if ($mensaje): ?>
                <div class="message <?php echo $tipo_mensaje; ?>">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'error' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['nombre'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['nombre']); ?></div>
                    <div class="user-meta">
                        <i class="fas fa-calendar"></i> Registrado: <?php echo $user['fecha_registro']; ?>
                        <?php if ($user['ultimo_login']): ?>
                            | <i class="fas fa-sign-in-alt"></i> Último login: <?php echo $user['ultimo_login']; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-cog"></i>
                        Información Básica
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre">Nombre Completo <span style="color: #dc3545;">*</span></label>
                            <input type="text" id="nombre" name="nombre" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['nombre']); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="correo">Correo Electrónico <span style="color: #dc3545;">*</span></label>
                            <input type="email" id="correo" name="correo" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['correo']); ?>" 
                                   required>
                            <div class="form-info">Debe ser un correo válido y único</div>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="cedula">Cédula <span style="color: #dc3545;">*</span></label>
                            <input type="text" id="cedula" name="cedula" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['cedula']); ?>" 
                                   required pattern="[0-9]{6,10}" 
                                   title="Solo números, entre 6 y 10 dígitos">
                            <div class="form-info">Solo números, sin puntos ni guiones</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['telefono']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        Configuración de Acceso
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="rol">Rol del Usuario <span style="color: #dc3545;">*</span></label>
                            <select id="rol" name="rol" class="form-control" required>
                                <option value="usuario" <?php echo $user['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario Normal</option>
                                <option value="admin" <?php echo $user['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                <option value="vendedor" <?php echo $user['rol'] === 'vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                            </select>
                            <div class="form-info">Determine los permisos del usuario</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Estado de la Cuenta</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" 
                                       value="1" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active">Cuenta activa</label>
                            </div>
                            <div class="form-info">Si se desactiva, el usuario no podrá iniciar sesión</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_id">ID del Usuario</label>
                        <input type="text" id="user_id" class="form-control readonly-field" 
                               value="<?php echo $user['id']; ?>" readonly>
                        <div class="form-info">ID único del usuario en el sistema</div>
                    </div>
                </div>
                
                <div class="actions">
                    <a href="/proyecto/panel_admin/panel_admin.html?id=<?php echo $user['id']; ?>" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Restablecer
                    </button>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validación en tiempo real del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const cedulaInput = document.getElementById('cedula');
            const telefonoInput = document.getElementById('telefono');
            const emailInput = document.getElementById('correo');
            
            // Validar cédula (solo números)
            cedulaInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            // Validar teléfono (solo números y espacios opcionales)
            telefonoInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9\s]/g, '');
            });
            
            // Validar formato de email en tiempo real
            emailInput.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.value)) {
                    this.style.borderColor = '#dc3545';
                    this.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
                } else {
                    this.style.borderColor = '#28a745';
                    this.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.1)';
                }
            });
            
            // Confirmar antes de enviar
            form.addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                const correo = document.getElementById('correo').value.trim();
                const cedula = document.getElementById('cedula').value.trim();
                
                if (!nombre || !correo || !cedula) {
                    e.preventDefault();
                    alert('Por favor complete todos los campos requeridos (*)');
                    return;
                }
                
                if (!confirm('¿Está seguro de guardar los cambios en este usuario?')) {
                    e.preventDefault();
                }
            });
            
            // Efecto de animación en los botones
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>