-- ============================================================================
-- MIGRACIÓN: Nuevas Funcionalidades - IA Predictiva, 2FA, BI, WhatsApp
-- Versión: 1.1.0 - Compatible con MySQL 5.7+ / MariaDB 10.3+
-- ============================================================================

-- ##########################################################################
-- 1. IA PREDICTIVA
-- ##########################################################################

DROP TABLE IF EXISTS predicciones_ventas;
CREATE TABLE predicciones_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NULL,
    categoria VARCHAR(100) NULL,
    mes INT NOT NULL,
    anio INT NOT NULL,
    ventas_reales DECIMAL(12,2) DEFAULT 0,
    ventas_predichas DECIMAL(12,2) DEFAULT 0,
    precision_prediccion DECIMAL(5,2) DEFAULT 0,
    tendencia VARCHAR(20) DEFAULT 'estable',
    nivel_confianza DECIMAL(5,2) DEFAULT 0,
    stock_sugerido INT DEFAULT 0,
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_prediccion (producto_id, mes, anio),
    INDEX idx_categoria (categoria),
    INDEX idx_fecha (mes, anio),
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS alertas_stock;
CREATE TABLE alertas_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo VARCHAR(30) DEFAULT 'bajo',
    nivel_actual INT DEFAULT 0,
    nivel_sugerido INT DEFAULT 0,
    dias_para_agotar INT NULL,
    mensaje TEXT NULL,
    leida TINYINT(1) DEFAULT 0,
    resuelta TINYINT(1) DEFAULT 0,
    fecha_alerta DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    INDEX idx_producto (producto_id),
    INDEX idx_tipo (tipo),
    INDEX idx_leida (leida),
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 2. 2FA - Agregar columnas SOLO si no existen
-- ##########################################################################

DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DELIMITER //
CREATE PROCEDURE add_column_if_not_exists()
BEGIN
    DECLARE cont INT;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = '2fa_enabled';
    IF cont = 0 THEN
        ALTER TABLE admin_users ADD COLUMN `2fa_enabled` TINYINT(1) DEFAULT 0;
    END IF;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = '2fa_secret';
    IF cont = 0 THEN
        ALTER TABLE admin_users ADD COLUMN `2fa_secret` VARCHAR(255) NULL;
    END IF;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = '2fa_backup_codes';
    IF cont = 0 THEN
        ALTER TABLE admin_users ADD COLUMN `2fa_backup_codes` TEXT NULL;
    END IF;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = '2fa_verified_at';
    IF cont = 0 THEN
        ALTER TABLE admin_users ADD COLUMN `2fa_verified_at` DATETIME NULL;
    END IF;
END//
DELIMITER ;
CALL add_column_if_not_exists();
DROP PROCEDURE IF EXISTS add_column_if_not_exists;

DROP TABLE IF EXISTS sesiones_2fa;
CREATE TABLE sesiones_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    token_verificacion VARCHAR(64) UNIQUE NOT NULL,
    intentos INT DEFAULT 0,
    expiracion DATETIME NOT NULL,
    completado TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_token (token_verificacion),
    INDEX idx_expiracion (expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 3. BI - BUSINESS INTELLIGENCE
-- ##########################################################################

DROP TABLE IF EXISTS bi_metricas_diarias;
CREATE TABLE bi_metricas_diarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    ventas_totales DECIMAL(12,2) DEFAULT 0,
    numero_pedidos INT DEFAULT 0,
    ticket_promedio DECIMAL(10,2) DEFAULT 0,
    clientes_nuevos INT DEFAULT 0,
    productos_vendidos INT DEFAULT 0,
    ingresos_efectivo DECIMAL(12,2) DEFAULT 0,
    ingresos_transferencia DECIMAL(12,2) DEFAULT 0,
    ingresos_pago_movil DECIMAL(12,2) DEFAULT 0,
    tasa_conversion DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 4. WHATSAPP - Configuración (INSERT IGNORE para evitar duplicados)
-- ##########################################################################

INSERT IGNORE INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden) VALUES
('whatsapp_api_url', '', 'text', 'whatsapp', 'URL de la API de WhatsApp', 1, 50),
('whatsapp_api_token', '', 'password', 'whatsapp', 'Token de la API de WhatsApp', 1, 51),
('whatsapp_numero', '', 'text', 'whatsapp', 'Numero de WhatsApp de la empresa', 1, 52),
('whatsapp_notificaciones_pedido', '0', 'boolean', 'whatsapp', 'Notificar nuevos pedidos por WhatsApp', 1, 53),
('whatsapp_notificaciones_stock', '0', 'boolean', 'whatsapp', 'Notificar stock bajo por WhatsApp', 1, 54),
('telegram_token', '', 'password', 'telegram', 'Token del bot de Telegram', 1, 55),
('telegram_chat_id', '', 'text', 'telegram', 'Chat ID de Telegram', 1, 56);

-- ##########################################################################
-- 5. DATOS DE EJEMPLO
-- ##########################################################################

INSERT IGNORE INTO predicciones_ventas (producto_id, mes, anio, ventas_reales, ventas_predichas, precision_prediccion, tendencia, nivel_confianza, stock_sugerido)
SELECT p.id, MONTH(CURRENT_DATE), YEAR(CURRENT_DATE), COALESCE(SUM(pd.cantidad), 0), GREATEST(COALESCE(SUM(pd.cantidad), 0) * 1.15, 5), 85.00, 'estable', 75.00, GREATEST(CEIL(COALESCE(SUM(pd.cantidad), 0) * 1.2), 10)
FROM products p LEFT JOIN pedido_detalles pd ON p.id = pd.producto_id LEFT JOIN pedidos pe ON pd.pedido_id = pe.id AND pe.estado NOT IN ('cancelado')
GROUP BY p.id HAVING COALESCE(SUM(pd.cantidad), 0) > 0 LIMIT 20;

INSERT IGNORE INTO alertas_stock (producto_id, tipo, nivel_actual, nivel_sugerido, mensaje)
SELECT id, CASE WHEN stock = 0 THEN 'critico' WHEN stock <= 5 THEN 'critico' ELSE 'bajo' END, stock, GREATEST(15, stock * 2),
CONCAT('El producto ', name, CASE WHEN stock = 0 THEN ' esta AGOTADO' WHEN stock <= 5 THEN CONCAT(' tiene stock CRITICO (', stock, ' unidades)') ELSE CONCAT(' tiene stock BAJO (', stock, ' unidades)') END, '. Se recomienda reabastecer.')
FROM products WHERE stock <= 10 ORDER BY stock ASC;

SELECT 'Migracion completada exitosamente' as Mensaje;
