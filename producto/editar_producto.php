<?php
// editar_producto.php - Formulario para editar un producto
session_start();

// Verificar autenticación y permisos de administrador
$isAdmin = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin') {
    $isAdmin = true;
} elseif (isset($_SESSION['user_correo']) && (stripos($_SESSION['user_correo'], 'picca.ventas@gmail.com') !== false || stripos($_SESSION['user_correo'], 'admin') !== false)) {
    $isAdmin = true;
    $_SESSION['user_rol'] = 'admin';
}

if (!$isAdmin) {
    header('Location: /proyecto/interfaz_usuario/login.html');
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

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
            <a href="/proyecto/panel_admin/panel_admin.php" class="btn">Panel Admin</a>
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
        
        // Obtener datos del formulario
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
<a href="/proyecto/panel_admin/panel_admin.php" class="btn">Ver Productos</a>
            </div>
        </div>';
    exit;
}
?><!-- Mensajes --><?php if ($success): ?>
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