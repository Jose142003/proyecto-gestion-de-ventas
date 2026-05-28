<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
require_once __DIR__ . '/../conexion/conexion.php';

$email = $_GET['email'] ?? '';
$accion = $_GET['accion'] ?? '';
$mensaje = '';

if ($email && $accion) {
    try {
        $pdo = conectarDB();
        if ($accion === 'suscribir') {
            $stmt = $pdo->prepare("INSERT INTO suscripciones_recomendaciones (cliente_email, cliente_nombre, activo) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE activo = 1");
            $stmt->execute([$email, $email]);
            $mensaje = 'Te has suscrito correctamente a las recomendaciones.';
        } elseif ($accion === 'desuscribir') {
            $stmt = $pdo->prepare("UPDATE suscripciones_recomendaciones SET activo = 0 WHERE cliente_email = ?");
            $stmt->execute([$email]);
            $mensaje = 'Te has dado de baja de las recomendaciones.';
        }
    } catch (Throwable $e) {
        $mensaje = 'Error al procesar la solicitud.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción a Recomendaciones - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        body { background:linear-gradient(135deg,#2c3e50,#3498db); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .card { background:white; border-radius:16px; padding:40px; max-width:450px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; }
        .card h1 { color:#2c3e50; font-size:1.4rem; margin-bottom:10px; }
        .card p { color:#666; font-size:0.95rem; margin-bottom:20px; line-height:1.5; }
        .icon { font-size:4rem; margin-bottom:15px; color:#27ae60; }
        .btn { display:inline-block; background:#2c3e50; color:white; padding:12px 30px; border-radius:8px; text-decoration:none; font-weight:600; }
        .btn:hover { background:#3498db; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas <?= $accion === 'desuscribir' ? 'fa-times-circle' : 'fa-check-circle' ?>"></i></div>
        <h1><?= $mensaje ?: 'Preferencias de recomendaciones' ?></h1>
        <p><?= $mensaje ? 'Puedes cambiar esta opción en cualquier momento.' : 'Enlace no válido.' ?></p>
        <a href="/proyecto/interfaz_usuario/index.html" class="btn"><i class="fas fa-home"></i> Volver a la tienda</a>
    </div>
</body>
</html>
