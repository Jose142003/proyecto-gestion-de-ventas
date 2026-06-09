CREATE TABLE IF NOT EXISTS cliente_interacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    tipo ENUM('llamada','correo','reunion','nota','seguimiento','recordatorio') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    fecha_interaccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_cliente (cliente_id),
    INDEX idx_fecha (fecha_interaccion),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
