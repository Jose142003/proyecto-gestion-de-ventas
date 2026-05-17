<?php
// enviar_notificacion_pedido.php - Enviar notificación por email al cliente
session_start();

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once  'Exception.php';
require_once  'PHPMailer.php';
require_once  'SMTP.php';

$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $pedido_id = $data['pedido_id'] ?? 0;
    
    if (!$pedido_id) {
        echo json_encode(['success' => false, 'message' => 'ID de pedido no proporcionado']);
        exit;
    }
    
    // Obtener datos del pedido y cliente
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre as cliente_nombre, u.correo as cliente_email, u.telefono as cliente_telefono
        FROM pedidos p
        LEFT JOIN users u ON p.usuario_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    
    // Obtener detalles del pedido
    $stmt = $pdo->prepare("
        SELECT pd.*, pr.name as producto_nombre
        FROM pedido_detalles pd
        LEFT JOIN products pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Configurar correo
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jose14chacon2003@gmail.com';
        $mail->Password = 'bzwusevvegbuqozg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom('jose14chacon2003@gmail.com', 'PIC - Productos Industriales');
        $mail->addAddress($pedido['cliente_email'], $pedido['cliente_nombre']);
        
        // Contenido del correo
        $estados = [
            'pendiente' => 'Pendiente de confirmación',
            'procesando' => 'En proceso de preparación',
            'enviado' => 'Enviado a su dirección',
            'entregado' => 'Entregado con éxito',
            'cancelado' => 'Cancelado',
            'facturado' => 'Facturado'
        ];
        
        $estado_texto = $estados[$pedido['estado']] ?? $pedido['estado'];
        
        // Construir tabla de productos
        $productos_html = '';
        foreach ($detalles as $detalle) {
            $productos_html .= "
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #eee;'>{$detalle['producto_nombre']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: center;'>{$detalle['cantidad']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right;'>Bs. " . number_format($detalle['precio_unitario'], 2, ',', '.') . "</td>
                    <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right;'>Bs. " . number_format($detalle['subtotal'], 2, ',', '.') . "</td>
                </tr>
            ";
        }
        
        $mail->isHTML(true);
        $mail->Subject = 'Actualización de tu pedido #' . $pedido['numero_pedido'] . ' - PIC';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Actualización de Pedido</title>
        </head>
        <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #050C18, #294E90); color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>PIC</h1>
                    <p style='margin: 5px 0 0;'>Productos Industriales y Comerciales</p>
                </div>
                
                <div style='padding: 20px;'>
                    <h2 style='color: #294E90; margin-top: 0;'>¡Tu pedido ha sido actualizado!</h2>
                    
                    <p>Hola <strong>" . htmlspecialchars($pedido['cliente_nombre']) . "</strong>,</p>
                    
                    <p>Te informamos que el estado de tu pedido <strong>#" . htmlspecialchars($pedido['numero_pedido']) . "</strong> ha cambiado a:</p>
                    
                    <div style='background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center; margin: 15px 0;'>
                        <span style='font-size: 1.2rem; font-weight: bold; color: #2e7d32;'>📦 " . $estado_texto . "</span>
                    </div>
                    
                    <h3 style='color: #294E90;'>Detalle del pedido:</h3>
                    
                    <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                        <thead>
                            <tr>
                                <th style='background: #f4f4f4; padding: 10px; text-align: left;'>Producto</th>
                                <th style='background: #f4f4f4; padding: 10px; text-align: center;'>Cantidad</th>
                                <th style='background: #f4f4f4; padding: 10px; text-align: right;'>Precio</th>
                                <th style='background: #f4f4f4; padding: 10px; text-align: right;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            $productos_html
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='3' style='padding: 10px; text-align: right;'><strong>Subtotal:</strong></td>
                                <td style='padding: 10px; text-align: right;'><strong>Bs. " . number_format($pedido['subtotal'], 2, ',', '.') . "</strong></td>
                            </tr>
                            <tr>
                                <td colspan='3' style='padding: 10px; text-align: right;'><strong>IVA (16%):</strong></td>
                                <td style='padding: 10px; text-align: right;'><strong>Bs. " . number_format($pedido['iva'], 2, ',', '.') . "</strong></td>
                            </tr>
                            <tr style='background: #e8f5e9;'>
                                <td colspan='3' style='padding: 10px; text-align: right; font-size: 1.1rem;'><strong>TOTAL:</strong></td>
                                <td style='padding: 10px; text-align: right; font-size: 1.1rem;'><strong>Bs. " . number_format($pedido['total'], 2, ',', '.') . "</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div style='background: #f4f4f4; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                        <p style='margin: 5px 0;'><strong>📧 Email:</strong> " . htmlspecialchars($pedido['cliente_email']) . "</p>
                        <p style='margin: 5px 0;'><strong>📞 Teléfono:</strong> " . htmlspecialchars($pedido['cliente_telefono'] ?? 'No registrado') . "</p>
                        <p style='margin: 5px 0;'><strong>💳 Método de pago:</strong> " . ucfirst($pedido['metodo_pago'] ?? 'No especificado') . "</p>
                    </div>
                    
                    <p>Puedes consultar el estado de tu pedido en cualquier momento ingresando a tu cuenta en nuestra tienda.</p>
                    
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    
                    <p style='color: #666; font-size: 0.85rem; text-align: center;'>
                        📍 PIC - Productos Industriales y Comerciales<br>
                        📞 0212-5551234 | 📧 info@pic.com.ve
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Hola {$pedido['cliente_nombre']},\n\n";
        $mail->AltBody .= "Tu pedido #{$pedido['numero_pedido']} ha cambiado a: {$estado_texto}\n\n";
        $mail->AltBody .= "Total: Bs. " . number_format($pedido['total'], 2, ',', '.') . "\n\n";
        $mail->AltBody .= "Gracias por tu compra.\n\nPIC - Productos Industriales y Comerciales";
        
        $mail->send();
        
        echo json_encode(['success' => true, 'message' => 'Notificación enviada correctamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al enviar correo: ' . $mail->ErrorInfo]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>