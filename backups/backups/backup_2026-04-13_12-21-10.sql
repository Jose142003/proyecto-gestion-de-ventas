-- Backup: 2026-04-13 12:21:10
-- Base de datos: carrito_db
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET SQL_MODE='';



-- --------------------------------------------------------
-- Estructura de tabla: admin_users
-- --------------------------------------------------------
CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin','vendedor') DEFAULT 'admin',
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: admin_users
--
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `usuario`, `contrasena`, `rol`, `activo`, `ultimo_login`, `fecha_registro`, `updated_at`) VALUES ('1','Administrador','picca.ventas@gmail.com','admin','240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9','superadmin','1',NULL,'2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `usuario`, `contrasena`, `rol`, `activo`, `ultimo_login`, `fecha_registro`, `updated_at`) VALUES ('2','Vendedor 1','vendedor1@empresa.com','vendedor1','56976bf24998ca63e35fe4f1e2469b5751d1856003e8d16fef0aafef496ed044','vendedor','1',NULL,'2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `usuario`, `contrasena`, `rol`, `activo`, `ultimo_login`, `fecha_registro`, `updated_at`) VALUES ('3','Admin 2','admin2@empresa.com','admin2','becf77f3ec82a43422b7712134d1860e3205c6ce778b08417a7389b43f2b4661','admin','1',NULL,'2026-04-12 11:19:24','2026-04-12 11:19:24');



-- --------------------------------------------------------
-- Estructura de tabla: auditoria_logs
-- --------------------------------------------------------
CREATE TABLE `auditoria_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `usuario_nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_rol` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `tabla_afectada` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro_id` int DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_registro` (`tabla_afectada`,`registro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: auditoria_logs
--
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('1','6','Jose Chacon','usuario','crear','usuarios','Usuario creado: Jose Chacon (Picca.admin@gmail.com)','0.0.0.0',NULL,NULL,NULL,'users','6','2026-04-12 11:20:41');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('2','6','Jose Chacon','admin','editar','usuarios','Usuario editado: Jose Chacon','0.0.0.0',NULL,NULL,NULL,'users','6','2026-04-12 11:21:13');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('3',NULL,'Sistema','sistema','actualizar','facturacion','Factura FAC-2024-000002 cambió estado: pagada -> anulada','0.0.0.0',NULL,NULL,NULL,'facturas','2','2026-04-12 11:23:28');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('4',NULL,'Sistema','sistema','editar','productos','Producto editado: Termometro infrarrojo','0.0.0.0',NULL,NULL,NULL,'products','5','2026-04-12 11:23:28');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('5',NULL,'Sistema','sistema','editar','productos','Producto editado: Pinza amperimetrica digital','0.0.0.0',NULL,NULL,NULL,'products','8','2026-04-12 11:23:28');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('6',NULL,'Sistema','sistema','editar','productos','Producto editado: Rele de nivel para conductores','0.0.0.0',NULL,NULL,NULL,'products','9','2026-04-12 11:23:28');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('7',NULL,'Sistema','sistema','actualizar','pedidos','Pedido PED-2024-000003 cambió estado: pendiente -> facturado','0.0.0.0',NULL,NULL,NULL,'pedidos','3','2026-04-12 11:42:20');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('8','7','jose gregorio','usuario','crear','usuarios','Usuario creado: jose gregorio (jose14chacon2003@gmail.com)','0.0.0.0',NULL,NULL,NULL,'users','7','2026-04-13 08:12:20');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('9',NULL,'Sistema','sistema','actualizar','pedidos','Pedido PED-2026-000004 cambió estado: pendiente -> facturado','0.0.0.0',NULL,NULL,NULL,'pedidos','4','2026-04-13 08:14:55');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`) VALUES ('10',NULL,'Sistema','sistema','actualizar','facturacion','Factura FAC-2026-000002 cambió estado: pendiente -> pagada','0.0.0.0',NULL,NULL,NULL,'facturas','4','2026-04-13 08:15:26');



-- --------------------------------------------------------
-- Estructura de tabla: backups
-- --------------------------------------------------------
CREATE TABLE `backups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanio_bytes` bigint NOT NULL,
  `tipo` enum('completo','estructura','datos') COLLATE utf8mb4_unicode_ci DEFAULT 'completo',
  `estado` enum('completado','fallido','en_progreso') COLLATE utf8mb4_unicode_ci DEFAULT 'completado',
  `usuario_id` int NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_eliminacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Estructura de tabla: caja_arqueos
-- --------------------------------------------------------
CREATE TABLE `caja_arqueos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_arqueo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `usuario_apertura_id` int NOT NULL,
  `usuario_cierre_id` int DEFAULT NULL,
  `monto_inicial` decimal(12,2) NOT NULL DEFAULT '0.00',
  `monto_ingresos` decimal(12,2) DEFAULT '0.00',
  `monto_egresos` decimal(12,2) DEFAULT '0.00',
  `monto_esperado` decimal(12,2) DEFAULT '0.00',
  `monto_real` decimal(12,2) DEFAULT '0.00',
  `diferencia` decimal(12,2) DEFAULT '0.00',
  `estado` enum('abierta','cerrada','suspendida') COLLATE utf8mb4_unicode_ci DEFAULT 'abierta',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_arqueo` (`numero_arqueo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_apertura` (`fecha_apertura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Estructura de tabla: caja_movimientos
-- --------------------------------------------------------
CREATE TABLE `caja_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `arqueo_id` int NOT NULL,
  `tipo` enum('ingreso','egreso') COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia','cheque','pago_movil') COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo',
  `usuario_id` int NOT NULL,
  `fecha_movimiento` datetime DEFAULT CURRENT_TIMESTAMP,
  `factura_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_arqueo_id` (`arqueo_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fecha` (`fecha_movimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Estructura de tabla: cart_items
-- --------------------------------------------------------
CREATE TABLE `cart_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_product` (`user_id`,`product_id`),
  KEY `fk_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: cart_items
--
INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES ('1','5','3','2','2026-04-12 11:19:25');
INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES ('2','5','10','1','2026-04-12 11:19:25');
INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES ('3','5','26','1','2026-04-12 11:19:25');



-- --------------------------------------------------------
-- Estructura de tabla: clientes
-- --------------------------------------------------------
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_documento` enum('cedula','ruc','pasaporte','dni') DEFAULT 'cedula',
  `documento` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text,
  `ciudad` varchar(50) DEFAULT NULL,
  `estado` enum('activo','inactivo','moroso') DEFAULT 'activo',
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento` (`documento`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: clientes
--
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('1','cedula','12345678','Juan Pérez','juan@email.com','04121234567','Av. Principal #123, Caracas',NULL,'activo','2026-04-12 11:19:24');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('2','cedula','87654321','María González','maria@email.com','04149876543','Calle Secundaria #45, Maracaibo',NULL,'activo','2026-04-12 11:19:24');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('3','cedula','11223344','Carlos Rodríguez','carlos@email.com','04161122334','Urb. Las Flores, Valencia',NULL,'activo','2026-04-12 11:19:24');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('4','cedula','11111111','Cliente de Prueba','cliente@test.com','04141234567','Calle Principal, Barquisimeto',NULL,'activo','2026-04-12 11:19:24');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('8','cedula','31129605','Jose Chacon','Picca.admin@gmail.com','04121311228','Urb trigal Sur Calle Camoruco',NULL,'activo','2026-04-12 11:20:41');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('11','cedula','17314511','jose gregorio','jose14chacon2003@gmail.com','04144030184','Urb trigal Sur Calle Camoruco',NULL,'activo','2026-04-13 08:12:20');



-- --------------------------------------------------------
-- Estructura de tabla: compra_detalles
-- --------------------------------------------------------
CREATE TABLE `compra_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compra_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `cantidad_recibida` int DEFAULT '0',
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `producto_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `producto_sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_compra_id` (`compra_id`),
  KEY `idx_producto_id` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Estructura de tabla: compras
-- --------------------------------------------------------
CREATE TABLE `compras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_orden` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proveedor_id` int NOT NULL,
  `fecha_orden` date NOT NULL,
  `fecha_requerida` date DEFAULT NULL,
  `fecha_recibido` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `iva` decimal(12,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(12,2) DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado` enum('cotizacion','aprobada','enviada','recibida_parcial','recibida_total','anulada') COLLATE utf8mb4_unicode_ci DEFAULT 'cotizacion',
  `metodo_pago` enum('transferencia','efectivo','cheque','credito') COLLATE utf8mb4_unicode_ci DEFAULT 'transferencia',
  `condiciones_pago` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_creacion_id` int NOT NULL,
  `usuario_aprobacion_id` int DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_orden` (`numero_orden`),
  KEY `idx_proveedor_id` (`proveedor_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_orden` (`fecha_orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Estructura de tabla: comprobantes
-- --------------------------------------------------------
CREATE TABLE `comprobantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `tipo_comprobante` enum('transferencia','deposito','pago_movil','zelle','paypal') NOT NULL,
  `numero_comprobante` varchar(50) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_comprobante` date NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `estado` enum('pendiente','verificado','rechazado') DEFAULT 'pendiente',
  `observaciones` text,
  `usuario_verificacion_id` int DEFAULT NULL,
  `fecha_verificacion` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comprobantes_cliente` (`cliente_id`),
  KEY `fk_comprobantes_usuario` (`usuario_verificacion_id`),
  CONSTRAINT `fk_comprobantes_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comprobantes_usuario` FOREIGN KEY (`usuario_verificacion_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- --------------------------------------------------------
-- Estructura de tabla: configuracion_sistema
-- --------------------------------------------------------
CREATE TABLE `configuracion_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `grupo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editable` tinyint(1) DEFAULT '1',
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`),
  KEY `idx_grupo` (`grupo`),
  KEY `idx_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: configuracion_sistema
--
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('1','empresa_nombre','PIC - Productos Industriales y Comerciales','text','empresa','Nombre de la empresa','1','1','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('2','empresa_rif','J-12345678-9','text','empresa','RIF de la empresa','1','2','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('3','empresa_direccion','Av. Principal, Zona Industrial, Caracas','text','empresa','Dirección de la empresa','1','3','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('4','empresa_telefono','0212-5551234','text','empresa','Teléfono de contacto','1','4','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('5','empresa_email','info@pic.com.ve','email','empresa','Email de contacto','1','5','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('6','iva_porcentaje','16','number','facturacion','Porcentaje de IVA aplicado','1','10','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('7','moneda_principal','Bs','text','facturacion','Moneda principal del sistema','1','11','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('8','factura_prefijo','FAC','text','facturacion','Prefijo para números de factura','1','12','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('9','factura_longitud','6','number','facturacion','Longitud del correlativo','1','13','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('10','notificaciones_email','1','boolean','notificaciones','Enviar notificaciones por email','1','20','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('11','notificaciones_whatsapp','0','boolean','notificaciones','Enviar notificaciones por WhatsApp','1','21','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('12','stock_minimo_alerta','5','number','inventario','Stock mínimo para alertas','1','30','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('13','modo_mantenimiento','0','boolean','sistema','Modo mantenimiento del sistema','1','40','2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('14','version_sistema','2.0.0','text','sistema','Versión actual del sistema','0','41','2026-04-12 11:19:26','2026-04-12 11:19:26');



-- --------------------------------------------------------
-- Estructura de tabla: factura_detalles
-- --------------------------------------------------------
CREATE TABLE `factura_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `factura_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_factura_detalles_factura` (`factura_id`),
  KEY `fk_factura_detalles_producto` (`producto_id`),
  CONSTRAINT `fk_factura_detalles_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_factura_detalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: factura_detalles
--
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('1','1','1','5','150.00','750.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('2','1','2','10','75.00','750.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('3','2','5','1','450.00','450.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('4','2','8','2','120.00','240.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('5','2','9','1','185.00','185.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('6','3','26','3','380.00','1140.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('7','3','41','2','420.00','840.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('8','3','51','1','4200.00','4200.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('9','4','4','1','55.00','55.00');



-- --------------------------------------------------------
-- Estructura de tabla: facturas
-- --------------------------------------------------------
CREATE TABLE `facturas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(50) NOT NULL,
  `cliente_id` int NOT NULL,
  `pedido_id` int DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `iva` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia','cheque','paypal','débito') DEFAULT 'efectivo',
  `estado` enum('pendiente','pagada','anulada','vencida') DEFAULT 'pendiente',
  `usuario_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`),
  KEY `fk_facturas_cliente` (`cliente_id`),
  KEY `fk_facturas_usuario` (`usuario_id`),
  KEY `idx_facturas_pedido` (`pedido_id`),
  KEY `idx_facturas_pedido_fecha` (`pedido_id`,`fecha_emision`),
  CONSTRAINT `fk_facturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_facturas_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_facturas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: facturas
--
INSERT INTO `facturas` (`id`, `numero_factura`, `cliente_id`, `pedido_id`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`, `updated_at`) VALUES ('1','FAC-2024-000001','1','1','2024-01-15','2024-02-15','1500.00','240.00','1740.00','transferencia','pagada','1','2026-04-12 11:19:25','2026-04-12 11:19:25');
INSERT INTO `facturas` (`id`, `numero_factura`, `cliente_id`, `pedido_id`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`, `updated_at`) VALUES ('2','FAC-2024-000002','2','2','2024-01-20','2024-02-20','875.00','140.00','1015.00','tarjeta','anulada','1','2026-04-12 11:19:25','2026-04-12 11:23:28');
INSERT INTO `facturas` (`id`, `numero_factura`, `cliente_id`, `pedido_id`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`, `updated_at`) VALUES ('3','FAC-2026-000001','3','3','2026-04-12','2026-05-12','6180.00','988.80','7168.80','transferencia','pendiente','6','2026-04-12 11:42:20','2026-04-12 11:42:20');
INSERT INTO `facturas` (`id`, `numero_factura`, `cliente_id`, `pedido_id`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`, `updated_at`) VALUES ('4','FAC-2026-000002','11','4','2026-04-13','2026-05-13','46.20','8.80','55.00','transferencia','pagada','6','2026-04-13 08:14:55','2026-04-13 08:15:26');



-- --------------------------------------------------------
-- Estructura de tabla: movimientos_inventario
-- --------------------------------------------------------
CREATE TABLE `movimientos_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste','devolucion') NOT NULL,
  `cantidad` int NOT NULL,
  `descripcion` text,
  `motivo` varchar(255) DEFAULT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `compra_id` int DEFAULT NULL,
  `factura_id` int DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `fecha_movimiento` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_movimientos_producto` (`producto_id`),
  KEY `fk_movimientos_usuario` (`usuario_id`),
  KEY `idx_compra_id` (`compra_id`),
  KEY `idx_factura_id` (`factura_id`),
  CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: movimientos_inventario
--
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('1','1','salida','5','Venta factura FAC-2024-000001',NULL,'FAC-2024-000001',NULL,NULL,'1','2026-04-12 11:19:25');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('2','2','salida','10','Venta factura FAC-2024-000001',NULL,'FAC-2024-000001',NULL,NULL,'1','2026-04-12 11:19:25');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('3','5','salida','1','Venta factura FAC-2024-000002',NULL,'FAC-2024-000002',NULL,NULL,'1','2026-04-12 11:19:25');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('4','8','salida','2','Venta factura FAC-2024-000002',NULL,'FAC-2024-000002',NULL,NULL,'1','2026-04-12 11:19:25');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('5','9','salida','1','Venta factura FAC-2024-000002',NULL,'FAC-2024-000002',NULL,NULL,'1','2026-04-12 11:19:25');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('6','26','salida','3','Facturación pedido FAC-2026-000001',NULL,'FAC-2026-000001',NULL,NULL,'6','2026-04-12 11:42:20');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('7','41','salida','2','Facturación pedido FAC-2026-000001',NULL,'FAC-2026-000001',NULL,NULL,'6','2026-04-12 11:42:20');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('8','51','salida','1','Facturación pedido FAC-2026-000001',NULL,'FAC-2026-000001',NULL,NULL,'6','2026-04-12 11:42:20');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `motivo`, `referencia`, `compra_id`, `factura_id`, `usuario_id`, `fecha_movimiento`) VALUES ('9','4','salida','1','Facturación pedido FAC-2026-000002',NULL,'FAC-2026-000002',NULL,NULL,'6','2026-04-13 08:14:55');



-- --------------------------------------------------------
-- Estructura de tabla: pagos
-- --------------------------------------------------------
CREATE TABLE `pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `factura_id` int NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia','cheque','paypal','débito') NOT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int NOT NULL,
  `observaciones` text,
  PRIMARY KEY (`id`),
  KEY `fk_pagos_factura` (`factura_id`),
  KEY `fk_pagos_usuario` (`usuario_id`),
  CONSTRAINT `fk_pagos_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: pagos
--
INSERT INTO `pagos` (`id`, `factura_id`, `monto`, `metodo_pago`, `referencia`, `fecha_pago`, `usuario_id`, `observaciones`) VALUES ('1','1','1740.00','transferencia','REF001','2026-04-12 11:19:25','1',NULL);
INSERT INTO `pagos` (`id`, `factura_id`, `monto`, `metodo_pago`, `referencia`, `fecha_pago`, `usuario_id`, `observaciones`) VALUES ('2','2','1015.00','tarjeta','REF002','2026-04-12 11:19:25','1',NULL);
INSERT INTO `pagos` (`id`, `factura_id`, `monto`, `metodo_pago`, `referencia`, `fecha_pago`, `usuario_id`, `observaciones`) VALUES ('3','4','55.00','transferencia',NULL,'2026-04-13 08:15:26','6','Pago registrado manualmente desde el sistema');



-- --------------------------------------------------------
-- Estructura de tabla: pedido_detalles
-- --------------------------------------------------------
CREATE TABLE `pedido_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_original` decimal(10,2) NOT NULL,
  `descuento_aplicado` decimal(5,2) DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL,
  `producto_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `producto_sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producto_categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pedido_id` (`pedido_id`),
  KEY `idx_producto_id` (`producto_id`),
  CONSTRAINT `fk_pedido_detalles_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedido_detalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`),
  CONSTRAINT `pedido_detalles_chk_1` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: pedido_detalles
--
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('1','1','1','5','150.00','150.00','0.00','750.00','Sensor inductivo prt12-4dp','PROD-0001','Sensores','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('2','1','2','10','75.00','75.00','0.00','750.00','Boton pulsador Autonics Nc S3pf-p1rb','PROD-0002','Botoneras','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('3','2','5','1','450.00','450.00','0.00','450.00','Termometro infrarrojo','PROD-0005','Instrumentos de Medición','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('4','2','8','2','120.00','120.00','0.00','240.00','Pinza amperimetrica digital','PROD-0008','Instrumentos de Medición','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('5','2','9','1','185.00','185.00','0.00','185.00','Rele de nivel para conductores','PROD-0009','Relés','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('6','3','26','3','380.00','380.00','0.00','1140.00','Contactor 25amp 24vdc','PROD-0026','Contactores','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('7','3','41','2','420.00','420.00','0.00','840.00','Contactor 40amp 110v','PROD-0041','Contactores','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('8','3','51','1','4200.00','4200.00','0.00','4200.00','Variador de velociadad 5hp 440v','PROD-0051','Variadores','2026-04-12 11:19:25');
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `descuento_aplicado`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`, `created_at`) VALUES ('9','4','4','1','55.00','55.00','0.00','55.00','Caja para pulsadores 2 huecos','','','2026-04-13 08:12:55');



-- --------------------------------------------------------
-- Estructura de tabla: pedidos
-- --------------------------------------------------------
CREATE TABLE `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `numero_pedido` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `iva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(10,2) DEFAULT '0.00',
  `metodo_pago` enum('transferencia','pago_movil','efectivo','tarjeta','zelle','paypal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','procesando','facturado','completado','cancelado','rechazado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `direccion_entrega` text COLLATE utf8mb4_unicode_ci,
  `ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_contacto` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_receptor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `referencia_pago` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_pago` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `notas_internas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_facturacion` datetime DEFAULT NULL,
  `usuario_procesa_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pedido` (`numero_pedido`),
  KEY `fk_pedidos_usuario_procesa` (`usuario_procesa_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_numero_pedido` (`numero_pedido`),
  KEY `idx_estado` (`estado`),
  KEY `idx_pedidos_fecha` (`created_at`),
  KEY `idx_pedidos_estado_fecha` (`estado`,`created_at`),
  CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedidos_usuario_procesa` FOREIGN KEY (`usuario_procesa_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: pedidos
--
INSERT INTO `pedidos` (`id`, `usuario_id`, `numero_pedido`, `total`, `subtotal`, `iva`, `descuento`, `metodo_pago`, `estado`, `direccion_entrega`, `ciudad`, `telefono_contacto`, `nombre_receptor`, `fecha_entrega`, `referencia_pago`, `comprobante_pago`, `observaciones`, `notas_internas`, `created_at`, `updated_at`, `fecha_facturacion`, `usuario_procesa_id`) VALUES ('1','2','PED-2024-000001','1740.00','1500.00','240.00','0.00','transferencia','completado','Av. Principal #123',NULL,'04121234567','Juan Pérez',NULL,NULL,NULL,NULL,NULL,'2024-01-10 10:30:00','2026-04-12 11:19:25',NULL,NULL);
INSERT INTO `pedidos` (`id`, `usuario_id`, `numero_pedido`, `total`, `subtotal`, `iva`, `descuento`, `metodo_pago`, `estado`, `direccion_entrega`, `ciudad`, `telefono_contacto`, `nombre_receptor`, `fecha_entrega`, `referencia_pago`, `comprobante_pago`, `observaciones`, `notas_internas`, `created_at`, `updated_at`, `fecha_facturacion`, `usuario_procesa_id`) VALUES ('2','3','PED-2024-000002','1015.00','875.00','140.00','0.00','tarjeta','facturado','Calle Secundaria #45',NULL,'04149876543','María González',NULL,NULL,NULL,NULL,NULL,'2024-01-15 14:20:00','2026-04-12 11:19:25',NULL,NULL);
INSERT INTO `pedidos` (`id`, `usuario_id`, `numero_pedido`, `total`, `subtotal`, `iva`, `descuento`, `metodo_pago`, `estado`, `direccion_entrega`, `ciudad`, `telefono_contacto`, `nombre_receptor`, `fecha_entrega`, `referencia_pago`, `comprobante_pago`, `observaciones`, `notas_internas`, `created_at`, `updated_at`, `fecha_facturacion`, `usuario_procesa_id`) VALUES ('3','4','PED-2024-000003','7168.80','6180.00','988.80','0.00','pago_movil','facturado','Urb. Las Flores',NULL,'04161122334','Carlos Rodríguez',NULL,NULL,NULL,NULL,NULL,'2024-01-20 09:15:00','2026-04-12 11:42:20','2026-04-12 11:42:20',NULL);
INSERT INTO `pedidos` (`id`, `usuario_id`, `numero_pedido`, `total`, `subtotal`, `iva`, `descuento`, `metodo_pago`, `estado`, `direccion_entrega`, `ciudad`, `telefono_contacto`, `nombre_receptor`, `fecha_entrega`, `referencia_pago`, `comprobante_pago`, `observaciones`, `notas_internas`, `created_at`, `updated_at`, `fecha_facturacion`, `usuario_procesa_id`) VALUES ('4','7','PED-2026-000004','55.00','46.20','8.80','0.00','pago_movil','facturado',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,'2026-04-13 08:12:55','2026-04-13 08:14:55','2026-04-13 08:14:55',NULL);



-- --------------------------------------------------------
-- Estructura de tabla: products
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(512) DEFAULT NULL,
  `description` text,
  `category` varchar(100) DEFAULT 'General',
  `rating` decimal(2,1) DEFAULT '0.0',
  `views_count` int DEFAULT '0',
  `specs` text,
  `stock` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `weight` decimal(10,2) DEFAULT '0.00',
  `dimensions` varchar(100) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'Bs',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: products
--
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('1','PROD-0001','Sensor inductivo prt12-4dp','150.00','https://http2.mlstatic.com/D_Q_NP_2X_907785-MLV42256115993_062020-E.webp','Sensores Autonics Inductivos, Capacitivos, rasante, no rasante 2 hilos abierto o cerrado, 3 hilos pnp o npn Fotoeléctricos, a conector, a cable.Marca Autonics Modelo Pr12-4dp .','Sensores','4.5','0','','40','1','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:25');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('2','PROD-0002','Boton pulsador Autonics Nc S3pf-p1rb','75.00','https://http2.mlstatic.com/D_NQ_NP_2X_927922-MLV52483035472_112022-F.webp','Boton pulsador Autonics modelo S3pf-p1rb contacto sa/cbno iluminado empotrado rojo normalmente cerrado 300A 110VAC CA/10A 250VAC CA/6A.','Botoneras','4.0','0','','55','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:25');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('3','PROD-0003','Rele termico regulable 48-65a Ldr365','320.00','https://http2.mlstatic.com/D_Q_NP_2X_971966-MLV42316060787_062020-E.webp','Rele termic regulable 48-65a Marca Schneider Electric modelo Lrd365c .','Relés','5.0','0','','25','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('4','PROD-0004','Guardamotor','280.00','https://http2.mlstatic.com/D_Q_NP_2X_987302-MLV42319903598_062020-E.webp',' Marca Schneider Electric. modelo Gv2me08','Protecciones','4.2','0','','40','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('5','PROD-0005','Termometro infrarrojo','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_780836-MLV48799544246_012022-F.webp','Termómetro Infrarrojo -32°c A 1050°c marca unit-t modelo ut302d.','Instrumentos de Medición','4.8','0','','18','1','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:23:28');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('6','PROD-0006','Botonera colgante','180.00','https://http2.mlstatic.com/D_Q_NP_2X_605998-MLV91579814235_092025-E.webp','Botonera colgante de 6 pulsadores Marca schneider electric modelo xaca671 material propipolineno.','Botoneras','4.1','0','','35','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('7','PROD-0007','Sensor fotoelectrico mfr','220.00','https://http2.mlstatic.com/D_Q_NP_2X_781132-MLV90889351684_082025-E.webp','Sensor fotorelectrico Autonics bx5m.','Sensores','4.6','0','','42','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('8','PROD-0008','Pinza amperimetrica digital','120.00','https://http2.mlstatic.com/D_NQ_NP_2X_919873-MLV50246492941_062022-F.webp','Marca uni-t Modelo Ut201+','Instrumentos de Medición','4.9','0','','28','1','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:23:28');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('9','PROD-0009','Rele de nivel para conductores','185.00','https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp','marca exceline modelo grn-mv.','Relés','4.3','0','','32','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:23:28');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('10','PROD-0010','Manometro festo','95.00','https://http2.mlstatic.com/D_Q_NP_2X_782534-MLV80960384399_112024-E.webp','Marca festo modelo ma-50-10-1/4-enef162838.','Instrumentos de Medición','4.4','0','','55','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('11','PROD-0011','Mini termo anemometro y medidor de humedad','650.00','https://http2.mlstatic.com/D_Q_NP_2X_954754-MLV76879763367_062024-E.webp','Marca Extech Modelo 45158.','Instrumentos de Medición','4.0','0','','12','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('12','PROD-0012','Sensor capacitivo Autonics','210.00','https://http2.mlstatic.com/D_Q_NP_2X_764408-MLV42258601667_062020-E.webp','Sensores Inductivos, Capacitivos, rasante, no rasante 2 hilos abierto o cerrado, 3 hilos pnp o npn Fotoeléctricos, a conector, a cable Marca Autonics Modelo Cr18-8ac.','Sensores','4.7','0','','38','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('13','PROD-0013','Selector 2 posiciones','45.00','https://http2.mlstatic.com/D_Q_NP_2X_946997-MLV46271812962_062021-E.webp','Marca Scneider Electric Modelo XB4BD21.','Controles','4.5','0','','70','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('14','PROD-0014','Etiquetadora panduit','2800.00','https://http2.mlstatic.com/D_Q_NP_2X_845848-MLV75886383737_042024-E.webp','Marca Extech Modelo PanTher LS8E.','Herramientas','4.8','0','','5','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('15','PROD-0015','Rele de nivel para lquidos conductores','185.00','https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp','Marca Exceline Modelo Grn-mv Voltaje 110-220.','Relés','4.2','0','','32','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('16','PROD-0016','Rele de estado solido Autonics','125.00','https://http2.mlstatic.com/D_NQ_NP_2X_823517-MLV49140687804_022022-F.webp','Marca Autonics Modelo SR1-4415.','Relés','4.5','0','','48','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('17','PROD-0017','Final de carrera','195.00','https://http2.mlstatic.com/D_Q_NP_2X_853654-MLV42315651853_062020-E.webp','Marca Telemecanique/schneider Modelo XCKJO513.','Sensores','4.6','0','','28','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('18','PROD-0018','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_854049-MLV42347247961_062020-E.webp','Marca scheneider/telemecanique XCKP2121G11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('19','PROD-0019','Contador temporizador','280.00','https://http2.mlstatic.com/D_Q_NP_2X_868764-MLV82980146035_032025-E.webp','Marca Autonics Modelo CT6Y-1P2.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('20','PROD-0020','Pinza amperimetrica','135.00','https://http2.mlstatic.com/D_NQ_NP_2X_685608-MLV43035297361_082020-F.webp','Marca uni-t Modelo UT202a+.','Instrumentos de Medición','4.7','0','','35','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('21','PROD-0021','Contador temporizador','280.00','https://http2.mlstatic.com/D_Q_NP_2X_943529-MLV82980293127_032025-E.webp','Marca Autonics Modelo CT6Y-1P4.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('22','PROD-0022','Pinza amperimetrica extech','420.00','https://http2.mlstatic.com/D_Q_NP_2X_891753-MLV48858956084_012022-E.webp','Marca extech Modelo UT210d.','Instrumentos de Medición','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('23','PROD-0023','Sensor TIq5mc1 Jootiden','60.00','https://http2.mlstatic.com/D_Q_NP_2X_983049-MLV78136901025_082024-E.webp','Marca generica Modelo TL-Q5MC1.','Sensores','4.7','0','','85','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('24','PROD-0024','Pinza amperimetrica + termometro','220.00','https://http2.mlstatic.com/D_NQ_NP_2X_826593-MLV46165148147_052021-F.webp','Marca extech modelo EX470.','Instrumentos de Medición','4.8','0','','25','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('25','PROD-0025','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_904301-MLV42315881121_062020-E.webp','Marca schneider/telemecanique Modelo XCKP2118G11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('26','PROD-0026','Contactor 25amp 24vdc','380.00','https://images.wiautomation.com/public/images/landing/anticipa/product/LC1DT206SLS207.jpg','Marca scheider electric Modelo LCD1E25BD.','Contactores','4.7','0','','18','1','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('27','PROD-0027','Contactor 80amp 220v','680.00','https://http2.mlstatic.com/D_NQ_NP_2X_774386-MLV42329989223_062020-F.webp','Marca scheneider electric Modelo LC1d80m.','Contactores','4.7','0','','10','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('28','PROD-0028','osiloscopio extech','850.00','https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-F.webp','Marca scheneider electric Modelo LC1D09BD.','Instrumentos de Medición','4.7','0','','8','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('29','PROD-0029','Pinza amperimetrica digital','120.00','https://http2.mlstatic.com/D_NQ_NP_2X_928642-MLV54457071668_032023-F.webp','Marca uni-t Modelo UT201+.','Instrumentos de Medición','4.7','0','','35','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('30','PROD-0030','Fuente de poder 5amp 12vdc Aunonics','185.00','https://http2.mlstatic.com/D_NQ_NP_2X_606115-MLV82504917240_022025-F.webp','Marca scheneider electric Modelo SPB-O6O-12.','Fuentes de Poder','4.7','0','','30','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('31','PROD-0031','kit maletin legrand starfix','320.00','https://http2.mlstatic.com/D_Q_NP_2X_983177-MLV71749528286_092023-E.webp','Marca lengard Modelo 376 59/60.','Herramientas','4.7','0','','20','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('32','PROD-0032','Controlador de temperatura ','290.00','https://http2.mlstatic.com/D_NQ_NP_2X_966407-MLV54265777533_032023-F.webp','Marca Autonics Modelo tk4s-bn4r.','Controladores','4.7','0','','16','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('33','PROD-0033','Descanso ajustable para pie','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_978007-MLV71801855063_092023-F.webp','Marca lengard Modelo 376 59/60.','Accesorios','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('34','PROD-0034','Controlador de temperaura 48x69','210.00','https://http2.mlstatic.com/D_NQ_NP_2X_821401-MLV73213656021_122023-F.webp','Marca 3M Modelo FR53OCB.','Controladores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('35','PROD-0035','Temporizador Autonics','140.00','https://http2.mlstatic.com/D_NQ_NP_2X_956520-MLV52366651303_112022-F.webp','Marca Autonics Modelo Le8n-bfle8n-bn.','Temporizadores','4.7','0','','38','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('36','PROD-0036','Controlador temporizador','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_674477-MLV51061339386_082022-F.webp','Marca Autonics Modelo Ct6-1p2.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('37','PROD-0037','Multimetro uni-t','150.00','https://http2.mlstatic.com/D_NQ_NP_2X_841899-MLV46427086846_062021-F.webp','Marca uni-t Modelo Ut89x.','Instrumentos de Medición','4.7','0','','30','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('38','PROD-0038','Sensor amplificador para fibra optica','200.00','https://http2.mlstatic.com/D_NQ_NP_2X_964670-MLV42255017336_062020-F.webp','Marca Autonics Modelo Bf4rp.','Sensores','4.7','0','','25','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('39','PROD-0039','Rele estado solido trifasico 30amp','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_754022-MLV82085220364_022025-F.webp','Marca Autonics Modelo Sr3-4430.','Relés','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('40','PROD-0040','Sensor fotoelectrico','210.00','https://http2.mlstatic.com/D_NQ_NP_2X_656456-MLV42255069136_062020-F.webp','Marca Autonics Modelo Brqm400-ddta.','Sensores','4.7','0','','28','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('41','PROD-0041','Contactor 40amp 110v','420.00','https://http2.mlstatic.com/D_NQ_NP_2X_697183-MLV81969170376_022025-F.webp','Marca Schneider Electric Modelo LCD1D4OAF7.','Contactores','4.7','0','','15','1','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('42','PROD-0042','Fuente de poder 8amp 12vdc','240.00','https://http2.mlstatic.com/D_NQ_NP_2X_731782-MLV78217056967_082024-F.webp','Marca Autonics Modelo SPB-120-12.','Fuentes de Poder','4.7','0','','20','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('43','PROD-0043','Rele termic regulable 30-40a','340.00','https://http2.mlstatic.com/D_NQ_NP_2X_719268-MLV42301142622_062020-F.webp','Marca scheneider electric Modelo LRD3355.','Relés','4.7','0','','16','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('44','PROD-0044','Guardamotor 1-1.6a','290.00','https://http2.mlstatic.com/D_NQ_NP_2X_842891-MLV42319762831_062020-F.webp','Marca Schneider Electric Modelo GV2ME06 .','Protecciones','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('45','PROD-0045','Idicadores de frecuencia uni-t','300.00','https://http2.mlstatic.com/D_NQ_NP_2X_892854-MLV48915410648_012022-F.webp','Marca uni-t Modelo Ut261a.','Instrumentos de Medición','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('46','PROD-0046','Protector televisor','85.00','https://http2.mlstatic.com/D_NQ_NP_2X_728315-MLV46442590142_062021-F.webp','Marca Exceline Modelo Gsm-tv120.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('47','PROD-0047','Rele estado solido','125.00','https://http2.mlstatic.com/D_NQ_NP_2X_692640-MLV49139833433_022022-F.webp','Marca Autonics Modelo Sr1-1450.','Relés','4.7','0','','48','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('48','PROD-0048','Fuente de poder 20amp 12vdc','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_987249-MLV73944702421_012024-F.webp','Marca Autonics Modelo Sp240-12.','Fuentes de Poder','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('49','PROD-0049','Cable para sesor M8','55.00','https://http2.mlstatic.com/D_Q_NP_2X_855871-MLV70628379777_072023-E.webp','Marca Telemecanique Modelo Xzcp0941l2.','Accesorios','4.7','0','','90','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('50','PROD-0050','Contactor 25amp 220v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_961256-MLV42321081111_062020-F.webp','Marca Scheneider electric Modelo Lc1d25m7.','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('51','PROD-0051','Variador de velociadad 5hp 440v','4200.00','https://http2.mlstatic.com/D_NQ_NP_2X_693722-MLA76246464467_052024-F.webp','Marca scheneider electric Modelo Atv320u40n4c .','Variadores','4.7','0','','4','1','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('52','PROD-0052','Sensor fotoelectrico reflectivo','195.00','https://http2.mlstatic.com/D_NQ_NP_2X_896757-MLV78646725763_082024-F.webp','Marca Autonics Modelo Brqm3m-pdta-c-p .','Sensores','4.7','0','','28','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('53','PROD-0053','Contactor 32amp 220v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_775520-MLV42321197517_062020-F.webp','Marca scheneider electric Modelo Lc1d32m7 .','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('54','PROD-0054','Rele termico regulable','390.00','https://http2.mlstatic.com/D_Q_NP_2X_720989-MLV42320094116_062020-E.webp','Marca scheneider electric Modelo Lrd3357.','Relés','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('55','PROD-0055','Caja para pulsadores plastica 3 huecos','70.00','https://http2.mlstatic.com/D_Q_NP_2X_719143-MLV42346979496_062020-E.webp','Marca scheneider Modelo Xald03.','Accesorios','4.7','0','','65','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('56','PROD-0056','Temporizacion Rele estrella triangulo','240.00','https://http2.mlstatic.com/D_Q_NP_2X_966925-MLV83523597338_042025-E.webp','Marca scheneider Modelo Re22r2qtmr.','Relés','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('57','PROD-0057','Caja para pulsadores 2 huecos','55.00','https://http2.mlstatic.com/D_Q_NP_2X_783226-MLV42347077873_062020-E.webp','Marca scheneider Modelo Xald02.','Accesorios','4.7','0','','75','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('58','PROD-0058','Contactor 18amp 24vdc','330.00','https://http2.mlstatic.com/D_NQ_NP_2X_669780-MLV42320450829_062020-E.webp','Marca scheneider electric Modelo Lc1d18bd.','Contactores','4.7','0','','20','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('59','PROD-0059','Contactor 38amp 220v','420.00','https://http2.mlstatic.com/D_NQ_NP_2X_761868-MLV42321342194_062020-F.webp','Marca scheneider electric Modelo Lc1d38m7.','Contactores','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('60','PROD-0060','Guardamotor 48-65a','580.00','https://http2.mlstatic.com/D_Q_NP_2X_879405-MLV42346668428_062020-E.webp','Marca scheneider electric Modelo Gv3p65.','Protecciones','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('61','PROD-0061','Contactor 256a 220v','3800.00','https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp','Marca scheneider Modelo Lc1f265m7.','Contactores','4.7','0','','3','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('62','PROD-0062','Rele termico regulable','390.00','https://http2.mlstatic.com/D_Q_NP_2X_719268-MLV42301142622_062020-E.webp','Marca scheneider electric Modelo Ldr3355.','Relés','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('63','PROD-0063','Sensor de marca fotocelula','850.00','https://http2.mlstatic.com/D_NQ_NP_2X_965275-MLV42316005076_062020-F.webp','Marca Telemecanique.Modelo Xurk1ksmm12 ','Sensores','4.7','0','','8','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('64','PROD-0064','Variador de velocidad 7.5hp','5800.00','https://http2.mlstatic.com/D_NQ_NP_2X_617200-MLV46302539269_062021-F.webp','Marca scheneider electric Modelo Atv320u55m3c.','Variadores','4.7','0','','3','1','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('65','PROD-0065','Sensor inductivo','95.00','https://http2.mlstatic.com/D_Q_NP_2X_835762-MLV50041103539_052022-E.webp','Marca Autonics Modelo prd18 8dp.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('66','PROD-0066','Contactor 265a 220v','3800.00','https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp','Marca scheneider electric Modelo Lc1f265m7.','Contactores','4.7','0','','3','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('67','PROD-0067','Lockout 63amp seleccionador bloqueador','550.00','https://http2.mlstatic.com/D_Q_NP_2X_651999-MLV42345796245_062020-E.webp','Marca scheneider Modelo Vcf5ge.','Accesorios','4.7','0','','10','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('68','PROD-0068','Lockout 100amp seleccionador bloqueador','680.00','https://http2.mlstatic.com/D_Q_NP_2X_833469-MLV42331350351_062020-E.webp','Marca scheneider electric Modelo Vcf5gen.','Accesorios','4.7','0','','8','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('69','PROD-0069','Sensor inductivo de rotacion','420.00','https://http2.mlstatic.com/D_Q_NP_2X_998055-MLV53428583531_012023-E.webp','Marca Telemecanique Modelo Xsav12373.','Sensores','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('70','PROD-0070','Sensor','140.00','https://http2.mlstatic.com/D_Q_NP_2X_912959-MLV42259148070_062020-E.webp','Marca Autonics Modelo Prcm30-5dp.','Sensores','4.7','0','','38','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('71','PROD-0071','Protctor para aires y refrigeradores','95.00','https://http2.mlstatic.com/D_Q_NP_2X_995442-MLV42253383680_062020-E.webp','Marca Exceline Modelo Gsm-rt120.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('72','PROD-0072','Flotador electrico multivoltaje','70.00','https://http2.mlstatic.com/D_Q_NP_2X_837451-MLV42253919704_062020-E.webp','Marca Exceline Modelo Gfe-mv3m.','Accesorios','4.7','0','','65','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('73','PROD-0073','Contactor 9amp 24vdc','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-E.webp','Marca scheneider electric Modelo Lc190bd.','Contactores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('74','PROD-0074','Contactor 32amp 110v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_849936-MLV42321134886_062020-E.webp','Marca scheneider electric Modelo Lc1d32f7.','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('75','PROD-0075','Contactor 40amp 24vdc','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_966221-MLV46232503172_062021-E.webp','Marca sccheneider electric Lc1d40ab7.','Contactores','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('76','PROD-0076','Contactor 65amp 220v','650.00','https://http2.mlstatic.com/D_NQ_NP_2X_616564-MLV42329913652_062020-E.webp','Marca scheneider electric Modelo Lc1d65am7.','Contactores','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('77','PROD-0077','Contactor 185amp 220v','2500.00','https://http2.mlstatic.com/D_NQ_NP_2X_838661-MLV50182142214_062022-E.webp','Marca scheneider Modelo Lc1f185m7.','Contactores','4.7','0','','5','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('78','PROD-0078','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_921213-MLV42302675953_062020-E.webp','Marca scheneider/Telemecanique Modelo Xckp2127g11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('79','PROD-0079','Mini termometro infrrarojo','125.00','https://http2.mlstatic.com/D_Q_NP_2X_887138-MLV50723282858_072022-E.webp','Marca uni-t Modelo Ut300a+.','Instrumentos de Medición','4.7','0','','48','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('80','PROD-0080','Protector para motores monofasicos','85.00','https://http2.mlstatic.com/D_Q_NP_2X_721232-MLV42314862754_062020-E.webp','Marca exceline Modelo Gsm-r220b.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','2026-04-12 11:19:24','2026-04-12 11:19:24');



-- --------------------------------------------------------
-- Estructura de tabla: proveedores
-- --------------------------------------------------------
CREATE TABLE `proveedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_comercial` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_documento` enum('cedula','ruc','pasaporte','dni') COLLATE utf8mb4_unicode_ci DEFAULT 'ruc',
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_principal` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_secundario` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_principal` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_secundario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sitio_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condiciones_pago` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plazo_entrega` int DEFAULT '0',
  `forma_pago` enum('transferencia','efectivo','cheque','mixto') COLLATE utf8mb4_unicode_ci DEFAULT 'transferencia',
  `moneda` enum('Bs','USD','EUR') COLLATE utf8mb4_unicode_ci DEFAULT 'Bs',
  `estado` enum('activo','inactivo','suspendido') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `saldo_pendiente` decimal(12,2) DEFAULT '0.00',
  `calificacion` decimal(2,1) DEFAULT '0.0',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `ruc` (`ruc`),
  KEY `idx_estado` (`estado`),
  KEY `idx_ruc` (`ruc`),
  KEY `idx_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: proveedores
--
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('1','PROV-001','Autonics Venezuela','Autonics C.A.','J-12345678-9','ruc','Av. Principal, Zona Industrial','Caracas','0212-5551234',NULL,'ventas@autonics.com.ve',NULL,'Carlos Méndez',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('2','PROV-002','Schneider Electric','Schneider Electric Venezuela','J-87654321-0','ruc','Calle 5, Parque Industrial','Valencia','0241-5555678',NULL,'ventas@schneider.com.ve',NULL,'Ana Rodríguez',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-12 11:19:26','2026-04-12 11:19:26');
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('3','PROV-003','UNI-T Venezuela','UNI-T Instruments C.A.','J-11223344-5','ruc','Av. Libertador, Centro Comercial','Maracaibo','0261-5559012',NULL,'importaciones@unit.com.ve',NULL,'Luis Fernández',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-12 11:19:26','2026-04-12 11:19:26');



-- --------------------------------------------------------
-- Estructura de tabla: secuencias_facturacion
-- --------------------------------------------------------
CREATE TABLE `secuencias_facturacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL,
  `prefijo` varchar(10) NOT NULL,
  `siguiente_valor` int NOT NULL DEFAULT '1',
  `longitud` int DEFAULT '6',
  `anio` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipo_prefijo_anio` (`tipo`,`prefijo`,`anio`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: secuencias_facturacion
--
INSERT INTO `secuencias_facturacion` (`id`, `tipo`, `prefijo`, `siguiente_valor`, `longitud`, `anio`) VALUES ('1','factura','FAC','3','6','2026');
INSERT INTO `secuencias_facturacion` (`id`, `tipo`, `prefijo`, `siguiente_valor`, `longitud`, `anio`) VALUES ('2','pedido','PED','5','6','2026');



-- --------------------------------------------------------
-- Estructura de tabla: system_errors
-- --------------------------------------------------------
CREATE TABLE `system_errors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nivel` enum('info','warning','error','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'error',
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linea` int DEFAULT NULL,
  `trace` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `resuelto` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_nivel` (`nivel`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_resuelto` (`resuelto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Estructura de tabla: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'activo',
  `cedula` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `rol` varchar(20) DEFAULT 'usuario',
  `is_active` tinyint(1) DEFAULT '1',
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `idx_users_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: users
--
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('1','Usuario Administrador','default@carrito.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Oficina Principal','activo','00000000','0000000000',NULL,'admin','1','0',NULL,NULL,'2026-04-12 11:19:24');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('2','Juan Pérez','juan@email.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Av. Principal #123, Caracas','activo','12345678','04121234567',NULL,'usuario','1','0',NULL,NULL,'2026-04-12 11:19:24');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('3','María González','maria@email.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Calle Secundaria #45, Maracaibo','activo','87654321','04149876543',NULL,'usuario','1','0',NULL,NULL,'2026-04-12 11:19:24');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('4','Carlos Rodríguez','carlos@email.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Urb. Las Flores, Valencia','activo','11223344','04161122334',NULL,'usuario','1','0',NULL,NULL,'2026-04-12 11:19:24');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('5','Cliente de Prueba','cliente@test.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Calle Principal, Barquisimeto','activo','11111111','04141234567',NULL,'usuario','1','0',NULL,NULL,'2026-04-12 11:19:24');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('6','Jose Chacon','Picca.admin@gmail.com','$2y$10$2EJ66brv4.5balHF550.3OYbKTlH7rWfRaAxjPBZW5BfWVmThyC3W','Urb trigal Sur Calle Camoruco','activo','31129605','04121311228',NULL,'admin','1','1',NULL,'2026-04-13 08:13:34','2026-04-12 11:20:41');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('7','jose gregorio','jose14chacon2003@gmail.com','$2y$10$lRaVfeRLom.B.LThdJ4HeerDxqolBEai6dMVKwI1rEksv0z2ROUKO','Urb trigal Sur Calle Camoruco','activo','17314511','04144030184',NULL,'usuario','1','1',NULL,'2026-04-13 08:12:29','2026-04-13 08:12:20');

SET FOREIGN_KEY_CHECKS=1;
