-- Migración: Foreign Keys e Índices faltantes
-- ============================================================================
USE carrito_db;

-- 1. FOREIGN KEYS FALTANTES
-- ============================================================================

-- 1.1 caja_movimientos -> caja_arqueos
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'caja_movimientos'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE caja_movimientos ADD CONSTRAINT fk_caja_mov_arqueo FOREIGN KEY (arqueo_id) REFERENCES caja_arqueos(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.2 caja_movimientos usuario_id -> admin_users
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'caja_movimientos'
                  AND CONSTRAINT_NAME = 'fk_caja_mov_usuario');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE caja_movimientos ADD CONSTRAINT fk_caja_mov_usuario FOREIGN KEY (usuario_id) REFERENCES admin_users(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.3 caja_arqueos usuario_apertura_id -> admin_users
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'caja_arqueos'
                  AND CONSTRAINT_NAME = 'fk_caja_arqueo_usuario_apertura');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE caja_arqueos ADD CONSTRAINT fk_caja_arqueo_usuario_apertura FOREIGN KEY (usuario_apertura_id) REFERENCES admin_users(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.4 backups usuario_id -> admin_users
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'backups'
                  AND CONSTRAINT_NAME = 'fk_backups_usuario');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE backups ADD CONSTRAINT fk_backups_usuario FOREIGN KEY (usuario_id) REFERENCES admin_users(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.5 compras usuario_creacion_id -> admin_users
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'compras'
                  AND CONSTRAINT_NAME = 'fk_compras_usuario_creacion');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE compras ADD CONSTRAINT fk_compras_usuario_creacion FOREIGN KEY (usuario_creacion_id) REFERENCES admin_users(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 1.6 compras proveedor_id -> proveedores
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'compras'
                  AND CONSTRAINT_NAME = 'fk_compras_proveedor');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE compras ADD CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. ÍNDICES FALTANTES
-- ============================================================================

-- 2.1 factura_detalles.factura_id
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'factura_detalles'
                   AND INDEX_NAME = 'idx_factura_detalles_factura_id');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_factura_detalles_factura_id ON factura_detalles(factura_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.2 pedido_detalles.pedido_id
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'pedido_detalles'
                   AND INDEX_NAME = 'idx_pedido_detalles_pedido_id');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_pedido_detalles_pedido_id ON pedido_detalles(pedido_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.3 pedido_detalles.producto_id
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'pedido_detalles'
                   AND INDEX_NAME = 'idx_pedido_detalles_producto_id');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_pedido_detalles_producto_id ON pedido_detalles(producto_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.4 clientes.email
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'clientes'
                   AND INDEX_NAME = 'idx_clientes_email');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_clientes_email ON clientes(email)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.5 cotizaciones.cliente_id
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'cotizaciones'
                   AND INDEX_NAME = 'idx_cotizaciones_cliente_id');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_cotizaciones_cliente_id ON cotizaciones(cliente_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.6 cotizaciones.usuario_id
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'cotizaciones'
                   AND INDEX_NAME = 'idx_cotizaciones_usuario_id');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_cotizaciones_usuario_id ON cotizaciones(usuario_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2.7 productos FULLTEXT para búsqueda eficiente
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'products'
                   AND INDEX_NAME = 'idx_products_fulltext');
SET @sql = IF(@idx_exists = 0,
    'CREATE FULLTEXT INDEX idx_products_fulltext ON products(name, description)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migración de FKs e índices completada exitosamente' as Mensaje;
