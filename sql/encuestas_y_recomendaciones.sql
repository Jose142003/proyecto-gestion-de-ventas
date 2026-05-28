CREATE TABLE IF NOT EXISTS encuestas_satisfaccion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NULL,
    pedido_numero VARCHAR(50) NULL,
    cliente_email VARCHAR(255) NOT NULL,
    cliente_nombre VARCHAR(255) NOT NULL,
    puntuacion TINYINT NULL CHECK (puntuacion >= 1 AND puntuacion <= 10),
    comentarios TEXT NULL,
    fecha_envio DATETIME NULL,
    fecha_respuesta DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cliente_email (cliente_email),
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS suscripciones_recomendaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_email VARCHAR(255) NOT NULL UNIQUE,
    cliente_nombre VARCHAR(255) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS envios_recomendaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_email VARCHAR(255) NOT NULL,
    tipo ENUM('recomendacion','nuevo_producto','encuesta') NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    enviado TINYINT(1) DEFAULT 1,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cliente (cliente_email),
    INDEX idx_fecha (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
