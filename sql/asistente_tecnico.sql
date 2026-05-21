-- ============================================================================
-- ASISTENTE TÉCNICO INTELIGENTE - Tablas de datos técnicos
-- ============================================================================

-- 1. Fórmulas y cálculos de protección eléctrica (por tipo de equipo)
DROP TABLE IF EXISTS formulas_tecnicas;
CREATE TABLE formulas_tecnicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_equipo VARCHAR(100) NOT NULL,
    parametros_entrada JSON NOT NULL COMMENT 'Ej: {"hp":"HP","voltaje":"V","fases":"#","distancia":"m"}',
    formulas JSON NOT NULL COMMENT 'Ej: {"corriente_nominal":"(hp*746)/(V*factor_potencia*eficiencia)","breaker":"I_nominal*1.25"}',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO formulas_tecnicas (tipo_equipo, parametros_entrada, formulas, notas) VALUES
('Motor Trifásico', 
 '{"hp":"HP del motor","voltaje":"Voltaje (V)","distancia":"Distancia al motor (m)","factor_potencia":"Factor de potencia (0.8-0.95)","eficiencia":"Eficiencia (0.85-0.95)"}',
 '{"corriente_nominal":"(hp * 746) / (voltaje * 1.732 * factor_potencia * eficiencia)","corriente_arranque":"corriente_nominal * 6","breaker":"corriente_nominal * 1.25","contactor_ac1":"corriente_nominal * 1.15","contactor_ac3":"corriente_nominal * 1.5","rele_termico_min":"corriente_nominal * 0.95","rele_termico_max":"corriente_nominal * 1.15","cable_mm2_50m":"calcular_cable(corriente_nominal, distancia, 50)","cable_mm2_100m":"calcular_cable(corriente_nominal, distancia, 100)","cable_mm2_150m":"calcular_cable(corriente_nominal, distancia, 150)"}',
 'Fórmulas estándar IEEE/NFPA para selección de protecciones de motores trifásicos. Considerar curva de disparo del breaker tipo D para motores.'),
('Motor Monofásico',
 '{"hp":"HP del motor","voltaje":"Voltaje (V)","distancia":"Distancia al motor (m)","factor_potencia":"Factor de potencia (0.7-0.9)","eficiencia":"Eficiencia (0.75-0.85)"}',
 '{"corriente_nominal":"(hp * 746) / (voltaje * factor_potencia * eficiencia)","corriente_arranque":"corriente_nominal * 5","breaker":"corriente_nominal * 1.25","cable_mm2":"calcular_cable(corriente_nominal, distancia, 50)"}',
 'Fórmulas NEMA para motores monofásicos con capacitor de arranque.'),
('Carga Resistiva (Alumbrado/Calefacción)',
 '{"potencia_w":"Potencia en Watts (W)","voltaje":"Voltaje (V)","distancia":"Distancia (m)","fases":"Número de fases (1/3)"}',
 '{"corriente_nominal":"potencia_w / (voltaje * (1 si fases=1 else 1.732))","breaker":"corriente_nominal * 1.20","cable_mm2":"calcular_cable(corriente_nominal, distancia, 50)"}',
 'Para cargas resistivas (factor de potencia = 1). Breaker tipo C recomendado.'),
('Variador de Frecuencia (VFD)',
 '{"hp":"HP del motor","voltaje":"Voltaje (V)","fases":"Fases de entrada (1/3)"}',
 '{"corriente_nominal":"(hp * 746) / (voltaje * 1.732 * 0.85 * 0.9)","vfd_recomendado":"corriente_nominal * 1.25","breaker_entrada":"corriente_nominal * 1.25","cable_entrada":"calcular_cable(corriente_nominal, 30, 50)","cable_motor":"calcular_cable(corriente_nominal, 50, 75)"}',
 'Selección de VFD: sobredimensionar 25% sobre corriente nominal. Incluir reactor de línea para armónicos.');

-- 2. Compatibilidad entre marcas (cross-reference)
DROP TABLE IF EXISTS compatibilidad_marcas;
CREATE TABLE compatibilidad_marcas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(100) NOT NULL,
    marca_a VARCHAR(100) NOT NULL,
    modelo_a VARCHAR(200) NOT NULL,
    marca_b VARCHAR(100) NOT NULL,
    modelo_b VARCHAR(200) NOT NULL,
    tipo_compatibilidad ENUM('directo', 'adaptador', 'funcional') DEFAULT 'directo',
    notas TEXT,
    producto_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_categoria (categoria),
    INDEX idx_marca_a (marca_a, modelo_a),
    INDEX idx_marca_b (marca_b, modelo_b)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO compatibilidad_marcas (categoria, marca_a, modelo_a, marca_b, modelo_b, tipo_compatibilidad, notas) VALUES
-- Contactores Schneider ↔ Telemecanique (son la misma marca)
('Contactores', 'Schneider Electric', 'LC1D25M7', 'Telemecanique', 'LC1D25M7', 'directo', 'Schneider adquirió Telemecanique. Mismos modelos y referencias.'),
('Contactores', 'Schneider Electric', 'LC1D18BD', 'Telemecanique', 'LC1D18BD', 'directo', 'Mismo producto, distinto empaque.'),
('Contactores', 'Schneider Electric', 'LC1D32M7', 'Telemecanique', 'LC1D32M7', 'directo', 'Compatibilidad total, misma línea de producción.'),
-- Relés térmicos Schneider ↔ Telemecanique
('Relés Térmicos', 'Schneider Electric', 'LRD3355', 'Telemecanique', 'LRD3355', 'directo', 'Mismo producto.'),
('Relés Térmicos', 'Schneider Electric', 'LRD3357', 'Telemecanique', 'LRD3357', 'directo', 'Compatibilidad total.'),
-- Guardamotores Schneider GV2 ↔ GV3
('Guardamotores', 'Schneider Electric', 'GV2ME06', 'Schneider Electric', 'GV3P40', 'funcional', 'GV3 es la evolución del GV2. Mayor capacidad de ruptura. Verificar curva.'),
('Guardamotores', 'Schneider Electric', 'GV2ME08', 'Schneider Electric', 'GV3P40', 'funcional', 'Reemplazo funcional. Verifique rango de ajuste.'),
-- Autonics sensores inductivos
('Sensores Inductivos', 'Autonics', 'PRT12-4DP', 'Autonics', 'PRD18-8DP', 'funcional', 'PRD18 tiene mayor distancia de detección (8mm vs 4mm). Verificar espacio de montaje.'),
('Sensores Inductivos', 'Autonics', 'PRCM30-5DP', 'Autonics', 'PRD30-10DP', 'funcional', 'PRD30 ofrece mayor alcance. Compatible en montaje M30.'),
-- Autonics sensores capacitivos
('Sensores Capacitivos', 'Autonics', 'CR18-8AC', 'Autonics', 'CR30-15AC', 'funcional', 'CR30-15AC mayor alcance (15mm vs 8mm). Diámetro M30.'),
-- Relevadores de estado sólido
('Relés Estado Sólido', 'Autonics', 'SR1-4415', 'Autonics', 'SR1-1450', 'funcional', 'SR1-1450 para cargas de hasta 50A. SR1-4415 hasta 15A.'),
('Relés Estado Sólido', 'Autonics', 'SR1-4415', 'Crydom', 'D2440', 'funcional', 'Crydom D2440: 40A, 24-280VAC. Compatible con Autoics SR1-4415. Verificar montaje.'),
-- Fuentes de poder
('Fuentes de Poder', 'Autonics', 'SPB-120-12', 'Mean Well', 'LRS-150-12', 'funcional', 'Mean Well LRS-150-12: 150W vs 120W. 12.5A. Más potencia, mismas dimensiones.'),
('Fuentes de Poder', 'Autonics', 'SPB-060-12', 'Mean Well', 'LRS-75-12', 'funcional', 'Misma función. LRS-75-12 ofrece 75W a 12VDC. 6.3A.'),
('Fuentes de Poder', 'Schneider Electric', 'SPB-O6O-12', 'Autonics', 'SPB-060-12', 'funcional', 'Ambas son fuentes de 60W 12VDC. Misma funcionalidad, distinto conector.'),
-- Variadores
('Variadores', 'Schneider Electric', 'ATV320U40N4C', 'Schneider Electric', 'ATV12H075M2', 'funcional', 'ATV12 para motores monofásicos. ATV320 para trifásicos. Diferente aplicación.'),
('Variadores', 'Schneider Electric', 'ATV320U55M3C', 'ABB', 'ACS355-03E-01A2-4', 'funcional', 'Compatibilidad funcional. MISMO RANGO. ABB ACS355 es equivalente. Verificar parámetros.');


-- 3. Tabla de configuración de tableros guardados por usuarios
DROP TABLE IF EXISTS configuraciones_tablero;
CREATE TABLE configuraciones_tablero (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    aplicacion VARCHAR(100) NOT NULL COMMENT 'Ej: Bomba de agua, Compresor, Cinta transportadora, etc.',
    parametros JSON NOT NULL COMMENT 'Parámetros de entrada (hp, voltaje, etc.)',
    componentes JSON NOT NULL COMMENT 'Lista de componentes seleccionados con cantidades',
    total_estimado DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_aplicacion (aplicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Alertas de mantenimiento programadas
DROP TABLE IF EXISTS alertas_mantenimiento;
CREATE TABLE alertas_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    producto_nombre VARCHAR(200) NOT NULL,
    usuario_id INT NULL,
    pedido_id INT NULL,
    fecha_compra DATE NOT NULL,
    intervalo_dias INT NOT NULL COMMENT 'Días recomendados entre mantenimientos',
    proximo_mantenimiento DATE NOT NULL,
    tipo ENUM('preventivo', 'predictivo', 'correctivo') DEFAULT 'preventivo',
    estado ENUM('pendiente', 'notificado', 'completado', 'cancelado') DEFAULT 'pendiente',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_proximo (proximo_mantenimiento),
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
