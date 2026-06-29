<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/proyecto/api';

$path = str_replace($basePath, '', $uri);
$path = trim($path, '/');
$segments = explode('/', $path);

$version = $segments[0] ?? 'v1';
$resource = $segments[1] ?? '';
$id = $segments[2] ?? null;

$allowedOrigin = defined('CORS_ORIGIN') ? CORS_ORIGIN : '*';
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$apiAuth = authenticateRequest();
if (!$apiAuth['success']) {
    http_response_code(401);
    echo json_encode($apiAuth);
    exit;
}

$userId = $apiAuth['user_id'];

$routes = [
    'GET productos' => 'productos/listar.php',
    'GET producto' => 'productos/obtener.php',
    'POST productos' => 'productos/crear.php',
    'PUT producto' => 'productos/actualizar.php',
    'DELETE producto' => 'productos/eliminar.php',

    'GET pedidos' => 'pedidos/listar.php',
    'GET pedido' => 'pedidos/obtener.php',
    'POST pedidos' => 'pedidos/crear.php',

    'GET clientes' => 'clientes/listar.php',
    'GET cliente' => 'clientes/obtener.php',
    'POST clientes' => 'clientes/crear.php',

    'GET facturas' => 'facturas/listar.php',
    'GET factura' => 'facturas/obtener.php',

    'GET dashboard' => 'dashboard/resumen.php',

    'GET almacenes' => 'almacenes/listar.php',
    'GET almacen' => 'almacenes/obtener.php',
];

$routeKey = "$method $resource";
$handler = $routes[$routeKey] ?? null;

if (!$handler) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint no encontrado', 'available' => array_keys($routes)]);
    exit;
}

$handlerPath = __DIR__ . '/' . $version . '/' . $handler;
if (!file_exists($handlerPath)) {
    http_response_code(501);
    echo json_encode(['success' => false, 'message' => "Handler no implementado: $handlerPath"]);
    exit;
}

$_REQUEST['api_id'] = $id;
$_REQUEST['api_user_id'] = $userId;
require $handlerPath;
