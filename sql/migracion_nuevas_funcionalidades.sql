-- ============================================================================
-- MIGRACIÓN: Nuevas Funcionalidades - IA Predictiva, 2FA, BI, WhatsApp
-- Versión: 1.0.0
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ##########################################################################
-- 1. IA PREDICTIVA - Predicción de Ventas
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
    tendencia ENUM('subiendo', 'bajando', 'estable') DEFAULT 'estable',
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
    tipo ENUM('critico', 'bajo', 'exceso', 'sin_movimiento', 'prediccion_agotamiento') DEFAULT 'bajo',
    nivel_actual INT DEFAULT 0,
    nivel_sugerido INT DEFAULT 0,
    dias_para_agotar INT NULL,
    mensaje TEXT NULL,
    leida BOOLEAN DEFAULT FALSE,
    resuelta BOOLEAN DEFAULT FALSE,
    fecha_alerta DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    INDEX idx_producto (producto_id),
    INDEX idx_tipo (tipo),
    INDEX idx_leida (leida),
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 2. AUTENTICACIÓN DE DOS FACTORES (2FA)
-- ##########################################################################

ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS 2fa_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS 2fa_secret VARCHAR(255) NULL;
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS 2fa_backup_codes TEXT NULL;
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS 2fa_verified_at DATETIME NULL;

DROP TABLE IF EXISTS sesiones_2fa;
CREATE TABLE sesiones_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    token_verificacion VARCHAR(64) UNIQUE NOT NULL,
    intentos INT DEFAULT 0,
    expiracion DATETIME NOT NULL,
    completado BOOLEAN DEFAULT FALSE,
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
-- 4. WHATSAPP - CONFIGURACIÓN Y NOTIFICACIONES
-- ##########################################################################

INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden)
SELECT * FROM (
    SELECT 'whatsapp_api_url' as clave, '' as valor, 'text' as tipo, 'whatsapp' as grupo, 'URL de la API de WhatsApp' as descripcion, 1 as editable, 50 as orden
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM configuracion_sistema WHERE clave = 'whatsapp_api_url'
);

INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden)
SELECT * FROM (
    SELECT 'whatsapp_api_token' as clave, '' as valor, 'password' as tipo, 'whatsapp' as grupo, 'Token de la API de WhatsApp' as descripcion, 1 as editable, 51 as orden
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM configuracion_sistema WHERE clave = 'whatsapp_api_token'
);

INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden)
SELECT * FROM (
    SELECT 'whatsapp_numero' as clave, '' as valor, 'text' as tipo, 'whatsapp' as grupo, 'Número de WhatsApp de la empresa' as descripcion, 1 as editable, 52 as orden
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM configuracion_sistema WHERE clave = 'whatsapp_numero'
);

INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden)
SELECT * FROM (
    SELECT 'whatsapp_notificaciones_pedido' as clave, '0' as valor, 'boolean' as tipo, 'whatsapp' as grupo, 'Notificar nuevos pedidos por WhatsApp' as descripcion, 1 as editable, 53 as orden
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM configuracion_sistema WHERE clave = 'whatsapp_notificaciones_pedido'
);

INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden)
SELECT * FROM (
    SELECT 'whatsapp_notificaciones_stock' as clave, '0' as valor, 'boolean' as tipo, 'whatsapp' as grupo, 'Notificar stock bajo por WhatsApp' as descripcion, 1 as editable, 54 as orden
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM configuracion_sistema WHERE clave = 'whatsapp_notificaciones_stock'
);

-- ##########################################################################
-- 5. DATOS DE EJEMPLO PARA PREDICCIONES (basados en pedidos existentes)
-- ##########################################################################

-- Generar predicciones iniciales basadas en datos históricos
INSERT INTO predicciones_ventas (producto_id, mes, anio, ventas_reales, ventas_predichas, precision_prediccion, tendencia, nivel_confianza, stock_sugerido)
SELECT 
    p.id as producto_id,
    MONTH(CURRENT_DATE) as mes,
    YEAR(CURRENT_DATE) as anio,
    COALESCE(SUM(pd.cantidad), 0) as ventas_reales,
    COALESCE(SUM(pd.cantidad) * 1.15, 5) as ventas_predichas,
    85.00 as precision_prediccion,
    'estable' as tendencia,
    75.00 as nivel_confianza,
    GREATEST(CEIL(COALESCE(SUM(pd.cantidad), 0) * 1.2), 10) as stock_sugerido
FROM products p
LEFT JOIN pedido_detalles pd ON p.id = pd.producto_id
LEFT JOIN pedidos pe ON pd.pedido_id = pe.id AND pe.estado NOT IN ('cancelado')
GROUP BY p.id
HAVING ventas_reales > 0
LIMIT 20;

-- Generar alertas de stock para productos con stock bajo
INSERT INTO alertas_stock (producto_id, tipo, nivel_actual, nivel_sugerido, mensaje)
SELECT 
    id as producto_id,
    CASE 
        WHEN stock = 0 THEN 'critico'
        WHEN stock <= 5 THEN 'critico'
        WHEN stock <= 10 THEN 'bajo'
        ELSE 'bajo'
    END as tipo,
    stock as nivel_actual,
    GREATEST(15, stock * 2) as nivel_sugerido,
    CONCAT('El producto "', name, '" tiene stock ', 
        CASE WHEN stock = 0 THEN 'AGOTADO' 
             WHEN stock <= 5 THEN CONCAT('CRÍTICO (', stock, ' unidades)')
             ELSE CONCAT('BAJO (', stock, ' unidades)')
        END, 
        '. Se recomienda reabastecer.'
    ) as mensaje
FROM products 
WHERE stock <= 10
ORDER BY stock ASC;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migración completada exitosamente: IA Predictiva, 2FA, BI Dashboard, WhatsApp' as Mensaje;
