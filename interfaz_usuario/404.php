<?php
http_response_code(404);
require_once __DIR__ . '/../conexion/conexion.php';
$titulo = 'Página no encontrada';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 - Página no encontrada | PIC Industrial</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; display: flex; align-items: center; }
.error-container { text-align: center; padding: 40px 20px; }
.error-code { font-size: 120px; font-weight: 800; color: #294E90; line-height: 1; margin-bottom: 10px; text-shadow: 3px 3px 0 rgba(41,78,144,0.1); }
.error-title { font-size: 24px; color: #333; margin-bottom: 15px; }
.error-text { color: #666; margin-bottom: 30px; font-size: 16px; }
.btn-primary { background: #294E90; border-color: #294E90; padding: 10px 30px; border-radius: 8px; }
.btn-primary:hover { background: #1a3566; border-color: #1a3566; }
.icon-404 { font-size: 48px; color: #3C91ED; margin-bottom: 20px; }
</style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="error-container">
                <div class="icon-404"><i class="fas fa-tools"></i></div>
                <div class="error-code">404</div>
                <div class="error-title">Página no encontrada</div>
                <div class="error-text">
                    La página que buscas no existe o ha sido movida.<br>
                    Si crees que esto es un error, contáctanos.
                </div>
                <a href="<?php echo defined('BASE_URL') ? BASE_URL . '/interfaz_usuario/pagina_modernizada.php' : '/proyecto/interfaz_usuario/pagina_modernizada.php'; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-home me-2"></i>Volver a la tienda
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>