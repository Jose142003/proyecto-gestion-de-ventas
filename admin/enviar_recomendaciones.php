<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../usuarios/config_email.php';

function obtenerRecomendacionesParaCliente(PDO $pdo, string $clienteEmail, int $limite = 6): array {
    $categorias = $pdo->prepare("
        SELECT p.category FROM pedidos ped
        JOIN pedido_detalles pd ON ped.id = pd.pedido_id
        JOIN products p ON pd.producto_id = p.id
        WHERE ped.cliente_id = (SELECT id FROM clientes WHERE email = ? LIMIT 1)
        AND p.active = 1
        GROUP BY p.category
        ORDER BY MAX(ped.created_at) DESC
        LIMIT 3
    ");
    $categorias->execute([$clienteEmail]);
    $cats = $categorias->fetchAll(PDO::FETCH_COLUMN);

    if (empty($cats)) {
        $stmt = $pdo->prepare("SELECT id, name, price, image_url, category, stock, description FROM products WHERE active = 1 ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    $placeholders = implode(',', array_fill(0, count($cats), '?'));
    $stmt = $pdo->prepare("
        SELECT id, name, price, image_url, category, stock, description 
        FROM products 
        WHERE category IN ($placeholders) AND active = 1
        ORDER BY RAND()
        LIMIT ?
    ");
    foreach ($cats as $i => $cat) {
        $stmt->bindValue($i + 1, $cat);
    }
    $stmt->bindValue(count($cats) + 1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    $productos = $stmt->fetchAll();

    if (count($productos) < $limite) {
        $excluir = array_merge($cats, ['']);
        $placeholders2 = implode(',', array_fill(0, count($excluir), '?'));
        $stmt2 = $pdo->prepare("
            SELECT id, name, price, image_url, category, stock, description 
            FROM products 
            WHERE category NOT IN ($placeholders2) AND active = 1
            ORDER BY RAND()
            LIMIT ?
        ");
        foreach ($excluir as $i => $ex) {
            $stmt2->bindValue($i + 1, $ex);
        }
        $stmt2->bindValue(count($excluir) + 1, $limite - count($productos), PDO::PARAM_INT);
        $stmt2->execute();
        $productos = array_merge($productos, $stmt2->fetchAll());
    }

    return $productos;
}

function enviarRecomendaciones(PDO $pdo, string $clienteEmail, string $clienteNombre): array {
    try {
        $productos = obtenerRecomendacionesParaCliente($pdo, $clienteEmail);
        if (empty($productos)) {
            return ['success' => false, 'message' => 'No hay productos para recomendar'];
        }

        $productosHtml = '';
        foreach ($productos as $p) {
            $img = htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/200x200?text=Producto');
            $nom = htmlspecialchars($p['name']);
            $precio = 'Bs ' . number_format((float)$p['price'], 2);
            $cat = htmlspecialchars($p['category']);
            $stockColor = $p['stock'] < 5 ? '#dc3545' : ($p['stock'] < 15 ? '#ffc107' : '#28a745');
            $productosHtml .= "
                <div style='width:48%;margin-bottom:15px;background:#f8f9fa;border-radius:8px;overflow:hidden;display:inline-block;vertical-align:top;min-width:200px'>
                    <img src='{$img}' alt='{$nom}' style='width:100%;height:150px;object-fit:cover'>
                    <div style='padding:10px'>
                        <div style='font-weight:600;font-size:0.9rem;margin-bottom:4px;color:#2c3e50'>{$nom}</div>
                        <div style='font-size:0.75rem;color:#999;margin-bottom:4px'>{$cat}</div>
                        <div style='font-weight:700;color:#2c3e50;font-size:1rem'>{$precio}</div>
                        <div style='font-size:0.75rem;color:{$stockColor};margin-top:4px'><i class='fas fa-box'></i> Stock: {$p['stock']}</div>
                    </div>
                </div>
            ";
        }

        $unsubscribeLink = rtrim((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) . '/proyecto/interfaz_usuario/recomendaciones_suscripcion.php?email=' . urlencode($clienteEmail) . '&accion=desuscribir';

        $html = "<html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px'>";
        $html .= "<div style='background:linear-gradient(135deg,#2c3e50,#3498db);color:white;padding:25px;text-align:center;border-radius:12px 12px 0 0'>";
        $html .= "<h1 style='margin:0;font-size:1.3rem'><i class='fas fa-lightbulb'></i> Productos que te pueden interesar</h1>";
        $html .= "<p style='margin-top:8px;opacity:.9;font-size:0.85rem'>Basados en tus compras anteriores</p></div>";
        $html .= "<div style='background:white;padding:20px;border:1px solid #eee;border-top:none'>";
        $html .= "<p>Hola <strong>" . htmlspecialchars($clienteNombre) . "</strong>,</p>";
        $html .= "<p>Hemos seleccionado algunos productos que creemos pueden ser de tu interés:</p>";
        $html .= "<div style='margin:20px 0;text-align:center'>{$productosHtml}</div>";
        $html .= "<div style='text-align:center;margin-top:20px'>";
        $html .= "<a href='" . rtrim((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) . "/proyecto/interfaz_usuario/index.html' style='display:inline-block;background:#3498db;color:white;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:600'>Ver más productos</a>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div style='text-align:center;padding:15px;color:#999;font-size:0.75em'>";
        $html .= "Proyectos Industriales del Centro &copy; " . date('Y') . "<br>";
        $html .= "<a href='{$unsubscribeLink}' style='color:#999'>Cancelar suscripción a recomendaciones</a>";
        $html .= "</div></body></html>";

        enviarCorreo($clienteEmail, 'Productos recomendados para ti - Proyectos Industriales del Centro', $html, 'Recomendaciones PIC');

        $stmt = $pdo->prepare("INSERT INTO envios_recomendaciones (cliente_email, tipo, asunto, fecha_envio) VALUES (?, 'recomendacion', 'Productos recomendados para ti', NOW())");
        $stmt->execute([$clienteEmail]);

        return ['success' => true, 'message' => 'Recomendaciones enviadas a ' . $clienteEmail];
    } catch (Throwable $e) {
        error_log("Error enviando recomendaciones: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

function enviarNotificacionNuevoProducto(PDO $pdo, int $productoId, string $productoNombre, string $categoria): array {
    try {
        $stmt = $pdo->prepare("SELECT id, name, price, image_url, description FROM products WHERE id = ?");
        $stmt->execute([$productoId]);
        $producto = $stmt->fetch();
        if (!$producto) return ['success' => false, 'message' => 'Producto no encontrado'];

        $stmt = $pdo->prepare("
            SELECT DISTINCT email, nombre FROM (
                SELECT c.email, c.nombre FROM clientes c JOIN pedidos p ON c.id = p.cliente_id WHERE p.estado IN ('completado','facturado') AND c.email IS NOT NULL AND c.email != ''
                UNION
                SELECT u.correo AS email, u.nombre FROM users u JOIN pedidos p ON u.id = p.usuario_id WHERE p.estado IN ('completado','facturado') AND u.correo IS NOT NULL AND u.correo != ''
            ) AS todos_los_clientes
        ");
        $stmt->execute();
        $clientes = $stmt->fetchAll();

        $enviados = 0;
        $img = htmlspecialchars($producto['image_url'] ?: 'https://via.placeholder.com/200x200?text=Nuevo+Producto');
        $nom = htmlspecialchars($producto['name']);
        $precio = 'Bs ' . number_format((float)$producto['price'], 2);
        $desc = htmlspecialchars($producto['description'] ?: 'Nuevo producto agregado a nuestro catálogo');

        foreach ($clientes as $cliente) {
            try {
                $html = "<html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px'>";
                $html .= "<div style='background:linear-gradient(135deg,#e74c3c,#c0392b);color:white;padding:25px;text-align:center;border-radius:12px 12px 0 0'>";
                $html .= "<h1 style='margin:0;font-size:1.3rem'><i class='fas fa-star'></i> ¡Nuevo producto disponible!</h1></div>";
                $html .= "<div style='background:white;padding:20px;border:1px solid #eee;border-top:none'>";
                $html .= "<p>Hola <strong>" . htmlspecialchars($cliente['nombre']) . "</strong>,</p>";
                $html .= "<p>Agregamos un nuevo producto a nuestro catálogo que podría interesarte:</p>";
                $html .= "<div style='text-align:center;margin:20px 0;background:#f8f9fa;border-radius:10px;padding:15px'>";
                $html .= "<img src='{$img}' alt='{$nom}' style='max-height:180px;border-radius:8px;margin-bottom:10px'>";
                $html .= "<h2 style='font-size:1.1rem;color:#2c3e50;margin:8px 0'>{$nom}</h2>";
                $html .= "<p style='color:#666;font-size:0.85rem;margin-bottom:8px'>{$desc}</p>";
                $html .= "<div style='font-size:1.3rem;font-weight:700;color:#2c3e50'>{$precio}</div>";
                $html .= "</div>";
                $html .= "<div style='text-align:center;margin-top:15px'>";
                $html .= "<a href='" . rtrim((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) . "/proyecto/interfaz_usuario/index.html' style='display:inline-block;background:#e74c3c;color:white;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:600'>Ver en la tienda</a>";
                $html .= "</div></div>";
                $html .= "<div style='text-align:center;padding:15px;color:#999;font-size:0.75em'>Proyectos Industriales del Centro &copy; " . date('Y') . "</div></body></html>";

                enviarCorreo($cliente['email'], "¡Nuevo producto! {$nom} - Proyectos Industriales del Centro", $html, 'Novedades PIC');
                $enviados++;
            } catch (Throwable $e) {
                continue;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO envios_recomendaciones (cliente_email, tipo, asunto, fecha_envio) VALUES ('sistema', 'nuevo_producto', ?, NOW())");
        $stmt->execute(["Nuevo producto: {$productoNombre}"]);

        return ['success' => true, 'message' => "Notificación enviada a {$enviados} clientes"];
    } catch (Throwable $e) {
        error_log("Error notificando nuevo producto: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// CLI / Cron mode
if (PHP_SAPI === 'cli') {
    $accion = $argv[1] ?? 'recomendar';
    try {
        $pdo = conectarDB();
        if ($accion === 'recomendar') {
            $stmt = $pdo->query("SELECT DISTINCT c.email, c.nombre FROM clientes c JOIN pedidos p ON c.id = p.cliente_id WHERE c.email IS NOT NULL AND c.email != '' AND p.estado IN ('completado','facturado') AND NOT EXISTS (SELECT 1 FROM envios_recomendaciones er WHERE er.cliente_email = c.email AND er.tipo = 'recomendacion' AND er.fecha_envio > DATE_SUB(NOW(), INTERVAL 30 DAY))");
            $clientes = $stmt->fetchAll();
            foreach ($clientes as $c) {
                enviarRecomendaciones($pdo, $c['email'], $c['nombre']);
            }
            echo "Recomendaciones enviadas a " . count($clientes) . " clientes\n";
        }
    } catch (Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    exit;
}

// Web mode
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

try {
    $pdo = conectarDB();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $accion = $_GET['accion'] ?? '';
        if ($accion === 'historial') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM envios_recomendaciones");
            $total = $stmt->fetchColumn();
            $stmt = $pdo->query("SELECT COUNT(*) FROM envios_recomendaciones WHERE tipo = 'recomendacion'");
            $recomendaciones = $stmt->fetchColumn();
            $stmt = $pdo->query("SELECT COUNT(*) FROM envios_recomendaciones WHERE tipo = 'nuevo_producto'");
            $nuevos_productos = $stmt->fetchColumn();
            $stmt = $pdo->query("SELECT COUNT(*) FROM envios_recomendaciones WHERE tipo = 'encuesta'");
            $encuestas = $stmt->fetchColumn();
            $stmt = $pdo->query("SELECT * FROM envios_recomendaciones ORDER BY fecha_envio DESC LIMIT 100");
            $envios = $stmt->fetchAll();
            echo json_encode(['success' => true, 'total' => $total, 'recomendaciones' => $recomendaciones, 'nuevos_productos' => $nuevos_productos, 'encuestas' => $encuestas, 'envios' => $envios]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $input = json_decode(file_get_contents('php://input'), true);
        $accion = trim($input['accion'] ?? '');

        if ($accion === 'recomendaciones') {
            $email = trim($input['email'] ?? '');
            $nombre = trim($input['nombre'] ?? '');
            if (!$email) { echo json_encode(['success' => false, 'message' => 'Email requerido']); exit; }
            $resultado = enviarRecomendaciones($pdo, $email, $nombre);
            echo json_encode($resultado);
        } elseif ($accion === 'recomendar_masivo') {
            $stmt = $pdo->query("
                SELECT DISTINCT email, nombre FROM (
                    SELECT c.email, c.nombre FROM clientes c JOIN pedidos p ON c.id = p.cliente_id WHERE c.email IS NOT NULL AND c.email != '' AND p.estado IN ('completado','facturado')
                    UNION
                    SELECT u.correo AS email, u.nombre FROM users u JOIN pedidos p ON u.id = p.usuario_id WHERE u.correo IS NOT NULL AND u.correo != '' AND p.estado IN ('completado','facturado')
                ) AS todos_los_clientes
            ");
            $clientes = $stmt->fetchAll();
            $enviados = 0;
            foreach ($clientes as $c) {
                $res = enviarRecomendaciones($pdo, $c['email'], $c['nombre']);
                if ($res['success']) $enviados++;
            }
            echo json_encode(['success' => $enviados > 0, 'message' => "Recomendaciones enviadas a {$enviados} de " . count($clientes) . " clientes"]);
        } elseif ($accion === 'nuevo_producto') {
            $productoId = (int)($input['producto_id'] ?? 0);
            $nombre = trim($input['nombre'] ?? '');
            $categoria = trim($input['categoria'] ?? '');
            if (!$productoId) { echo json_encode(['success' => false, 'message' => 'Producto ID requerido']); exit; }
            $resultado = enviarNotificacionNuevoProducto($pdo, $productoId, $nombre, $categoria);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
