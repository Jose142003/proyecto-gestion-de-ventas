<?php
session_start();

error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../usuarios/config_email.php';

function enviarEncuestaSatisfaccion(PDO $pdo, int $pedidoId, string $clienteEmail, string $clienteNombre, string $numeroPedido = ''): array {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM encuestas_satisfaccion WHERE pedido_id = ?");
        $stmt->execute([$pedidoId]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => true, 'message' => 'Encuesta ya enviada para este pedido'];
        }

        $stmt = $pdo->prepare("INSERT INTO encuestas_satisfaccion (pedido_id, pedido_numero, cliente_email, cliente_nombre, fecha_envio) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$pedidoId, $numeroPedido, $clienteEmail, $clienteNombre]);
        $encuestaId = $pdo->lastInsertId();

        $token = hash_hmac('sha256', $encuestaId . '|' . $clienteEmail . '|' . $pedidoId, defined('BASE_URL') ? BASE_URL : 'pic-secret-key');
        $surveyUrl = rtrim((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) . url('/interfaz_usuario/encuesta_satisfaccion.php?token=' . urlencode($token) . '&pedido=' . $encuestaId);

        $html = "<html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px'>";
        $html .= "<div style='background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:30px;text-align:center;border-radius:12px 12px 0 0'>";
        $html .= "<h1 style='margin:0;font-size:1.5rem'>¿Cómo fue tu experiencia?</h1>";
        $html .= "<p style='margin-top:8px;opacity:.9'>Tu opinión nos ayuda a mejorar</p></div>";
        $html .= "<div style='background:white;padding:30px;border:1px solid #eee;border-top:none'>";
        $html .= "<p>Hola <strong>" . htmlspecialchars($clienteNombre) . "</strong>,</p>";
        $html .= "<p>Gracias por tu compra <strong>#" . htmlspecialchars($numeroPedido) . "</strong>. Nos encantaría saber cómo fue tu experiencia en nuestra tienda.</p>";
        $html .= "<div style='text-align:center;margin:25px 0'>";
        $html .= "<a href='{$surveyUrl}' style='display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:14px 35px;border-radius:8px;text-decoration:none;font-weight:600;font-size:1rem'>Calificar mi experiencia</a>";
        $html .= "</div>";
        $html .= "<p style='color:#999;font-size:0.85rem'>Solo te tomará 1 minuto. Tu feedback es muy valioso para nosotros.</p>";
        $html .= "</div>";
        $html .= "<div style='text-align:center;padding:15px;color:#999;font-size:0.8em'>Proyectos Industriales del Centro &copy; " . date('Y') . "</div>";
        $html .= "</body></html>";

        $correoRes = enviarCorreo($clienteEmail, '¿Cómo fue tu experiencia? - Proyectos Industriales del Centro', $html, 'Atención al Cliente PIC');

        if (!$correoRes['success']) {
            $pdo->prepare("DELETE FROM encuestas_satisfaccion WHERE id = ?")->execute([$encuestaId]);
            error_log("Encuesta: fallo envio correo a {$clienteEmail}: " . ($correoRes['message'] ?? ''));
            return ['success' => false, 'message' => 'Error al enviar correo: ' . ($correoRes['message'] ?? 'desconocido')];
        }

        return ['success' => true, 'message' => 'Encuesta enviada correctamente'];
    } catch (Throwable $e) {
        error_log("Error enviando encuesta: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Solo ejecutar lógica principal si se accede directamente (no por include)
if (basename($_SERVER['SCRIPT_FILENAME']) === 'enviar_encuesta_satisfaccion.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        try {
            $pdo = conectarDB();
            $input = json_decode(file_get_contents('php://input'), true);
            $pedidoId = (int)($input['pedido_id'] ?? 0);
            $email = trim($input['email'] ?? '');
            $nombre = trim($input['nombre'] ?? '');
            $numero = trim($input['numero_pedido'] ?? '');

            if (!$pedidoId || !$email) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }

            $resultado = enviarEncuestaSatisfaccion($pdo, $pedidoId, $email, $nombre, $numero);
            echo json_encode($resultado);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
}
