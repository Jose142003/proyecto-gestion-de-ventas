<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
require_once __DIR__ . '/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_GET;
$accion = $input['accion'] ?? '';

try {
    $db = obtenerDb();

    if ($accion === 'categorias') {
        $stmt = $db->query("SELECT DISTINCT categoria FROM compatibilidad_marcas ORDER BY categoria");
        $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
        responder(['success' => true, 'categorias' => $cats]);
    }

    elseif ($accion === 'marcas') {
        $categoria = $input['categoria'] ?? '';
        $stmt = $db->prepare("
            SELECT DISTINCT marca_a as marca FROM compatibilidad_marcas WHERE categoria = ?
            UNION 
            SELECT DISTINCT marca_b FROM compatibilidad_marcas WHERE categoria = ?
            ORDER BY marca
        ");
        $stmt->execute([$categoria, $categoria]);
        $marcas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        responder(['success' => true, 'marcas' => array_values(array_unique($marcas))]);
    }

    elseif ($accion === 'buscar') {
        $marca = $input['marca'] ?? '';
        $modelo = $input['modelo'] ?? '';
        $categoria = $input['categoria'] ?? '';

        $sql = "SELECT cm.*, p.name as producto_nombre, p.price as producto_precio, p.image_url
                FROM compatibilidad_marcas cm
                LEFT JOIN products p ON cm.producto_id = p.id
                WHERE 1=1";
        $params = [];

        if ($categoria) {
            $sql .= " AND cm.categoria = ?";
            $params[] = $categoria;
        }
        if ($marca) {
            $sql .= " AND (cm.marca_a LIKE ? OR cm.marca_b LIKE ?)";
            $params[] = "%$marca%";
            $params[] = "%$marca%";
        }
        if ($modelo) {
            $sql .= " AND (cm.modelo_a LIKE ? OR cm.modelo_b LIKE ?)";
            $params[] = "%$modelo%";
            $params[] = "%$modelo%";
        }

        $stmt = $db->prepare($sql . " ORDER BY cm.categoria, cm.marca_a LIMIT 50");
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados as &$r) {
            if (is_string($r['parametros_entrada'] ?? null)) {
                $r['parametros_entrada'] = json_decode($r['parametros_entrada'], true);
            }
        }

        responder(['success' => true, 'resultados' => $resultados]);
    }

    elseif ($accion === 'producto') {
        $productoId = intval($input['producto_id'] ?? 0);

        $stmt = $db->prepare("SELECT name, price, category, description FROM products WHERE id = ?");
        $stmt->execute([$productoId]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) responder(['error' => 'Producto no encontrado'], 404);

        $categoria = $producto['category'];
        $nombre = $producto['name'];

        $marcasConocidas = ['Autonics', 'Schneider', 'Telemecanique', 'Exceline', 'UNI-T', 'Extech', 'Mean Well', 'ABB'];
        $marcaEncontrada = '';
        foreach ($marcasConocidas as $m) {
            if (stripos($nombre, $m) !== false || stripos($categoria, $m) !== false) {
                $marcaEncontrada = $m;
                break;
            }
        }

        $stmtCompat = $db->prepare("
            SELECT cm.*, p.name as producto_nombre, p.price as producto_precio
            FROM compatibilidad_marcas cm
            LEFT JOIN products p ON cm.producto_id = p.id
            WHERE cm.categoria LIKE ? 
            AND (cm.marca_a LIKE ? OR cm.marca_b LIKE ?)
            LIMIT 10
        ");
        $busqueda = "%$marcaEncontrada%";
        $stmtCompat->execute([$categoria, $busqueda, $busqueda]);
        $compatibles = $stmtCompat->fetchAll(PDO::FETCH_ASSOC);

        $stmtSimilares = $db->prepare("
            SELECT id, name, price, image_url, stock 
            FROM products 
            WHERE category = ? AND id != ? 
            ORDER BY price ASC 
            LIMIT 4
        ");
        $stmtSimilares->execute([$categoria, $productoId]);
        $similares = $stmtSimilares->fetchAll(PDO::FETCH_ASSOC);

        responder([
            'success' => true,
            'producto' => $producto,
            'marca_detectada' => $marcaEncontrada,
            'compatibilidades' => $compatibles,
            'alternativas' => $similares
        ]);
    }

    else {
        $stmt = $db->query("SELECT DISTINCT categoria, COUNT(*) as total FROM compatibilidad_marcas GROUP BY categoria");
        $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);
        responder(['success' => true, 'resumen' => $resumen]);
    }

} catch (Exception $e) {
    responder(['error' => 'Error interno: ' . $e->getMessage()], 500);
}
