<?php
$input = json_decode(file_get_contents('php://input'), true);

$nombre = trim($input['nombre'] ?? '');
$email = trim($input['email'] ?? '');
$documento = trim($input['documento'] ?? '');
$tipoDocumento = trim($input['tipo_documento'] ?? 'cedula');
$telefono = trim($input['telefono'] ?? '');
$direccion = trim($input['direccion'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$estado = trim($input['estado'] ?? 'activo');

if (!$nombre) {
    apiError('El nombre del cliente es requerido');
}
if (!$documento) {
    apiError('El documento es requerido');
}
if (!$email) {
    apiError('El email es requerido');
}

$tiposValidos = ['cedula', 'ruc', 'pasaporte', 'dni'];
if (!in_array($tipoDocumento, $tiposValidos)) {
    apiError("Tipo de documento inválido. Válidos: " . implode(', ', $tiposValidos));
}

$estadosValidos = ['activo', 'inactivo', 'moroso'];
if (!in_array($estado, $estadosValidos)) {
    apiError("Estado inválido. Válidos: " . implode(', ', $estadosValidos));
}

try {
    $pdo = Database::getConnection();

    $check = $pdo->prepare("SELECT id FROM clientes WHERE documento = ?");
    $check->execute([$documento]);
    if ($check->fetch()) {
        apiError("El documento '$documento' ya está registrado");
    }

    $check = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        apiError("El email '$email' ya está registrado");
    }

    $stmt = $pdo->prepare("INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, direccion, ciudad, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tipoDocumento, $documento, $nombre, $email, $telefono, $direccion, $ciudad, $estado]);

    $id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();

    apiResponse(['success' => true, 'message' => 'Cliente creado exitosamente', 'data' => $cliente], 201);
} catch (Exception $e) {
    apiError('Error al crear cliente: ' . $e->getMessage(), 500);
}
