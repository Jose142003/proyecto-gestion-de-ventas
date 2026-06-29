<?php
// editar_producto.php - Formulario para editar un producto
session_start();

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../config/i18n_helpers.php';
$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
setcookie('lang', $locale, time()+31536000, '/');
\I18n::load($locale);

// Verificar autenticación y permisos de administrador
$isAdmin = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin') {
    $isAdmin = true;
} elseif (isset($_SESSION['user_correo'])) {
    try {
        $tmpPdo = Database::getConnection();
        $tmpStmt = $tmpPdo->prepare("SELECT id FROM admin_users WHERE correo = ? AND activo = 1 LIMIT 1");
        $tmpStmt->execute([$_SESSION['user_correo']]);
        if ($tmpStmt->fetch()) {
            $isAdmin = true;
            $_SESSION['user_rol'] = 'admin';
        }
    } catch (Throwable $e) {
        $isAdmin = false;
    }
}

if (!$isAdmin) {
    header('Location: ' . url('/interfaz_usuario/login.html'));
    exit;
}

// Función helper para manejar valores null de forma segura
function safeStripTags($value) {
    return strip_tags($value ?? '');
}

// Verificar CSRF en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
}

// Obtener ID del producto
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si no hay ID válido, mostrar error
if ($id === 0) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="<?php echo htmlspecialchars($locale); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - ID no válido</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background: #f8f9fa; 
                padding: 50px; 
                text-align: center; 
            }
            .error-container { 
                background: white; 
                padding: 40px; 
                border-radius: 10px; 
                box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
                max-width: 600px; 
                margin: 0 auto; 
            }
            .error-icon { 
                font-size: 50px; 
                color: #dc3545; 
                margin-bottom: 20px; 
            }
            .btn { 
                display: inline-block; 
                margin-top: 20px; 
                padding: 10px 20px; 
                background: #007bff; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <h1>ID de Producto no Válido</h1>
            <p>El ID proporcionado no es válido o no se especificó.</p>
            <a href="productos.php" class="btn">Ver Productos</a>
            <a href="<?= url('/panel_admin/panel_admin.php') ?>" class="btn">Panel Admin</a>
        </div>
    </body>
    </html>';
    exit();
}

// Variables
$producto = null;
$error = '';
$success = '';
$categorias = ['Sensores', 'Contactores', 'Relés', 'Variadores', 'Fuentes de Poder', 'Instrumentos de Medición', 'Botoneras', 'Protecciones', 'Temporizadores', 'Controladores', 'Accesorios', 'General'];
$monedas = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'MXN'];

// Procesar formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        // Obtener datos del formulario
        $nombre = $_POST['nombre'];
        $sku = $_POST['sku'];
        $descripcion = $_POST['descripcion'];
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);
        $categoria = $_POST['categoria'];
        $imagen = mb_substr($_POST['imagen'], 0, 512);
        $rating = floatval($_POST['rating']);
        $specs = $_POST['specs'];
        $peso = floatval($_POST['peso']);
        $dimensiones = substr($_POST['dimensiones'], 0, 100);
        $moneda = $_POST['moneda'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        
        // Validaciones básicas
        if (empty($nombre) || empty($sku) || $precio <= 0) {
            $error = 'Nombre, SKU y precio son campos requeridos';
        } else {
            // Actualizar producto en la base de datos
            $sql = "UPDATE products SET 
                    name = ?,
                    sku = ?,
                    description = ?,
                    price = ?,
                    stock = ?,
                    category = ?,
                    image_url = ?,
                    rating = ?,
                    specs = ?,
                    weight = ?,
                    dimensions = ?,
                    currency = ?,
                    is_featured = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre, $sku, $descripcion, $precio, $stock, $categoria,
                $imagen, $rating, $specs, $peso, $dimensiones, $moneda,
                $destacado, $id
            ]);
            
            if ($stmt->rowCount() > 0) {
                $success = 'Producto actualizado correctamente';
                auditoriaRegistrar('editar_producto', 'producto', "Producto ID $id editado: $nombre");
            } else {
                $error = 'Error al actualizar el producto';
            }
        }
        
    } catch (Exception $e) {
        error_log("Error en editar_producto (POST): " . $e->getMessage());
        $error = 'Error interno del servidor';
    }
}

// Obtener datos actuales del producto
try {
    $pdo = conectarDB();

    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        $error = 'Producto no encontrado';
    }
    
} catch (Exception $e) {
    error_log("Error en editar_producto (GET): " . $e->getMessage());
    $error = 'Error interno del servidor';
}

// Si hay error al obtener el producto, mostrar mensaje
if ($error && !$producto) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="<?php echo htmlspecialchars($locale); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Producto no encontrado</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background: #f8f9fa; 
                padding: 50px; 
                text-align: center; 
            }
            .error-container { 
                background: white; 
                padding: 40px; 
                border-radius: 10px; 
                box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
                max-width: 600px; 
                margin: 0 auto; 
            }
            .error-icon { 
                font-size: 50px; 
                color: #dc3545; 
                margin-bottom: 20px; 
            }
            .btn { 
                display: inline-block; 
                margin-top: 20px; 
                padding: 10px 20px; 
                background: #007bff; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
                margin-right: 10px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <h1>' . htmlspecialchars($error) . '</h1>
            <a href="<?= url('/panel_admin/panel_admin.php') ?>" class="btn">Ver Productos</a>
        </div>
    </body>
    </html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Editar Producto - PIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f0f2f5; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            width: 100%;
        }
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px;
        }
        .header { 
            background: linear-gradient(135deg, #050C18, #294E90); 
            color: white; 
            padding: 20px; 
            border-radius: 15px; 
            margin-bottom: 20px; 
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header-text { flex: 1; }
        .header h1 { font-size: 1.8rem; margin: 0 0 5px 0; }
        .header p { margin: 0; opacity: 0.9; }
        .header small { font-size: 0.8rem; opacity: 0.8; }
        .btn-volver {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-volver:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateX(-3px);
        }
        .card-custom { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.08); 
            margin-bottom: 20px; 
            overflow: hidden; 
            background: white;
        }
        .card-header-custom { 
            background: linear-gradient(135deg, #294E90, #3C91ED); 
            color: white; 
            padding: 15px 20px; 
            font-weight: 600; 
        }
        .card-body-custom { padding: 20px; }
        .form-label { 
            font-weight: 600; 
            color: #050C18; 
            margin-bottom: 8px; 
            display: block;
        }
        .form-control, .form-select { 
            border-radius: 10px; 
            border: 1px solid #ddd; 
            padding: 10px 15px; 
            width: 100%;
            font-size: 16px;
        }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .row { display: flex; flex-wrap: wrap; margin: 0 -10px; }
        .col { flex: 1; padding: 0 10px; }
        .col-12 { width: 100%; padding: 0 10px; }
        .col-md-6 { width: 50%; padding: 0 10px; }
        .btn-submit { 
            background: linear-gradient(135deg, #294E90, #3C91ED); 
            border: none; 
            padding: 12px 20px; 
            border-radius: 30px; 
            font-weight: 600; 
            color: white; 
            width: 100%;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-submit:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(60,145,237,0.3); 
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        .btn-secondary:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .checkbox-group label {
            font-weight: 600;
            color: #050C18;
            cursor: pointer;
            margin: 0;
        }
        .help-text { 
            color: #6c757d; 
            font-size: 0.75rem; 
            margin-top: 5px; 
        }
        body.dark-mode { background: #1a1a1a; }
        body.dark-mode .card-custom { background: #2a2a2a; color: #f0f2f5; }
        body.dark-mode .form-label { color: #f0f2f5; }
        body.dark-mode .form-control,
        body.dark-mode .form-select { background: #333; border-color: #555; color: #f0f2f5; }
        body.dark-mode .checkbox-group label { color: #f0f2f5; }
        body.dark-mode .help-text { color: #aaa; }
        .dark-mode-toggle {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #294E90, #3C91ED);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        .dark-mode-toggle:hover { transform: scale(1.1); }
        @media (max-width: 768px) {
            .dark-mode-toggle { width: 40px; height: 40px; font-size: 1.2rem; bottom: 15px; left: 15px; }
            .container { padding: 10px; }
            .header { padding: 15px; }
            .header-content { flex-direction: column; text-align: center; }
            .header-text { text-align: center; }
            .header h1 { font-size: 1.5rem; }
            .btn-volver { align-self: center; }
            .card-header-custom { padding: 12px 15px; font-size: 1rem; }
            .card-body-custom { padding: 15px; }
            .col-md-6 { width: 100%; margin-bottom: 15px; }
            .row { flex-direction: column; }
            .form-control, .form-select, .btn-submit { font-size: 16px; }
            .form-actions { flex-direction: column; }
            .form-actions .btn-secondary,
            .form-actions .btn-success { width: 100%; }
        }
        @media (max-width: 480px) {
            .header h1 { font-size: 1.2rem; }
            .form-label { font-size: 0.85rem; }
        }
        html { scroll-behavior: smooth; }
        button, .btn, .btn-volver, select, input[type="submit"] {
            cursor: pointer;
            min-height: 44px;
        }
        input, select, textarea { font-size: 16px !important; }
        :focus { outline: 2px solid #3C91ED; outline-offset: 2px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-content">
            <div class="header-text">
                <h1><i class="fas fa-edit"></i> Editar Producto</h1>
                <p>Modifica los datos del producto #<?php echo htmlspecialchars($id ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                <small><i class="fas fa-info-circle"></i> Los campos marcados con * son obligatorios</small>
            </div>
            <div>
                <a href="<?= url('/panel_admin/panel_admin.php') ?>" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> <?php echo __('back'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <span>✅</span> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-error">
            <span>❌</span> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card-custom">
        <div class="card-header-custom">
            <i class="fas fa-box"></i> Datos del Producto
        </div>
        <div class="card-body-custom">
            <form method="POST" action="">
                <?php echo campoCSRF(); ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?php echo htmlspecialchars(safeStripTags($producto['name'])); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">SKU (Código único) *</label>
                            <input type="text" class="form-control" name="sku" 
                                   value="<?php echo htmlspecialchars(safeStripTags($producto['sku'])); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('price'); ?> *</label>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($producto['price']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Stock disponible</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0"
                                   value="<?php echo htmlspecialchars($producto['stock']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
<label class="form-label"><?php echo __('category'); ?></label>
            <select class="form-select" name="categoria">
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo ($producto['category'] == $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Calificación (0-5)</label>
                            <input type="number" class="form-control" id="rating" name="rating" step="0.1" min="0" max="5"
                                   value="<?php echo htmlspecialchars($producto['rating']); ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">URL de la Imagen</label>
                            <input type="text" class="form-control" id="imagen" name="imagen" 
                                   value="<?php echo htmlspecialchars($producto['image_url'] ?? ''); ?>"
                                   placeholder="https://ejemplo.com/imagen.jpg">
                            <?php if (!empty($producto['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($producto['image_url']); ?>" 
                                     alt="Vista previa" class="preview-image"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('description'); ?></label>
                            <textarea class="form-control" name="descripcion" rows="4"><?php echo htmlspecialchars(safeStripTags($producto['description'])); ?></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Especificaciones adicionales</label>
                            <textarea class="form-control" name="specs" rows="3" placeholder="Características técnicas, especificaciones..."><?php echo htmlspecialchars(safeStripTags($producto['specs'])); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Peso (kg)</label>
                            <input type="number" class="form-control" name="peso" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($producto['weight']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Dimensiones</label>
                            <input type="text" class="form-control" name="dimensiones" 
                                   value="<?php echo htmlspecialchars(safeStripTags($producto['dimensions'])); ?>"
                                   placeholder="Largo x Ancho x Alto">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Moneda</label>
                            <select class="form-select" name="moneda">
                                <?php foreach ($monedas as $mon): ?>
                                    <option value="<?php echo htmlspecialchars($mon); ?>" 
                                        <?php echo ($producto['currency'] == $mon) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mon); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <div class="checkbox-group">
                                <input type="checkbox" id="destacado" name="destacado" 
                                       <?php echo $producto['is_featured'] ? 'checked' : ''; ?>>
                                <label for="destacado">Producto destacado</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="detalles_producto.php?id=<?php echo $id; ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> <?php echo __('cancel'); ?>
                    </a>
                    <button type="submit" class="btn-success">
                        <i class="fas fa-save"></i> <?php echo __('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<button class="dark-mode-toggle" id="darkModeToggle" aria-label="Modo oscuro/claro">
    <i class="fas fa-moon"></i>
</button>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const imagenInput = document.getElementById('imagen');
    
    function actualizarPreview() {
        const previewContainer = imagenInput.closest('.mb-3');
        const oldPreview = previewContainer.querySelector('.preview-image');
        if (oldPreview) oldPreview.remove();
        
        if (imagenInput.value.trim()) {
            const img = document.createElement('img');
            img.src = imagenInput.value;
            img.alt = 'Vista previa';
            img.className = 'preview-image';
            img.onerror = function() { this.style.display = 'none'; };
            previewContainer.appendChild(img);
        }
    }
    
    if (imagenInput) {
        imagenInput.addEventListener('change', actualizarPreview);
        actualizarPreview();
    }
    
    document.querySelector('form').addEventListener('submit', function(e) {
        const precio = document.getElementById('precio');
        const stock = document.getElementById('stock');
        const rating = document.getElementById('rating');
        
        if (precio && precio.value <= 0) {
            alert('El precio debe ser mayor a 0');
            precio.focus();
            e.preventDefault();
            return false;
        }
        
        if (stock && stock.value < 0) {
            alert('El stock no puede ser negativo');
            stock.focus();
            e.preventDefault();
            return false;
        }
        
        if (rating && (rating.value < 0 || rating.value > 5)) {
            alert('La calificación debe estar entre 0 y 5');
            rating.focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    function initDarkMode() {
        const toggle = document.getElementById('darkModeToggle');
        const saved = localStorage.getItem('adminDarkMode');
        if (saved === 'enabled') {
            document.body.classList.add('dark-mode');
            if (toggle) toggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        if (toggle) {
            toggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                const isDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('adminDarkMode', isDark ? 'enabled' : 'disabled');
                toggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            });
        }
    }
    
    document.addEventListener('DOMContentLoaded', initDarkMode);
</script>
</body>
</html>