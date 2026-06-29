-- ============================================================================
-- MIGRACIÓN: 5 Funcionalidades faltantes
-- 1. Multi-almacén
-- 2. Cuentas por Cobrar/Pagar
-- 3. Notas de Crédito/Débito
-- 4. Variantes de productos
-- 5. API REST (tabla de tokens)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. MULTI-ALMACÉN
-- ============================================================================

DROP TABLE IF EXISTS almacenes;
CREATE TABLE almacenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    direccion TEXT NULL,
    ciudad VARCHAR(100) NULL,
    telefono VARCHAR(20) NULL,
    encargado VARCHAR(100) NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_principal (es_principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO almacenes (codigo, nombre, direccion, ciudad, es_principal, activo) VALUES
('ALM-PPAL', 'Almacén Principal', 'Av. Principal, Zona Industrial', 'Caracas', TRUE, TRUE),
('ALM-SEC', 'Almacén Secundario', 'Calle 5, Parque Industrial', 'Valencia', FALSE, TRUE);

DROP TABLE IF EXISTS producto_almacen;
CREATE TABLE producto_almacen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    almacen_id INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 5,
    stock_maximo INT NULL,
    ubicacion VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_producto_almacen (producto_id, almacen_id),
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (almacen_id) REFERENCES almacenes(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    INDEX idx_almacen (almacen_id),
    INDEX idx_stock_bajo (stock, stock_minimo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Poblar stock inicial desde products.stock al almacén principal
INSERT INTO producto_almacen (producto_id, almacen_id, stock, stock_minimo)
SELECT id, 1, stock, 5 FROM products WHERE active = 1;

DROP TABLE IF EXISTS transferencias_almacen;
CREATE TABLE transferencias_almacen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_transferencia VARCHAR(30) UNIQUE NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    almacen_origen_id INT NOT NULL,
    almacen_destino_id INT NOT NULL,
    usuario_id INT NOT NULL,
    estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'pendiente',
    observaciones TEXT NULL,
    fecha_completada DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (almacen_origen_id) REFERENCES almacenes(id) ON DELETE CASCADE,
    FOREIGN KEY (almacen_destino_id) REFERENCES almacenes(id) ON DELETE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. CUENTAS POR COBRAR / PAGAR
-- ============================================================================

DROP TABLE IF EXISTS cuentas_cobrar;
CREATE TABLE cuentas_cobrar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    factura_id INT NULL,
    numero_documento VARCHAR(50) NOT NULL,
    monto_original DECIMAL(12,2) NOT NULL,
    saldo_pendiente DECIMAL(12,2) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('pendiente', 'parcial', 'pagada', 'vencida', 'anulada') DEFAULT 'pendiente',
    prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL,
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado),
    INDEX idx_vencimiento (fecha_vencimiento),
    INDEX idx_prioridad (prioridad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS cuentas_pagar;
CREATE TABLE cuentas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    compra_id INT NULL,
    numero_documento VARCHAR(50) NOT NULL,
    monto_original DECIMAL(12,2) NOT NULL,
    saldo_pendiente DECIMAL(12,2) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('pendiente', 'parcial', 'pagada', 'vencida', 'anulada') DEFAULT 'pendiente',
    prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE SET NULL,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_estado (estado),
    INDEX idx_vencimiento (fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS pagos_cobro;
CREATE TABLE pagos_cobro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_cobrar_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'pago_movil', 'cheque', 'tarjeta', 'deposito') DEFAULT 'efectivo',
    referencia VARCHAR(100) NULL,
    fecha_pago DATE NOT NULL,
    usuario_id INT NOT NULL,
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cuenta_cobrar_id) REFERENCES cuentas_cobrar(id) ON DELETE CASCADE,
    INDEX idx_cuenta (cuenta_cobrar_id),
    INDEX idx_fecha (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS pagos_proveedor;
CREATE TABLE pagos_proveedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_pagar_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'deposito') DEFAULT 'transferencia',
    referencia VARCHAR(100) NULL,
    fecha_pago DATE NOT NULL,
    usuario_id INT NOT NULL,
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cuenta_pagar_id) REFERENCES cuentas_pagar(id) ON DELETE CASCADE,
    INDEX idx_cuenta (cuenta_pagar_id),
    INDEX idx_fecha (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. NOTAS DE CRÉDITO / DÉBITO
-- ============================================================================

DROP TABLE IF EXISTS notas_credito;
CREATE TABLE notas_credito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_nota VARCHAR(30) UNIQUE NOT NULL,
    factura_id INT NOT NULL,
    cliente_id INT NOT NULL,
    motivo ENUM('devolucion', 'descuento', 'error_facturacion', 'anulacion', 'otro') NOT NULL,
    descripcion TEXT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    iva DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    usuario_id INT NOT NULL,
    estado ENUM('emitida', 'aplicada', 'anulada') DEFAULT 'emitida',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_factura (factura_id),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS notas_credito_detalles;
CREATE TABLE notas_credito_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nota_credito_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (nota_credito_id) REFERENCES notas_credito(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_nota (nota_credito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS notas_debito;
CREATE TABLE notas_debito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_nota VARCHAR(30) UNIQUE NOT NULL,
    factura_id INT NOT NULL,
    cliente_id INT NOT NULL,
    motivo ENUM('interes_mora', 'diferencia_precio', 'gastos_adicionales', 'error_cargo', 'otro') NOT NULL,
    descripcion TEXT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    iva DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    usuario_id INT NOT NULL,
    estado ENUM('emitida', 'aplicada', 'anulada') DEFAULT 'emitida',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_factura (factura_id),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS notas_debito_detalles;
CREATE TABLE notas_debito_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nota_debito_id INT NOT NULL,
    producto_id INT NULL,
    concepto VARCHAR(255) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (nota_debito_id) REFERENCES notas_debito(id) ON DELETE CASCADE,
    INDEX idx_nota (nota_debito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. VARIANTES DE PRODUCTOS
-- ============================================================================

DROP TABLE IF EXISTS producto_atributos;
CREATE TABLE producto_atributos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    UNIQUE KEY unique_atributo (producto_id, nombre, valor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS producto_variantes;
CREATE TABLE producto_variantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    sku_variante VARCHAR(100) UNIQUE NOT NULL,
    nombre_variante VARCHAR(255) NOT NULL,
    combinacion JSON NOT NULL,
    precio_adicional DECIMAL(10,2) DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    imagen_url VARCHAR(512) NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    INDEX idx_sku (sku_variante),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. API REST - Tokens de acceso
-- ============================================================================

DROP TABLE IF EXISTS api_tokens;
CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    permisos JSON NOT NULL,
    ultimo_uso DATETIME NULL,
    expires_at DATETIME NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Token por defecto para desarrollo
INSERT INTO api_tokens (usuario_id, nombre, token, permisos)
VALUES (1, 'Token Desarrollo', SHA2('dev-token-pic-system-2026', 256), '["*"]');

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Migración 5 features completada exitosamente' as Mensaje;
