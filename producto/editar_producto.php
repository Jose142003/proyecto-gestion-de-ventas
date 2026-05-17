<?php
// editar_producto.php - Formulario para editar un producto

require_once '../conexion/conexion.php';

// Obtener ID del producto
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si no hay ID válido, mostrar error
if ($id === 0) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="es">
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
            <a href="/proyecto/admin-panel/panel_admin.php" class="btn">admin-panel</a>
        </div>
    </body>
    </html>';
    exit();
}

// Variables
$producto = null;
$error = '';
$success = '';
$categorias = ['Electrónica', 'Ropa', 'Hogar', 'Deportes', 'Libros', 'Juguetes', 'Alimentos', 'Belleza'];
$monedas = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'MXN'];

// Procesar formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        // Obtener y sanitizar datos del formulario
        $nombre = $_POST['nombre'];
        $sku = $_POST['sku'];
        $descripcion = $_POST['descripcion'];
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);
        $categoria = $_POST['categoria'];
        $imagen = $_POST['imagen'];
        $rating = floatval($_POST['rating']);
        $specs = $_POST['specs'];
        $peso = floatval($_POST['peso']);
        // SOLUCIÓN: Truncar dimensiones a 100 caracteres máximo (VARCHAR(100) en BD)
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
            
            if ($stmt->execute([
                $nombre, $sku, $descripcion, $precio, $stock, $categoria,
                $imagen, $rating, $specs, $peso, $dimensiones, $moneda,
                $destacado, $id
            ])) {
                $success = 'Producto actualizado correctamente';
            } else {
                $errorInfo = $stmt->errorInfo();
                $error = 'Error al actualizar el producto: ' . ($errorInfo[2] ?? 'Error desconocido');
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Obtener datos actuales del producto
try {
    $pdo = conectarDB();

    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        $error = 'Producto no encontrado';
    } else {
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = 'Error al obtener el producto: ' . $e->getMessage();
}

// Si hay error al obtener el producto, mostrar mensaje
if ($error && !$producto) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="es">
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
            <a href="/proyecto/admin-panel/panel_admin.html" class="btn">Ver Productos</a>
            <a href="detalles_producto.php?id=' . $id . '" class="btn">Volver a Detalles</a>
        </div>
    </body>
    </html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - <?php echo htmlspecialchars($producto['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: linear-gradient(90deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 25px 30px;
        }
        
        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        /* Mensajes */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Formulario */
        .form-container {
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            padding: 10px;
            background: #f8f9fa;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .header-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>✏️ Editar Producto: <?php echo htmlspecialchars($producto['name']); ?></h1>
            <div class="header-actions">
                <a href="detalles_producto.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    ← Volver a Detalles
                </a>
                <a href="/proyecto/admin-panel/panel_admin.html" class="btn btn-secondary">
                    📋 Ver Todos los Productos
                </a>
            </div>
        </div>
        
        <!-- Mensajes -->
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
        
        <!-- Formulario -->
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-grid">
                    <!-- Información básica -->
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($producto['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sku">SKU (Código único) *</label>
                        <input type="text" id="sku" name="sku" 
                               value="<?php echo htmlspecialchars($producto['sku']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($producto['price']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock disponible</label>
                        <input type="number" id="stock" name="stock" min="0"
                               value="<?php echo htmlspecialchars($producto['stock']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria">
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>" 
                                    <?php echo ($producto['category'] == $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Calificación (0-5)</label>
                        <input type="number" id="rating" name="rating" step="0.1" min="0" max="5"
                               value="<?php echo htmlspecialchars($producto['rating']); ?>">
                    </div>
                    
                    <!-- Imagen -->
                    <div class="form-group full-width">
                        <label for="imagen">URL de la Imagen</label>
                        <input type="text" id="imagen" name="imagen" 
                               value="<?php echo htmlspecialchars($producto['image_url']); ?>"
                               placeholder="https://ejemplo.com/imagen.jpg">
                        <?php if ($producto['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($producto['image_url']); ?>" 
                                 alt="Vista previa" class="preview-image"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="form-group full-width">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($producto['description']); ?></textarea>
                    </div>
                    
                    <!-- Especificaciones -->
                    <div class="form-group full-width">
                        <label for="specs">Especificaciones adicionales</label>
                        <textarea id="specs" name="specs" rows="3" placeholder="Características técnicas, especificaciones..."><?php echo htmlspecialchars($producto['specs']); ?></textarea>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="form-group">
                        <label for="peso">Peso (kg)</label>
                        <input type="number" id="peso" name="peso" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($producto['weight']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="dimensiones">Dimensiones</label>
                        <input type="text" id="dimensiones" name="dimensiones" 
                               value="<?php echo htmlspecialchars($producto['dimensions']); ?>"
                               placeholder="Largo x Ancho x Alto">
                    </div>
                    
                    <div class="form-group">
                        <label for="moneda">Moneda</label>
                        <select id="moneda" name="moneda">
                            <?php foreach ($monedas as $mon): ?>
                                <option value="<?php echo $mon; ?>" 
                                    <?php echo ($producto['currency'] == $mon) ? 'selected' : ''; ?>>
                                    <?php echo $mon; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Opciones -->
                    <div class="form-group full-width">
                        <div class="checkbox-group">
                            <input type="checkbox" id="destacado" name="destacado" 
                                   <?php echo $producto['is_featured'] ? 'checked' : ''; ?>>
                            <label for="destacado">Producto destacado</label>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones del formulario -->
                <div class="form-actions">
                    <a href="detalles_producto.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        💾 Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Previsualización de imagen
        const imagenInput = document.getElementById('imagen');
        const previewContainer = imagenInput.parentElement;
        
        function actualizarPreview() {
            // Eliminar preview anterior
            const oldPreview = previewContainer.querySelector('.preview-image');
            if (oldPreview) {
                oldPreview.remove();
            }
            
            // Crear nueva preview si hay URL
            if (imagenInput.value.trim()) {
                const img = document.createElement('img');
                img.src = imagenInput.value;
                img.alt = 'Vista previa';
                img.className = 'preview-image';
                img.onerror = function() {
                    this.style.display = 'none';
                };
                previewContainer.appendChild(img);
            }
        }
        
        imagenInput.addEventListener('change', actualizarPreview);
        
        // Inicializar preview
        actualizarPreview();
        
        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const precio = document.getElementById('precio');
            const stock = document.getElementById('stock');
            const rating = document.getElementById('rating');
            
            if (precio.value <= 0) {
                alert('El precio debe ser mayor a 0');
                precio.focus();
                e.preventDefault();
                return false;
            }
            
            if (stock.value < 0) {
                alert('El stock no puede ser negativo');
                stock.focus();
                e.preventDefault();
                return false;
            }
            
            if (rating.value < 0 || rating.value > 5) {
                alert('La calificación debe estar entre 0 y 5');
                rating.focus();
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>