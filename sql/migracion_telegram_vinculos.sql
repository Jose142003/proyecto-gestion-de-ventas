-- Permite a usuarios de la web vincular su cuenta de Telegram
-- para recibir notificaciones de sus pedidos y consultar estado

ALTER TABLE users ADD COLUMN telegram_chat_id BIGINT NULL AFTER telefono;
ALTER TABLE users ADD INDEX idx_telegram_chat_id (telegram_chat_id);

-- Tabla para aprendizaje mejorado del bot
CREATE TABLE IF NOT EXISTS bot_conocimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patron VARCHAR(100) NOT NULL,
    respuesta TEXT NOT NULL,
    tipo ENUM('producto','envio','pago','horario','ubicacion','empresa','otro') DEFAULT 'otro',
    activo TINYINT(1) DEFAULT 1,
    frecuencia INT DEFAULT 0,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patron (patron),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
