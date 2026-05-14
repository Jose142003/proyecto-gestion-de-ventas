-- Backup: 2026-04-28 12:29:17
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
  `foto_perfil` varchar(255) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: admin_users
--
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `ultimo_login`, `fecha_registro`, `updated_at`) VALUES ('1','Administrador','picca.ventas@gmail.com','admin','240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9','superadmin','1',NULL,NULL,'2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `ultimo_login`, `fecha_registro`, `updated_at`) VALUES ('2','Vendedor 1','vendedor1@empresa.com','vendedor1','56976bf24998ca63e35fe4f1e2469b5751d1856003e8d16fef0aafef496ed044','vendedor','1',NULL,NULL,'2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `ultimo_login`, `fecha_registro`, `updated_at`) VALUES ('3','Admin 2','admin2@empresa.com','admin2','becf77f3ec82a43422b7712134d1860e3205c6ce778b08417a7389b43f2b4661','admin','1',NULL,NULL,'2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `ultimo_login`, `fecha_registro`, `updated_at`) VALUES ('4','Jose Chacon','jose14chacon2003@gmail.com','jose_chacon','8D969EEF6ECAD3C29A3A629280E686CF0C3F5D5A86AFF3CA12020C923ADC6C92','admin','1','/uploads/perfiles/admin_users_4_1777326217_92e7f757.jpg','2026-04-28 08:15:15','2026-04-27 17:35:39','2026-04-28 08:15:15');



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
  `edit_count` int NOT NULL DEFAULT '0',
  `edit_history` text COLLATE utf8mb4_unicode_ci,
  `last_edit_by` int DEFAULT NULL,
  `last_edit_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_fecha` (`fecha_creacion`),
  KEY `idx_registro` (`tabla_afectada`,`registro_id`),
  KEY `idx_last_edit_by` (`last_edit_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: auditoria_logs
--
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('1','4','Jose Chacon','admin','actualizar_perfil','perfil','Usuario actualizó su perfil','::1',NULL,NULL,NULL,NULL,NULL,'2026-04-27 17:56:19','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('2','6','Jose Chacon','usuario','cambiar_contraseña','seguridad','Usuario cambió su contraseña','::1',NULL,NULL,NULL,NULL,NULL,'2026-04-28 08:11:11','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('3','4','Jose Chacon','admin','marcar_pagada','facturas','Factura #FAC-2026-000004 marcada como pagada. Total: Bs. 87.00','::1',NULL,NULL,NULL,'facturas','4','2026-04-28 08:16:24','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('4','4','Jose Chacon','admin','enviar_email','facturas','Factura #4 enviada a jose14chacon200@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','4','2026-04-28 08:17:46','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('5','4','Jose Chacon','admin','enviar_email','facturas','Factura #4 enviada a jose14chacon200@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','4','2026-04-28 08:25:17','0',NULL,NULL,NULL);



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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: clientes
--
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('1','cedula','12345678','Juan Pérez','juan@email.com','04121234567','Av. Principal #123, Caracas',NULL,'activo','2026-04-27 17:34:50');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('2','cedula','87654321','María González','maria@email.com','04149876543','Calle Secundaria #45, Maracaibo',NULL,'activo','2026-04-27 17:34:50');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('3','cedula','11223344','Carlos Rodríguez','carlos@email.com','04161122334','Urb. Las Flores, Valencia',NULL,'activo','2026-04-27 17:34:50');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('4','cedula','11111111','Cliente de Prueba','cliente@test.com','04141234567','Calle Principal, Barquisimeto',NULL,'activo','2026-04-27 17:34:50');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('5','cedula','12544102','Jose Chacon','jose14chacon200@gmail.com','04221311228','Urb trigal Sur Calle Camoruco',NULL,'activo','2026-04-28 08:10:35');



-- --------------------------------------------------------
-- Estructura de tabla: compra_detalles
-- --------------------------------------------------------
CREATE TABLE `compra_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compra_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_compra_id` (`compra_id`),
  KEY `idx_producto_id` (`producto_id`),
  CONSTRAINT `compra_detalles_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compra_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: compra_detalles
--
INSERT INTO `compra_detalles` (`id`, `compra_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `created_at`) VALUES ('1','1','8','1','120.00','120.00','2026-04-27 17:46:27');



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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: compras
--
INSERT INTO `compras` (`id`, `numero_orden`, `proveedor_id`, `fecha_orden`, `fecha_requerida`, `fecha_recibido`, `subtotal`, `iva`, `descuento`, `total`, `estado`, `metodo_pago`, `condiciones_pago`, `usuario_creacion_id`, `usuario_aprobacion_id`, `fecha_aprobacion`, `observaciones`, `created_at`, `updated_at`) VALUES ('1','OC-202604-0001','3','2026-04-27',NULL,NULL,'120.00','19.20','0.00','139.20','aprobada','transferencia',NULL,'4',NULL,NULL,'','2026-04-27 17:46:27','2026-04-27 17:46:27');



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
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('1','empresa_nombre','PIC - Productos Industriales y Comerciales','text','empresa','Nombre de la empresa','1','1','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('2','empresa_rif','J-12345678-9','text','empresa','RIF de la empresa','1','2','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('3','empresa_direccion','Av. Principal, Zona Industrial, Caracas','text','empresa','Dirección de la empresa','1','3','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('4','empresa_telefono','0212-5551234','text','empresa','Teléfono de contacto','1','4','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('5','empresa_email','info@pic.com.ve','email','empresa','Email de contacto','1','5','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('6','iva_porcentaje','16','number','facturacion','Porcentaje de IVA aplicado','1','10','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('7','moneda_principal','Bs','text','facturacion','Moneda principal del sistema','1','11','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('8','factura_prefijo','FAC','text','facturacion','Prefijo para números de factura','1','12','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('9','factura_longitud','6','number','facturacion','Longitud del correlativo','1','13','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('10','notificaciones_email','1','boolean','notificaciones','Enviar notificaciones por email','1','20','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('11','notificaciones_whatsapp','0','boolean','notificaciones','Enviar notificaciones por WhatsApp','1','21','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('12','stock_minimo_alerta','5','number','inventario','Stock mínimo para alertas','1','30','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('13','modo_mantenimiento','0','boolean','sistema','Modo mantenimiento del sistema','1','40','2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('14','version_sistema','2.0.0','text','sistema','Versión actual del sistema','0','41','2026-04-27 17:34:51','2026-04-27 17:34:51');



-- --------------------------------------------------------
-- Estructura de tabla: factura_detalles
-- --------------------------------------------------------
CREATE TABLE `factura_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `factura_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `factura_detalles_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factura_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: factura_detalles
--
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('1','1','57','1','55.00','55.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('2','2','2','1','55.00','55.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('3','3','3','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('4','4','2','1','75.00','75.00');



-- --------------------------------------------------------
-- Estructura de tabla: facturas
-- --------------------------------------------------------
CREATE TABLE `facturas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int DEFAULT NULL,
  `cliente_id` int NOT NULL,
  `numero_factura` varchar(20) NOT NULL,
  `fecha_emision` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_vencimiento` date DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT '0.00',
  `iva` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `estado` enum('pendiente','pagada','anulada') DEFAULT 'pendiente',
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`),
  UNIQUE KEY `pedido_id` (`pedido_id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facturas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: facturas
--
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('1','1','5','FAC-2026-000001','2026-04-28 08:11:48',NULL,'55.00','8.80','63.80','efectivo','pagada','6','2026-04-28 08:11:48');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('2','2','5','FAC-2026-000002','2026-04-28 08:12:26',NULL,'47.41','7.59','55.00','pago_movil','pendiente','6','2026-04-28 08:12:26');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('3','3','5','FAC-2026-000003','2026-04-28 08:12:59',NULL,'155.17','24.83','180.00','transferencia','pendiente','6','2026-04-28 08:12:59');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('4','4','5','FAC-2026-000004','2026-04-28 08:14:54',NULL,'75.00','12.00','87.00','mixto','pagada','6','2026-04-28 08:14:54');



-- --------------------------------------------------------
-- Estructura de tabla: historial_stock
-- --------------------------------------------------------
CREATE TABLE `historial_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `cantidad` int NOT NULL,
  `stock_anterior` int NOT NULL,
  `stock_nuevo` int NOT NULL,
  `tipo` enum('venta','compra','ajuste','devolucion') COLLATE utf8mb4_unicode_ci DEFAULT 'venta',
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_fecha` (`fecha`),
  CONSTRAINT `historial_stock_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Estructura de tabla: movimientos_inventario
-- --------------------------------------------------------
CREATE TABLE `movimientos_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste','devolucion') NOT NULL,
  `cantidad` int NOT NULL DEFAULT '0',
  `descripcion` text,
  `referencia` varchar(100) DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `fecha_movimiento` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: movimientos_inventario
--
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `referencia`, `usuario_id`, `fecha_movimiento`) VALUES ('1','8','entrada','1','Compra de productos - Orden: OC-202604-0001','OC-202604-0001','4','2026-04-27 17:46:27');



-- --------------------------------------------------------
-- Estructura de tabla: pedido_detalles
-- --------------------------------------------------------
CREATE TABLE `pedido_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `precio_original` decimal(10,2) DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `producto_nombre` varchar(255) DEFAULT NULL,
  `producto_sku` varchar(100) DEFAULT NULL,
  `producto_categoria` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `pedido_detalles_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedido_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: pedido_detalles
--
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('1','1','57','1','55.00','0.00','55.00','Caja para pulsadores 2 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('2','2','2','1','55.00','55.00','55.00','Cable para sensor M8',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('3','3','3','1','180.00','180.00','180.00','Botonera colgante',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('4','4','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);



-- --------------------------------------------------------
-- Estructura de tabla: pedidos
-- --------------------------------------------------------
CREATE TABLE `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `numero_pedido` varchar(20) NOT NULL,
  `fecha_pedido` datetime DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `impuesto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `iva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('pendiente','procesando','enviado','entregado','cancelado','facturado') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `comprobante_pago` varchar(255) DEFAULT NULL,
  `notas_cliente` text,
  `notas_internas` text,
  `observaciones` text,
  `fecha_facturacion` datetime DEFAULT NULL,
  `direccion_envio` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pedido` (`numero_pedido`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: pedidos
--
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('1','5','6','PED-2026-000001','2026-04-28 08:11:47','55.00','0.00','8.80','63.80','pendiente','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo',NULL,NULL,'2026-04-28 08:11:47','2026-04-28 08:11:47');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('2',NULL,'6','PED-20260428-3908','2026-04-28 08:12:26','47.41','0.00','7.59','55.00','pendiente','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890',NULL,NULL,'2026-04-28 08:12:26','2026-04-28 08:12:26');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('3',NULL,'6','PED-20260428-4101','2026-04-28 08:12:59','155.17','0.00','24.83','180.00','pendiente','transferencia','12345678',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 12345678',NULL,NULL,'2026-04-28 08:12:59','2026-04-28 08:12:59');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('4','5','6','PED-2026-000002','2026-04-28 08:14:54','75.00','0.00','12.00','87.00','facturado','mixto',NULL,NULL,NULL,NULL,'Pedido por mixto','2026-04-28 08:16:24',NULL,'2026-04-28 08:14:54','2026-04-28 08:16:24');



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
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('1','PROD-0001','Sensor inductivo prt12-4dp','150.00','https://http2.mlstatic.com/D_Q_NP_2X_907785-MLV42256115993_062020-E.webp','Sensores Autonics Inductivos...','Sensores','4.5','0','','45','1','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('2','PROD-0002','Boton pulsador Autonics Nc S3pf-p1rb','75.00','https://http2.mlstatic.com/D_NQ_NP_2X_927922-MLV52483035472_112022-F.webp','Boton pulsador Autonics modelo S3pf-p1rb...','Botoneras','4.0','0','','64','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-28 08:14:54');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('3','PROD-0003','Rele termico regulable 48-65a Ldr365','320.00','https://http2.mlstatic.com/D_Q_NP_2X_971966-MLV42316060787_062020-E.webp','Rele termic regulable 48-65a...','Relés','5.0','0','','25','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('4','PROD-0004','Guardamotor','280.00','https://http2.mlstatic.com/D_Q_NP_2X_987302-MLV42319903598_062020-E.webp',' Marca Schneider Electric. modelo Gv2me08','Protecciones','4.2','0','','40','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('5','PROD-0005','Termometro infrarrojo','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_780836-MLV48799544246_012022-F.webp','Termómetro Infrarrojo -32°c A 1050°c marca unit-t modelo ut302d.','Instrumentos de Medición','4.8','0','','18','1','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('6','PROD-0006','Botonera colgante','180.00','https://http2.mlstatic.com/D_Q_NP_2X_605998-MLV91579814235_092025-E.webp','Botonera colgante de 6 pulsadores Marca schneider electric modelo xaca671 material propipolineno.','Botoneras','4.1','0','','35','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('7','PROD-0007','Sensor fotoelectrico mfr','220.00','https://http2.mlstatic.com/D_Q_NP_2X_781132-MLV90889351684_082025-E.webp','Sensor fotorelectrico Autonics bx5m.','Sensores','4.6','0','','42','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('8','PROD-0008','Pinza amperimetrica digital','120.00','https://http2.mlstatic.com/D_NQ_NP_2X_919873-MLV50246492941_062022-F.webp','Marca uni-t Modelo Ut201+','Instrumentos de Medición','4.9','0','','29','1','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:46:27');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('9','PROD-0009','Rele de nivel para conductores','185.00','https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp','marca exceline modelo grn-mv.','Relés','4.3','0','','32','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('10','PROD-0010','Manometro festo','95.00','https://http2.mlstatic.com/D_Q_NP_2X_782534-MLV80960384399_112024-E.webp','Marca festo modelo ma-50-10-1/4-enef162838.','Instrumentos de Medición','4.4','0','','55','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('11','PROD-0011','Mini termo anemometro y medidor de humedad','650.00','https://http2.mlstatic.com/D_Q_NP_2X_954754-MLV76879763367_062024-E.webp','Marca Extech Modelo 45158.','Instrumentos de Medición','4.0','0','','12','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('12','PROD-0012','Sensor capacitivo Autonics','210.00','https://http2.mlstatic.com/D_Q_NP_2X_764408-MLV42258601667_062020-E.webp','Marca Autonics Modelo Cr18-8ac.','Sensores','4.7','0','','38','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('13','PROD-0013','Selector 2 posiciones','45.00','https://http2.mlstatic.com/D_Q_NP_2X_946997-MLV46271812962_062021-E.webp','Marca Scneider Electric Modelo XB4BD21.','Controles','4.5','0','','70','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('14','PROD-0014','Etiquetadora panduit','2800.00','https://http2.mlstatic.com/D_Q_NP_2X_845848-MLV75886383737_042024-E.webp','Marca Extech Modelo PanTher LS8E.','Herramientas','4.8','0','','5','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('15','PROD-0015','Rele de nivel para lquidos conductores','185.00','https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp','Marca Exceline Modelo Grn-mv Voltaje 110-220.','Relés','4.2','0','','32','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('16','PROD-0016','Rele de estado solido Autonics','125.00','https://http2.mlstatic.com/D_NQ_NP_2X_823517-MLV49140687804_022022-F.webp','Marca Autonics Modelo SR1-4415.','Relés','4.5','0','','48','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('17','PROD-0017','Final de carrera','195.00','https://http2.mlstatic.com/D_Q_NP_2X_853654-MLV42315651853_062020-E.webp','Marca Telemecanique/schneider Modelo XCKJO513.','Sensores','4.6','0','','28','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('18','PROD-0018','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_854049-MLV42347247961_062020-E.webp','Marca scheneider/telemecanique XCKP2121G11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('19','PROD-0019','Contador temporizador','280.00','https://http2.mlstatic.com/D_Q_NP_2X_868764-MLV82980146035_032025-E.webp','Marca Autonics Modelo CT6Y-1P2.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('20','PROD-0020','Pinza amperimetrica','135.00','https://http2.mlstatic.com/D_NQ_NP_2X_685608-MLV43035297361_082020-F.webp','Marca uni-t Modelo UT202a+.','Instrumentos de Medición','4.7','0','','35','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('21','PROD-0021','Contador temporizador','280.00','https://http2.mlstatic.com/D_Q_NP_2X_943529-MLV82980293127_032025-E.webp','Marca Autonics Modelo CT6Y-1P4.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('22','PROD-0022','Pinza amperimetrica extech','420.00','https://http2.mlstatic.com/D_Q_NP_2X_891753-MLV48858956084_012022-E.webp','Marca extech Modelo UT210d.','Instrumentos de Medición','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('23','PROD-0023','Sensor TIq5mc1 Jootiden','60.00','https://http2.mlstatic.com/D_Q_NP_2X_983049-MLV78136901025_082024-E.webp','Marca generica Modelo TL-Q5MC1.','Sensores','4.7','0','','85','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('24','PROD-0024','Pinza amperimetrica + termometro','220.00','https://http2.mlstatic.com/D_NQ_NP_2X_826593-MLV46165148147_052021-F.webp','Marca extech modelo EX470.','Instrumentos de Medición','4.8','0','','25','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('25','PROD-0025','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_904301-MLV42315881121_062020-E.webp','Marca schneider/telemecanique Modelo XCKP2118G11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('26','PROD-0026','Contactor 25amp 24vdc','380.00','https://images.wiautomation.com/public/images/landing/anticipa/product/LC1DT206SLS207.jpg','Marca scheider electric Modelo LCD1E25BD.','Contactores','4.7','0','','18','1','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('27','PROD-0027','Contactor 80amp 220v','680.00','https://http2.mlstatic.com/D_NQ_NP_2X_774386-MLV42329989223_062020-F.webp','Marca scheneider electric Modelo LC1d80m.','Contactores','4.7','0','','10','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('28','PROD-0028','osiloscopio extech','850.00','https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-F.webp','Marca scheneider electric Modelo LC1D09BD.','Instrumentos de Medición','4.7','0','','8','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('29','PROD-0029','Pinza amperimetrica digital','120.00','https://http2.mlstatic.com/D_NQ_NP_2X_928642-MLV54457071668_032023-F.webp','Marca uni-t Modelo UT201+.','Instrumentos de Medición','4.7','0','','35','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('30','PROD-0030','Fuente de poder 5amp 12vdc Aunonics','185.00','https://http2.mlstatic.com/D_NQ_NP_2X_606115-MLV82504917240_022025-F.webp','Marca scheneider electric Modelo SPB-O6O-12.','Fuentes de Poder','4.7','0','','30','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('31','PROD-0031','kit maletin legrand starfix','320.00','https://http2.mlstatic.com/D_Q_NP_2X_983177-MLV71749528286_092023-E.webp','Marca lengard Modelo 376 59/60.','Herramientas','4.7','0','','20','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('32','PROD-0032','Controlador de temperatura ','290.00','https://http2.mlstatic.com/D_NQ_NP_2X_966407-MLV54265777533_032023-F.webp','Marca Autonics Modelo tk4s-bn4r.','Controladores','4.7','0','','16','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('33','PROD-0033','Descanso ajustable para pie','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_978007-MLV71801855063_092023-F.webp','Marca lengard Modelo 376 59/60.','Accesorios','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('34','PROD-0034','Controlador de temperaura 48x69','210.00','https://http2.mlstatic.com/D_NQ_NP_2X_821401-MLV73213656021_122023-F.webp','Marca 3M Modelo FR53OCB.','Controladores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('35','PROD-0035','Temporizador Autonics','140.00','https://http2.mlstatic.com/D_NQ_NP_2X_956520-MLV52366651303_112022-F.webp','Marca Autonics Modelo Le8n-bfle8n-bn.','Temporizadores','4.7','0','','38','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('36','PROD-0036','Controlador temporizador','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_674477-MLV51061339386_082022-F.webp','Marca Autonics Modelo Ct6-1p2.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('37','PROD-0037','Multimetro uni-t','150.00','https://http2.mlstatic.com/D_NQ_NP_2X_841899-MLV46427086846_062021-F.webp','Marca uni-t Modelo Ut89x.','Instrumentos de Medición','4.7','0','','30','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('38','PROD-0038','Sensor amplificador para fibra optica','200.00','https://http2.mlstatic.com/D_NQ_NP_2X_964670-MLV42255017336_062020-F.webp','Marca Autonics Modelo Bf4rp.','Sensores','4.7','0','','25','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('39','PROD-0039','Rele estado solido trifasico 30amp','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_754022-MLV82085220364_022025-F.webp','Marca Autonics Modelo Sr3-4430.','Relés','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('40','PROD-0040','Sensor fotoelectrico','210.00','https://http2.mlstatic.com/D_NQ_NP_2X_656456-MLV42255069136_062020-F.webp','Marca Autonics Modelo Brqm400-ddta.','Sensores','4.7','0','','28','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('41','PROD-0041','Contactor 40amp 110v','420.00','https://http2.mlstatic.com/D_NQ_NP_2X_697183-MLV81969170376_022025-F.webp','Marca Schneider Electric Modelo LCD1D4OAF7.','Contactores','4.7','0','','15','1','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('42','PROD-0042','Fuente de poder 8amp 12vdc','240.00','https://http2.mlstatic.com/D_NQ_NP_2X_731782-MLV78217056967_082024-F.webp','Marca Autonics Modelo SPB-120-12.','Fuentes de Poder','4.7','0','','20','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('43','PROD-0043','Rele termic regulable 30-40a','340.00','https://http2.mlstatic.com/D_NQ_NP_2X_719268-MLV42301142622_062020-F.webp','Marca scheneider electric Modelo LRD3355.','Relés','4.7','0','','16','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('44','PROD-0044','Guardamotor 1-1.6a','290.00','https://http2.mlstatic.com/D_NQ_NP_2X_842891-MLV42319762831_062020-F.webp','Marca Schneider Electric Modelo GV2ME06 .','Protecciones','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('45','PROD-0045','Idicadores de frecuencia uni-t','300.00','https://http2.mlstatic.com/D_NQ_NP_2X_892854-MLV48915410648_012022-F.webp','Marca uni-t Modelo Ut261a.','Instrumentos de Medición','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('46','PROD-0046','Protector televisor','85.00','https://http2.mlstatic.com/D_NQ_NP_2X_728315-MLV46442590142_062021-F.webp','Marca Exceline Modelo Gsm-tv120.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('47','PROD-0047','Rele estado solido','125.00','https://http2.mlstatic.com/D_NQ_NP_2X_692640-MLV49139833433_022022-F.webp','Marca Autonics Modelo Sr1-1450.','Relés','4.7','0','','48','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('48','PROD-0048','Fuente de poder 20amp 12vdc','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_987249-MLV73944702421_012024-F.webp','Marca Autonics Modelo Sp240-12.','Fuentes de Poder','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('49','PROD-0049','Cable para sensor M8','55.00','https://http2.mlstatic.com/D_Q_NP_2X_855871-MLV70628379777_072023-E.webp','Marca Telemecanique Modelo Xzcp0941l2.','Accesorios','4.7','0','','90','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('50','PROD-0050','Contactor 25amp 220v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_961256-MLV42321081111_062020-F.webp','Marca Scheneider electric Modelo Lc1d25m7.','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('51','PROD-0051','Variador de velociadad 5hp 440v','4200.00','https://http2.mlstatic.com/D_NQ_NP_2X_693722-MLA76246464467_052024-F.webp','Marca scheneider electric Modelo Atv320u40n4c .','Variadores','4.7','0','','4','1','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('52','PROD-0052','Sensor fotoelectrico reflectivo','195.00','https://http2.mlstatic.com/D_NQ_NP_2X_896757-MLV78646725763_082024-F.webp','Marca Autonics Modelo Brqm3m-pdta-c-p .','Sensores','4.7','0','','28','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('53','PROD-0053','Contactor 32amp 220v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_775520-MLV42321197517_062020-F.webp','Marca scheneider electric Modelo Lc1d32m7 .','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('54','PROD-0054','Rele termico regulable','390.00','https://http2.mlstatic.com/D_Q_NP_2X_720989-MLV42320094116_062020-E.webp','Marca scheneider electric Modelo Lrd3357.','Relés','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('55','PROD-0055','Caja para pulsadores plastica 3 huecos','70.00','https://http2.mlstatic.com/D_Q_NP_2X_719143-MLV42346979496_062020-E.webp','Marca scheneider Modelo Xald03.','Accesorios','4.7','0','','65','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('56','PROD-0056','Temporizacion Rele estrella triangulo','240.00','https://http2.mlstatic.com/D_Q_NP_2X_966925-MLV83523597338_042025-E.webp','Marca scheneider Modelo Re22r2qtmr.','Relés','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('57','PROD-0057','Caja para pulsadores 2 huecos','55.00','https://http2.mlstatic.com/D_Q_NP_2X_783226-MLV42347077873_062020-E.webp','Marca scheneider Modelo Xald02.','Accesorios','4.7','0','','74','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-28 08:11:48');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('58','PROD-0058','Contactor 18amp 24vdc','330.00','https://http2.mlstatic.com/D_NQ_NP_2X_669780-MLV42320450829_062020-E.webp','Marca scheneider electric Modelo Lc1d18bd.','Contactores','4.7','0','','20','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('59','PROD-0059','Contactor 38amp 220v','420.00','https://http2.mlstatic.com/D_NQ_NP_2X_761868-MLV42321342194_062020-F.webp','Marca scheneider electric Modelo Lc1d38m7.','Contactores','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('60','PROD-0060','Guardamotor 48-65a','580.00','https://http2.mlstatic.com/D_Q_NP_2X_879405-MLV42346668428_062020-E.webp','Marca scheneider electric Modelo Gv3p65.','Protecciones','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('61','PROD-0061','Contactor 256a 220v','3800.00','https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp','Marca scheneider Modelo Lc1f265m7.','Contactores','4.7','0','','3','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('62','PROD-0062','Rele termico regulable','390.00','https://http2.mlstatic.com/D_Q_NP_2X_719268-MLV42301142622_062020-E.webp','Marca scheneider electric Modelo Ldr3355.','Relés','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('63','PROD-0063','Sensor de marca fotocelula','850.00','https://http2.mlstatic.com/D_NQ_NP_2X_965275-MLV42316005076_062020-F.webp','Marca Telemecanique.Modelo Xurk1ksmm12 ','Sensores','4.7','0','','8','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('64','PROD-0064','Variador de velocidad 7.5hp','5800.00','https://http2.mlstatic.com/D_NQ_NP_2X_617200-MLV46302539269_062021-F.webp','Marca scheneider electric Modelo Atv320u55m3c.','Variadores','4.7','0','','3','1','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('65','PROD-0065','Sensor inductivo','95.00','https://http2.mlstatic.com/D_Q_NP_2X_835762-MLV50041103539_052022-E.webp','Marca Autonics Modelo prd18 8dp.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('66','PROD-0066','Contactor 265a 220v','3800.00','https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp','Marca scheneider electric Modelo Lc1f265m7.','Contactores','4.7','0','','3','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('67','PROD-0067','Lockout 63amp seleccionador bloqueador','550.00','https://http2.mlstatic.com/D_Q_NP_2X_651999-MLV42345796245_062020-E.webp','Marca scheneider Modelo Vcf5ge.','Accesorios','4.7','0','','10','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('68','PROD-0068','Lockout 100amp seleccionador bloqueador','680.00','https://http2.mlstatic.com/D_Q_NP_2X_833469-MLV42331350351_062020-E.webp','Marca scheneider electric Modelo Vcf5gen.','Accesorios','4.7','0','','8','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('69','PROD-0069','Sensor inductivo de rotacion','420.00','https://http2.mlstatic.com/D_Q_NP_2X_998055-MLV53428583531_012023-E.webp','Marca Telemecanique Modelo Xsav12373.','Sensores','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('70','PROD-0070','Sensor','140.00','https://http2.mlstatic.com/D_Q_NP_2X_912959-MLV42259148070_062020-E.webp','Marca Autonics Modelo Prcm30-5dp.','Sensores','4.7','0','','38','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('71','PROD-0071','Protctor para aires y refrigeradores','95.00','https://http2.mlstatic.com/D_Q_NP_2X_995442-MLV42253383680_062020-E.webp','Marca Exceline Modelo Gsm-rt120.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('72','PROD-0072','Flotador electrico multivoltaje','70.00','https://http2.mlstatic.com/D_Q_NP_2X_837451-MLV42253919704_062020-E.webp','Marca Exceline Modelo Gfe-mv3m.','Accesorios','4.7','0','','65','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('73','PROD-0073','Contactor 9amp 24vdc','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-E.webp','Marca scheneider electric Modelo Lc190bd.','Contactores','4.7','0','','22','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('74','PROD-0074','Contactor 32amp 110v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_849936-MLV42321134886_062020-E.webp','Marca scheneider electric Modelo Lc1d32f7.','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('75','PROD-0075','Contactor 40amp 24vdc','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_966221-MLV46232503172_062021-E.webp','Marca sccheneider electric Lc1d40ab7.','Contactores','4.7','0','','15','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('76','PROD-0076','Contactor 65amp 220v','650.00','https://http2.mlstatic.com/D_NQ_NP_2X_616564-MLV42329913652_062020-E.webp','Marca scheneider electric Modelo Lc1d65am7.','Contactores','4.7','0','','12','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('77','PROD-0077','Contactor 185amp 220v','2500.00','https://http2.mlstatic.com/D_NQ_NP_2X_838661-MLV50182142214_062022-E.webp','Marca scheneider Modelo Lc1f185m7.','Contactores','4.7','0','','5','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('78','PROD-0078','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_921213-MLV42302675953_062020-E.webp','Marca scheneider/Telemecanique Modelo Xckp2127g11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('79','PROD-0079','Mini termometro infrrarojo','125.00','https://http2.mlstatic.com/D_Q_NP_2X_887138-MLV50723282858_072022-E.webp','Marca uni-t Modelo Ut300a+.','Instrumentos de Medición','4.7','0','','48','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `created_at`, `updated_at`) VALUES ('80','PROD-0080','Protector para motores monofasicos','85.00','https://http2.mlstatic.com/D_Q_NP_2X_721232-MLV42314862754_062020-E.webp','Marca exceline Modelo Gsm-r220b.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','2026-04-27 17:34:50','2026-04-27 17:34:50');



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
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('1','PROV-001','Autonics Venezuela','Autonics C.A.','J-12345678-9','ruc','Av. Principal, Zona Industrial','Caracas','0212-5551234',NULL,'ventas@autonics.com.ve',NULL,'Carlos Méndez',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('2','PROV-002','Schneider Electric','Schneider Electric Venezuela','J-87654321-0','ruc','Calle 5, Parque Industrial','Valencia','0241-5555678',NULL,'ventas@schneider.com.ve',NULL,'Ana Rodríguez',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-27 17:34:51','2026-04-27 17:34:51');
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('3','PROV-003','UNI-T Venezuela','UNI-T Instruments C.A.','J-11223344-5','ruc','Av. Libertador, Centro Comercial','Maracaibo','0261-5559012',NULL,'importaciones@unit.com.ve',NULL,'Luis Fernández',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-27 17:34:51','2026-04-27 17:34:51');



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
  UNIQUE KEY `tipo` (`tipo`,`prefijo`,`anio`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: secuencias_facturacion
--
INSERT INTO `secuencias_facturacion` (`id`, `tipo`, `prefijo`, `siguiente_valor`, `longitud`, `anio`) VALUES ('1','pedido','PED-','1','6','2026');
INSERT INTO `secuencias_facturacion` (`id`, `tipo`, `prefijo`, `siguiente_valor`, `longitud`, `anio`) VALUES ('2','factura','FAC-','1','6','2026');



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
  KEY `idx_users_estado` (`estado`),
  KEY `idx_users_correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: users
--
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('1','Usuario Administrador','default@carrito.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Oficina Principal','activo','00000000','0000000000',NULL,'admin','1','0',NULL,NULL,'2026-04-27 17:34:50');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('2','Juan Pérez','juan@email.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Av. Principal #123, Caracas','activo','12345678','04121234567',NULL,'usuario','1','0',NULL,NULL,'2026-04-27 17:34:50');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('3','María González','maria@email.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Calle Secundaria #45, Maracaibo','activo','87654321','04149876543',NULL,'usuario','1','0',NULL,NULL,'2026-04-27 17:34:50');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('4','Carlos Rodríguez','carlos@email.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Urb. Las Flores, Valencia','activo','11223344','04161122334',NULL,'usuario','1','0',NULL,NULL,'2026-04-27 17:34:50');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('5','Cliente de Prueba','cliente@test.com','$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K','Calle Principal, Barquisimeto','activo','11111111','04141234567',NULL,'usuario','1','0',NULL,NULL,'2026-04-27 17:34:50');
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`) VALUES ('6','Jose Chacon','jose14chacon200@gmail.com','$2y$12$zxwgRB6W0ezuT8V.UibKiu2YYfA2YQhpIw3jJxIldRQzwkOcaAWv2','Urb trigal Sur Calle Camoruco','activo','12544102','04221311228',NULL,'usuario','1','1',NULL,'2026-04-28 08:11:27','2026-04-28 08:10:35');

SET FOREIGN_KEY_CHECKS=1;
