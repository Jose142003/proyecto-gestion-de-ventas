-- Tabla de cotizaciones (CRM)
CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_cotizacion VARCHAR(50) NOT NULL UNIQUE,
    cliente_id INT DEFAULT NULL,
    cliente_nombre VARCHAR(255) NOT NULL,
    cliente_email VARCHAR(255) DEFAULT NULL,
    cliente_telefono VARCHAR(50) DEFAULT NULL,
    cliente_direccion TEXT DEFAULT NULL,
    usuario_id INT NOT NULL,
    subtotal DECIMAL(12,2) DEFAULT 0,
    iva DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    estado ENUM('pendiente','aprobada','rechazada','vencida','convertida') DEFAULT 'pendiente',
    seguimiento TEXT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE DEFAULT NULL,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de detalles de cotización
CREATE TABLE IF NOT EXISTS cotizacion_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    producto_id INT DEFAULT NULL,
    producto_nombre VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
