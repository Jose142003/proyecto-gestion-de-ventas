-- Backup: 2026-06-07 11:31:11
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
  `telefono` varchar(20) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin','vendedor') DEFAULT 'admin',
  `activo` tinyint(1) DEFAULT '1',
  `foto_perfil` varchar(255) DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `2fa_enabled` tinyint(1) DEFAULT '0',
  `2fa_secret` varchar(255) DEFAULT NULL,
  `2fa_backup_codes` text,
  `2fa_verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: admin_users
--
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `telefono`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `verification_token`, `ultimo_login`, `fecha_registro`, `updated_at`, `2fa_enabled`, `2fa_secret`, `2fa_backup_codes`, `2fa_verified_at`) VALUES ('1','Administrador','picca.ventas@gmail.com',NULL,'admin','240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9','superadmin','1',NULL,NULL,NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02','0',NULL,NULL,NULL);
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `telefono`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `verification_token`, `ultimo_login`, `fecha_registro`, `updated_at`, `2fa_enabled`, `2fa_secret`, `2fa_backup_codes`, `2fa_verified_at`) VALUES ('2','Vendedor 1','vendedor1@empresa.com',NULL,'vendedor1','56976bf24998ca63e35fe4f1e2469b5751d1856003e8d16fef0aafef496ed044','vendedor','1',NULL,NULL,NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02','0',NULL,NULL,NULL);
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `telefono`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `verification_token`, `ultimo_login`, `fecha_registro`, `updated_at`, `2fa_enabled`, `2fa_secret`, `2fa_backup_codes`, `2fa_verified_at`) VALUES ('3','Admin 2','admin2@empresa.com',NULL,'admin2','becf77f3ec82a43422b7712134d1860e3205c6ce778b08417a7389b43f2b4661','admin','1',NULL,NULL,NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02','0',NULL,NULL,NULL);
INSERT INTO `admin_users` (`id`, `nombre`, `correo`, `telefono`, `usuario`, `contrasena`, `rol`, `activo`, `foto_perfil`, `verification_token`, `ultimo_login`, `fecha_registro`, `updated_at`, `2fa_enabled`, `2fa_secret`, `2fa_backup_codes`, `2fa_verified_at`) VALUES ('4','Jose Chacon','jose142003chacon@gmail.com','04121311228','jose_chacon','$2y$12$b2zs.ICk.SrvNU4RCkoYye3sME5uGf2A556wxfnA.gxpPP69w5pQW','admin','1','/uploads/perfiles/admin_users_4_1778597424_291a52b1.jpg',NULL,'2026-06-06 08:40:38','2026-04-29 14:01:25','2026-06-06 09:14:25','1','EGQG74HEVD3IAY3DXPJFMFYIYUV5FPQD','[\"9904c915-2949\",\"c8da750d-7c7c\",\"918ddaa5-16fb\",\"4167f571-73e8\",\"b64a1442-4981\",\"cfbe89cc-9ec7\",\"96bb951c-d615\",\"278684fe-b919\"]','2026-06-06 09:14:25');



-- --------------------------------------------------------
-- Estructura de tabla: alertas_mantenimiento
-- --------------------------------------------------------
CREATE TABLE `alertas_mantenimiento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `producto_nombre` varchar(200) NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `pedido_id` int DEFAULT NULL,
  `fecha_compra` date NOT NULL,
  `intervalo_dias` int NOT NULL COMMENT 'Días recomendados entre mantenimientos',
  `proximo_mantenimiento` date NOT NULL,
  `tipo` enum('preventivo','predictivo','correctivo') DEFAULT 'preventivo',
  `estado` enum('pendiente','notificado','completado','cancelado') DEFAULT 'pendiente',
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `idx_proximo` (`proximo_mantenimiento`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `alertas_mantenimiento_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alertas_mantenimiento_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: alertas_mantenimiento
--
INSERT INTO `alertas_mantenimiento` (`id`, `producto_id`, `producto_nombre`, `usuario_id`, `pedido_id`, `fecha_compra`, `intervalo_dias`, `proximo_mantenimiento`, `tipo`, `estado`, `notas`, `created_at`) VALUES ('2','10','Manometro festo','4',NULL,'2026-05-28','90','2026-08-26','preventivo','pendiente',NULL,'2026-05-28 10:35:34');
INSERT INTO `alertas_mantenimiento` (`id`, `producto_id`, `producto_nombre`, `usuario_id`, `pedido_id`, `fecha_compra`, `intervalo_dias`, `proximo_mantenimiento`, `tipo`, `estado`, `notas`, `created_at`) VALUES ('3','10','Manometro festo','4',NULL,'2026-06-04','30','2026-07-04','preventivo','pendiente',NULL,'2026-06-04 10:38:07');



-- --------------------------------------------------------
-- Estructura de tabla: alertas_stock
-- --------------------------------------------------------
CREATE TABLE `alertas_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'bajo',
  `nivel_actual` int DEFAULT '0',
  `nivel_sugerido` int DEFAULT '0',
  `dias_para_agotar` int DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci,
  `leida` tinyint(1) DEFAULT '0',
  `resuelta` tinyint(1) DEFAULT '0',
  `fecha_alerta` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_leida` (`leida`),
  CONSTRAINT `alertas_stock_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: alertas_stock
--
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('1','61','critico','3','15',NULL,'El producto Contactor 256a 220v tiene stock CRITICO (3 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:49');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('2','64','critico','3','15',NULL,'El producto Variador de velocidad 7.5hp tiene stock CRITICO (3 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:50');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('3','66','critico','3','15',NULL,'El producto Contactor 265a 220v tiene stock CRITICO (3 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:51');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('4','51','critico','4','15',NULL,'El producto Variador de velociadad 5hp 440v tiene stock CRITICO (4 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:52');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('5','14','critico','5','15',NULL,'El producto Etiquetadora panduit tiene stock CRITICO (5 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:52');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('6','77','critico','5','15',NULL,'El producto Contactor 185amp 220v tiene stock CRITICO (5 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:53');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('7','28','bajo','8','16',NULL,'El producto osiloscopio extech tiene stock BAJO (8 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:54');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('8','63','bajo','8','16',NULL,'El producto Sensor de marca fotocelula tiene stock BAJO (8 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:55');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('9','68','bajo','8','16',NULL,'El producto Lockout 100amp seleccionador bloqueador tiene stock BAJO (8 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:41');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('10','27','bajo','10','20',NULL,'El producto Contactor 80amp 220v tiene stock BAJO (10 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:39');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('11','67','bajo','10','20',NULL,'El producto Lockout 63amp seleccionador bloqueador tiene stock BAJO (10 unidades). Se recomienda reabastecer.','0','1','2026-05-21 09:39:59','2026-05-28 09:28:26');
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('12','14','critico','5','10',NULL,'Stock critico: \'Etiquetadora panduit\' tiene 5 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('13','27','bajo','10','10',NULL,'Stock bajo: \'Contactor 80amp 220v\' tiene 10 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('14','28','bajo','8','10',NULL,'Stock bajo: \'osiloscopio extech\' tiene 8 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('15','51','critico','4','10',NULL,'Stock critico: \'Variador de velociadad 5hp 440v\' tiene 4 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('16','61','critico','3','10',NULL,'Stock critico: \'Contactor 256a 220v\' tiene 3 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('17','63','bajo','8','10',NULL,'Stock bajo: \'Sensor de marca fotocelula\' tiene 8 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('18','64','critico','3','10',NULL,'Stock critico: \'Variador de velocidad 7.5hp\' tiene 3 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('19','66','critico','3','10',NULL,'Stock critico: \'Contactor 265a 220v\' tiene 3 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('20','67','bajo','10','10',NULL,'Stock bajo: \'Lockout 63amp seleccionador bloqueador\' tiene 10 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('21','68','bajo','8','10',NULL,'Stock bajo: \'Lockout 100amp seleccionador bloqueador\' tiene 8 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);
INSERT INTO `alertas_stock` (`id`, `producto_id`, `tipo`, `nivel_actual`, `nivel_sugerido`, `dias_para_agotar`, `mensaje`, `leida`, `resuelta`, `fecha_alerta`, `fecha_resolucion`) VALUES ('22','77','critico','5','10',NULL,'Stock critico: \'Contactor 185amp 220v\' tiene 5 unidades (sugerido: 10)','0','0','2026-05-28 09:29:03',NULL);



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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: auditoria_logs
--
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('1','4','Jose Chacon','admin','mostrar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Visible','::1',NULL,NULL,NULL,'products','1','2026-04-30 08:19:29','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('2','4','Jose Chacon','admin','ocultar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Ocultado','::1',NULL,NULL,NULL,'products','1','2026-04-30 08:23:38','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('3','4','Jose Chacon','admin','mostrar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Visible','::1',NULL,NULL,NULL,'products','1','2026-04-30 08:55:54','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('4','4','Jose Chacon','admin','ocultar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Ocultado','::1',NULL,NULL,NULL,'products','1','2026-04-30 09:41:10','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('5','4','Jose Chacon','admin','mostrar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Visible','::1',NULL,NULL,NULL,'products','1','2026-04-30 09:44:08','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('6','4','Jose Chacon','admin','ocultar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Ocultado','::1',NULL,NULL,NULL,'products','1','2026-04-30 09:44:16','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('7','4','Jose Chacon','admin','mostrar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Visible','::1',NULL,NULL,NULL,'products','1','2026-04-30 09:49:40','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('8','4','Jose Chacon','admin','ocultar','productos','Producto #1 (Sensor inductivo prt12-4dp) - Ocultado','::1',NULL,NULL,NULL,'products','1','2026-04-30 09:49:49','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('9','4','Jose Chacon','admin','MOSTRAR_PRODUCTO','productos','Producto \"Sensor inductivo prt12-4dp\" (ID: 1) ha sido MOSTRADO en la tienda','::1','Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','{\"active\": 0}','{\"active\": 1}','products','1','2026-04-30 10:04:40','1',NULL,'4','2026-04-30 10:04:40');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('10','4','Jose Chacon','admin','OCULTAR_PRODUCTO','productos','Producto \"Sensor inductivo prt12-4dp\" (ID: 1) ha sido OCULTADO de la tienda','::1','Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','{\"active\": 1}','{\"active\": 0}','products','1','2026-04-30 10:04:53','1',NULL,'4','2026-04-30 10:04:53');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('11','4','Jose Chacon','admin','MOSTRAR_PRODUCTO','productos','Producto \"Sensor inductivo prt12-4dp\" (ID: 1) ha sido MOSTRADO en la tienda','::1','Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','{\"active\": 0}','{\"active\": 1}','products','1','2026-04-30 10:07:13','1',NULL,'4','2026-04-30 10:07:13');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('12','4','Jose Chacon','admin','OCULTAR_PRODUCTO','productos','Producto \"Sensor inductivo prt12-4dp\" (ID: 1) ha sido OCULTADO de la tienda','::1','Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36','{\"active\": 1}','{\"active\": 0}','products','1','2026-04-30 10:09:18','1',NULL,'4','2026-04-30 10:09:18');
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('13','4','Jose Chacon','admin','CREAR','productos','Creó el producto: Jose Chacon (SKU: PROD-0081) - Precio: Bs. 280 - Stock: 56','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','81','2026-04-30 10:49:14','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('14','4','Jose Chacon','admin','CREAR','productos','Creó el producto: CEO Principal (SKU: PROD-0081)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','82','2026-04-30 11:11:00','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('15','4','Jose Chacon','admin','CREAR','productos','Creó el producto: Jose Chacon (SKU: PROD-0081)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','83','2026-04-30 11:14:25','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('16','4','Jose Chacon','admin','CREAR','productos','Creó el producto: nksm.,mz, (SKU: PROD-0084)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','84','2026-04-30 11:18:33','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('17','4','Jose Chacon','admin','CREAR','productos','Creó el producto: Marivic (SKU: PROD-0081)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','85','2026-04-30 11:29:03','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('18','4','Jose Chacon','admin','CREAR','productos','Creó el producto: CEO Principal (SKU: PROD-0086)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','86','2026-04-30 11:30:18','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('19','4','Jose Chacon','admin','CREAR','productos','Creó el producto: jhon (SKU: PROD-0087)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','87','2026-04-30 11:31:20','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('20','4','Jose Chacon','admin','CREAR','productos','Creó el producto: Jose Chacon (SKU: PROD-0081)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','88','2026-04-30 11:41:03','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('21','4','Jose Chacon','admin','CREAR','productos','Creó el producto: Jose Chacon (SKU: PROD-00081) - Precio: 100 Bs, Stock: 55, Categoría: Herramientas','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','89','2026-05-01 10:39:01','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('22','4','Jose Chacon','admin','CREAR','productos','Creó el producto: Jose Chacon (SKU: PROD-0090) - Precio: 800 Bs, Stock: 90','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','90','2026-05-01 11:09:53','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('23','4','Jose Chacon','admin','CREAR','productos','Creó el producto: Marivic (SKU: PROD-0091) - Precio: 31 Bs, Stock: 55','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','91','2026-05-01 11:12:03','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('24','4','Jose Chacon','admin','crear','productos','Creó el producto: Jose (SKU: JOSE-0092)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','92','2026-05-01 11:26:03','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('25','4','Jose Chacon','admin','limpiar','productos','Eliminó productos de prueba: Jose Chacon, Jose Chacon, Marivic, Jose',NULL,NULL,NULL,NULL,'products',NULL,'2026-05-01 11:42:54','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('26','4','Jose Chacon','admin','crear','productos','Creó el producto: sensor capacitivo 220 (SKU: SENSOR0081)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,NULL,'products','81','2026-05-01 11:45:52','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('27','4','Jose Chacon','admin','crear_producto','productos','Producto creado: AT8N (SKU: )','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',NULL,'{\"stock\": 51, \"imagen\": \"https://www.autonics.com/web/2022/08/22/6/53/4/AT8N_main.webp\", \"nombre\": \"AT8N\", \"precio\": 25, \"categoria\": \"Relés\", \"descripcion\": \"Método de operación: Ajuste de hora\\r\\nFuncionamiento de salida: RETARDO DE ENCENDIDO, PARPADEO, INTERVALO\\r\\nFuncionamiento por tiempo: ENCENDIDO INICIO\\r\\nTerminal: conector de 8 pines\\r\\nTensión de alimentación: 100-240 VCA~, 24-240 VCCcadena especial\\r\\nSalida de control: Límite de tiempo DPDT (2c), Límite de tiempo SPDT (1c) + SPDT instantáneo (1c)\"}','products','83','2026-05-02 10:44:32','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('28','6','antonio','usuario','cambiar_contraseña','seguridad','Usuario cambió su contraseña','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-02 15:47:23','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('29','4','Carlos Rodríguez','usuario','CAMBIO_ESTADO','pedidos','Estado cambiado a: procesando','::1',NULL,NULL,NULL,'pedidos','5','2026-05-03 11:32:52','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('30','4','Jose Chacon','admin','enviar_email','facturas','Factura #18 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','18','2026-05-12 08:55:28','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('31','6','antonio','usuario','cambiar_contraseña','seguridad','Usuario cambió su contraseña','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-12 11:09:25','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('32','4','Jose Chacon','admin','actualizar_perfil','perfil','Usuario actualizó su perfil','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-13 15:56:57','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('33','4','Jose Chacon','admin','actualizar_perfil','perfil','Usuario actualizó su perfil','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-13 16:10:57','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('34','4','Jose Chacon','admin','actualizar_perfil','perfil','Usuario actualizó su perfil','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-13 16:11:15','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('35','4','Jose Chacon','admin','enviar_email','facturas','Factura #30 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','30','2026-05-24 09:23:15','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('36','4','Jose Chacon','admin','enviar_email','facturas','Factura #30 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','30','2026-05-24 09:29:14','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('37','4','Jose Chacon','admin','enviar_email','facturas','Factura #30 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','30','2026-05-24 09:31:50','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('38','4','Jose Chacon','admin','enviar_email','facturas','Factura #30 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','30','2026-05-24 09:50:58','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('39','4','Jose Chacon','admin','enviar_email','facturas','Factura #30 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','30','2026-05-24 10:00:09','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('40','4','Jose Chacon','admin','enviar_email','facturas','Factura #30 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','30','2026-05-24 10:07:04','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('41','4','Jose Chacon','admin','enviar_email','facturas','Factura #61 enviada a jose14chacon2003@gmail.com (proveedor: desconocido)','::1',NULL,NULL,NULL,'facturas','61','2026-05-27 13:56:30','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('42','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #11','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:26','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('43','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #10','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:39','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('44','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #9','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:41','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('45','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #1','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:49','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('46','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #2','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:50','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('47','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #3','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:51','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('48','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #4','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:52','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('49','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #5','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:52','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('50','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #6','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:53','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('51','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #7','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:54','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('52','4','Jose Chacon',NULL,'resolver_alerta_stock','inventario','Alerta de stock resuelta: #8','::1',NULL,NULL,NULL,NULL,NULL,'2026-05-28 09:28:55','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('53',NULL,'sistema',NULL,'notificar_pedido_telegram','telegram','Notificación de nuevo pedido #PED-20260605-5306 enviada por Telegram','',NULL,NULL,NULL,NULL,NULL,'2026-06-05 12:18:36','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('54','6','antonio',NULL,'notificar_pedido_telegram','telegram','Notificación de nuevo pedido #PED-20260605-5624 enviada por Telegram','::1',NULL,NULL,NULL,NULL,NULL,'2026-06-05 13:01:21','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('55','6','antonio',NULL,'notificar_pedido_telegram','telegram','Notificación de nuevo pedido #PED-20260605-1571 enviada por Telegram','::1',NULL,NULL,NULL,NULL,NULL,'2026-06-05 13:03:01','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('56','6','antonio',NULL,'notificar_pedido_telegram','telegram','Notificación de nuevo pedido #PED-2026-000040 enviada por Telegram','::1',NULL,NULL,NULL,NULL,NULL,'2026-06-05 13:03:55','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('57','6','antonio',NULL,'notificar_pedido_telegram','telegram','Notificación de nuevo pedido #PED-2026-000041 enviada por Telegram','::1',NULL,NULL,NULL,NULL,NULL,'2026-06-05 13:05:37','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('58',NULL,'sistema',NULL,'notificar_pedido_telegram','telegram','Notificación de nuevo pedido #PED-20260606-9060 enviada por Telegram','',NULL,NULL,NULL,NULL,NULL,'2026-06-06 06:55:43','0',NULL,NULL,NULL);
INSERT INTO `auditoria_logs` (`id`, `usuario_id`, `usuario_nombre`, `usuario_rol`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `datos_anteriores`, `datos_nuevos`, `tabla_afectada`, `registro_id`, `fecha_creacion`, `edit_count`, `edit_history`, `last_edit_by`, `last_edit_at`) VALUES ('59',NULL,'sistema',NULL,'notificar_pedido_telegram','telegram','Notificación de nuevo pedido #PED-2026-000042 enviada por Telegram','',NULL,NULL,NULL,NULL,NULL,'2026-06-06 07:38:42','0',NULL,NULL,NULL);



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
  KEY `idx_tipo` (`tipo`),
  KEY `fk_backups_usuario` (`usuario_id`),
  CONSTRAINT `fk_backups_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: backups
--
INSERT INTO `backups` (`id`, `nombre_archivo`, `ruta_archivo`, `tamanio_bytes`, `tipo`, `estado`, `usuario_id`, `descripcion`, `fecha_creacion`, `fecha_eliminacion`) VALUES ('1','backup_2026-05-03_12-49-34.sql','C:\\laragon\\www\\proyecto\\backups/backups/backup_2026-05-03_12-49-34.sql','101714','completo','completado','4',NULL,'2026-05-03 08:49:34',NULL);
INSERT INTO `backups` (`id`, `nombre_archivo`, `ruta_archivo`, `tamanio_bytes`, `tipo`, `estado`, `usuario_id`, `descripcion`, `fecha_creacion`, `fecha_eliminacion`) VALUES ('2','backup_2026-05-13_14-32-05.sql','C:\\laragon\\www\\proyecto\\backups/backups/backup_2026-05-13_14-32-05.sql','113863','completo','completado','4',NULL,'2026-05-13 10:32:05',NULL);
INSERT INTO `backups` (`id`, `nombre_archivo`, `ruta_archivo`, `tamanio_bytes`, `tipo`, `estado`, `usuario_id`, `descripcion`, `fecha_creacion`, `fecha_eliminacion`) VALUES ('3','backup_2026-05-18_13-54-31.sql','C:\\laragon\\www\\proyecto\\backups/backups/backup_2026-05-18_13-54-31.sql','122048','completo','completado','4',NULL,'2026-05-18 09:54:32',NULL);
INSERT INTO `backups` (`id`, `nombre_archivo`, `ruta_archivo`, `tamanio_bytes`, `tipo`, `estado`, `usuario_id`, `descripcion`, `fecha_creacion`, `fecha_eliminacion`) VALUES ('4','backup_2026-05-19_18-30-49.sql','C:\\laragon\\www\\proyecto\\backups/backups/backup_2026-05-19_18-30-49.sql','124888','completo','completado','4',NULL,'2026-05-19 14:30:49',NULL);
INSERT INTO `backups` (`id`, `nombre_archivo`, `ruta_archivo`, `tamanio_bytes`, `tipo`, `estado`, `usuario_id`, `descripcion`, `fecha_creacion`, `fecha_eliminacion`) VALUES ('5','backup_2026-05-30_11-46-19.sql','C:\\laragon\\www\\proyecto\\backups/backups/backup_2026-05-30_11-46-19.sql','250785','completo','completado','4',NULL,'2026-05-30 07:46:19',NULL);



-- --------------------------------------------------------
-- Estructura de tabla: bi_metricas_diarias
-- --------------------------------------------------------
CREATE TABLE `bi_metricas_diarias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `ventas_totales` decimal(12,2) DEFAULT '0.00',
  `numero_pedidos` int DEFAULT '0',
  `ticket_promedio` decimal(10,2) DEFAULT '0.00',
  `clientes_nuevos` int DEFAULT '0',
  `productos_vendidos` int DEFAULT '0',
  `ingresos_efectivo` decimal(12,2) DEFAULT '0.00',
  `ingresos_transferencia` decimal(12,2) DEFAULT '0.00',
  `ingresos_pago_movil` decimal(12,2) DEFAULT '0.00',
  `tasa_conversion` decimal(5,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha` (`fecha`),
  KEY `idx_fecha` (`fecha`)
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
  KEY `idx_fecha_apertura` (`fecha_apertura`),
  KEY `fk_caja_arqueo_usuario_apertura` (`usuario_apertura_id`),
  CONSTRAINT `fk_caja_arqueo_usuario_apertura` FOREIGN KEY (`usuario_apertura_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: caja_arqueos
--
INSERT INTO `caja_arqueos` (`id`, `numero_arqueo`, `fecha_apertura`, `fecha_cierre`, `usuario_apertura_id`, `usuario_cierre_id`, `monto_inicial`, `monto_ingresos`, `monto_egresos`, `monto_esperado`, `monto_real`, `diferencia`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES ('1','CAJA-20260513-48D8','2026-05-13 10:19:13','2026-05-13 10:24:40','4','4','200.00','201.00','0.00','401.00','401.00','0.00','cerrada','prueba','2026-05-13 10:19:13','2026-05-13 10:24:40');
INSERT INTO `caja_arqueos` (`id`, `numero_arqueo`, `fecha_apertura`, `fecha_cierre`, `usuario_apertura_id`, `usuario_cierre_id`, `monto_inicial`, `monto_ingresos`, `monto_egresos`, `monto_esperado`, `monto_real`, `diferencia`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES ('2','CAJA-20260513-F408','2026-05-13 15:35:11',NULL,'4',NULL,'100.00','0.00','10.00','0.00','0.00','0.00','abierta','venta','2026-05-13 15:35:11','2026-05-13 15:36:01');
INSERT INTO `caja_arqueos` (`id`, `numero_arqueo`, `fecha_apertura`, `fecha_cierre`, `usuario_apertura_id`, `usuario_cierre_id`, `monto_inicial`, `monto_ingresos`, `monto_egresos`, `monto_esperado`, `monto_real`, `diferencia`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES ('3','CAJA-20260519-9415','2026-05-19 14:19:34',NULL,'4',NULL,'200.00','200.00','400.00','0.00','0.00','0.00','abierta','prueba','2026-05-19 14:19:34','2026-05-19 14:25:40');
INSERT INTO `caja_arqueos` (`id`, `numero_arqueo`, `fecha_apertura`, `fecha_cierre`, `usuario_apertura_id`, `usuario_cierre_id`, `monto_inicial`, `monto_ingresos`, `monto_egresos`, `monto_esperado`, `monto_real`, `diferencia`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES ('4','CAJA-20260520-9D42','2026-05-20 07:57:09',NULL,'4',NULL,'200.00','620.00','600.00','0.00','0.00','0.00','abierta','prueba','2026-05-20 07:57:09','2026-05-20 08:10:44');
INSERT INTO `caja_arqueos` (`id`, `numero_arqueo`, `fecha_apertura`, `fecha_cierre`, `usuario_apertura_id`, `usuario_cierre_id`, `monto_inicial`, `monto_ingresos`, `monto_egresos`, `monto_esperado`, `monto_real`, `diferencia`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES ('5','CAJA-20260527-9B08','2026-05-27 14:07:15','2026-05-27 14:08:09','4','4','200.00','100.00','0.00','300.00','300.00','0.00','cerrada','prueba','2026-05-27 14:07:15','2026-05-27 14:08:09');



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
  KEY `idx_fecha` (`fecha_movimiento`),
  KEY `fk_caja_mov_usuario` (`usuario_id`),
  CONSTRAINT `fk_caja_mov_arqueo` FOREIGN KEY (`arqueo_id`) REFERENCES `caja_arqueos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_caja_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: caja_movimientos
--
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('1','1','ingreso','invento','201.00','',NULL,'efectivo','4','2026-05-13 10:24:14',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('2','2','egreso','movimiento ','10.00','prueba',NULL,'efectivo','4','2026-05-13 15:36:01',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('3','3','ingreso','movimiento ','200.00','',NULL,'efectivo','4','2026-05-19 14:20:22',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('4','3','egreso','movimiento ','200.00','',NULL,'efectivo','4','2026-05-19 14:20:45',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('5','3','egreso','movimiento ','200.00','',NULL,'efectivo','4','2026-05-19 14:25:40',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('6','4','ingreso','movimiento ','100.00','',NULL,'efectivo','4','2026-05-20 07:57:37',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('7','4','ingreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 07:58:07',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('8','4','egreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 07:58:49',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('9','4','egreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 08:04:49',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('10','4','ingreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 08:05:08',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('11','4','ingreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 08:06:01',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('12','4','egreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 08:06:19',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('13','4','egreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 08:08:13',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('14','4','ingreso','movimiento 3','100.00','',NULL,'efectivo','4','2026-05-20 08:08:33',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('15','4','ingreso','movimiento 3','120.00','',NULL,'efectivo','4','2026-05-20 08:10:17',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('16','4','egreso','movimiento 3','200.00','',NULL,'efectivo','4','2026-05-20 08:10:44',NULL);
INSERT INTO `caja_movimientos` (`id`, `arqueo_id`, `tipo`, `categoria`, `monto`, `descripcion`, `referencia`, `metodo_pago`, `usuario_id`, `fecha_movimiento`, `factura_id`) VALUES ('17','5','ingreso','invento','100.00','prueba\n',NULL,'efectivo','4','2026-05-27 14:07:51',NULL);



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
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- --------------------------------------------------------
-- Estructura de tabla: cliente_interacciones
-- --------------------------------------------------------
CREATE TABLE `cliente_interacciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `tipo` enum('llamada','correo','reunion','nota','seguimiento','recordatorio') COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fecha_interaccion` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_fecha` (`fecha_interaccion`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `cliente_interacciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cliente_interacciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: cliente_interacciones
--
INSERT INTO `cliente_interacciones` (`id`, `cliente_id`, `usuario_id`, `tipo`, `titulo`, `descripcion`, `fecha_interaccion`, `created_at`) VALUES ('1','6','4','correo','prueba','esto es una prueba','2026-06-04 14:34:00','2026-06-04 10:34:56');
INSERT INTO `cliente_interacciones` (`id`, `cliente_id`, `usuario_id`, `tipo`, `titulo`, `descripcion`, `fecha_interaccion`, `created_at`) VALUES ('2','6','4','recordatorio','prueba','prueba 2','2026-06-04 14:35:00','2026-06-04 10:36:11');



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
  UNIQUE KEY `documento` (`documento`),
  KEY `idx_clientes_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: clientes
--
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('1','cedula','12345678','Juan Pérez','juan@email.com','04121234567','Av. Principal #123, Caracas',NULL,'activo','2026-04-29 13:58:02');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('2','cedula','87654321','María González','maria@email.com','04149876543','Calle Secundaria #45, Maracaibo',NULL,'activo','2026-04-29 13:58:02');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('3','cedula','11223344','Carlos Rodríguez','carlos@email.com','04161122334','Urb. Las Flores, Valencia',NULL,'activo','2026-04-29 13:58:02');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('4','cedula','11111111','Cliente de Prueba','cliente@test.com','04141234567','Calle Principal, Barquisimeto',NULL,'activo','2026-04-29 13:58:02');
INSERT INTO `clientes` (`id`, `tipo_documento`, `documento`, `nombre`, `email`, `telefono`, `direccion`, `ciudad`, `estado`, `fecha_registro`) VALUES ('8','cedula','17314511','antonio','jose14chacon2003@gmail.com','04121311220','Urb trigal Sur Calle Camoruco',NULL,'activo','2026-04-29 13:59:23');



-- --------------------------------------------------------
-- Estructura de tabla: cola_notificaciones
-- --------------------------------------------------------
CREATE TABLE `cola_notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) NOT NULL,
  `pedido_id` int DEFAULT NULL,
  `factura_id` int DEFAULT NULL,
  `estado` enum('pendiente','procesando','completado','fallido') DEFAULT 'pendiente',
  `intentos` int DEFAULT '0',
  `max_intentos` int DEFAULT '3',
  `datos_extra` text,
  `error` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `procesado_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: cola_notificaciones
--
INSERT INTO `cola_notificaciones` (`id`, `tipo`, `pedido_id`, `factura_id`, `estado`, `intentos`, `max_intentos`, `datos_extra`, `error`, `created_at`, `procesado_at`) VALUES ('1','email_factura','110','108','completado','1','3',NULL,NULL,'2026-06-06 06:55:36','2026-06-06 06:55:42');
INSERT INTO `cola_notificaciones` (`id`, `tipo`, `pedido_id`, `factura_id`, `estado`, `intentos`, `max_intentos`, `datos_extra`, `error`, `created_at`, `procesado_at`) VALUES ('2','telegram_pedido','110','108','completado','1','3',NULL,NULL,'2026-06-06 06:55:36','2026-06-06 06:55:43');
INSERT INTO `cola_notificaciones` (`id`, `tipo`, `pedido_id`, `factura_id`, `estado`, `intentos`, `max_intentos`, `datos_extra`, `error`, `created_at`, `procesado_at`) VALUES ('3','encuesta_satisfaccion','110',NULL,'completado','1','3','{\"email\":\"jose14chacon2003@gmail.com\",\"nombre\":\"antonio\",\"numero_factura\":\"FAC-2026-000097\"}',NULL,'2026-06-06 06:55:36','2026-06-06 06:55:45');
INSERT INTO `cola_notificaciones` (`id`, `tipo`, `pedido_id`, `factura_id`, `estado`, `intentos`, `max_intentos`, `datos_extra`, `error`, `created_at`, `procesado_at`) VALUES ('4','email_factura','111','109','completado','1','3',NULL,NULL,'2026-06-06 07:38:31','2026-06-06 07:38:41');
INSERT INTO `cola_notificaciones` (`id`, `tipo`, `pedido_id`, `factura_id`, `estado`, `intentos`, `max_intentos`, `datos_extra`, `error`, `created_at`, `procesado_at`) VALUES ('5','telegram_pedido','111','109','completado','1','3',NULL,NULL,'2026-06-06 07:38:31','2026-06-06 07:38:42');
INSERT INTO `cola_notificaciones` (`id`, `tipo`, `pedido_id`, `factura_id`, `estado`, `intentos`, `max_intentos`, `datos_extra`, `error`, `created_at`, `procesado_at`) VALUES ('6','encuesta_satisfaccion','111',NULL,'completado','1','3','{\"email\":\"jose14chacon2003@gmail.com\",\"nombre\":\"antonio\",\"numero_factura\":\"FAC-2026-000098\"}',NULL,'2026-06-06 07:38:31','2026-06-06 07:38:44');



-- --------------------------------------------------------
-- Estructura de tabla: compatibilidad_marcas
-- --------------------------------------------------------
CREATE TABLE `compatibilidad_marcas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria` varchar(100) NOT NULL,
  `marca_a` varchar(100) NOT NULL,
  `modelo_a` varchar(200) NOT NULL,
  `marca_b` varchar(100) NOT NULL,
  `modelo_b` varchar(200) NOT NULL,
  `tipo_compatibilidad` enum('directo','adaptador','funcional') DEFAULT 'directo',
  `notas` text,
  `producto_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_marca_a` (`marca_a`,`modelo_a`),
  KEY `idx_marca_b` (`marca_b`,`modelo_b`),
  CONSTRAINT `compatibilidad_marcas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: compatibilidad_marcas
--
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('1','Contactores','Schneider Electric','LC1D25M7','Telemecanique','LC1D25M7','directo','Schneider adquirió Telemecanique. Mismos modelos y referencias.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('2','Contactores','Schneider Electric','LC1D18BD','Telemecanique','LC1D18BD','directo','Mismo producto, distinto empaque.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('3','Contactores','Schneider Electric','LC1D32M7','Telemecanique','LC1D32M7','directo','Compatibilidad total, misma línea de producción.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('4','Relés Térmicos','Schneider Electric','LRD3355','Telemecanique','LRD3355','directo','Mismo producto.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('5','Relés Térmicos','Schneider Electric','LRD3357','Telemecanique','LRD3357','directo','Compatibilidad total.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('6','Guardamotores','Schneider Electric','GV2ME06','Schneider Electric','GV3P40','funcional','GV3 es la evolución del GV2. Mayor capacidad de ruptura. Verificar curva.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('7','Guardamotores','Schneider Electric','GV2ME08','Schneider Electric','GV3P40','funcional','Reemplazo funcional. Verifique rango de ajuste.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('8','Sensores Inductivos','Autonics','PRT12-4DP','Autonics','PRD18-8DP','funcional','PRD18 tiene mayor distancia de detección (8mm vs 4mm). Verificar espacio de montaje.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('9','Sensores Inductivos','Autonics','PRCM30-5DP','Autonics','PRD30-10DP','funcional','PRD30 ofrece mayor alcance. Compatible en montaje M30.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('10','Sensores Capacitivos','Autonics','CR18-8AC','Autonics','CR30-15AC','funcional','CR30-15AC mayor alcance (15mm vs 8mm). Diámetro M30.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('11','Relés Estado Sólido','Autonics','SR1-4415','Autonics','SR1-1450','funcional','SR1-1450 para cargas de hasta 50A. SR1-4415 hasta 15A.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('12','Relés Estado Sólido','Autonics','SR1-4415','Crydom','D2440','funcional','Crydom D2440: 40A, 24-280VAC. Compatible con Autoics SR1-4415. Verificar montaje.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('13','Fuentes de Poder','Autonics','SPB-120-12','Mean Well','LRS-150-12','funcional','Mean Well LRS-150-12: 150W vs 120W. 12.5A. Más potencia, mismas dimensiones.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('14','Fuentes de Poder','Autonics','SPB-060-12','Mean Well','LRS-75-12','funcional','Misma función. LRS-75-12 ofrece 75W a 12VDC. 6.3A.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('15','Fuentes de Poder','Schneider Electric','SPB-O6O-12','Autonics','SPB-060-12','funcional','Ambas son fuentes de 60W 12VDC. Misma funcionalidad, distinto conector.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('16','Variadores','Schneider Electric','ATV320U40N4C','Schneider Electric','ATV12H075M2','funcional','ATV12 para motores monofásicos. ATV320 para trifásicos. Diferente aplicación.',NULL,'2026-05-20 09:20:47');
INSERT INTO `compatibilidad_marcas` (`id`, `categoria`, `marca_a`, `modelo_a`, `marca_b`, `modelo_b`, `tipo_compatibilidad`, `notas`, `producto_id`, `created_at`) VALUES ('17','Variadores','Schneider Electric','ATV320U55M3C','ABB','ACS355-03E-01A2-4','funcional','Compatibilidad funcional. MISMO RANGO. ABB ACS355 es equivalente. Verificar parámetros.',NULL,'2026-05-20 09:20:47');



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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: compra_detalles
--
INSERT INTO `compra_detalles` (`id`, `compra_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `created_at`) VALUES ('1','1','5','1','450.00','450.00','2026-05-02 15:58:58');
INSERT INTO `compra_detalles` (`id`, `compra_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `created_at`) VALUES ('2','2','8','1','120.00','120.00','2026-05-12 08:16:26');
INSERT INTO `compra_detalles` (`id`, `compra_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `created_at`) VALUES ('3','3','6','1','180.00','180.00','2026-05-19 14:17:40');



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
  KEY `idx_fecha_orden` (`fecha_orden`),
  KEY `fk_compras_usuario_creacion` (`usuario_creacion_id`),
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_compras_usuario_creacion` FOREIGN KEY (`usuario_creacion_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: compras
--
INSERT INTO `compras` (`id`, `numero_orden`, `proveedor_id`, `fecha_orden`, `fecha_requerida`, `fecha_recibido`, `subtotal`, `iva`, `descuento`, `total`, `estado`, `metodo_pago`, `condiciones_pago`, `usuario_creacion_id`, `usuario_aprobacion_id`, `fecha_aprobacion`, `observaciones`, `created_at`, `updated_at`) VALUES ('1','ORD-20260502-4827','3','2026-05-02',NULL,NULL,'450.00','72.00','0.00','522.00','aprobada','transferencia',NULL,'4',NULL,NULL,'','2026-05-02 15:58:58','2026-05-02 15:58:58');
INSERT INTO `compras` (`id`, `numero_orden`, `proveedor_id`, `fecha_orden`, `fecha_requerida`, `fecha_recibido`, `subtotal`, `iva`, `descuento`, `total`, `estado`, `metodo_pago`, `condiciones_pago`, `usuario_creacion_id`, `usuario_aprobacion_id`, `fecha_aprobacion`, `observaciones`, `created_at`, `updated_at`) VALUES ('2','ORD-20260512-6619','3','2026-05-12',NULL,NULL,'120.00','19.20','0.00','139.20','aprobada','transferencia',NULL,'4',NULL,NULL,'','2026-05-12 08:16:26','2026-05-12 08:16:26');
INSERT INTO `compras` (`id`, `numero_orden`, `proveedor_id`, `fecha_orden`, `fecha_requerida`, `fecha_recibido`, `subtotal`, `iva`, `descuento`, `total`, `estado`, `metodo_pago`, `condiciones_pago`, `usuario_creacion_id`, `usuario_aprobacion_id`, `fecha_aprobacion`, `observaciones`, `created_at`, `updated_at`) VALUES ('3','ORD-20260519-5451','3','2026-05-19',NULL,NULL,'180.00','28.80','0.00','208.80','aprobada','transferencia',NULL,'4',NULL,NULL,'prueba','2026-05-19 14:17:40','2026-05-19 14:17:40');



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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: configuracion_sistema
--
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('1','empresa_nombre','PIC - Productos Industriales y Comerciales','text','empresa','Nombre de la empresa','1','1','2026-04-29 13:58:03','2026-05-18 09:54:15');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('2','empresa_rif','J-12345678-9','text','empresa','RIF de la empresa','1','2','2026-04-29 13:58:03','2026-05-18 09:54:15');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('3','empresa_direccion','Av. Principal, Zona Industrial, Caracas','text','empresa','Dirección de la empresa','1','3','2026-04-29 13:58:03','2026-05-18 09:54:15');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('4','empresa_telefono','0212-5551234','text','empresa','Teléfono de contacto','1','4','2026-04-29 13:58:03','2026-05-18 09:54:15');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('5','empresa_email','info@pic.com.ve','email','empresa','Email de contacto','1','5','2026-04-29 13:58:03','2026-05-18 09:54:15');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('6','iva_porcentaje','16','number','facturacion','Porcentaje de IVA aplicado','1','10','2026-04-29 13:58:03','2026-05-18 09:54:15');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('7','moneda_principal','Bs','text','facturacion','Moneda principal del sistema','1','11','2026-04-29 13:58:03','2026-05-18 09:54:16');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('8','factura_prefijo','FAC','text','facturacion','Prefijo para números de factura','1','12','2026-04-29 13:58:03','2026-05-18 09:54:16');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('9','factura_longitud','6','number','facturacion','Longitud del correlativo','1','13','2026-04-29 13:58:03','2026-05-18 09:54:16');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('10','notificaciones_email','1','boolean','notificaciones','Enviar notificaciones por email','1','20','2026-04-29 13:58:03','2026-05-18 09:54:16');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('11','notificaciones_whatsapp','0','boolean','notificaciones','Enviar notificaciones por WhatsApp','1','21','2026-04-29 13:58:03','2026-05-18 09:54:16');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('12','stock_minimo_alerta','5','number','inventario','Stock mínimo para alertas','1','30','2026-04-29 13:58:03','2026-05-18 09:54:16');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('13','modo_mantenimiento','0','boolean','sistema','Modo mantenimiento del sistema','1','40','2026-04-29 13:58:03','2026-05-18 09:54:16');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('14','version_sistema','2.0.0','text','sistema','Versión actual del sistema','0','41','2026-04-29 13:58:03','2026-04-29 13:58:03');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('15','whatsapp_api_url','','text','whatsapp','URL de la API de WhatsApp','1','50','2026-05-21 09:39:00','2026-05-21 09:39:00');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('16','whatsapp_api_token','','password','whatsapp','Token de la API de WhatsApp','1','51','2026-05-21 09:39:00','2026-05-21 09:39:00');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('17','whatsapp_numero','584121311228','text','whatsapp','Número de WhatsApp de la empresa','1','52','2026-05-21 09:39:00','2026-05-27 08:48:08');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('18','whatsapp_notificaciones_pedido','1','boolean','whatsapp','Notificar nuevos pedidos por WhatsApp','1','53','2026-05-21 09:39:00','2026-05-27 08:48:08');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('19','whatsapp_notificaciones_stock','0','boolean','whatsapp','Notificar stock bajo por WhatsApp','1','54','2026-05-21 09:39:00','2026-05-21 09:39:00');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('21','telegram_token','8908472899:AAHsKpdZupx1BuFyGtnrF7UooWxggl88-qs','text','general',NULL,'1','0','2026-05-27 10:05:32','2026-05-27 10:56:49');
INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `tipo`, `grupo`, `descripcion`, `editable`, `orden`, `created_at`, `updated_at`) VALUES ('22','telegram_chat_id','1700619516','text','general',NULL,'1','0','2026-05-27 10:05:32','2026-05-27 10:56:50');



-- --------------------------------------------------------
-- Estructura de tabla: configuraciones_tablero
-- --------------------------------------------------------
CREATE TABLE `configuraciones_tablero` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text,
  `aplicacion` varchar(100) NOT NULL COMMENT 'Ej: Bomba de agua, Compresor, Cinta transportadora, etc.',
  `parametros` json NOT NULL COMMENT 'Parámetros de entrada (hp, voltaje, etc.)',
  `componentes` json NOT NULL COMMENT 'Lista de componentes seleccionados con cantidades',
  `total_estimado` decimal(12,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_aplicacion` (`aplicacion`),
  CONSTRAINT `configuraciones_tablero_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: configuraciones_tablero
--
INSERT INTO `configuraciones_tablero` (`id`, `usuario_id`, `nombre`, `descripcion`, `aplicacion`, `parametros`, `componentes`, `total_estimado`, `created_at`, `updated_at`) VALUES ('2','4','5','50m','Otro','{\"hp\": 5, \"voltaje\": 220}','[{\"id\": \"13\", \"name\": \"Selector 2 posiciones\", \"precio\": 45, \"cantidad\": 1}]','45.00','2026-05-28 10:55:50','2026-05-28 10:55:50');



-- --------------------------------------------------------
-- Estructura de tabla: cotizacion_detalles
-- --------------------------------------------------------
CREATE TABLE `cotizacion_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int NOT NULL,
  `producto_id` int DEFAULT NULL,
  `producto_nombre` varchar(255) NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cotizacion_id` (`cotizacion_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `cotizacion_detalles_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cotizacion_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: cotizacion_detalles
--
INSERT INTO `cotizacion_detalles` (`id`, `cotizacion_id`, `producto_id`, `producto_nombre`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('1','3','14','Etiquetadora panduit','2','2800.00','5600.00');
INSERT INTO `cotizacion_detalles` (`id`, `cotizacion_id`, `producto_id`, `producto_nombre`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('2','4','1','Sensor inductivo prt12-4dp','1','150.00','150.00');
INSERT INTO `cotizacion_detalles` (`id`, `cotizacion_id`, `producto_id`, `producto_nombre`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('3','5','34','Controlador de temperatura 48x69','1','210.00','210.00');
INSERT INTO `cotizacion_detalles` (`id`, `cotizacion_id`, `producto_id`, `producto_nombre`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('4','6','35','Temporizador Autonics','1','140.00','140.00');
INSERT INTO `cotizacion_detalles` (`id`, `cotizacion_id`, `producto_id`, `producto_nombre`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('5','7','41','Contactor 40amp 110v','1','420.00','420.00');



-- --------------------------------------------------------
-- Estructura de tabla: cotizaciones
-- --------------------------------------------------------
CREATE TABLE `cotizaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_cotizacion` varchar(50) NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `cliente_nombre` varchar(255) NOT NULL,
  `cliente_email` varchar(255) DEFAULT NULL,
  `cliente_telefono` varchar(50) DEFAULT NULL,
  `cliente_direccion` text,
  `usuario_id` int DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT '0.00',
  `iva` decimal(12,2) DEFAULT '0.00',
  `total` decimal(12,2) DEFAULT '0.00',
  `estado` enum('pendiente','aprobada','rechazada','vencida','convertida') DEFAULT 'pendiente',
  `seguimiento` text,
  `notas` text,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  KEY `idx_cotizaciones_cliente_id` (`cliente_id`),
  KEY `idx_cotizaciones_usuario_id` (`usuario_id`),
  CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cotizaciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `admin_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: cotizaciones
--
INSERT INTO `cotizaciones` (`id`, `numero_cotizacion`, `cliente_id`, `cliente_nombre`, `cliente_email`, `cliente_telefono`, `cliente_direccion`, `usuario_id`, `subtotal`, `iva`, `total`, `estado`, `seguimiento`, `notas`, `fecha_creacion`, `fecha_vencimiento`, `fecha_actualizacion`) VALUES ('3','COT-WEB-2026-000001',NULL,'jose gregorio','jose14chacon2003@gmail.com','04121311228',NULL,NULL,'5600.00','896.00','6496.00','pendiente',NULL,'Solicitada vía web por jose gregorio','2026-05-28 10:13:55',NULL,'2026-05-28 10:13:55');
INSERT INTO `cotizaciones` (`id`, `numero_cotizacion`, `cliente_id`, `cliente_nombre`, `cliente_email`, `cliente_telefono`, `cliente_direccion`, `usuario_id`, `subtotal`, `iva`, `total`, `estado`, `seguimiento`, `notas`, `fecha_creacion`, `fecha_vencimiento`, `fecha_actualizacion`) VALUES ('4','COT-WEB-2026-000002',NULL,'jose gregorio','jose14chacon2003@gmail.com','04121311228',NULL,NULL,'150.00','24.00','174.00','pendiente',NULL,'Solicitada vía web por jose gregorio','2026-05-28 10:15:31',NULL,'2026-05-28 10:15:31');
INSERT INTO `cotizaciones` (`id`, `numero_cotizacion`, `cliente_id`, `cliente_nombre`, `cliente_email`, `cliente_telefono`, `cliente_direccion`, `usuario_id`, `subtotal`, `iva`, `total`, `estado`, `seguimiento`, `notas`, `fecha_creacion`, `fecha_vencimiento`, `fecha_actualizacion`) VALUES ('5','COT-WEB-2026-000003',NULL,'jose gregorio','jose14chacon2003@gmail.com','04121311228',NULL,NULL,'210.00','33.60','243.60','pendiente',NULL,'Solicitada vía web por jose gregorio','2026-05-28 10:18:44',NULL,'2026-05-28 10:18:44');
INSERT INTO `cotizaciones` (`id`, `numero_cotizacion`, `cliente_id`, `cliente_nombre`, `cliente_email`, `cliente_telefono`, `cliente_direccion`, `usuario_id`, `subtotal`, `iva`, `total`, `estado`, `seguimiento`, `notas`, `fecha_creacion`, `fecha_vencimiento`, `fecha_actualizacion`) VALUES ('6','COT-WEB-2026-000004',NULL,'jose gregorio','jose142003@gmail.com','04121311228',NULL,NULL,'140.00','22.40','162.40','pendiente',NULL,'Solicitada vía web por jose gregorio','2026-06-04 12:59:21',NULL,'2026-06-04 12:59:21');
INSERT INTO `cotizaciones` (`id`, `numero_cotizacion`, `cliente_id`, `cliente_nombre`, `cliente_email`, `cliente_telefono`, `cliente_direccion`, `usuario_id`, `subtotal`, `iva`, `total`, `estado`, `seguimiento`, `notas`, `fecha_creacion`, `fecha_vencimiento`, `fecha_actualizacion`) VALUES ('7','COT-WEB-2026-000005',NULL,'jose gregorio','jose14chacon2003@gmail.com','04121311228',NULL,NULL,'420.00','67.20','487.20','pendiente',NULL,'Solicitada vía web por jose gregorio','2026-06-07 06:55:07',NULL,'2026-06-07 06:55:07');



-- --------------------------------------------------------
-- Estructura de tabla: encuestas_satisfaccion
-- --------------------------------------------------------
CREATE TABLE `encuestas_satisfaccion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int DEFAULT NULL,
  `pedido_numero` varchar(50) DEFAULT NULL,
  `cliente_email` varchar(255) NOT NULL,
  `cliente_nombre` varchar(255) NOT NULL,
  `puntuacion` tinyint DEFAULT NULL,
  `comentarios` text,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_respuesta` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente_email` (`cliente_email`),
  KEY `idx_pedido` (`pedido_id`),
  CONSTRAINT `encuestas_satisfaccion_chk_1` CHECK (((`puntuacion` >= 1) and (`puntuacion` <= 10)))
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: encuestas_satisfaccion
--
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('1','88','FAC-2026-000075','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-01 08:38:00',NULL,'2026-06-01 08:38:00');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('2','89','FAC-2026-000076','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-01 08:39:19',NULL,'2026-06-01 08:39:19');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('3','91','FAC-2026-000078','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-01 11:06:14',NULL,'2026-06-01 11:06:14');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('4','92','FAC-2026-000079','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-01 11:07:31',NULL,'2026-06-01 11:07:31');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('5','94','FAC-2026-000081','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 08:47:52',NULL,'2026-06-05 08:47:52');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('6','95','FAC-2026-000082','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:29:28',NULL,'2026-06-05 11:29:28');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('7','96','FAC-2026-000083','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:30:22',NULL,'2026-06-05 11:30:22');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('8','97','FAC-2026-000084','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:31:23',NULL,'2026-06-05 11:31:23');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('9','98','FAC-2026-000085','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:33:53',NULL,'2026-06-05 11:33:53');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('10','99','FAC-2026-000086','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:36:26',NULL,'2026-06-05 11:36:26');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('11','100','FAC-2026-000087','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:50:47',NULL,'2026-06-05 11:50:47');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('12','101','FAC-2026-000088','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:52:29',NULL,'2026-06-05 11:52:29');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('13','102','FAC-2026-000089','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:53:30',NULL,'2026-06-05 11:53:30');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('14','103','FAC-2026-000090','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:54:52',NULL,'2026-06-05 11:54:52');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('15','104','FAC-2026-000091','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 11:57:11',NULL,'2026-06-05 11:57:11');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('16','105','FAC-2026-000092','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 12:07:41',NULL,'2026-06-05 12:07:41');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('17','106','FAC-2026-000093','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 13:01:21',NULL,'2026-06-05 13:01:21');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('18','107','FAC-2026-000094','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 13:03:01',NULL,'2026-06-05 13:03:01');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('19','108','FAC-2026-000095','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 13:03:55',NULL,'2026-06-05 13:03:55');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('20','109','FAC-2026-000096','jose14chacon2003@gmail.com','antonio',NULL,NULL,'2026-06-05 13:05:37',NULL,'2026-06-05 13:05:37');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('21','110','FAC-2026-000097','jose14chacon2003@gmail.com','antonio','5','fluido rapido y comodo me gusta','2026-06-06 06:55:43','2026-06-06 07:01:32','2026-06-06 06:55:43');
INSERT INTO `encuestas_satisfaccion` (`id`, `pedido_id`, `pedido_numero`, `cliente_email`, `cliente_nombre`, `puntuacion`, `comentarios`, `fecha_envio`, `fecha_respuesta`, `created_at`) VALUES ('22','111','FAC-2026-000098','jose14chacon2003@gmail.com','antonio','4','genial','2026-06-06 07:38:42','2026-06-06 07:40:14','2026-06-06 07:38:42');



-- --------------------------------------------------------
-- Estructura de tabla: envios_recomendaciones
-- --------------------------------------------------------
CREATE TABLE `envios_recomendaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_email` varchar(255) NOT NULL,
  `tipo` enum('recomendacion','nuevo_producto','encuesta') NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `enviado` tinyint(1) DEFAULT '1',
  `fecha_envio` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_email`),
  KEY `idx_fecha` (`fecha_envio`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: envios_recomendaciones
--
INSERT INTO `envios_recomendaciones` (`id`, `cliente_email`, `tipo`, `asunto`, `enviado`, `fecha_envio`) VALUES ('1','jose14chacon2003@gmail.com','recomendacion','Productos recomendados para ti','1','2026-05-30 07:11:04');
INSERT INTO `envios_recomendaciones` (`id`, `cliente_email`, `tipo`, `asunto`, `enviado`, `fecha_envio`) VALUES ('2','jose14chacon2003@gmail.com','recomendacion','Productos recomendados para ti','1','2026-05-30 07:11:10');



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
  KEY `producto_id` (`producto_id`),
  KEY `idx_factura_detalles_factura_id` (`factura_id`),
  CONSTRAINT `factura_detalles_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `factura_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: factura_detalles
--
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('1','1','14','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('2','2','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('3','3','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('4','4','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('5','5','18','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('6','6','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('7','7','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('8','8','19','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('9','9','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('10','10','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('11','11','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('12','12','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('13','13','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('14','14','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('15','15','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('16','16','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('17','17','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('18','18','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('19','30','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('20','31','55','1','55.00','55.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('21','32','54','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('22','33','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('23','34','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('24','35','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('25','36','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('26','37','49','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('27','38','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('28','39','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('29','40','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('30','41','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('31','42','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('32','43','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('33','44','40','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('34','45','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('35','46','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('36','47','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('37','48','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('38','49','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('39','50','34','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('40','51','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('41','52','61','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('42','53','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('43','54','63','1','55.00','55.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('44','55','55','1','70.00','70.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('45','56','55','1','70.00','70.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('46','57','55','1','70.00','70.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('47','58','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('48','59','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('49','60','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('50','61','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('51','62','69','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('52','63','69','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('53','64','69','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('54','65','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('55','66','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('56','67','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('57','68','72','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('58','69','73','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('59','70','73','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('60','71','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('61','72','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('62','73','75','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('63','74','76','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('64','75','77','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('65','76','78','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('66','77','79','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('67','78','80','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('68','79','80','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('69','80','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('70','81','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('71','82','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('72','83','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('73','84','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('74','85','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('75','86','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('76','87','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('77','88','57','1','55.00','55.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('78','89','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('79','90','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('80','91','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('81','92','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('82','93','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('83','94','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('84','95','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('85','96','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('86','97','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('87','98','87','2','24.99','49.98');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('88','99','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('89','100','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('90','101','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('91','102','6','1','180.00','180.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('92','103','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('93','104','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('94','105','2','1','75.00','75.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('95','106','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('96','107','57','1','55.00','55.00');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('97','108','87','1','24.99','24.99');
INSERT INTO `factura_detalles` (`id`, `factura_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES ('98','109','2','1','75.00','75.00');



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
  `observaciones` text,
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
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: facturas
--
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('1',NULL,'8','FAC-2026-000001','2026-05-03 07:12:28',NULL,'21.54','3.45','24.99',NULL,'transferencia','pendiente','6','2026-05-03 07:12:28');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('2',NULL,'8','FAC-2026-000002','2026-05-03 07:31:35',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','6','2026-05-03 07:31:35');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('3',NULL,'8','FAC-2026-000003','2026-05-03 07:34:39',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','6','2026-05-03 07:34:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('4',NULL,'8','FAC-2026-000004','2026-05-03 07:41:12',NULL,'180.00','28.80','208.80',NULL,'mixto','pendiente','6','2026-05-03 07:41:12');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('5',NULL,'8','FAC-2026-000005','2026-05-03 07:48:06',NULL,'64.66','10.34','75.00',NULL,'pago_movil','pendiente','6','2026-05-03 07:48:06');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('6',NULL,'8','FAC-2026-000006','2026-05-03 10:28:43',NULL,'180.00','28.80','208.80',NULL,'mixto','pendiente','4','2026-05-03 10:28:43');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('7',NULL,'8','FAC-2026-000007','2026-05-03 10:28:45',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-03 10:28:45');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('8',NULL,'3','FAC-2026-000008','2026-05-05 09:25:06',NULL,'21.54','3.45','24.99',NULL,'pago_movil','pendiente','4','2026-05-05 09:25:06');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('9',NULL,'8','FAC-2026-000009','2026-05-09 08:38:15',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','6','2026-05-09 08:38:15');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('10',NULL,'8','FAC-2026-000010','2026-05-09 08:54:06',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','6','2026-05-09 08:54:06');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('11',NULL,'8','FAC-2026-000011','2026-05-09 09:18:48',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','6','2026-05-09 09:18:48');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('12',NULL,'8','FAC-2026-000012','2026-05-09 09:31:27',NULL,'180.00','28.80','208.80',NULL,'efectivo','pagada','6','2026-05-09 09:31:27');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('13',NULL,'8','FAC-2026-000013','2026-05-09 09:31:28',NULL,'180.00','28.80','208.80',NULL,'efectivo','pagada','6','2026-05-09 09:31:28');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('14',NULL,'8','FAC-2026-000014','2026-05-09 09:31:30',NULL,'180.00','28.80','208.80',NULL,'efectivo','pagada','6','2026-05-09 09:31:30');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('15',NULL,'8','FAC-2026-000015','2026-05-09 09:56:42',NULL,'24.99','4.00','28.99',NULL,'efectivo','pagada','6','2026-05-09 09:56:42');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('16',NULL,'8','FAC-2026-000016','2026-05-09 09:56:44',NULL,'24.99','4.00','28.99',NULL,'efectivo','pagada','6','2026-05-09 09:56:44');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('17',NULL,'8','FAC-2026-000017','2026-05-09 10:27:11',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','6','2026-05-09 10:27:11');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('18',NULL,'8','FAC-2026-000018','2026-05-09 10:31:19',NULL,'75.00','12.00','87.00',NULL,'efectivo','pagada','6','2026-05-09 10:31:19');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('30','25','8','FAC-2026-000019','2026-05-12 00:00:00','2026-06-11','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-12 09:22:00');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('31','45','8','FAC-2026-000020','2026-05-24 00:00:00','2026-06-23','47.41','7.59','55.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:23');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('32','44','8','FAC-2026-000021','2026-05-24 00:00:00','2026-06-23','21.54','3.45','24.99',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('33','43','8','FAC-2026-000022','2026-05-24 00:00:00','2026-06-23','24.99','4.00','28.99',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('34','42','8','FAC-2026-000023','2026-05-24 00:00:00','2026-06-23','24.99','4.00','28.99',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('35','41','8','FAC-2026-000024','2026-05-24 00:00:00','2026-06-23','24.99','4.00','28.99',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('36','40','8','FAC-2026-000025','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('37','39','8','FAC-2026-000026','2026-05-24 00:00:00','2026-06-23','21.54','3.45','24.99',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('38','38','8','FAC-2026-000027','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('39','37','8','FAC-2026-000028','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('40','36','8','FAC-2026-000029','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('41','35','8','FAC-2026-000030','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('42','34','8','FAC-2026-000031','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('43','33','8','FAC-2026-000032','2026-05-24 00:00:00','2026-06-23','24.99','4.00','28.99',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('44','32','8','FAC-2026-000033','2026-05-24 00:00:00','2026-06-23','64.66','10.34','75.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('45','31','8','FAC-2026-000034','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('46','30','8','FAC-2026-000035','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('47','29','8','FAC-2026-000036','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('48','28','8','FAC-2026-000037','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('49','27','8','FAC-2026-000038','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('50','26','8','FAC-2026-000039','2026-05-24 00:00:00','2026-06-23','64.66','10.34','75.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('51','24','8','FAC-2026-000040','2026-05-24 00:00:00','2026-06-23','75.00','12.00','87.00',NULL,'efectivo','pagada','4','2026-05-24 10:14:39');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('52','51','8','FAC-2026-000041','2026-05-25 00:00:00','2026-06-24','21.54','3.45','24.99',NULL,'efectivo','pagada','4','2026-05-25 09:57:25');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('53','52','8','FAC-2026-000042','2026-05-25 00:00:00','2026-06-24','24.99','4.00','28.99',NULL,'efectivo','pagada','4','2026-05-25 09:59:16');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('54','53','8','FAC-2026-000043','2026-05-25 00:00:00','2026-06-24','47.41','7.59','55.00','Pedido por transferencia - Referencia: 1234567895','transferencia','pendiente','6','2026-05-25 10:09:06');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('55','54','8','FAC-2026-000044','2026-05-25 00:00:00','2026-06-24','70.00','11.20','81.20','Pedido por efectivo','efectivo','pagada','6','2026-05-25 10:15:52');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('56','55','8','FAC-2026-000045','2026-05-25 00:00:00','2026-06-24','70.00','11.20','81.20','Pedido por efectivo','efectivo','pagada','6','2026-05-25 10:15:56');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('57','56','8','FAC-2026-000046','2026-05-25 00:00:00','2026-06-24','70.00','11.20','81.20','Pedido por efectivo','efectivo','pagada','6','2026-05-25 10:15:59');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('58','57','8','FAC-2026-000047','2026-05-25 00:00:00','2026-06-24','180.00','28.80','208.80','Pedido por efectivo','efectivo','pagada','6','2026-05-25 10:43:46');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('59','58','8','FAC-2026-000048','2026-05-25 00:00:00','2026-06-24','24.99','4.00','28.99','Pedido por efectivo','efectivo','pagada','6','2026-05-25 10:47:20');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('60','59','8','FAC-2026-000049','2026-05-25 00:00:00','2026-06-24','24.99','4.00','28.99','Pedido por efectivo','efectivo','pagada','6','2026-05-25 11:23:30');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('61','60','8','FAC-2026-000050','2026-05-27 00:00:00','2026-06-26','75.00','12.00','87.00','Pedido por efectivo','efectivo','pendiente','6','2026-05-27 09:00:12');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('62','61','8','FAC-2026-000051','2026-05-27 00:00:00','2026-06-26','21.54','3.45','24.99','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-27 14:00:31');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('63','62','8','FAC-2026-000052','2026-05-27 00:00:00','2026-06-26','21.54','3.45','24.99','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-27 14:01:17');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('64','63','8','FAC-2026-000053','2026-05-27 00:00:00','2026-06-26','21.54','3.45','24.99','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-27 14:01:31');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('65','64','8','FAC-2026-000054','2026-05-27 00:00:00','2026-06-26','24.99','4.00','28.99','Pedido por efectivo','efectivo','pendiente','6','2026-05-27 14:02:22');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('66','65','8','FAC-2026-000055','2026-05-27 00:00:00','2026-06-26','180.00','28.80','208.80','Pedido por efectivo','efectivo','pendiente','6','2026-05-27 14:23:12');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('67','66','8','FAC-2026-000056','2026-05-27 00:00:00','2026-06-26','24.99','4.00','28.99','Pedido por efectivo','efectivo','pendiente','6','2026-05-27 16:27:44');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('68','67','8','FAC-2026-000057','2026-05-27 00:00:00','2026-06-26','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-27 16:33:35');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('69','68','8','FAC-2026-000058','2026-05-27 00:00:00','2026-06-26','21.54','3.45','24.99','Pedido por transferencia - Referencia: 123456789','transferencia','pendiente','6','2026-05-27 16:36:32');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('70','69','8','FAC-2026-000059','2026-05-27 00:00:00','2026-06-26','21.54','3.45','24.99','Pedido por transferencia - Referencia: 123456789','transferencia','pendiente','6','2026-05-27 16:41:03');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('71','70','8','FAC-2026-000060','2026-05-28 00:00:00','2026-06-27','180.00','28.80','208.80','Pedido por efectivo','efectivo','pendiente','6','2026-05-28 09:42:15');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('72','71','8','FAC-2026-000061','2026-05-28 00:00:00','2026-06-27','180.00','28.80','208.80','Pedido por efectivo','efectivo','pendiente','6','2026-05-28 09:56:32');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('73','72','8','FAC-2026-000062','2026-05-31 00:00:00','2026-06-30','64.66','10.34','75.00','Pedido por transferencia - Referencia: 1234567890','transferencia','pendiente','6','2026-05-31 07:27:11');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('74','73','8','FAC-2026-000063','2026-05-31 00:00:00','2026-06-30','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-31 07:29:16');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('75','74','8','FAC-2026-000064','2026-05-31 00:00:00','2026-06-30','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-31 07:48:00');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('76','75','8','FAC-2026-000065','2026-05-31 00:00:00','2026-06-30','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-31 07:51:20');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('77','76','8','FAC-2026-000066','2026-05-31 00:00:00','2026-06-30','21.54','3.45','24.99','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-31 07:52:14');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('78','77','8','FAC-2026-000067','2026-05-31 00:00:00','2026-06-30','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-31 08:04:10');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('79','78','8','FAC-2026-000068','2026-05-31 00:00:00','2026-06-30','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-05-31 08:15:56');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('80','82','8','FAC-2026-000069','2026-05-31 00:00:00','2026-06-30','24.99','4.00','28.99','Pedido por efectivo','efectivo','pendiente','6','2026-05-31 08:22:16');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('81','83','8','FAC-2026-000070','2026-05-31 00:00:00','2026-06-30','75.00','12.00','87.00','Pedido por mixto','mixto','pendiente','6','2026-05-31 08:33:50');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('82','84','8','FAC-2026-000071','2026-05-31 00:00:00','2026-06-30','24.99','4.00','28.99','Pedido por mixto','mixto','pendiente','6','2026-05-31 08:38:03');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('83','85','8','FAC-2026-000072','2026-06-01 00:00:00','2026-07-01','64.66','10.34','75.00','Pedido por transferencia - Referencia: 1234567890','transferencia','pendiente','6','2026-06-01 08:23:02');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('84','86','8','FAC-2026-000073','2026-06-01 00:00:00','2026-07-01','64.66','10.34','75.00','Pedido por transferencia - Referencia: 1234567890','transferencia','pendiente','6','2026-06-01 08:23:19');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('85','87','8','FAC-2026-000074','2026-06-01 00:00:00','2026-07-01','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-06-01 08:25:42');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('86','88','8','FAC-2026-000075','2026-06-01 00:00:00','2026-07-01','21.54','3.45','24.99','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pendiente','6','2026-06-01 08:37:57');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('87','89','8','FAC-2026-000076','2026-06-01 00:00:00','2026-07-01','21.54','3.45','24.99','Pedido por transferencia - Referencia: 1234567890','transferencia','pendiente','6','2026-06-01 08:39:15');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('88','90','8','FAC-2026-000077','2026-06-01 00:00:00','2026-07-01','55.00','8.80','63.80','Pedido por efectivo','efectivo','pendiente','6','2026-06-01 08:41:23');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('89','91','8','FAC-2026-000078','2026-06-01 00:00:00','2026-07-01','64.66','10.34','75.00','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-01 11:06:10');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('90','92','8','FAC-2026-000079','2026-06-01 00:00:00','2026-07-01','21.54','3.45','24.99','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pagada','6','2026-06-01 11:07:28');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('91','93','8','FAC-2026-000080','2026-06-04 00:00:00','2026-07-04','75.00','12.00','87.00','Pedido por efectivo','efectivo','pendiente','6','2026-06-04 11:14:10');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('92','94','8','FAC-2026-000081','2026-06-05 00:00:00','2026-07-05','21.54','3.45','24.99','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-05 08:47:46');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('93','95','8','FAC-2026-000082','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-05 11:29:26');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('94','96','8','FAC-2026-000083','2026-06-05 00:00:00','2026-07-05','155.17','24.83','180.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pagada','6','2026-06-05 11:30:19');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('95','97','8','FAC-2026-000084','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pagada','6','2026-06-05 11:31:21');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('96','98','8','FAC-2026-000085','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-05 11:33:50');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('97','99','8','FAC-2026-000086','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pagada','6','2026-06-05 11:36:24');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('98','100','8','FAC-2026-000087','2026-06-05 00:00:00','2026-07-05','43.09','6.89','49.98','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-05 11:50:43');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('99','101','8','FAC-2026-000088','2026-06-05 00:00:00','2026-07-05','21.54','3.45','24.99','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-05 11:52:26');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('100','102','8','FAC-2026-000089','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pagada','6','2026-06-05 11:53:27');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('101','103','8','FAC-2026-000090','2026-06-05 00:00:00','2026-07-05','75.00','12.00','87.00','Pedido por efectivo','efectivo','pendiente','6','2026-06-05 11:54:49');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('102','104','8','FAC-2026-000091','2026-06-05 00:00:00','2026-07-05','180.00','28.80','208.80','Pedido por mixto','mixto','pendiente','6','2026-06-05 11:57:08');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('103','105','8','FAC-2026-000092','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pagada','6','2026-06-05 12:07:37');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('104','106','8','FAC-2026-000093','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-05 13:01:17');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('105','107','8','FAC-2026-000094','2026-06-05 00:00:00','2026-07-05','64.66','10.34','75.00','Pedido por pago_movil - Referencia: 1234567890','pago_movil','pagada','6','2026-06-05 13:02:57');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('106','108','8','FAC-2026-000095','2026-06-05 00:00:00','2026-07-05','24.99','4.00','28.99','Pedido por efectivo','efectivo','pendiente','6','2026-06-05 13:03:51');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('107','109','8','FAC-2026-000096','2026-06-05 00:00:00','2026-07-05','55.00','8.80','63.80','Pedido por mixto','mixto','pendiente','6','2026-06-05 13:05:33');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('108','110','8','FAC-2026-000097','2026-06-06 00:00:00','2026-07-06','21.54','3.45','24.99','Pedido por transferencia - Referencia: 1234567890','transferencia','pagada','6','2026-06-06 06:55:35');
INSERT INTO `facturas` (`id`, `pedido_id`, `cliente_id`, `numero_factura`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `iva`, `total`, `observaciones`, `metodo_pago`, `estado`, `usuario_id`, `created_at`) VALUES ('109','111','8','FAC-2026-000098','2026-06-06 00:00:00','2026-07-06','75.00','12.00','87.00','Pedido por efectivo','efectivo','pendiente','6','2026-06-06 07:38:31');



-- --------------------------------------------------------
-- Estructura de tabla: favoritos
-- --------------------------------------------------------
CREATE TABLE `favoritos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorito` (`usuario_id`,`producto_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



-- --------------------------------------------------------
-- Estructura de tabla: formulas_tecnicas
-- --------------------------------------------------------
CREATE TABLE `formulas_tecnicas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_equipo` varchar(100) NOT NULL,
  `parametros_entrada` json NOT NULL COMMENT 'Ej: {"hp":"HP","voltaje":"V","fases":"#","distancia":"m"}',
  `formulas` json NOT NULL COMMENT 'Ej: {"corriente_nominal":"(hp*746)/(V*factor_potencia*eficiencia)","breaker":"I_nominal*1.25"}',
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: formulas_tecnicas
--
INSERT INTO `formulas_tecnicas` (`id`, `tipo_equipo`, `parametros_entrada`, `formulas`, `notas`, `created_at`) VALUES ('1','Motor Trifásico','{\"hp\": \"HP del motor\", \"voltaje\": \"Voltaje (V)\", \"distancia\": \"Distancia al motor (m)\", \"eficiencia\": \"Eficiencia (0.85-0.95)\", \"factor_potencia\": \"Factor de potencia (0.8-0.95)\"}','{\"breaker\": \"corriente_nominal * 1.25\", \"cable_mm2_50m\": \"calcular_cable(corriente_nominal, distancia, 50)\", \"contactor_ac1\": \"corriente_nominal * 1.15\", \"contactor_ac3\": \"corriente_nominal * 1.5\", \"cable_mm2_100m\": \"calcular_cable(corriente_nominal, distancia, 100)\", \"cable_mm2_150m\": \"calcular_cable(corriente_nominal, distancia, 150)\", \"rele_termico_max\": \"corriente_nominal * 1.15\", \"rele_termico_min\": \"corriente_nominal * 0.95\", \"corriente_nominal\": \"(hp * 746) / (voltaje * 1.732 * factor_potencia * eficiencia)\", \"corriente_arranque\": \"corriente_nominal * 6\"}','Fórmulas estándar IEEE/NFPA para selección de protecciones de motores trifásicos. Considerar curva de disparo del breaker tipo D para motores.','2026-05-20 09:20:47');
INSERT INTO `formulas_tecnicas` (`id`, `tipo_equipo`, `parametros_entrada`, `formulas`, `notas`, `created_at`) VALUES ('2','Motor Monofásico','{\"hp\": \"HP del motor\", \"voltaje\": \"Voltaje (V)\", \"distancia\": \"Distancia al motor (m)\", \"eficiencia\": \"Eficiencia (0.75-0.85)\", \"factor_potencia\": \"Factor de potencia (0.7-0.9)\"}','{\"breaker\": \"corriente_nominal * 1.25\", \"cable_mm2\": \"calcular_cable(corriente_nominal, distancia, 50)\", \"corriente_nominal\": \"(hp * 746) / (voltaje * factor_potencia * eficiencia)\", \"corriente_arranque\": \"corriente_nominal * 5\"}','Fórmulas NEMA para motores monofásicos con capacitor de arranque.','2026-05-20 09:20:47');
INSERT INTO `formulas_tecnicas` (`id`, `tipo_equipo`, `parametros_entrada`, `formulas`, `notas`, `created_at`) VALUES ('3','Carga Resistiva (Alumbrado/Calefacción)','{\"fases\": \"Número de fases (1/3)\", \"voltaje\": \"Voltaje (V)\", \"distancia\": \"Distancia (m)\", \"potencia_w\": \"Potencia en Watts (W)\"}','{\"breaker\": \"corriente_nominal * 1.20\", \"cable_mm2\": \"calcular_cable(corriente_nominal, distancia, 50)\", \"corriente_nominal\": \"potencia_w / (voltaje * (1 si fases=1 else 1.732))\"}','Para cargas resistivas (factor de potencia = 1). Breaker tipo C recomendado.','2026-05-20 09:20:47');
INSERT INTO `formulas_tecnicas` (`id`, `tipo_equipo`, `parametros_entrada`, `formulas`, `notas`, `created_at`) VALUES ('4','Variador de Frecuencia (VFD)','{\"hp\": \"HP del motor\", \"fases\": \"Fases de entrada (1/3)\", \"voltaje\": \"Voltaje (V)\"}','{\"cable_motor\": \"calcular_cable(corriente_nominal, 50, 75)\", \"cable_entrada\": \"calcular_cable(corriente_nominal, 30, 50)\", \"breaker_entrada\": \"corriente_nominal * 1.25\", \"vfd_recomendado\": \"corriente_nominal * 1.25\", \"corriente_nominal\": \"(hp * 746) / (voltaje * 1.732 * 0.85 * 0.9)\"}','Selección de VFD: sobredimensionar 25% sobre corriente nominal. Incluir reactor de línea para armónicos.','2026-05-20 09:20:47');



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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: historial_stock
--
INSERT INTO `historial_stock` (`id`, `producto_id`, `usuario_id`, `cantidad`, `stock_anterior`, `stock_nuevo`, `tipo`, `referencia`, `fecha`) VALUES ('3','5','4','1','18','19','compra','ORD-20260502-4827','2026-05-02 15:58:58');
INSERT INTO `historial_stock` (`id`, `producto_id`, `usuario_id`, `cantidad`, `stock_anterior`, `stock_nuevo`, `tipo`, `referencia`, `fecha`) VALUES ('4','8','4','1','28','29','compra','ORD-20260512-6619','2026-05-12 08:16:26');
INSERT INTO `historial_stock` (`id`, `producto_id`, `usuario_id`, `cantidad`, `stock_anterior`, `stock_nuevo`, `tipo`, `referencia`, `fecha`) VALUES ('5','6','4','1','30','31','compra','ORD-20260519-5451','2026-05-19 14:17:40');



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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: movimientos_inventario
--
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `referencia`, `usuario_id`, `fecha_movimiento`) VALUES ('1','5','entrada','1','Compra a proveedor - Orden: ORD-20260502-4827','ORD-20260502-4827','4','2026-05-02 15:58:58');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `referencia`, `usuario_id`, `fecha_movimiento`) VALUES ('2','8','entrada','1','Compra a proveedor - Orden: ORD-20260512-6619','ORD-20260512-6619','4','2026-05-12 08:16:26');
INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo_movimiento`, `cantidad`, `descripcion`, `referencia`, `usuario_id`, `fecha_movimiento`) VALUES ('3','6','entrada','1','Compra a proveedor - Orden: ORD-20260519-5451','ORD-20260519-5451','4','2026-05-19 14:17:40');



-- --------------------------------------------------------
-- Estructura de tabla: notificaciones
-- --------------------------------------------------------
CREATE TABLE `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` varchar(50) DEFAULT 'pedido',
  `referencia_id` int DEFAULT NULL,
  `leida` tinyint(1) DEFAULT '0',
  `creada_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



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
  KEY `idx_pedido_detalles_pedido_id` (`pedido_id`),
  KEY `idx_pedido_detalles_producto_id` (`producto_id`),
  CONSTRAINT `pedido_detalles_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedido_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: pedido_detalles
--
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('24','24','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('25','25','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('26','26','34','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('27','27','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('28','28','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('29','29','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('30','30','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('31','31','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('32','32','40','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('33','33','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('34','34','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('35','35','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('36','36','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('37','37','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('38','38','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('39','39','49','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('40','40','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('41','41','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('42','42','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('43','43','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('44','44','54','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('45','45','55','1','55.00','55.00','55.00','Caja para pulsadores 2 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('46','46','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('47','47','57','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('48','48','58','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('49','49','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('50','50','60','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('51','51','61','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('52','52','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('53','53','63','1','55.00','55.00','55.00','Caja para pulsadores 2 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('54','54','55','1','70.00','0.00','70.00','Caja para pulsadores plastica 3 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('55','55','55','1','70.00','0.00','70.00','Caja para pulsadores plastica 3 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('56','56','55','1','70.00','0.00','70.00','Caja para pulsadores plastica 3 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('57','57','6','1','180.00','0.00','180.00','Botonera colgante',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('58','58','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('59','59','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('60','60','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('61','61','69','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('62','62','69','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('63','63','69','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('64','64','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('65','65','6','1','180.00','0.00','180.00','Botonera colgante',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('66','66','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('67','67','72','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('68','68','73','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('69','69','73','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('70','70','6','1','180.00','0.00','180.00','Botonera colgante',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('71','71','6','1','180.00','0.00','180.00','Botonera colgante',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('72','72','75','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('73','73','76','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('74','74','77','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('75','75','78','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('76','76','79','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('77','77','80','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('78','78','80','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('82','82','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('83','83','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('84','84','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('85','85','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('86','86','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('87','87','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('88','88','87','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('89','89','87','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('90','90','57','1','55.00','0.00','55.00','Caja para pulsadores 2 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('91','91','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('92','92','87','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('93','93','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('94','94','87','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('95','95','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('96','96','6','1','180.00','180.00','180.00','Botonera colgante',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('97','97','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('98','98','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('99','99','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('100','100','87','2','24.99','24.99','49.98','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('101','101','87','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('102','102','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('103','103','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('104','104','6','1','180.00','0.00','180.00','Botonera colgante',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('105','105','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('106','106','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('107','107','2','1','75.00','75.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('108','108','87','1','24.99','0.00','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('109','109','57','1','55.00','0.00','55.00','Caja para pulsadores 2 huecos',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('110','110','87','1','24.99','24.99','24.99','AT8N',NULL,NULL);
INSERT INTO `pedido_detalles` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_original`, `subtotal`, `producto_nombre`, `producto_sku`, `producto_categoria`) VALUES ('111','111','2','1','75.00','0.00','75.00','Boton pulsador Autonics Nc S3pf-p1rb',NULL,NULL);



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
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: pedidos
--
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('24','8','6','PED-2026-000001','2026-05-12 09:13:40','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-12 09:13:40','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('25','8','6','PED-2026-000002','2026-05-12 09:14:08','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-12 09:22:00',NULL,'2026-05-12 09:14:08','2026-05-12 09:22:00');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('26',NULL,'6','PED-20260512-1003','2026-05-12 09:34:18','64.66','0.00','10.34','75.00','facturado','pago_movil','1578966',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1578966','2026-05-24 10:14:39',NULL,'2026-05-12 09:34:18','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('27','8','6','PED-2026-000003','2026-05-12 09:54:33','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-12 09:54:33','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('28','8','6','PED-2026-000004','2026-05-12 09:59:52','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-12 09:59:52','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('29','8','6','PED-2026-000005','2026-05-12 10:17:40','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-12 10:17:40','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('30','8','6','PED-2026-000006','2026-05-12 10:17:59','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-12 10:17:59','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('31','8','6','PED-2026-000007','2026-05-12 11:14:57','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-12 11:14:57','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('32',NULL,'6','PED-20260512-6610','2026-05-12 11:49:45','64.66','0.00','10.34','75.00','facturado','pago_movil','123456789',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 123456789','2026-05-24 10:14:39',NULL,'2026-05-12 11:49:45','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('33','8','6','PED-2026-000008','2026-05-14 08:40:23','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-14 08:40:23','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('34','8','6','PED-2026-000009','2026-05-14 08:51:36','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-14 08:51:36','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('35','8','6','PED-2026-000010','2026-05-14 08:59:30','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-14 08:59:30','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('36','8','6','PED-2026-000011','2026-05-14 09:14:21','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-14 09:14:21','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('37','8','6','PED-2026-000012','2026-05-14 09:19:18','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-14 09:19:18','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('38','8','6','PED-2026-000013','2026-05-18 09:22:49','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-18 09:22:49','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('39',NULL,'6','PED-20260518-4028','2026-05-18 09:23:39','21.54','0.00','3.45','24.99','facturado','pago_movil','123055',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 123055','2026-05-24 10:14:39',NULL,'2026-05-18 09:23:39','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('40','8','6','PED-2026-000014','2026-05-24 08:05:10','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-24 08:05:10','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('41','8','6','PED-2026-000015','2026-05-24 08:06:13','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-24 08:06:13','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('42','8','6','PED-2026-000016','2026-05-24 08:08:15','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-24 10:14:39',NULL,'2026-05-24 08:08:15','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('43','8','6','PED-2026-000017','2026-05-24 08:09:23','24.99','0.00','4.00','28.99','facturado','mixto',NULL,NULL,NULL,NULL,'Pedido por mixto','2026-05-24 10:14:39',NULL,'2026-05-24 08:09:23','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('44',NULL,'6','PED-20260524-0762','2026-05-24 08:17:05','21.54','0.00','3.45','24.99','facturado','transferencia','1234567895',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567895','2026-05-24 10:14:39',NULL,'2026-05-24 08:17:05','2026-05-24 10:14:39');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('45',NULL,'6','PED-20260524-9164','2026-05-24 08:17:28','47.41','0.00','7.59','55.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-24 10:14:23',NULL,'2026-05-24 08:17:28','2026-05-24 10:14:23');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('46','8','6','PED-2026-000018','2026-05-25 08:43:09','75.00','0.00','12.00','87.00','pendiente','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo',NULL,NULL,'2026-05-25 08:43:09','2026-05-25 08:43:09');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('47',NULL,'6','PED-20260525-0694','2026-05-25 08:53:23','21.54','0.00','3.45','24.99','pendiente','transferencia','123456789',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 123456789',NULL,NULL,'2026-05-25 08:53:23','2026-05-25 08:53:23');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('48',NULL,'6','PED-20260525-6785','2026-05-25 08:58:11','21.54','0.00','3.45','24.99','pendiente','pago_movil','1234567895',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567895',NULL,NULL,'2026-05-25 08:58:11','2026-05-25 08:58:11');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('49','8','6','PED-2026-000019','2026-05-25 08:59:04','24.99','0.00','4.00','28.99','pendiente','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo',NULL,NULL,'2026-05-25 08:59:04','2026-05-25 08:59:04');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('50',NULL,'6','PED-20260525-7835','2026-05-25 09:18:35','64.66','0.00','10.34','75.00','pendiente','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890',NULL,NULL,'2026-05-25 09:18:35','2026-05-25 09:18:35');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('51',NULL,'6','PED-20260525-6455','2026-05-25 09:47:59','21.54','0.00','3.45','24.99','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-25 09:57:25',NULL,'2026-05-25 09:47:59','2026-05-25 09:57:25');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('52','8','6','PED-2026-000020','2026-05-25 09:58:37','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-25 09:59:16',NULL,'2026-05-25 09:58:37','2026-05-25 09:59:16');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('53',NULL,'6','PED-20260525-9801','2026-05-25 10:09:06','47.41','0.00','7.59','55.00','pendiente','transferencia','1234567895',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567895',NULL,NULL,'2026-05-25 10:09:06','2026-05-25 10:09:06');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('54','8','6','PED-2026-000021','2026-05-25 10:15:52','70.00','0.00','11.20','81.20','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-25 10:15:52',NULL,'2026-05-25 10:15:52','2026-05-25 10:15:52');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('55','8','6','PED-2026-000022','2026-05-25 10:15:56','70.00','0.00','11.20','81.20','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-25 10:15:56',NULL,'2026-05-25 10:15:56','2026-05-25 10:15:56');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('56','8','6','PED-2026-000023','2026-05-25 10:15:59','70.00','0.00','11.20','81.20','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-25 10:15:59',NULL,'2026-05-25 10:15:59','2026-05-25 10:15:59');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('57','8','6','PED-2026-000024','2026-05-25 10:43:46','180.00','0.00','28.80','208.80','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-25 10:43:46',NULL,'2026-05-25 10:43:46','2026-05-25 10:43:46');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('58','8','6','PED-2026-000025','2026-05-25 10:47:20','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-25 10:47:20',NULL,'2026-05-25 10:47:20','2026-05-25 10:47:20');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('59','8','6','PED-2026-000026','2026-05-25 11:23:30','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-25 11:23:30',NULL,'2026-05-25 11:23:30','2026-05-25 11:23:30');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('60','8','6','PED-2026-000027','2026-05-27 09:00:12','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-27 09:00:12',NULL,'2026-05-27 09:00:12','2026-05-27 09:00:12');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('61',NULL,'6','PED-20260527-5524','2026-05-27 14:00:31','21.54','0.00','3.45','24.99','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-27 14:00:31',NULL,'2026-05-27 14:00:31','2026-05-27 14:00:31');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('62',NULL,'6','PED-20260527-5980','2026-05-27 14:01:17','21.54','0.00','3.45','24.99','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-27 14:01:17',NULL,'2026-05-27 14:01:17','2026-05-27 14:01:17');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('63',NULL,'6','PED-20260527-6936','2026-05-27 14:01:31','21.54','0.00','3.45','24.99','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-27 14:01:31',NULL,'2026-05-27 14:01:31','2026-05-27 14:01:31');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('64','8','6','PED-2026-000028','2026-05-27 14:02:22','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-27 14:02:22',NULL,'2026-05-27 14:02:22','2026-05-27 14:02:22');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('65','8','6','PED-2026-000029','2026-05-27 14:23:12','180.00','0.00','28.80','208.80','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-27 14:23:12',NULL,'2026-05-27 14:23:12','2026-05-27 14:23:12');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('66','8','6','PED-2026-000030','2026-05-27 16:27:44','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-27 16:27:44',NULL,'2026-05-27 16:27:44','2026-05-27 16:27:44');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('67',NULL,'6','PED-20260527-1413','2026-05-27 16:33:35','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-27 16:33:35',NULL,'2026-05-27 16:33:35','2026-05-27 16:33:35');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('68',NULL,'6','PED-20260527-2782','2026-05-27 16:36:32','21.54','0.00','3.45','24.99','facturado','transferencia','123456789',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 123456789','2026-05-27 16:36:32',NULL,'2026-05-27 16:36:32','2026-05-27 16:36:32');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('69',NULL,'6','PED-20260527-0625','2026-05-27 16:41:03','21.54','0.00','3.45','24.99','facturado','transferencia','123456789',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 123456789','2026-05-27 16:41:03',NULL,'2026-05-27 16:41:03','2026-05-27 16:41:03');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('70','8','6','PED-2026-000031','2026-05-28 09:42:15','180.00','0.00','28.80','208.80','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-28 09:42:15',NULL,'2026-05-28 09:42:15','2026-05-28 09:42:15');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('71','8','6','PED-2026-000032','2026-05-28 09:56:32','180.00','0.00','28.80','208.80','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-28 09:56:32',NULL,'2026-05-28 09:56:32','2026-05-28 09:56:32');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('72',NULL,'6','PED-20260531-4909','2026-05-31 07:27:11','64.66','0.00','10.34','75.00','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-05-31 07:27:11',NULL,'2026-05-31 07:27:11','2026-05-31 07:27:11');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('73',NULL,'6','PED-20260531-1008','2026-05-31 07:29:16','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-31 07:29:16',NULL,'2026-05-31 07:29:16','2026-05-31 07:29:16');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('74',NULL,'6','PED-20260531-9837','2026-05-31 07:48:00','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-31 07:48:00',NULL,'2026-05-31 07:48:00','2026-05-31 07:48:00');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('75',NULL,'6','PED-20260531-2397','2026-05-31 07:51:20','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-31 07:51:20',NULL,'2026-05-31 07:51:20','2026-05-31 07:51:20');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('76',NULL,'6','PED-20260531-9286','2026-05-31 07:52:14','21.54','0.00','3.45','24.99','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-31 07:52:14',NULL,'2026-05-31 07:52:14','2026-05-31 07:52:14');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('77',NULL,'6','PED-20260531-0802','2026-05-31 08:04:10','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-31 08:04:10',NULL,'2026-05-31 08:04:10','2026-05-31 08:04:10');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('78',NULL,'6','PED-20260531-3964','2026-05-31 08:15:56','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-05-31 08:15:56',NULL,'2026-05-31 08:15:56','2026-05-31 08:15:56');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('82','8','6','PED-2026-000033','2026-05-31 08:22:16','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-05-31 08:22:16',NULL,'2026-05-31 08:22:16','2026-05-31 08:22:16');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('83','8','6','PED-2026-000034','2026-05-31 08:33:49','75.00','0.00','12.00','87.00','facturado','mixto',NULL,NULL,NULL,NULL,'Pedido por mixto','2026-05-31 08:33:50',NULL,'2026-05-31 08:33:49','2026-05-31 08:33:50');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('84','8','6','PED-2026-000035','2026-05-31 08:38:03','24.99','0.00','4.00','28.99','facturado','mixto',NULL,NULL,NULL,NULL,'Pedido por mixto','2026-05-31 08:38:03',NULL,'2026-05-31 08:38:03','2026-05-31 08:38:03');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('85',NULL,'6','PED-20260601-0925','2026-06-01 08:23:02','64.66','0.00','10.34','75.00','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-01 08:23:02',NULL,'2026-06-01 08:23:02','2026-06-01 08:23:02');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('86',NULL,'6','PED-20260601-4440','2026-06-01 08:23:19','64.66','0.00','10.34','75.00','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-01 08:23:19',NULL,'2026-06-01 08:23:19','2026-06-01 08:23:19');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('87',NULL,'6','PED-20260601-8311','2026-06-01 08:25:42','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-01 08:25:42',NULL,'2026-06-01 08:25:42','2026-06-01 08:25:42');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('88',NULL,'6','PED-20260601-8072','2026-06-01 08:37:57','21.54','0.00','3.45','24.99','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-01 08:37:57',NULL,'2026-06-01 08:37:57','2026-06-01 08:37:57');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('89',NULL,'6','PED-20260601-9449','2026-06-01 08:39:15','21.54','0.00','3.45','24.99','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-01 08:39:15',NULL,'2026-06-01 08:39:15','2026-06-01 08:39:15');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('90','8','6','PED-2026-000036','2026-06-01 08:41:23','55.00','0.00','8.80','63.80','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-06-01 08:41:23',NULL,'2026-06-01 08:41:23','2026-06-01 08:41:23');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('91',NULL,'6','PED-20260601-1790','2026-06-01 11:06:10','64.66','0.00','10.34','75.00','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-01 11:06:10',NULL,'2026-06-01 11:06:10','2026-06-01 11:06:10');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('92',NULL,'6','PED-20260601-2295','2026-06-01 11:07:28','21.54','0.00','3.45','24.99','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-01 11:07:28',NULL,'2026-06-01 11:07:28','2026-06-01 11:07:28');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('93','8','6','PED-2026-000037','2026-06-04 11:14:10','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-06-04 11:14:10',NULL,'2026-06-04 11:14:10','2026-06-04 11:14:10');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('94',NULL,'6','PED-20260605-5385','2026-06-05 08:47:46','21.54','0.00','3.45','24.99','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-05 08:47:46',NULL,'2026-06-05 08:47:46','2026-06-05 08:47:46');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('95',NULL,'6','PED-20260605-1375','2026-06-05 11:29:26','64.66','0.00','10.34','75.00','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-05 11:29:26',NULL,'2026-06-05 11:29:26','2026-06-05 11:29:26');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('96',NULL,'6','PED-20260605-1804','2026-06-05 11:30:19','155.17','0.00','24.83','180.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-05 11:30:19',NULL,'2026-06-05 11:30:19','2026-06-05 11:30:19');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('97',NULL,'6','PED-20260605-4876','2026-06-05 11:31:20','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-05 11:31:21',NULL,'2026-06-05 11:31:20','2026-06-05 11:31:21');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('98',NULL,'6','PED-20260605-0160','2026-06-05 11:33:50','64.66','0.00','10.34','75.00','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-05 11:33:50',NULL,'2026-06-05 11:33:50','2026-06-05 11:33:50');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('99',NULL,'6','PED-20260605-8594','2026-06-05 11:36:24','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-05 11:36:24',NULL,'2026-06-05 11:36:24','2026-06-05 11:36:24');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('100',NULL,'6','PED-20260605-8144','2026-06-05 11:50:43','43.09','0.00','6.89','49.98','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-05 11:50:43',NULL,'2026-06-05 11:50:43','2026-06-05 11:50:43');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('101',NULL,'6','PED-20260605-6840','2026-06-05 11:52:26','21.54','0.00','3.45','24.99','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-05 11:52:26',NULL,'2026-06-05 11:52:26','2026-06-05 11:52:26');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('102',NULL,'6','PED-20260605-6752','2026-06-05 11:53:27','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-05 11:53:27',NULL,'2026-06-05 11:53:27','2026-06-05 11:53:27');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('103','8','6','PED-2026-000038','2026-06-05 11:54:48','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-06-05 11:54:49',NULL,'2026-06-05 11:54:48','2026-06-05 11:54:49');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('104','8','6','PED-2026-000039','2026-06-05 11:57:08','180.00','0.00','28.80','208.80','facturado','mixto',NULL,NULL,NULL,NULL,'Pedido por mixto','2026-06-05 11:57:08',NULL,'2026-06-05 11:57:08','2026-06-05 11:57:08');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('105',NULL,'6','PED-20260605-5306','2026-06-05 12:07:37','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-05 12:07:37',NULL,'2026-06-05 12:07:37','2026-06-05 12:07:37');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('106',NULL,'6','PED-20260605-5624','2026-06-05 13:01:17','64.66','0.00','10.34','75.00','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-05 13:01:17',NULL,'2026-06-05 13:01:17','2026-06-05 13:01:17');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('107',NULL,'6','PED-20260605-1571','2026-06-05 13:02:57','64.66','0.00','10.34','75.00','facturado','pago_movil','1234567890',NULL,NULL,NULL,'Pedido por pago_movil - Referencia: 1234567890','2026-06-05 13:02:57',NULL,'2026-06-05 13:02:57','2026-06-05 13:02:57');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('108','8','6','PED-2026-000040','2026-06-05 13:03:51','24.99','0.00','4.00','28.99','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-06-05 13:03:51',NULL,'2026-06-05 13:03:51','2026-06-05 13:03:51');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('109','8','6','PED-2026-000041','2026-06-05 13:05:33','55.00','0.00','8.80','63.80','facturado','mixto',NULL,NULL,NULL,NULL,'Pedido por mixto','2026-06-05 13:05:33',NULL,'2026-06-05 13:05:33','2026-06-05 13:05:33');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('110',NULL,'6','PED-20260606-9060','2026-06-06 06:55:35','21.54','0.00','3.45','24.99','facturado','transferencia','1234567890',NULL,NULL,NULL,'Pedido por transferencia - Referencia: 1234567890','2026-06-06 06:55:35',NULL,'2026-06-06 06:55:35','2026-06-06 06:55:35');
INSERT INTO `pedidos` (`id`, `cliente_id`, `usuario_id`, `numero_pedido`, `fecha_pedido`, `subtotal`, `impuesto`, `iva`, `total`, `estado`, `metodo_pago`, `referencia_pago`, `comprobante_pago`, `notas_cliente`, `notas_internas`, `observaciones`, `fecha_facturacion`, `direccion_envio`, `created_at`, `updated_at`) VALUES ('111','8','6','PED-2026-000042','2026-06-06 07:38:31','75.00','0.00','12.00','87.00','facturado','efectivo',NULL,NULL,NULL,NULL,'Pedido por efectivo','2026-06-06 07:38:31',NULL,'2026-06-06 07:38:31','2026-06-06 07:38:31');



-- --------------------------------------------------------
-- Estructura de tabla: predicciones_ventas
-- --------------------------------------------------------
CREATE TABLE `predicciones_ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int DEFAULT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mes` int NOT NULL,
  `anio` int NOT NULL,
  `ventas_reales` decimal(12,2) DEFAULT '0.00',
  `ventas_predichas` decimal(12,2) DEFAULT '0.00',
  `precision_prediccion` decimal(5,2) DEFAULT '0.00',
  `tendencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'estable',
  `nivel_confianza` decimal(5,2) DEFAULT '0.00',
  `stock_sugerido` int DEFAULT '0',
  `fecha_generacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prediccion` (`producto_id`,`mes`,`anio`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_fecha` (`mes`,`anio`),
  CONSTRAINT `predicciones_ventas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=573 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: predicciones_ventas
--
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('330','1','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('331','2','General','5','2026','15.00','15.75','63.00','estable','82.00','24','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('332','3','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('333','4','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('334','5','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('335','6','General','5','2026','4.00','4.20','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('336','7','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('337','8','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('338','9','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('339','10','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('340','11','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('341','12','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('342','13','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('343','14','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('344','15','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('345','16','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('346','17','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('347','18','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('348','19','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('349','20','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('350','21','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('351','22','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('352','23','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('353','24','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('354','25','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('355','26','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('356','27','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('357','28','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('358','29','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('359','30','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('360','31','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('361','32','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('362','33','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('363','34','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('364','35','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('365','36','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('366','37','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('367','38','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('368','39','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('369','40','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('370','41','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('371','42','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('372','43','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('373','44','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('374','45','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('375','46','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('376','47','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('377','48','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('378','49','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('379','50','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('380','51','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('381','52','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('382','53','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('383','54','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('384','55','General','5','2026','4.00','4.20','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('385','56','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('386','57','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('387','58','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('388','59','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('389','60','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('390','61','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('391','62','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('392','63','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('393','64','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('394','65','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('395','66','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('396','67','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('397','68','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('398','69','General','5','2026','3.00','3.15','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('399','70','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('400','71','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('401','72','General','5','2026','1.00','1.05','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('402','73','General','5','2026','2.00','2.10','63.00','estable','82.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('403','74','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('404','75','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('405','76','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('406','77','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('407','78','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('408','79','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('409','80','General','5','2026','0.00','5.00','50.00','estable','50.00','10','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('410','87','General','5','2026','10.00','10.50','63.00','estable','82.00','16','2026-05-30 07:02:36');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('492','1','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('493','2','General','6','2026','15.00','16.28','66.00','estable','84.00','25','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('494','3','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('495','4','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('496','5','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('497','6','General','6','2026','2.00','3.15','66.00','estable','84.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('498','7','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('499','8','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('500','9','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('501','10','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('502','11','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('503','12','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('504','13','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('505','14','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('506','15','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('507','16','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:03');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('508','17','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('509','18','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('510','19','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('511','20','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('512','21','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('513','22','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('514','23','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('515','24','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('516','25','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('517','26','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('518','27','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('519','28','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('520','29','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('521','30','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('522','31','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('523','32','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('524','33','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('525','34','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('526','35','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('527','36','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('528','37','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('529','38','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('530','39','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('531','40','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('532','41','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('533','42','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('534','43','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('535','44','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('536','45','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('537','46','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('538','47','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('539','48','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('540','49','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('541','50','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('542','51','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('543','52','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('544','53','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('545','54','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('546','55','General','6','2026','4.00','4.20','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('547','56','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('548','57','General','6','2026','2.00','1.58','66.00','estable','84.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('549','58','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('550','59','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('551','60','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('552','61','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('553','62','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('554','63','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('555','64','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('556','65','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('557','66','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('558','67','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('559','68','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('560','69','General','6','2026','3.00','3.15','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('561','70','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('562','71','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('563','72','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('564','73','General','6','2026','2.00','2.10','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('565','74','General','6','2026','0.00','5.00','50.00','estable','50.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('566','75','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('567','76','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('568','77','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('569','78','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('570','79','General','6','2026','1.00','1.05','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('571','80','General','6','2026','2.00','2.10','63.00','estable','82.00','10','2026-06-07 07:26:04');
INSERT INTO `predicciones_ventas` (`id`, `producto_id`, `categoria`, `mes`, `anio`, `ventas_reales`, `ventas_predichas`, `precision_prediccion`, `tendencia`, `nivel_confianza`, `stock_sugerido`, `fecha_generacion`) VALUES ('572','87','General','6','2026','9.00','11.03','66.00','estable','84.00','17','2026-06-07 07:26:04');



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
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  FULLTEXT KEY `idx_products_fulltext` (`name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: products
--
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('1','PROD-0001','Sensor inductivo prt12-4dp','150.00','https://http2.mlstatic.com/D_Q_NP_2X_907785-MLV42256115993_062020-E.webp','Sensores Autonics Inductivos...','Sensores','4.5','0','','45','1','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-05-16 07:37:52');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('2','PROD-0002','Boton pulsador Autonics Nc S3pf-p1rb','75.00','https://http2.mlstatic.com/D_NQ_NP_2X_927922-MLV52483035472_112022-F.webp','Boton pulsador Autonics modelo S3pf-p1rb...','Botoneras','4.0','0','','35','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-06-06 07:38:31');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('3','PROD-0003','Rele termico regulable 48-65a Ldr365','320.00','https://http2.mlstatic.com/D_Q_NP_2X_971966-MLV42316060787_062020-E.webp','Rele termic regulable 48-65a...','Relés','5.0','0','','25','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('4','PROD-0004','Guardamotor','280.00','https://http2.mlstatic.com/D_Q_NP_2X_987302-MLV42319903598_062020-E.webp',' Marca Schneider Electric. modelo Gv2me08','Protecciones','4.2','0','','40','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('5','PROD-0005','Termometro infrarrojo','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_780836-MLV48799544246_012022-F.webp','Termómetro Infrarrojo -32°c A 1050°c marca unit-t modelo ut302d.','Instrumentos de Medición','4.8','0','','19','1','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-05-02 15:58:58');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('6','PROD-0006','Botonera colgante','180.00','https://http2.mlstatic.com/D_Q_NP_2X_605998-MLV91579814235_092025-E.webp','Botonera colgante de 6 pulsadores Marca schneider electric modelo xaca671 material propipolineno.','Botoneras','4.1','0','','26','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-06-05 11:57:08');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('7','PROD-0007','Sensor fotoelectrico mfr','220.00','https://http2.mlstatic.com/D_Q_NP_2X_781132-MLV90889351684_082025-E.webp','Sensor fotorelectrico Autonics bx5m.','Sensores','4.6','0','','42','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('8','PROD-0008','Pinza amperimetrica digital','120.00','https://http2.mlstatic.com/D_NQ_NP_2X_919873-MLV50246492941_062022-F.webp','Marca uni-t Modelo Ut201+','Instrumentos de Medición','4.9','0','','29','1','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-05-12 08:16:26');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('9','PROD-0009','Rele de nivel para conductores','185.00','https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp','marca exceline modelo grn-mv.','Relés','4.3','0','','32','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('10','PROD-0010','Manometro festo','95.00','https://http2.mlstatic.com/D_Q_NP_2X_782534-MLV80960384399_112024-E.webp','Marca festo modelo ma-50-10-1/4-enef162838.','Instrumentos de Medición','4.4','0','','55','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('11','PROD-0011','Mini termo anemometro y medidor de humedad','650.00','https://http2.mlstatic.com/D_Q_NP_2X_954754-MLV76879763367_062024-E.webp','Marca Extech Modelo 45158.','Instrumentos de Medición','4.0','0','','12','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('12','PROD-0012','Sensor capacitivo Autonics','210.00','https://http2.mlstatic.com/D_Q_NP_2X_764408-MLV42258601667_062020-E.webp','Marca Autonics Modelo Cr18-8ac.','Sensores','4.7','0','','38','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('13','PROD-0013','Selector 2 posiciones','45.00','https://http2.mlstatic.com/D_Q_NP_2X_946997-MLV46271812962_062021-E.webp','Marca Scneider Electric Modelo XB4BD21.','Controles','4.5','0','','70','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('14','PROD-0014','Etiquetadora panduit','2800.00','https://http2.mlstatic.com/D_Q_NP_2X_845848-MLV75886383737_042024-E.webp','Marca Extech Modelo PanTher LS8E.','Herramientas','4.8','0','','5','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('15','PROD-0015','Rele de nivel para lquidos conductores','185.00','https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp','Marca Exceline Modelo Grn-mv Voltaje 110-220.','Relés','4.2','0','','32','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('16','PROD-0016','Rele de estado solido Autonics','125.00','https://http2.mlstatic.com/D_NQ_NP_2X_823517-MLV49140687804_022022-F.webp','Marca Autonics Modelo SR1-4415.','Relés','4.5','0','','48','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('17','PROD-0017','Final de carrera','195.00','https://http2.mlstatic.com/D_Q_NP_2X_853654-MLV42315651853_062020-E.webp','Marca Telemecanique/schneider Modelo XCKJO513.','Sensores','4.6','0','','28','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('18','PROD-0018','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_854049-MLV42347247961_062020-E.webp','Marca scheneider/telemecanique XCKP2121G11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('19','PROD-0019','Contador temporizador','280.00','https://http2.mlstatic.com/D_Q_NP_2X_868764-MLV82980146035_032025-E.webp','Marca Autonics Modelo CT6Y-1P2.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('20','PROD-0020','Pinza amperimetrica','135.00','https://http2.mlstatic.com/D_NQ_NP_2X_685608-MLV43035297361_082020-F.webp','Marca uni-t Modelo UT202a+.','Instrumentos de Medición','4.7','0','','35','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('21','PROD-0021','Contador temporizador','280.00','https://http2.mlstatic.com/D_Q_NP_2X_943529-MLV82980293127_032025-E.webp','Marca Autonics Modelo CT6Y-1P4.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('22','PROD-0022','Pinza amperimetrica extech','420.00','https://http2.mlstatic.com/D_Q_NP_2X_891753-MLV48858956084_012022-E.webp','Marca extech Modelo UT210d.','Instrumentos de Medición','4.7','0','','15','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('23','PROD-0023','Sensor TIq5mc1 Jootiden','60.00','https://http2.mlstatic.com/D_Q_NP_2X_983049-MLV78136901025_082024-E.webp','Marca generica Modelo TL-Q5MC1.','Sensores','4.7','0','','85','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('24','PROD-0024','Pinza amperimetrica + termometro','220.00','https://http2.mlstatic.com/D_NQ_NP_2X_826593-MLV46165148147_052021-F.webp','Marca extech modelo EX470.','Instrumentos de Medición','4.8','0','','25','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('25','PROD-0025','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_904301-MLV42315881121_062020-E.webp','Marca schneider/telemecanique Modelo XCKP2118G11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('26','PROD-0026','Contactor 25amp 24vdc','380.00','https://images.wiautomation.com/public/images/landing/anticipa/product/LC1DT206SLS207.jpg','Marca scheider electric Modelo LCD1E25BD.','Contactores','4.7','0','','18','1','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('27','PROD-0027','Contactor 80amp 220v','680.00','https://http2.mlstatic.com/D_NQ_NP_2X_774386-MLV42329989223_062020-F.webp','Marca scheneider electric Modelo LC1d80m.','Contactores','4.7','0','','10','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('28','PROD-0028','osiloscopio extech','850.00','https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-F.webp','Marca scheneider electric Modelo LC1D09BD.','Instrumentos de Medición','4.7','0','','8','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('29','PROD-0029','Pinza amperimetrica digital','120.00','https://http2.mlstatic.com/D_NQ_NP_2X_928642-MLV54457071668_032023-F.webp','Marca uni-t Modelo UT201+.','Instrumentos de Medición','4.7','0','','35','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('30','PROD-0030','Fuente de poder 5amp 12vdc Aunonics','185.00','https://http2.mlstatic.com/D_NQ_NP_2X_606115-MLV82504917240_022025-F.webp','Marca scheneider electric Modelo SPB-O6O-12.','Fuentes de Poder','4.7','0','','30','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('31','PROD-0031','kit maletin legrand starfix','320.00','https://http2.mlstatic.com/D_Q_NP_2X_983177-MLV71749528286_092023-E.webp','Marca lengard Modelo 376 59/60.','Herramientas','4.7','0','','20','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('32','PROD-0032','Controlador de temperatura ','290.00','https://http2.mlstatic.com/D_NQ_NP_2X_966407-MLV54265777533_032023-F.webp','Marca Autonics Modelo tk4s-bn4r.','Controladores','4.7','0','','16','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('33','PROD-0033','Descanso ajustable para pie','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_978007-MLV71801855063_092023-F.webp','Marca lengard Modelo 376 59/60.','Accesorios','4.7','0','','12','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('34','PROD-0034','Controlador de temperaura 48x69','210.00','https://http2.mlstatic.com/D_NQ_NP_2X_821401-MLV73213656021_122023-F.webp','Marca 3M Modelo FR53OCB.','Controladores','4.7','0','','22','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('35','PROD-0035','Temporizador Autonics','140.00','https://http2.mlstatic.com/D_NQ_NP_2X_956520-MLV52366651303_112022-F.webp','Marca Autonics Modelo Le8n-bfle8n-bn.','Temporizadores','4.7','0','','38','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('36','PROD-0036','Controlador temporizador','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_674477-MLV51061339386_082022-F.webp','Marca Autonics Modelo Ct6-1p2.','Temporizadores','4.7','0','','22','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('37','PROD-0037','Multimetro uni-t','150.00','https://http2.mlstatic.com/D_NQ_NP_2X_841899-MLV46427086846_062021-F.webp','Marca uni-t Modelo Ut89x.','Instrumentos de Medición','4.7','0','','30','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('38','PROD-0038','Sensor amplificador para fibra optica','200.00','https://http2.mlstatic.com/D_NQ_NP_2X_964670-MLV42255017336_062020-F.webp','Marca Autonics Modelo Bf4rp.','Sensores','4.7','0','','25','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('39','PROD-0039','Rele estado solido trifasico 30amp','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_754022-MLV82085220364_022025-F.webp','Marca Autonics Modelo Sr3-4430.','Relés','4.7','0','','18','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('40','PROD-0040','Sensor fotoelectrico','210.00','https://http2.mlstatic.com/D_NQ_NP_2X_656456-MLV42255069136_062020-F.webp','Marca Autonics Modelo Brqm400-ddta.','Sensores','4.7','0','','28','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('41','PROD-0041','Contactor 40amp 110v','420.00','https://http2.mlstatic.com/D_NQ_NP_2X_697183-MLV81969170376_022025-F.webp','Marca Schneider Electric Modelo LCD1D4OAF7.','Contactores','4.7','0','','15','1','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('42','PROD-0042','Fuente de poder 8amp 12vdc','240.00','https://http2.mlstatic.com/D_NQ_NP_2X_731782-MLV78217056967_082024-F.webp','Marca Autonics Modelo SPB-120-12.','Fuentes de Poder','4.7','0','','20','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('43','PROD-0043','Rele termic regulable 30-40a','340.00','https://http2.mlstatic.com/D_NQ_NP_2X_719268-MLV42301142622_062020-F.webp','Marca scheneider electric Modelo LRD3355.','Relés','4.7','0','','16','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('44','PROD-0044','Guardamotor 1-1.6a','290.00','https://http2.mlstatic.com/D_NQ_NP_2X_842891-MLV42319762831_062020-F.webp','Marca Schneider Electric Modelo GV2ME06 .','Protecciones','4.7','0','','18','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('45','PROD-0045','Idicadores de frecuencia uni-t','300.00','https://http2.mlstatic.com/D_NQ_NP_2X_892854-MLV48915410648_012022-F.webp','Marca uni-t Modelo Ut261a.','Instrumentos de Medición','4.7','0','','15','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('46','PROD-0046','Protector televisor','85.00','https://http2.mlstatic.com/D_NQ_NP_2X_728315-MLV46442590142_062021-F.webp','Marca Exceline Modelo Gsm-tv120.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('47','PROD-0047','Rele estado solido','125.00','https://http2.mlstatic.com/D_NQ_NP_2X_692640-MLV49139833433_022022-F.webp','Marca Autonics Modelo Sr1-1450.','Relés','4.7','0','','48','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('48','PROD-0048','Fuente de poder 20amp 12vdc','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_987249-MLV73944702421_012024-F.webp','Marca Autonics Modelo Sp240-12.','Fuentes de Poder','4.7','0','','12','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('49','PROD-0049','Cable para sensor M8','55.00','https://http2.mlstatic.com/D_Q_NP_2X_855871-MLV70628379777_072023-E.webp','Marca Telemecanique Modelo Xzcp0941l2.','Accesorios','4.7','0','','90','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('50','PROD-0050','Contactor 25amp 220v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_961256-MLV42321081111_062020-F.webp','Marca Scheneider electric Modelo Lc1d25m7.','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('51','PROD-0051','Variador de velociadad 5hp 440v','4200.00','https://http2.mlstatic.com/D_NQ_NP_2X_693722-MLA76246464467_052024-F.webp','Marca scheneider electric Modelo Atv320u40n4c .','Variadores','4.7','0','','4','1','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('52','PROD-0052','Sensor fotoelectrico reflectivo','195.00','https://http2.mlstatic.com/D_NQ_NP_2X_896757-MLV78646725763_082024-F.webp','Marca Autonics Modelo Brqm3m-pdta-c-p .','Sensores','4.7','0','','28','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('53','PROD-0053','Contactor 32amp 220v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_775520-MLV42321197517_062020-F.webp','Marca scheneider electric Modelo Lc1d32m7 .','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('54','PROD-0054','Rele termico regulable','390.00','https://http2.mlstatic.com/D_Q_NP_2X_720989-MLV42320094116_062020-E.webp','Marca scheneider electric Modelo Lrd3357.','Relés','4.7','0','','15','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('55','PROD-0055','Caja para pulsadores plastica 3 huecos','70.00','https://http2.mlstatic.com/D_Q_NP_2X_719143-MLV42346979496_062020-E.webp','Marca scheneider Modelo Xald03.','Accesorios','4.7','0','','62','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-05-25 10:15:59');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('56','PROD-0056','Temporizacion Rele estrella triangulo','240.00','https://http2.mlstatic.com/D_Q_NP_2X_966925-MLV83523597338_042025-E.webp','Marca scheneider Modelo Re22r2qtmr.','Relés','4.7','0','','22','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('57','PROD-0057','Caja para pulsadores 2 huecos','55.00','https://http2.mlstatic.com/D_Q_NP_2X_783226-MLV42347077873_062020-E.webp','Marca scheneider Modelo Xald02.','Accesorios','4.7','0','','73','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-06-05 13:05:33');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('58','PROD-0058','Contactor 18amp 24vdc','330.00','https://http2.mlstatic.com/D_NQ_NP_2X_669780-MLV42320450829_062020-E.webp','Marca scheneider electric Modelo Lc1d18bd.','Contactores','4.7','0','','20','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('59','PROD-0059','Contactor 38amp 220v','420.00','https://http2.mlstatic.com/D_NQ_NP_2X_761868-MLV42321342194_062020-F.webp','Marca scheneider electric Modelo Lc1d38m7.','Contactores','4.7','0','','15','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('60','PROD-0060','Guardamotor 48-65a','580.00','https://http2.mlstatic.com/D_Q_NP_2X_879405-MLV42346668428_062020-E.webp','Marca scheneider electric Modelo Gv3p65.','Protecciones','4.7','0','','12','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('61','PROD-0061','Contactor 256a 220v','3800.00','https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp','Marca scheneider Modelo Lc1f265m7.','Contactores','4.7','0','','3','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('62','PROD-0062','Rele termico regulable','390.00','https://http2.mlstatic.com/D_Q_NP_2X_719268-MLV42301142622_062020-E.webp','Marca scheneider electric Modelo Ldr3355.','Relés','4.7','0','','15','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('63','PROD-0063','Sensor de marca fotocelula','850.00','https://http2.mlstatic.com/D_NQ_NP_2X_965275-MLV42316005076_062020-F.webp','Marca Telemecanique.Modelo Xurk1ksmm12 ','Sensores','4.7','0','','8','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('64','PROD-0064','Variador de velocidad 7.5hp','5800.00','https://http2.mlstatic.com/D_NQ_NP_2X_617200-MLV46302539269_062021-F.webp','Marca scheneider electric Modelo Atv320u55m3c.','Variadores','4.7','0','','3','1','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('65','PROD-0065','Sensor inductivo','95.00','https://http2.mlstatic.com/D_Q_NP_2X_835762-MLV50041103539_052022-E.webp','Marca Autonics Modelo prd18 8dp.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('66','PROD-0066','Contactor 265a 220v','3800.00','https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp','Marca scheneider electric Modelo Lc1f265m7.','Contactores','4.7','0','','3','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('67','PROD-0067','Lockout 63amp seleccionador bloqueador','550.00','https://http2.mlstatic.com/D_Q_NP_2X_651999-MLV42345796245_062020-E.webp','Marca scheneider Modelo Vcf5ge.','Accesorios','4.7','0','','10','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('68','PROD-0068','Lockout 100amp seleccionador bloqueador','680.00','https://http2.mlstatic.com/D_Q_NP_2X_833469-MLV42331350351_062020-E.webp','Marca scheneider electric Modelo Vcf5gen.','Accesorios','4.7','0','','8','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('69','PROD-0069','Sensor inductivo de rotacion','420.00','https://http2.mlstatic.com/D_Q_NP_2X_998055-MLV53428583531_012023-E.webp','Marca Telemecanique Modelo Xsav12373.','Sensores','4.7','0','','12','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('70','PROD-0070','Sensor','140.00','https://http2.mlstatic.com/D_Q_NP_2X_912959-MLV42259148070_062020-E.webp','Marca Autonics Modelo Prcm30-5dp.','Sensores','4.7','0','','38','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('71','PROD-0071','Protctor para aires y refrigeradores','95.00','https://http2.mlstatic.com/D_Q_NP_2X_995442-MLV42253383680_062020-E.webp','Marca Exceline Modelo Gsm-rt120.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('72','PROD-0072','Flotador electrico multivoltaje','70.00','https://http2.mlstatic.com/D_Q_NP_2X_837451-MLV42253919704_062020-E.webp','Marca Exceline Modelo Gfe-mv3m.','Accesorios','4.7','0','','65','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('73','PROD-0073','Contactor 9amp 24vdc','280.00','https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-E.webp','Marca scheneider electric Modelo Lc190bd.','Contactores','4.7','0','','22','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('74','PROD-0074','Contactor 32amp 110v','380.00','https://http2.mlstatic.com/D_NQ_NP_2X_849936-MLV42321134886_062020-E.webp','Marca scheneider electric Modelo Lc1d32f7.','Contactores','4.7','0','','18','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('75','PROD-0075','Contactor 40amp 24vdc','450.00','https://http2.mlstatic.com/D_NQ_NP_2X_966221-MLV46232503172_062021-E.webp','Marca sccheneider electric Lc1d40ab7.','Contactores','4.7','0','','15','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('76','PROD-0076','Contactor 65amp 220v','650.00','https://http2.mlstatic.com/D_NQ_NP_2X_616564-MLV42329913652_062020-E.webp','Marca scheneider electric Modelo Lc1d65am7.','Contactores','4.7','0','','12','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('77','PROD-0077','Contactor 185amp 220v','2500.00','https://http2.mlstatic.com/D_NQ_NP_2X_838661-MLV50182142214_062022-E.webp','Marca scheneider Modelo Lc1f185m7.','Contactores','4.7','0','','5','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('78','PROD-0078','Final de carrera','95.00','https://http2.mlstatic.com/D_Q_NP_2X_921213-MLV42302675953_062020-E.webp','Marca scheneider/Telemecanique Modelo Xckp2127g11.','Sensores','4.7','0','','62','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('79','PROD-0079','Mini termometro infrrarojo','125.00','https://http2.mlstatic.com/D_Q_NP_2X_887138-MLV50723282858_072022-E.webp','Marca uni-t Modelo Ut300a+.','Instrumentos de Medición','4.7','0','','48','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('80','PROD-0080','Protector para motores monofasicos','85.00','https://www.autonics.com/web/2022/08/22/6/53/4/AT8N_main.webp','Marca exceline Modelo Gsm-r220b.','Protecciones','4.7','0','','55','0','0.00',NULL,'Bs','1',NULL,'2026-04-29 13:58:02','2026-05-02 12:23:04');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `image_url`, `description`, `category`, `rating`, `views_count`, `specs`, `stock`, `is_featured`, `weight`, `dimensions`, `currency`, `active`, `deleted_at`, `created_at`, `updated_at`) VALUES ('87','PROD-0081','AT8N','24.99','https://www.autonics.com/web/2022/08/22/6/53/4/AT8N_main.webp','Método de operación : Ajuste de tiempo\r\nOperación de salida : RETARDO A LA CONEXIÓN, PARPADEO, INTERVALO\r\nOperación de tiempo : INICIO A LA CONEXIÓN\r\nTerminal : conector de 8 pines\r\nFuente de alimentación : 100-240VCA~, 24-240VCC specialstring\r\nSalida de control : Límite de tiempo DPDT (2c), Límite de tiempo SPDT (1c) + SPDT instantáneo (1c)','Temporizadores','4.0','0',NULL,'34','0','0.00',NULL,'Bs','1',NULL,'2026-05-02 11:15:50','2026-06-05 13:03:51');



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
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('1','PROV-001','Autonics Venezuela','Autonics C.A.','J-12345678-9','ruc','Av. Principal, Zona Industrial','Caracas','0212-5551234',NULL,'ventas@autonics.com.ve',NULL,'Carlos Méndez',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('2','PROV-002','Schneider Electric','Schneider Electric Venezuela','J-87654321-0','ruc','Calle 5, Parque Industrial','Valencia','0241-5555678',NULL,'ventas@schneider.com.ve',NULL,'Ana Rodríguez',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');
INSERT INTO `proveedores` (`id`, `codigo`, `nombre_comercial`, `razon_social`, `ruc`, `tipo_documento`, `direccion`, `ciudad`, `telefono_principal`, `telefono_secundario`, `email_principal`, `email_secundario`, `contacto_nombre`, `contacto_cargo`, `sitio_web`, `condiciones_pago`, `plazo_entrega`, `forma_pago`, `moneda`, `estado`, `saldo_pendiente`, `calificacion`, `notas`, `created_at`, `updated_at`) VALUES ('3','PROV-003','UNI-T Venezuela','UNI-T Instruments C.A.','J-11223344-5','ruc','Av. Libertador, Centro Comercial','Maracaibo','0261-5559012',NULL,'importaciones@unit.com.ve',NULL,'Luis Fernández',NULL,NULL,NULL,'0','transferencia','Bs','activo','0.00','0.0',NULL,'2026-04-29 13:58:02','2026-04-29 13:58:02');



-- --------------------------------------------------------
-- Estructura de tabla: qr_login_sessions
-- --------------------------------------------------------
CREATE TABLE `qr_login_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pending','scanned','approved','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `user_id` int DEFAULT NULL,
  `user_table` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_estado` (`estado`),
  KEY `idx_expiracion` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: qr_login_sessions
--
INSERT INTO `qr_login_sessions` (`id`, `token`, `estado`, `user_id`, `user_table`, `user_data`, `created_at`, `expires_at`) VALUES ('1','b0c4dfa7851951d488d52304a2dd21fef0bf0f3fc7a9bbf3e21d905b65f4645f','pending',NULL,NULL,NULL,'2026-05-30 08:26:20','2026-05-30 12:28:20');
INSERT INTO `qr_login_sessions` (`id`, `token`, `estado`, `user_id`, `user_table`, `user_data`, `created_at`, `expires_at`) VALUES ('2','becf554d934a3d2dd6ff2d729e8df667b42e4bfee86aeca44069ad9a851c7455','expired',NULL,NULL,NULL,'2026-06-01 08:50:05','2026-06-01 12:52:05');
INSERT INTO `qr_login_sessions` (`id`, `token`, `estado`, `user_id`, `user_table`, `user_data`, `created_at`, `expires_at`) VALUES ('3','af4c1ad2bfb0d3c8483b848c7d55a0fe34b5c59525e9380e5ef82a0cacf91471','pending',NULL,NULL,NULL,'2026-06-01 08:56:11','2026-06-01 12:58:11');
INSERT INTO `qr_login_sessions` (`id`, `token`, `estado`, `user_id`, `user_table`, `user_data`, `created_at`, `expires_at`) VALUES ('4','df5cdecf1c67a35e20f74dcc6e5fd6d8b914f17f18e9bd3b1843696ab1b14e01','expired',NULL,NULL,NULL,'2026-06-01 08:56:45','2026-06-01 12:58:45');
INSERT INTO `qr_login_sessions` (`id`, `token`, `estado`, `user_id`, `user_table`, `user_data`, `created_at`, `expires_at`) VALUES ('6','86fffb32d8bafcfa3d5fd33013df3feff014f71ead8651f8da31ed300e008e19','expired',NULL,NULL,NULL,'2026-06-06 09:16:49','2026-06-06 13:18:49');
INSERT INTO `qr_login_sessions` (`id`, `token`, `estado`, `user_id`, `user_table`, `user_data`, `created_at`, `expires_at`) VALUES ('7','5716afdd0f1386c7fb0b7a6d6b0b720906c0c55150eb426ca23da68a451bc326','expired',NULL,NULL,NULL,'2026-06-06 09:21:39','2026-06-06 13:23:39');
INSERT INTO `qr_login_sessions` (`id`, `token`, `estado`, `user_id`, `user_table`, `user_data`, `created_at`, `expires_at`) VALUES ('8','638edcd35ce6fb30663363f5e89dbdebb99585bacc5c24bc89fe8ca79c21ad11','pending',NULL,NULL,NULL,'2026-06-06 09:34:58','2026-06-06 13:36:58');



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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: secuencias_facturacion
--
INSERT INTO `secuencias_facturacion` (`id`, `tipo`, `prefijo`, `siguiente_valor`, `longitud`, `anio`) VALUES ('1','pedido','PED-','1','6','2026');
INSERT INTO `secuencias_facturacion` (`id`, `tipo`, `prefijo`, `siguiente_valor`, `longitud`, `anio`) VALUES ('2','factura','FAC-','1','6','2026');
INSERT INTO `secuencias_facturacion` (`id`, `tipo`, `prefijo`, `siguiente_valor`, `longitud`, `anio`) VALUES ('3','factura','FAC','3','6','2026');



-- --------------------------------------------------------
-- Estructura de tabla: sesiones_2fa
-- --------------------------------------------------------
CREATE TABLE `sesiones_2fa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_user_id` int NOT NULL,
  `token_verificacion` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intentos` int DEFAULT '0',
  `expiracion` datetime NOT NULL,
  `completado` tinyint(1) DEFAULT '0',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_verificacion` (`token_verificacion`),
  KEY `admin_user_id` (`admin_user_id`),
  KEY `idx_token` (`token_verificacion`),
  KEY `idx_expiracion` (`expiracion`),
  CONSTRAINT `sesiones_2fa_ibfk_1` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: sesiones_2fa
--
INSERT INTO `sesiones_2fa` (`id`, `admin_user_id`, `token_verificacion`, `intentos`, `expiracion`, `completado`, `fecha_creacion`) VALUES ('1','4','36a1beee9e6f1fbe67e8c578206161178f9577f93c2e275851ddbbfaabae8b00','1','2026-06-01 13:18:11','1','2026-06-01 09:13:11');
INSERT INTO `sesiones_2fa` (`id`, `admin_user_id`, `token_verificacion`, `intentos`, `expiracion`, `completado`, `fecha_creacion`) VALUES ('2','4','1758966bd5e360b34c30956f93d04043fb00b71d0d10d8c65d59de905d7fc734','1','2026-06-06 13:20:04','1','2026-06-06 09:15:04');
INSERT INTO `sesiones_2fa` (`id`, `admin_user_id`, `token_verificacion`, `intentos`, `expiracion`, `completado`, `fecha_creacion`) VALUES ('3','4','773bb0d6908785e621d7fee6dcabe6eb2cf1f45ad3d9e9674b903e0c348425ed','2','2026-06-07 11:29:31','1','2026-06-07 07:24:31');



-- --------------------------------------------------------
-- Estructura de tabla: sesiones_2fa_clientes
-- --------------------------------------------------------
CREATE TABLE `sesiones_2fa_clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token_verificacion` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intentos` int DEFAULT '0',
  `expiracion` datetime NOT NULL,
  `completado` tinyint(1) DEFAULT '0',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_verificacion` (`token_verificacion`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token_verificacion`),
  KEY `idx_expiracion` (`expiracion`),
  CONSTRAINT `sesiones_2fa_clientes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos de tabla: sesiones_2fa_clientes
--
INSERT INTO `sesiones_2fa_clientes` (`id`, `user_id`, `token_verificacion`, `intentos`, `expiracion`, `completado`, `fecha_creacion`) VALUES ('1','6','24046fe2f9663bbbc7c5e63b5abbcbced808c7454490b26ebc10eecbdfedfc16','2','2026-06-06 11:54:58','0','2026-06-06 07:49:58');
INSERT INTO `sesiones_2fa_clientes` (`id`, `user_id`, `token_verificacion`, `intentos`, `expiracion`, `completado`, `fecha_creacion`) VALUES ('2','6','4e73bf7640dec607171a525c006a7c63ccca361ba80149337cf73b54e3efd6b9','0','2026-06-06 13:20:56','0','2026-06-06 09:15:56');
INSERT INTO `sesiones_2fa_clientes` (`id`, `user_id`, `token_verificacion`, `intentos`, `expiracion`, `completado`, `fecha_creacion`) VALUES ('3','6','4d202688ce50514397c569b4ae24da185dcbd1f0c7f405b9d130735e96aee846','1','2026-06-07 11:27:57','1','2026-06-07 07:22:57');



-- --------------------------------------------------------
-- Estructura de tabla: suscripciones_recomendaciones
-- --------------------------------------------------------
CREATE TABLE `suscripciones_recomendaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_email` varchar(255) NOT NULL,
  `cliente_nombre` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cliente_email` (`cliente_email`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



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
  `2fa_enabled` tinyint(1) DEFAULT '0',
  `2fa_secret` varchar(255) DEFAULT NULL,
  `2fa_backup_codes` text,
  `2fa_verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `idx_users_estado` (`estado`),
  KEY `idx_users_correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos de tabla: users
--
INSERT INTO `users` (`id`, `nombre`, `correo`, `password`, `direccion`, `estado`, `cedula`, `telefono`, `foto_perfil`, `rol`, `is_active`, `email_verified`, `verification_token`, `last_login`, `created_at`, `2fa_enabled`, `2fa_secret`, `2fa_backup_codes`, `2fa_verified_at`) VALUES ('6','antonio','jose14chacon2003@gmail.com','$2y$12$FwdQ44yG5lbrv5oBKyMK6e.Q5L9BDTqwdqsVkWlRU04eI9YlWPR5u','Urb trigal Sur Calle Camoruco','activo','17314511','04121311220','/uploads/perfiles/users_6_a656dab81747173bb557618f15d6e72b.png','usuario','1','1',NULL,'2026-06-06 07:35:21','2026-04-29 13:59:23','1','67CDVGJW5EGHYGC2JHRDXRUXGU7RLPRU','[\"ae9cd9d7-a189\",\"ea800af3-7981\",\"4d5a702b-7c0b\",\"bbd2aa5c-9503\",\"e11b72fe-072a\",\"71aa364a-0fae\",\"3e73b9eb-c510\",\"70efe955-7816\"]','2026-06-06 07:37:16');

SET FOREIGN_KEY_CHECKS=1;
