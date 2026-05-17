<?php
/**
 * editar_proveedor.php
 * Formulario para editar un proveedor existente
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    header('Location: /proyecto/usuario/login.html');
    exit;
}

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
$usuario_nombre = $_SESSION['nombre'] ?? $_SESSION['usuario_nombre'] ?? 'Administrador';
$usuario_rol = $_SESSION['rol'] ?? 'admin';

// Verificar permisos
if (!in_array($usuario_rol, ['admin', 'superadmin'])) {
    die('Acceso denegado. No tiene permisos para editar proveedores.');
}

require_once dirname(__DIR__) . '/conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

$mensaje = '';
$tipo_mensaje = '';
$proveedor = null;

// Obtener ID del proveedor
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($id <= 0) {
    header('Location: /proyecto/admin-panel/panel_admin.html?error=ID no válido');
    exit;
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_comercial = trim($_POST['nombre_comercial'] ?? '');
    $razon_social = trim($_POST['razon_social'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $estado = $_POST['estado'] ?? 'activo';

    // Validaciones
    $errores = [];

    if (empty($nombre_comercial)) {
        $errores[] = 'El nombre comercial es obligatorio';
    }
    if (empty($telefono)) {
        $errores[] = 'El teléfono es obligatorio';
    }
    if (empty($email)) {
        $errores[] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido';
    }

    if (empty($errores)) {
        try {
            $sql = "UPDATE proveedores SET 
                        nombre_comercial = :nombre,
                        razon_social = :razon_social,
                        ruc = :ruc,
                        direccion = :direccion,
                        telefono_principal = :telefono,
                        email_principal = :email,
                        contacto_nombre = :contacto,
                        estado = :estado,
                        updated_at = NOW()
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre_comercial,
                ':razon_social' => $razon_social ?: null,
                ':ruc' => $ruc ?: null,
                ':direccion' => $direccion ?: null,
                ':telefono' => $telefono,
                ':email' => $email,
                ':contacto' => $contacto ?: null,
                ':estado' => $estado,
                ':id' => $id
            ]);

            // Registrar en auditoría
            $audit_sql = "INSERT INTO auditoria_logs (usuario_id, usuario_nombre, usuario_rol, accion, modulo, descripcion, tabla_afectada, registro_id, ip_address) 
                          VALUES (:usuario_id, :usuario_nombre, :usuario_rol, 'editar', 'proveedores', :descripcion, 'proveedores', :registro_id, :ip)";
            $audit_stmt = $pdo->prepare($audit_sql);
            $audit_stmt->execute([
                ':usuario_id' => $usuario_id,
                ':usuario_nombre' => $usuario_nombre,
                ':usuario_rol' => $usuario_rol,
                ':descripcion' => "Proveedor editado: $nombre_comercial",
                ':registro_id' => $id,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            // Redirigir al panel_admin.html con mensaje de éxito
            header('Location: /proyecto/admin-panel/panel_admin.html?mensaje=Proveedor actualizado correctamente&tipo=success');
            exit;

        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = implode('<br>', $errores);
        $tipo_mensaje = 'error';
    }
}

// Cargar datos del proveedor
if (!$proveedor) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt->execute([$id]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$proveedor) {
            header('Location: /proyecto/admin-panel/panel_admin.html?error=Proveedor no encontrado');
            exit;
        }
    } catch (PDOException $e) {
        die('Error al cargar proveedor: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Proveedor - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #050C18;
            --secondary-color: #294E90;
            --accent-color: #3C91ED;
            --light-color: #7EBDE9;
            --bg-color: #F3F3F3;
            --text-color: #050C18;
            --card-bg: #ffffff;
            --success: #2ed573;
            --danger: #ff4757;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 30px;
        }

        .form-header h2 {
            font-size: 1.6rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .form-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-color);
        }

        .form-label .required {
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(60, 145, 237, 0.1);
        }

        select.form-control {
            cursor: pointer;
            background-color: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), var(--light-color));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(60, 145, 237, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-back {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-back:hover {
            background-color: var(--secondary-color);
        }

        .info-box {
            background: #e8f4fd;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: var(--secondary-color);
        }

        .info-box i {
            font-size: 1.1rem;
        }

        @media (max-width: 600px) {
            body { padding: 20px; }
            .form-body { padding: 20px; }
            .form-row { grid-template-columns: 1fr; gap: 15px; }
            .form-actions { flex-direction: column-reverse; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-edit"></i> Editar Proveedor</h2>
            <p>Modifique los datos del proveedor en el sistema</p>
        </div>

        <div class="form-body">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <span>Los campos marcados con <strong class="required">*</strong> son obligatorios</span>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $proveedor['id']; ?>">

                <div class="form-group">
                    <label class="form-label"><span class="required">*</span> Nombre Comercial</label>
                    <input type="text" name="nombre_comercial" class="form-control" 
                           value="<?php echo htmlspecialchars($proveedor['nombre_comercial']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Razón Social</label>
                    <input type="text" name="razon_social" class="form-control" 
                           value="<?php echo htmlspecialchars($proveedor['razon_social'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">RUC / Documento</label>
                        <input type="text" name="ruc" class="form-control" 
                               value="<?php echo htmlspecialchars($proveedor['ruc'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="required">*</span> Teléfono</label>
                        <input type="text" name="telefono" class="form-control" 
                               value="<?php echo htmlspecialchars($proveedor['telefono_principal']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><span class="required">*</span> Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($proveedor['email_principal']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Persona de Contacto</label>
                        <input type="text" name="contacto" class="form-control" 
                               value="<?php echo htmlspecialchars($proveedor['contacto_nombre'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" class="form-control" rows="3"><?php echo htmlspecialchars($proveedor['direccion'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-control">
                            <option value="activo" <?php echo ($proveedor['estado'] ?? 'activo') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo ($proveedor['estado'] ?? '') == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                            <option value="suspendido" <?php echo ($proveedor['estado'] ?? '') == 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor['codigo'] ?? 'N/A'); ?>" disabled>
                        <small style="color:#666;">El código se genera automáticamente</small>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="/proyecto/admin-panel/panel_admin.html" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Volver al Panel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Confirmar antes de salir si hay cambios sin guardar
        let formularioModificado = false;
        const inputs = document.querySelectorAll('form input, form select, form textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => { formularioModificado = true; });
            input.addEventListener('input', () => { formularioModificado = true; });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formularioModificado) {
                e.preventDefault();
                e.returnValue = 'Hay cambios sin guardar. ¿Estás seguro de que quieres salir?';
                return e.returnValue;
            }
        });

        document.querySelector('form').addEventListener('submit', () => {
            formularioModificado = false;
        });
    </script>
</body>
</html>