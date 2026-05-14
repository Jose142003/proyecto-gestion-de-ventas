<?php
// /proyecto/producto/crear_producto.php
// IMPORTADOR MANUAL - VERSIÓN RESPONSIVE

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
    header('Location: /proyecto/login.html');
    exit;
}

// Configuración de base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "carrito_db";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Palabras prohibidas
$palabras_prohibidas = ['prueba', 'test', 'demo', 'xxxx', 'basura', 'eliminar', 'jose chacon', 'jose', 'marivic', 'chacon'];

// Función para generar SKU con formato PROD-XXXX
function generarSKU($conn, $nombre) {
    $result = $conn->query("SELECT MAX(id) as max_id FROM products");
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 80) + 1;
    return 'PROD-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

// Función para detectar categoría
function detectarCategoria($nombre) {
    $nombre_lower = strtolower($nombre);
    
    $categorias = [
        'sensor' => 'Sensores',
        'contactor' => 'Contactores',
        'rele' => 'Relés',
        'variador' => 'Variadores',
        'fuente' => 'Fuentes de Poder',
        'temporizador' => 'Temporizadores',
        'controlador' => 'Controladores',
        'boton' => 'Botoneras',
        'guardamotor' => 'Protecciones',
        'pinza' => 'Instrumentos de Medición',
        'multimetro' => 'Instrumentos de Medición',
        'timer' => 'Temporizadores',
        'at8n' => 'Temporizadores'
    ];
    
    foreach ($categorias as $keyword => $categoria) {
        if (strpos($nombre_lower, $keyword) !== false) {
            return $categoria;
        }
    }
    return 'General';
}

// Función para verificar si producto existe
function productoExiste($conn, $nombre) {
    $stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    return $existe;
}

// Función para crear producto
function crearProducto($conn, $datos, $usuario_id, $usuario_nombre) {
    $sku = generarSKU($conn, $datos['nombre']);
    $categoria = $datos['categoria'] ?: detectarCategoria($datos['nombre']);
    $descripcion = $datos['descripcion'] ?: "Producto importado manualmente. " . $datos['nombre'];
    
    $active = 1;
    $rating = 4.0;
    $stock = isset($datos['stock']) ? (int)$datos['stock'] : 5;
    
    $stmt = $conn->prepare("INSERT INTO products (sku, name, price, image_url, description, category, rating, stock, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssssii", $sku, $datos['nombre'], $datos['precio'], $datos['imagen'], $descripcion, $categoria, $rating, $stock, $active);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $id, 'sku' => $sku, 'nombre' => $datos['nombre']];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error, 'nombre' => $datos['nombre']];
    }
}

$usuario_id = $_SESSION['user_id'] ?? null;
$usuario_nombre = $_SESSION['user_nombre'] ?? $_SESSION['user_correo'] ?? 'Admin';
$mensaje = '';
$tipo_mensaje = '';
$importados = [];
$errores = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'importar_individual') {
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $imagen = trim($_POST['imagen'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $stock = intval($_POST['stock'] ?? 5);
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        $errores_validacion = [];
        
        if (empty($nombre)) $errores_validacion[] = "El nombre es obligatorio";
        if ($precio <= 0) $errores_validacion[] = "El precio debe ser mayor a 0";
        if (productoExiste($conn, $nombre)) $errores_validacion[] = "El producto ya existe en la base de datos";
        
        foreach ($palabras_prohibidas as $prohibida) {
            if (stripos($nombre, $prohibida) !== false) {
                $errores_validacion[] = "El nombre contiene la palabra prohibida: $prohibida";
                break;
            }
        }
        
        if (empty($errores_validacion)) {
            $datos = [
                'nombre' => $nombre,
                'precio' => $precio,
                'imagen' => $imagen,
                'categoria' => $categoria,
                'stock' => $stock,
                'descripcion' => $descripcion
            ];
            $resultado = crearProducto($conn, $datos, $usuario_id, $usuario_nombre);
            if ($resultado['success']) {
                $importados[] = $resultado;
                $mensaje = "✅ Producto importado correctamente. SKU: " . $resultado['sku'];
                $tipo_mensaje = "success";
            } else {
                $mensaje = "❌ Error: " . $resultado['error'];
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "❌ " . implode("<br>❌ ", $errores_validacion);
            $tipo_mensaje = "danger";
        }
    }
    
    elseif (isset($_POST['accion']) && $_POST['accion'] === 'importar_multiple') {
        $texto_pegado = trim($_POST['productos_texto'] ?? '');
        $lineas = explode("\n", $texto_pegado);
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;
            
            $partes = explode('|', $linea);
            if (count($partes) >= 2) {
                $nombre = trim($partes[0]);
                $precio = floatval(trim($partes[1]));
                $imagen = isset($partes[2]) ? trim($partes[2]) : '';
                $stock = isset($partes[3]) ? intval(trim($partes[3])) : 5;
                $categoria = '';
                
                $es_valido = true;
                foreach ($palabras_prohibidas as $prohibida) {
                    if (stripos($nombre, $prohibida) !== false) {
                        $es_valido = false;
                        break;
                    }
                }
                
                if (!empty($nombre) && $precio > 0 && !productoExiste($conn, $nombre) && $es_valido) {
                    $datos = [
                        'nombre' => $nombre,
                        'precio' => $precio,
                        'imagen' => $imagen,
                        'categoria' => $categoria,
                        'stock' => $stock,
                        'descripcion' => ''
                    ];
                    $resultado = crearProducto($conn, $datos, $usuario_id, $usuario_nombre);
                    if ($resultado['success']) {
                        $importados[] = $resultado;
                    } else {
                        $errores[] = $resultado;
                    }
                } else {
                    $errores[] = ['nombre' => $nombre, 'error' => 'Nombre vacío, precio inválido o producto ya existe'];
                }
            }
        }
        
        if (count($importados) > 0) {
            $mensaje = "✅ Se importaron " . count($importados) . " productos correctamente";
            $tipo_mensaje = "success";
            if (count($errores) > 0) {
                $mensaje .= " (❌ " . count($errores) . " errores)";
            }
        } else {
            $mensaje = "❌ No se pudo importar ningún producto. Verifica el formato (Nombre | Precio | URL_imagen | Stock)";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener productos existentes
$productos_existentes = [];
$result = $conn->query("SELECT id, sku, name, price, active FROM products ORDER BY id DESC LIMIT 20");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $productos_existentes[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Importar Productos - PIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== RESET Y CONFIGURACIÓN BASE RESPONSIVE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        /* ===== HEADER RESPONSIVE ===== */
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
        
        .header-text {
            flex: 1;
        }
        
        .header h1 { 
            font-size: 1.8rem; 
            margin: 0 0 5px 0;
        }
        
        .header p { 
            margin: 0; 
            opacity: 0.9;
        }
        
        .header small {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
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
        
        /* ===== TARJETAS RESPONSIVE ===== */
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
        
        .card-body-custom {
            padding: 20px;
        }
        
        /* ===== FORMULARIO RESPONSIVE ===== */
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
            font-size: 16px; /* Previene zoom en iOS */
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Grid de filas responsive */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .col {
            flex: 1;
            padding: 0 10px;
        }
        
        .col-12 {
            width: 100%;
            padding: 0 10px;
        }
        
        .col-md-6 {
            width: 50%;
            padding: 0 10px;
        }
        
        /* ===== BOTONES ===== */
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
        
        .btn-submit-multiple {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        /* ===== TABLA RESPONSIVE ===== */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table {
            width: 100%;
            min-width: 500px;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        /* ===== ALERTAS ===== */
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* ===== HELP TEXT ===== */
        .help-text { 
            color: #6c757d; 
            font-size: 0.75rem; 
            margin-top: 5px; 
        }
        
        /* ===== BADGES ===== */
        .badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .badge-success { background-color: #28a745; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        
        /* ===== MODO OSCURO ===== */
        body.dark-mode {
            background: #1a1a1a;
        }
        
        body.dark-mode .card-custom {
            background: #2a2a2a;
            color: #f0f2f5;
        }
        
        body.dark-mode .form-label {
            color: #f0f2f5;
        }
        
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background: #333;
            border-color: #555;
            color: #f0f2f5;
        }
        
        body.dark-mode .table th {
            background: #333;
            color: #f0f2f5;
        }
        
        body.dark-mode .table td {
            border-bottom-color: #444;
            color: #ddd;
        }
        
        body.dark-mode .help-text {
            color: #aaa;
        }
        
        /* ===== MEDIA QUERIES ===== */
        @media (max-width: 992px) {
            .header h1 { font-size: 1.5rem; }
            .header p { font-size: 0.85rem; }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 15px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .header-text {
                text-align: center;
            }
            
            .btn-volver {
                align-self: center;
            }
            
            .card-header-custom {
                padding: 12px 15px;
                font-size: 1rem;
            }
            
            .card-body-custom {
                padding: 15px;
            }
            
            .col-md-6 {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .row {
                flex-direction: column;
            }
            
            .form-control, .form-select, .btn-submit {
                font-size: 16px;
            }
            
            .table th, .table td {
                padding: 8px;
                font-size: 0.85rem;
            }
            
            .alert {
                font-size: 0.85rem;
                padding: 10px 12px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 { 
                font-size: 1.2rem; 
            }
            
            .header p {
                font-size: 0.75rem;
            }
            
            .header small {
                font-size: 0.7rem;
            }
            
            .card-header-custom {
                font-size: 0.9rem;
                padding: 10px 12px;
            }
            
            .form-label {
                font-size: 0.85rem;
            }
            
            .btn-submit {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .table th, .table td {
                padding: 6px;
                font-size: 0.75rem;
            }
            
            code {
                font-size: 0.7rem;
            }
        }
        
        /* ===== SCROLL SUAVE ===== */
        html {
            scroll-behavior: smooth;
        }
        
        /* ===== TOUCH FRIENDLY ===== */
        button, 
        .btn, 
        .btn-volver,
        select,
        input[type="submit"] {
            cursor: pointer;
            min-height: 44px; /* Tamaño mínimo para touch */
        }
        
        input, select, textarea {
            font-size: 16px !important; /* Previene zoom en iOS */
        }
        
        /* ===== ANIMACIONES ===== */
        .import-card {
            transition: transform 0.3s ease;
        }
        
        @media (hover: hover) {
            .import-card:hover {
                transform: translateY(-3px);
            }
        }
        
        /* ===== BOTÓN MODO OSCURO FLOTANTE ===== */
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
        
        .dark-mode-toggle:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .dark-mode-toggle {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
                bottom: 15px;
                left: 15px;
            }
        }
        
        /* ===== LOADING SPINNER ===== */
        .spinner-small {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* ===== ACCESIBILIDAD ===== */
        :focus {
            outline: 2px solid #3C91ED;
            outline-offset: 2px;
        }
        
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid #3C91ED;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- HEADER RESPONSIVE -->
    <div class="header">
        <div class="header-content">
            <div class="header-text">
                <h1><i class="fas fa-hand-pointer"></i> Importación de Productos</h1>
                <p>Copia los datos y pégalos aquí</p>
                <small><i class="fas fa-info-circle"></i> Los SKUs se generan automáticamente con formato <strong>PROD-XXXX</strong></small>
            </div>
            <div>
                <a href="/proyecto/panel admin/panel_admin.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <!-- ALERTA DE MENSAJE -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            <span><?php echo $mensaje; ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- TARJETAS DE IMPORTACIÓN - DISEÑO RESPONSIVE -->
    <div class="row">
        <!-- Panel 1: Importación Individual -->
        <div class="col-md-6">
            <div class="card-custom import-card">
                <div class="card-header-custom">
                    <i class="fas fa-box"></i> Importar Producto Individual
                </div>
                <div class="card-body-custom">
                    <form method="POST" id="formIndividual">
                        <input type="hidden" name="accion" value="importar_individual">
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-tag"></i> Nombre del Producto *</label>
                            <input type="text" class="form-control" name="nombre" required 
                                   placeholder="Ej: Autonics AT8N Timer">
                            <div class="help-text">Copia el título exacto del producto</div>
                        </div>
                        
                        <div class="mb-3">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1;">
                                    <label class="form-label"><i class="fas fa-dollar-sign"></i> Precio (USD) *</label>
                                    <input type="number" step="0.01" class="form-control" name="precio" required placeholder="0.00">
                                </div>
                                <div style="flex: 1;">
                                    <label class="form-label"><i class="fas fa-boxes"></i> Stock</label>
                                    <input type="number" class="form-control" name="stock" value="5" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-image"></i> URL de la Imagen</label>
                            <input type="url" class="form-control" name="imagen" 
                                   placeholder="https://http2.mlstatic.com/...">
                            <div class="help-text">URL de la imagen del producto (opcional)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-folder"></i> Categoría</label>
                            <select class="form-select" name="categoria">
                                <option value="">Detectar automáticamente</option>
                                <option>Sensores</option>
                                <option>Contactores</option>
                                <option>Relés</option>
                                <option>Variadores</option>
                                <option>Fuentes de Poder</option>
                                <option>Instrumentos de Medición</option>
                                <option>Botoneras</option>
                                <option>Protecciones</option>
                                <option>Temporizadores</option>
                                <option>Controladores</option>
                                <option>Accesorios</option>
                                <option>General</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-align-left"></i> Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3" 
                                      placeholder="Características del producto..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit" id="btnIndividual">
                            <i class="fas fa-save"></i> Importar Producto
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel 2: Importación Múltiple -->
        <div class="col-md-6">
            <div class="card-custom import-card">
                <div class="card-header-custom">
                    <i class="fas fa-layer-group"></i> Importación Múltiple
                </div>
                <div class="card-body-custom">
                    <form method="POST" id="formMultiple">
                        <input type="hidden" name="accion" value="importar_multiple">
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-paste"></i> Pegar productos (uno por línea)</label>
                            <textarea class="form-control" name="productos_texto" rows="10" 
                                      placeholder="Formato: Nombre | Precio | URL_imagen | Stock

Ejemplo:
Autonics AT8N Timer | 25.00 | https://http2.mlstatic.com/... | 10
Sensor inductivo PR12-4DP | 95.00 | https://http2.mlstatic.com/... | 5"></textarea>
                            <div class="help-text mt-2">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Formato:</strong> Nombre | Precio | URL_imagen | Stock
                                <br>Separar cada campo con el símbolo pipe (|)
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit btn-submit-multiple" id="btnMultiple">
                            <i class="fas fa-cloud-upload-alt"></i> Importar Todos
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 3: Últimos productos en BD - Tabla Responsive -->
    <div class="card-custom">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #6c757d, #495057);">
            <i class="fas fa-database"></i> Últimos Productos en Base de Datos
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>SKU</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos_existentes)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay productos registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos_existentes as $prod): ?>
                            <tr>
                                <td><?php echo $prod['id']; ?></td>
                                <td><code><?php echo htmlspecialchars($prod['sku'] ?? 'N/A'); ?></code></td>
                                <td><?php echo htmlspecialchars(substr($prod['name'], 0, 50)); ?></td>
                                <td>$<?php echo number_format($prod['price'], 2); ?></td>
                                <td>
                                    <?php if ($prod['active'] == 1): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Botón flotante modo oscuro -->
<button class="dark-mode-toggle" id="darkModeToggle" aria-label="Modo oscuro/claro">
    <i class="fas fa-moon"></i>
</button>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ===== MODO OSCURO =====
    function initDarkMode() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const savedMode = localStorage.getItem('adminDarkMode');
        
        if (savedMode === 'enabled') {
            document.body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('adminDarkMode', isDark ? 'enabled' : 'disabled');
            darkModeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    }
    
    // ===== LOADING EN BOTONES =====
    function setupLoadingButtons() {
        const formIndividual = document.getElementById('formIndividual');
        const formMultiple = document.getElementById('formMultiple');
        const btnIndividual = document.getElementById('btnIndividual');
        const btnMultiple = document.getElementById('btnMultiple');
        
        if (formIndividual) {
            formIndividual.addEventListener('submit', () => {
                btnIndividual.disabled = true;
                btnIndividual.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';
            });
        }
        
        if (formMultiple) {
            formMultiple.addEventListener('submit', () => {
                btnMultiple.disabled = true;
                btnMultiple.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando todos...';
            });
        }
    }
    
    // ===== VALIDACIONES EN TIEMPO REAL =====
    function setupValidations() {
        const precioInput = document.querySelector('input[name="precio"]');
        if (precioInput) {
            precioInput.addEventListener('change', () => {
                if (precioInput.value <= 0) {
                    precioInput.setCustomValidity('El precio debe ser mayor a 0');
                } else {
                    precioInput.setCustomValidity('');
                }
            });
        }
        
        const nombreInput = document.querySelector('input[name="nombre"]');
        if (nombreInput) {
            nombreInput.addEventListener('input', () => {
                const palabrasProhibidas = ['prueba', 'test', 'demo', 'xxxx', 'basura', 'eliminar', 'jose', 'chacon'];
                const nombreLower = nombreInput.value.toLowerCase();
                const prohibida = palabrasProhibidas.find(p => nombreLower.includes(p));
                if (prohibida) {
                    nombreInput.setCustomValidity(`El nombre contiene la palabra prohibida: ${prohibida}`);
                } else {
                    nombreInput.setCustomValidity('');
                }
            });
        }
    }
    
    // ===== AUTO-CERRAR ALERTAS =====
    function autoCloseAlerts() {
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            });
        }, 5000);
    }
    
    // ===== MEJORAR EXPERIENCIA EN MÓVIL =====
    function improveMobileExperience() {
        // Ajustar textarea para que no haga zoom
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(ta => {
            ta.addEventListener('focus', () => {
                ta.style.fontSize = '16px';
            });
        });
        
        // Hacer que los selects sean más fáciles de tocar
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                this.style.backgroundColor = '#e8f0fe';
                setTimeout(() => {
                    this.style.backgroundColor = '';
                }, 200);
            });
        });
    }
    
    // ===== INICIALIZACIÓN =====
    document.addEventListener('DOMContentLoaded', () => {
        initDarkMode();
        setupLoadingButtons();
        setupValidations();
        autoCloseAlerts();
        improveMobileExperience();
        
        // Smooth scroll para el header
        const header = document.querySelector('.header');
        if (header && window.innerWidth <= 768) {
            header.style.scrollMarginTop = '10px';
        }
    });
</script>
</body>
</html>