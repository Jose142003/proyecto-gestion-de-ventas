<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
require_once __DIR__ . '/../conexion/conexion.php';

$token = $_GET['token'] ?? '';
$pedido_id = $_GET['pedido'] ?? '';
$info = null;
$yaRespondio = false;
$respondido = false;

if ($token && $pedido_id) {
    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT e.*, p.numero_pedido FROM encuestas_satisfaccion e LEFT JOIN pedidos p ON e.pedido_id = p.id WHERE e.id = ? AND MD5(CONCAT(e.id, e.cliente_email, e.pedido_id)) = ?");
        $stmt->execute([(int)$pedido_id, $token]);
        $info = $stmt->fetch();

        if ($info) {
            if ($info['puntuacion'] !== null) {
                $yaRespondio = true;
            }
        }
    } catch (Throwable $e) {
        $info = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $info && !$yaRespondio) {
    $puntuacion = (int)($_POST['puntuacion'] ?? 0);
    $comentarios = trim($_POST['comentarios'] ?? '');
    if ($puntuacion >= 1 && $puntuacion <= 10) {
        try {
            $pdo = conectarDB();
            $stmt = $pdo->prepare("UPDATE encuestas_satisfaccion SET puntuacion = ?, comentarios = ?, fecha_respuesta = NOW() WHERE id = ?");
            $stmt->execute([$puntuacion, $comentarios, $info['id']]);
            $respondido = true;
        } catch (Throwable $e) {
            $error = 'Error al guardar tu respuesta.';
        }
    } else {
        $error = 'Selecciona una puntuación entre 1 y 10.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta de Satisfacción - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        body { background:linear-gradient(135deg,#667eea,#764ba2); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .card { background:white; border-radius:16px; padding:40px; max-width:520px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; }
        .card h1 { color:#2c3e50; font-size:1.5rem; margin-bottom:8px; }
        .card p { color:#666; font-size:0.95rem; margin-bottom:20px; }
        .stars { font-size:2.5rem; display:flex; gap:8px; justify-content:center; margin-bottom:20px; }
        .stars i { cursor:pointer; color:#ddd; transition:color .2s; }
        .stars i.active, .stars i:hover, .stars i:hover~i { color:#f1c40f; }
        .stars i.selected { color:#f1c40f; }
        .btn-submit { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; padding:12px 30px; border-radius:8px; font-size:1rem; cursor:pointer; font-weight:600; transition:transform .2s; }
        .btn-submit:hover { transform:translateY(-2px); }
        .btn-submit:disabled { opacity:.5; cursor:not-allowed; }
        textarea { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:8px; font-size:0.9rem; resize:vertical; margin-bottom:15px; box-sizing:border-box; }
        .success-icon { font-size:4rem; color:#27ae60; margin-bottom:15px; }
        .error-msg { color:#e74c3c; font-size:0.85rem; margin-bottom:10px; }
        .rating-nums { display:flex; gap:4px; justify-content:center; margin-bottom:20px; flex-wrap:wrap; }
        .rating-nums .num { width:36px; height:36px; border-radius:50%; border:2px solid #ddd; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:600; color:#666; transition:all .2s; }
        .rating-nums .num:hover { border-color:#667eea; color:#667eea; }
        .rating-nums .num.selected { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:transparent; }
        .rating-labels { display:flex; justify-content:space-between; font-size:0.75rem; color:#999; margin-top:-15px; margin-bottom:20px; }
    </style>
</head>
<body>
    <div class="card">
        <?php if (!$info && !$respondido): ?>
            <i class="fas fa-exclamation-circle" style="font-size:4rem;color:#e74c3c;margin-bottom:15px"></i>
            <h1>Enlace inválido</h1>
            <p>Este enlace de encuesta no es válido o ha expirado.</p>
        <?php elseif ($respondido || $yaRespondio): ?>
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h1>¡Gracias por tu feedback!</h1>
            <p><?= $yaRespondio ? 'Ya habías respondido esta encuesta anteriormente.' : 'Tu opinión nos ayuda a mejorar. ¡Apreciamos tu tiempo!' ?></p>
        <?php else: ?>
            <h1><i class="fas fa-star" style="color:#f1c40f"></i> ¿Cómo fue tu experiencia?</h1>
            <p>Ayúdanos a mejorar calificando tu compra #<?= htmlspecialchars($info['pedido_numero'] ?? '') ?></p>
            <?php if (isset($error)): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST" id="surveyForm">
                <input type="hidden" name="puntuacion" id="puntuacion" required>
                
                <label style="display:block;text-align:left;font-weight:600;color:#555;margin-bottom:8px;font-size:0.9rem">Puntúa tu experiencia:</label>
                <div class="rating-nums" id="ratingNums">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <div class="num" data-val="<?= $i ?>" onclick="seleccionar(<?= $i ?>)"><?= $i ?></div>
                    <?php endfor; ?>
                </div>
                <div class="rating-labels">
                    <span>Muy malo</span>
                    <span>Excelente</span>
                </div>

                <label style="display:block;text-align:left;font-weight:600;color:#555;margin-bottom:8px;font-size:0.9rem">Comentarios (opcional):</label>
                <textarea name="comentarios" rows="3" placeholder="Cuéntanos más sobre tu experiencia..."></textarea>
                
                <button type="submit" class="btn-submit" id="btnEnviar" disabled>
                    <i class="fas fa-paper-plane"></i> Enviar mi opinión
                </button>
            </form>
        <?php endif; ?>
    </div>
    <script>
        let selected = 0;
        function seleccionar(val) {
            selected = val;
            document.getElementById('puntuacion').value = val;
            document.querySelectorAll('.rating-nums .num').forEach(n => {
                n.classList.toggle('selected', parseInt(n.dataset.val) <= val);
            });
            document.getElementById('btnEnviar').disabled = false;
        }
    </script>
</body>
</html>
