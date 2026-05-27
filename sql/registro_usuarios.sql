-- ============================================================================
-- SISTEMA DE CARRITO DE COMPRAS - SQL DEFINITIVO CORREGIDO CON AUDITORÍA MEJORADA
-- Versión: 3.1.0
-- Fecha: 2026-04-26
-- CORRECCIONES: Columnas para historial de ediciones en auditoria_logs
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS carrito_db;
CREATE DATABASE IF NOT EXISTS carrito_db;
USE carrito_db;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ##########################################################################
-- 1. TABLA DE USUARIOS (users)
-- ##########################################################################

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    direccion VARCHAR(255) NULL,
    estado VARCHAR(20) DEFAULT 'activo',
    cedula VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    foto_perfil VARCHAR(255) NULL,
    rol VARCHAR(20) DEFAULT 'usuario',
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_estado ON users(estado);
CREATE INDEX idx_users_correo ON users(correo);

INSERT INTO users (id, nombre, correo, password, direccion, estado, cedula, telefono, rol) VALUES 
(1, 'Usuario Administrador', 'default@carrito.com', '$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K', 'Oficina Principal', 'activo', '00000000', '0000000000', 'admin'),
(2, 'Juan Pérez', 'juan@email.com', '$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K', 'Av. Principal #123, Caracas', 'activo', '12345678', '04121234567', 'usuario'),
(3, 'María González', 'maria@email.com', '$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K', 'Calle Secundaria #45, Maracaibo', 'activo', '87654321', '04149876543', 'usuario'),
(4, 'Carlos Rodríguez', 'carlos@email.com', '$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K', 'Urb. Las Flores, Valencia', 'activo', '11223344', '04161122334', 'usuario'),
(5, 'Cliente de Prueba', 'cliente@test.com', '$2y$10$7/O86p55T2t.tQxQxT2eBe7L47Pq.vL9yM.mN7kE2u7I.jD/j4c0K', 'Calle Principal, Barquisimeto', 'activo', '11111111', '04141234567', 'usuario');

-- ##########################################################################
-- 2. TABLA DE PRODUCTOS (80 productos)
-- ##########################################################################

DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) UNIQUE,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(512),
    description TEXT,
    category VARCHAR(100) DEFAULT 'General',
    rating DECIMAL(2, 1) DEFAULT 0.0,
    views_count INT DEFAULT 0,
    specs TEXT,
    stock INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    weight DECIMAL(10,2) DEFAULT 0.00,
    dimensions VARCHAR(100),
    currency VARCHAR(3) DEFAULT 'Bs',
    active TINYINT(1) NOT NULL DEFAULT 1,   -- ← NUEVA COLUMNA
    deleted_at DATETIME NULL,                -- ← NUEVA COLUMNA
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO products (id, name, price, image_url, description, category, rating, specs, stock) VALUES
(1, 'Sensor inductivo prt12-4dp', 150.00, 'https://http2.mlstatic.com/D_Q_NP_2X_907785-MLV42256115993_062020-E.webp', 'Sensores Autonics Inductivos...', 'Sensores', 4.5, '', 45),
(2, 'Boton pulsador Autonics Nc S3pf-p1rb', 75.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_927922-MLV52483035472_112022-F.webp', 'Boton pulsador Autonics modelo S3pf-p1rb...', 'Botoneras', 4.0, '', 65),
(3, 'Rele termico regulable 48-65a Ldr365', 320.00, 'https://http2.mlstatic.com/D_Q_NP_2X_971966-MLV42316060787_062020-E.webp', 'Rele termic regulable 48-65a...', 'Relés', 5.0, '', 25),
(4, 'Guardamotor', 280.00, 'https://http2.mlstatic.com/D_Q_NP_2X_987302-MLV42319903598_062020-E.webp', ' Marca Schneider Electric. modelo Gv2me08', 'Protecciones', 4.2, '', 40),
(5, 'Termometro infrarrojo', 450.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_780836-MLV48799544246_012022-F.webp', 'Termómetro Infrarrojo -32°c A 1050°c marca unit-t modelo ut302d.', 'Instrumentos de Medición', 4.8, '', 18),
(6, 'Botonera colgante', 180.00, 'https://http2.mlstatic.com/D_Q_NP_2X_605998-MLV91579814235_092025-E.webp', 'Botonera colgante de 6 pulsadores Marca schneider electric modelo xaca671 material propipolineno.', 'Botoneras', 4.1, '', 35),
(7, 'Sensor fotoelectrico mfr', 220.00, 'https://http2.mlstatic.com/D_Q_NP_2X_781132-MLV90889351684_082025-E.webp', 'Sensor fotorelectrico Autonics bx5m.', 'Sensores', 4.6, '', 42),
(8, 'Pinza amperimetrica digital', 120.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_919873-MLV50246492941_062022-F.webp', 'Marca uni-t Modelo Ut201+', 'Instrumentos de Medición', 4.9, '', 28),
(9, 'Rele de nivel para conductores', 185.00, 'https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp', 'marca exceline modelo grn-mv.', 'Relés', 4.3, '', 32),
(10, 'Manometro festo', 95.00, 'https://http2.mlstatic.com/D_Q_NP_2X_782534-MLV80960384399_112024-E.webp', 'Marca festo modelo ma-50-10-1/4-enef162838.', 'Instrumentos de Medición', 4.4, '', 55),
(11, 'Mini termo anemometro y medidor de humedad', 650.00, 'https://http2.mlstatic.com/D_Q_NP_2X_954754-MLV76879763367_062024-E.webp', 'Marca Extech Modelo 45158.', 'Instrumentos de Medición', 4.0, '', 12),
(12, 'Sensor capacitivo Autonics', 210.00, 'https://http2.mlstatic.com/D_Q_NP_2X_764408-MLV42258601667_062020-E.webp', 'Marca Autonics Modelo Cr18-8ac.', 'Sensores', 4.7, '', 38),
(13, 'Selector 2 posiciones', 45.00, 'https://http2.mlstatic.com/D_Q_NP_2X_946997-MLV46271812962_062021-E.webp', 'Marca Scneider Electric Modelo XB4BD21.', 'Controles', 4.5, '', 70),
(14, 'Etiquetadora panduit', 2800.00, 'https://http2.mlstatic.com/D_Q_NP_2X_845848-MLV75886383737_042024-E.webp', 'Marca Extech Modelo PanTher LS8E.', 'Herramientas', 4.8, '', 5),
(15, 'Rele de nivel para lquidos conductores', 185.00, 'https://http2.mlstatic.com/D_Q_NP_2X_684159-MLV42253139692_062020-E.webp', 'Marca Exceline Modelo Grn-mv Voltaje 110-220.', 'Relés', 4.2, '', 32),
(16, 'Rele de estado solido Autonics', 125.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_823517-MLV49140687804_022022-F.webp', 'Marca Autonics Modelo SR1-4415.', 'Relés', 4.5, '', 48),
(17, 'Final de carrera', 195.00, 'https://http2.mlstatic.com/D_Q_NP_2X_853654-MLV42315651853_062020-E.webp', 'Marca Telemecanique/schneider Modelo XCKJO513.', 'Sensores', 4.6, '', 28),
(18, 'Final de carrera', 95.00, 'https://http2.mlstatic.com/D_Q_NP_2X_854049-MLV42347247961_062020-E.webp', 'Marca scheneider/telemecanique XCKP2121G11.', 'Sensores', 4.7, '', 62),
(19, 'Contador temporizador', 280.00, 'https://http2.mlstatic.com/D_Q_NP_2X_868764-MLV82980146035_032025-E.webp', 'Marca Autonics Modelo CT6Y-1P2.', 'Temporizadores', 4.7, '', 22),
(20, 'Pinza amperimetrica', 135.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_685608-MLV43035297361_082020-F.webp', 'Marca uni-t Modelo UT202a+.', 'Instrumentos de Medición', 4.7, '', 35),
(21, 'Contador temporizador', 280.00, 'https://http2.mlstatic.com/D_Q_NP_2X_943529-MLV82980293127_032025-E.webp', 'Marca Autonics Modelo CT6Y-1P4.', 'Temporizadores', 4.7, '', 22),
(22, 'Pinza amperimetrica extech', 420.00, 'https://http2.mlstatic.com/D_Q_NP_2X_891753-MLV48858956084_012022-E.webp', 'Marca extech Modelo UT210d.', 'Instrumentos de Medición', 4.7, '', 15),
(23, 'Sensor TIq5mc1 Jootiden', 60.00, 'https://http2.mlstatic.com/D_Q_NP_2X_983049-MLV78136901025_082024-E.webp', 'Marca generica Modelo TL-Q5MC1.', 'Sensores', 4.7, '', 85),
(24, 'Pinza amperimetrica + termometro', 220.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_826593-MLV46165148147_052021-F.webp', 'Marca extech modelo EX470.', 'Instrumentos de Medición', 4.8, '', 25),
(25, 'Final de carrera', 95.00, 'https://http2.mlstatic.com/D_Q_NP_2X_904301-MLV42315881121_062020-E.webp', 'Marca schneider/telemecanique Modelo XCKP2118G11.', 'Sensores', 4.7, '', 62),
(26, 'Contactor 25amp 24vdc', 380.00, 'https://images.wiautomation.com/public/images/landing/anticipa/product/LC1DT206SLS207.jpg', 'Marca scheider electric Modelo LCD1E25BD.', 'Contactores', 4.7, '', 18),
(27, 'Contactor 80amp 220v', 680.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_774386-MLV42329989223_062020-F.webp', 'Marca scheneider electric Modelo LC1d80m.', 'Contactores', 4.7, '', 10),
(28, 'osiloscopio extech', 850.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-F.webp', 'Marca scheneider electric Modelo LC1D09BD.', 'Instrumentos de Medición', 4.7, '', 8),
(29, 'Pinza amperimetrica digital', 120.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_928642-MLV54457071668_032023-F.webp', 'Marca uni-t Modelo UT201+.', 'Instrumentos de Medición', 4.7, '', 35),
(30, 'Fuente de poder 5amp 12vdc Aunonics', 185.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_606115-MLV82504917240_022025-F.webp', 'Marca scheneider electric Modelo SPB-O6O-12.', 'Fuentes de Poder', 4.7, '', 30),
(31, 'kit maletin legrand starfix', 320.00, 'https://http2.mlstatic.com/D_Q_NP_2X_983177-MLV71749528286_092023-E.webp', 'Marca lengard Modelo 376 59/60.', 'Herramientas', 4.7, '', 20),
(32, 'Controlador de temperatura ', 290.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_966407-MLV54265777533_032023-F.webp', 'Marca Autonics Modelo tk4s-bn4r.', 'Controladores', 4.7, '', 16),
(33, 'Descanso ajustable para pie', 450.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_978007-MLV71801855063_092023-F.webp', 'Marca lengard Modelo 376 59/60.', 'Accesorios', 4.7, '', 12),
(34, 'Controlador de temperaura 48x69', 210.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_821401-MLV73213656021_122023-F.webp', 'Marca 3M Modelo FR53OCB.', 'Controladores', 4.7, '', 22),
(35, 'Temporizador Autonics', 140.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_956520-MLV52366651303_112022-F.webp', 'Marca Autonics Modelo Le8n-bfle8n-bn.', 'Temporizadores', 4.7, '', 38),
(36, 'Controlador temporizador', 280.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_674477-MLV51061339386_082022-F.webp', 'Marca Autonics Modelo Ct6-1p2.', 'Temporizadores', 4.7, '', 22),
(37, 'Multimetro uni-t', 150.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_841899-MLV46427086846_062021-F.webp', 'Marca uni-t Modelo Ut89x.', 'Instrumentos de Medición', 4.7, '', 30),
(38, 'Sensor amplificador para fibra optica', 200.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_964670-MLV42255017336_062020-F.webp', 'Marca Autonics Modelo Bf4rp.', 'Sensores', 4.7, '', 25),
(39, 'Rele estado solido trifasico 30amp', 280.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_754022-MLV82085220364_022025-F.webp', 'Marca Autonics Modelo Sr3-4430.', 'Relés', 4.7, '', 18),
(40, 'Sensor fotoelectrico', 210.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_656456-MLV42255069136_062020-F.webp', 'Marca Autonics Modelo Brqm400-ddta.', 'Sensores', 4.7, '', 28),
(41, 'Contactor 40amp 110v', 420.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_697183-MLV81969170376_022025-F.webp', 'Marca Schneider Electric Modelo LCD1D4OAF7.', 'Contactores', 4.7, '', 15),
(42, 'Fuente de poder 8amp 12vdc', 240.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_731782-MLV78217056967_082024-F.webp', 'Marca Autonics Modelo SPB-120-12.', 'Fuentes de Poder', 4.7, '', 20),
(43, 'Rele termic regulable 30-40a', 340.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_719268-MLV42301142622_062020-F.webp', 'Marca scheneider electric Modelo LRD3355.', 'Relés', 4.7, '', 16),
(44, 'Guardamotor 1-1.6a', 290.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_842891-MLV42319762831_062020-F.webp', 'Marca Schneider Electric Modelo GV2ME06 .', 'Protecciones', 4.7, '', 18),
(45, 'Idicadores de frecuencia uni-t', 300.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_892854-MLV48915410648_012022-F.webp', 'Marca uni-t Modelo Ut261a.', 'Instrumentos de Medición', 4.7, '', 15),
(46, 'Protector televisor', 85.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_728315-MLV46442590142_062021-F.webp', 'Marca Exceline Modelo Gsm-tv120.', 'Protecciones', 4.7, '', 55),
(47, 'Rele estado solido', 125.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_692640-MLV49139833433_022022-F.webp', 'Marca Autonics Modelo Sr1-1450.', 'Relés', 4.7, '', 48),
(48, 'Fuente de poder 20amp 12vdc', 380.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_987249-MLV73944702421_012024-F.webp', 'Marca Autonics Modelo Sp240-12.', 'Fuentes de Poder', 4.7, '', 12),
(49, 'Cable para sensor M8', 55.00, 'https://http2.mlstatic.com/D_Q_NP_2X_855871-MLV70628379777_072023-E.webp', 'Marca Telemecanique Modelo Xzcp0941l2.', 'Accesorios', 4.7, '', 90),
(50, 'Contactor 25amp 220v', 380.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_961256-MLV42321081111_062020-F.webp', 'Marca Scheneider electric Modelo Lc1d25m7.', 'Contactores', 4.7, '', 18),
(51, 'Variador de velociadad 5hp 440v', 4200.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_693722-MLA76246464467_052024-F.webp', 'Marca scheneider electric Modelo Atv320u40n4c .', 'Variadores', 4.7, '', 4),
(52, 'Sensor fotoelectrico reflectivo', 195.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_896757-MLV78646725763_082024-F.webp', 'Marca Autonics Modelo Brqm3m-pdta-c-p .', 'Sensores', 4.7, '', 28),
(53, 'Contactor 32amp 220v', 380.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_775520-MLV42321197517_062020-F.webp', 'Marca scheneider electric Modelo Lc1d32m7 .', 'Contactores', 4.7, '', 18),
(54, 'Rele termico regulable', 390.00, 'https://http2.mlstatic.com/D_Q_NP_2X_720989-MLV42320094116_062020-E.webp', 'Marca scheneider electric Modelo Lrd3357.', 'Relés', 4.7, '', 15),
(55, 'Caja para pulsadores plastica 3 huecos', 70.00, 'https://http2.mlstatic.com/D_Q_NP_2X_719143-MLV42346979496_062020-E.webp', 'Marca scheneider Modelo Xald03.', 'Accesorios', 4.7, '', 65),
(56, 'Temporizacion Rele estrella triangulo', 240.00, 'https://http2.mlstatic.com/D_Q_NP_2X_966925-MLV83523597338_042025-E.webp', 'Marca scheneider Modelo Re22r2qtmr.', 'Relés', 4.7, '', 22),
(57, 'Caja para pulsadores 2 huecos', 55.00, 'https://http2.mlstatic.com/D_Q_NP_2X_783226-MLV42347077873_062020-E.webp', 'Marca scheneider Modelo Xald02.', 'Accesorios', 4.7, '', 75),
(58, 'Contactor 18amp 24vdc', 330.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_669780-MLV42320450829_062020-E.webp', 'Marca scheneider electric Modelo Lc1d18bd.', 'Contactores', 4.7, '', 20),
(59, 'Contactor 38amp 220v', 420.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_761868-MLV42321342194_062020-F.webp', 'Marca scheneider electric Modelo Lc1d38m7.', 'Contactores', 4.7, '', 15),
(60, 'Guardamotor 48-65a', 580.00, 'https://http2.mlstatic.com/D_Q_NP_2X_879405-MLV42346668428_062020-E.webp', 'Marca scheneider electric Modelo Gv3p65.', 'Protecciones', 4.7, '', 12),
(61, 'Contactor 256a 220v', 3800.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp', 'Marca scheneider Modelo Lc1f265m7.', 'Contactores', 4.7, '', 3),
(62, 'Rele termico regulable', 390.00, 'https://http2.mlstatic.com/D_Q_NP_2X_719268-MLV42301142622_062020-E.webp', 'Marca scheneider electric Modelo Ldr3355.', 'Relés', 4.7, '', 15),
(63, 'Sensor de marca fotocelula', 850.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_965275-MLV42316005076_062020-F.webp', 'Marca Telemecanique.Modelo Xurk1ksmm12 ', 'Sensores', 4.7, '', 8),
(64, 'Variador de velocidad 7.5hp', 5800.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_617200-MLV46302539269_062021-F.webp', 'Marca scheneider electric Modelo Atv320u55m3c.', 'Variadores', 4.7, '', 3),
(65, 'Sensor inductivo', 95.00, 'https://http2.mlstatic.com/D_Q_NP_2X_835762-MLV50041103539_052022-E.webp', 'Marca Autonics Modelo prd18 8dp.', 'Sensores', 4.7, '', 62),
(66, 'Contactor 265a 220v', 3800.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_758918-MLV50182176910_062022-F.webp', 'Marca scheneider electric Modelo Lc1f265m7.', 'Contactores', 4.7, '', 3),
(67, 'Lockout 63amp seleccionador bloqueador', 550.00, 'https://http2.mlstatic.com/D_Q_NP_2X_651999-MLV42345796245_062020-E.webp', 'Marca scheneider Modelo Vcf5ge.', 'Accesorios', 4.7, '', 10),
(68, 'Lockout 100amp seleccionador bloqueador', 680.00, 'https://http2.mlstatic.com/D_Q_NP_2X_833469-MLV42331350351_062020-E.webp', 'Marca scheneider electric Modelo Vcf5gen.', 'Accesorios', 4.7, '', 8),
(69, 'Sensor inductivo de rotacion', 420.00, 'https://http2.mlstatic.com/D_Q_NP_2X_998055-MLV53428583531_012023-E.webp', 'Marca Telemecanique Modelo Xsav12373.', 'Sensores', 4.7, '', 12),
(70, 'Sensor', 140.00, 'https://http2.mlstatic.com/D_Q_NP_2X_912959-MLV42259148070_062020-E.webp', 'Marca Autonics Modelo Prcm30-5dp.', 'Sensores', 4.7, '', 38),
(71, 'Protctor para aires y refrigeradores', 95.00, 'https://http2.mlstatic.com/D_Q_NP_2X_995442-MLV42253383680_062020-E.webp', 'Marca Exceline Modelo Gsm-rt120.', 'Protecciones', 4.7, '', 55),
(72, 'Flotador electrico multivoltaje', 70.00, 'https://http2.mlstatic.com/D_Q_NP_2X_837451-MLV42253919704_062020-E.webp', 'Marca Exceline Modelo Gfe-mv3m.', 'Accesorios', 4.7, '', 65),
(73, 'Contactor 9amp 24vdc', 280.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_981637-MLV42320226910_062020-E.webp', 'Marca scheneider electric Modelo Lc190bd.', 'Contactores', 4.7, '', 22),
(74, 'Contactor 32amp 110v', 380.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_849936-MLV42321134886_062020-E.webp', 'Marca scheneider electric Modelo Lc1d32f7.', 'Contactores', 4.7, '', 18),
(75, 'Contactor 40amp 24vdc', 450.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_966221-MLV46232503172_062021-E.webp', 'Marca sccheneider electric Lc1d40ab7.', 'Contactores', 4.7, '', 15),
(76, 'Contactor 65amp 220v', 650.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_616564-MLV42329913652_062020-E.webp', 'Marca scheneider electric Modelo Lc1d65am7.', 'Contactores', 4.7, '', 12),
(77, 'Contactor 185amp 220v', 2500.00, 'https://http2.mlstatic.com/D_NQ_NP_2X_838661-MLV50182142214_062022-E.webp', 'Marca scheneider Modelo Lc1f185m7.', 'Contactores', 4.7, '', 5),
(78, 'Final de carrera', 95.00, 'https://http2.mlstatic.com/D_Q_NP_2X_921213-MLV42302675953_062020-E.webp', 'Marca scheneider/Telemecanique Modelo Xckp2127g11.', 'Sensores', 4.7, '', 62),
(79, 'Mini termometro infrrarojo', 125.00, 'https://http2.mlstatic.com/D_Q_NP_2X_887138-MLV50723282858_072022-E.webp', 'Marca uni-t Modelo Ut300a+.', 'Instrumentos de Medición', 4.7, '', 48),
(80, 'Protector para motores monofasicos', 85.00, 'https://http2.mlstatic.com/D_Q_NP_2X_721232-MLV42314862754_062020-E.webp', 'Marca exceline Modelo Gsm-r220b.', 'Protecciones', 4.7, '', 55);

UPDATE products SET sku = CONCAT('PROD-', LPAD(id, 4, '0')) WHERE sku IS NULL;
UPDATE products SET is_featured = TRUE WHERE id IN (1, 5, 8, 26, 41, 51, 64);

-- ##########################################################################
-- 3. TABLA DE HISTORIAL DE STOCK
-- ##########################################################################

CREATE TABLE IF NOT EXISTS historial_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    tipo ENUM('venta', 'compra', 'ajuste', 'devolucion') DEFAULT 'venta',
    referencia VARCHAR(100) NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 4. TABLAS DE ADMINISTRACIÓN Y CLIENTES
-- ##########################################################################

DROP TABLE IF EXISTS admin_users;
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'vendedor') DEFAULT 'admin',
    activo BOOLEAN DEFAULT TRUE,
    foto_perfil VARCHAR(255) NULL, 
    ultimo_login DATETIME,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO admin_users (nombre, correo, usuario, contrasena, rol) 
VALUES ('Administrador', 'picca.ventas@gmail.com', 'admin', '$2y$12$O6QpDDoKxZpxF3uXVZanlO63bFfxq6K.MVBIce5GNARLATXtiZCVa', 'superadmin');

INSERT INTO admin_users (nombre, correo, usuario, contrasena, rol) VALUES
('Vendedor 1', 'vendedor1@empresa.com', 'vendedor1', '$2y$12$gMI1iDfJK0DBxX8GLWS0vuwzwwJ1YV4N6NCxW8Rt3lFpgTX80ie5G', 'vendedor'),
('Admin 2', 'admin2@empresa.com', 'admin2', '$2y$12$WjlyDDRrqUaGXPewTXx88.L4.FpgJkicPR9zvXJ.ZmlY2jy81EziW', 'admin');

DROP TABLE IF EXISTS clientes;
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('cedula', 'ruc', 'pasaporte', 'dni') DEFAULT 'cedula',
    documento VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    ciudad VARCHAR(50),
    estado ENUM('activo', 'inactivo', 'moroso') DEFAULT 'activo',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, direccion, estado)
SELECT 'cedula', COALESCE(cedula, '00000000'), nombre, correo, telefono, COALESCE(direccion, 'Sin dirección'), estado
FROM users WHERE rol = 'usuario';

-- ##########################################################################
-- 5. FACTURACIÓN Y PEDIDOS - TABLAS CORREGIDAS CON DEFAULT VALUES
-- ##########################################################################

DROP TABLE IF EXISTS secuencias_facturacion;
CREATE TABLE secuencias_facturacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    prefijo VARCHAR(10) NOT NULL,
    siguiente_valor INT NOT NULL DEFAULT 1,
    longitud INT DEFAULT 6,
    anio INT,
    UNIQUE KEY (tipo, prefijo, anio)
);

INSERT INTO secuencias_facturacion (tipo, prefijo, siguiente_valor, longitud, anio) VALUES 
('pedido', 'PED-', 1, 6, YEAR(CURDATE())),
('factura', 'FAC-', 1, 6, YEAR(CURDATE()));

DROP TABLE IF EXISTS pedidos;
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL,
    usuario_id INT NULL,
    numero_pedido VARCHAR(20) UNIQUE NOT NULL,
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
    impuesto DECIMAL(10, 2) NOT NULL DEFAULT 0,
    iva DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado', 'facturado') DEFAULT 'pendiente',
    metodo_pago VARCHAR(50) NULL,
    referencia_pago VARCHAR(100) NULL,
    comprobante_pago VARCHAR(255) NULL,
    notas_cliente TEXT NULL,
    notas_internas TEXT NULL,
    observaciones TEXT NULL,
    fecha_facturacion DATETIME NULL,
    direccion_envio TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
);

DROP TABLE IF EXISTS pedido_detalles;
CREATE TABLE pedido_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10, 2) NOT NULL DEFAULT 0,
    precio_original DECIMAL(10, 2) DEFAULT 0,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
    producto_nombre VARCHAR(255) NULL,
    producto_sku VARCHAR(100) NULL,
    producto_categoria VARCHAR(100) NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS facturas;
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNIQUE NULL,
    cliente_id INT NOT NULL,
    numero_factura VARCHAR(20) UNIQUE NOT NULL,
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE NULL,
    subtotal DECIMAL(10, 2) DEFAULT 0,
    iva DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    metodo_pago VARCHAR(50) NULL,
    estado ENUM('pendiente', 'pagada', 'anulada') DEFAULT 'pendiente',
    usuario_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS factura_detalles;
CREATE TABLE factura_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10, 2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS movimientos_inventario;
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo_movimiento ENUM('entrada', 'salida', 'ajuste', 'devolucion') NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    descripcion TEXT NULL,
    referencia VARCHAR(100) NULL,
    usuario_id INT NULL,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- ##########################################################################
-- 6. TABLA DE CARRITO (cart_items)
-- ##########################################################################

CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ##########################################################################
-- 7. TABLA DE FAVORITOS
-- ##########################################################################

CREATE TABLE IF NOT EXISTS favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorito (usuario_id, producto_id),
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ##########################################################################
-- 8. TABLA DE PROVEEDORES
-- ##########################################################################

DROP TABLE IF EXISTS proveedores;
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre_comercial VARCHAR(150) NOT NULL,
    razon_social VARCHAR(200) NULL,
    ruc VARCHAR(20) UNIQUE NULL,
    tipo_documento ENUM('cedula', 'ruc', 'pasaporte', 'dni') DEFAULT 'ruc',
    direccion TEXT NULL,
    ciudad VARCHAR(100) NULL,
    telefono_principal VARCHAR(20) NOT NULL,
    telefono_secundario VARCHAR(20) NULL,
    email_principal VARCHAR(100) NOT NULL,
    email_secundario VARCHAR(100) NULL,
    contacto_nombre VARCHAR(100) NULL,
    contacto_cargo VARCHAR(100) NULL,
    sitio_web VARCHAR(255) NULL,
    condiciones_pago VARCHAR(100) NULL,
    plazo_entrega INT DEFAULT 0,
    forma_pago ENUM('transferencia', 'efectivo', 'cheque', 'mixto') DEFAULT 'transferencia',
    moneda ENUM('Bs', 'USD', 'EUR') DEFAULT 'Bs',
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    saldo_pendiente DECIMAL(12,2) DEFAULT 0.00,
    calificacion DECIMAL(2,1) DEFAULT 0.0,
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_ruc (ruc),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO proveedores (codigo, nombre_comercial, razon_social, ruc, direccion, ciudad, telefono_principal, email_principal, contacto_nombre, estado) VALUES
('PROV-001', 'Autonics Venezuela', 'Autonics C.A.', 'J-12345678-9', 'Av. Principal, Zona Industrial', 'Caracas', '0212-5551234', 'ventas@autonics.com.ve', 'Carlos Méndez', 'activo'),
('PROV-002', 'Schneider Electric', 'Schneider Electric Venezuela', 'J-87654321-0', 'Calle 5, Parque Industrial', 'Valencia', '0241-5555678', 'ventas@schneider.com.ve', 'Ana Rodríguez', 'activo'),
('PROV-003', 'UNI-T Venezuela', 'UNI-T Instruments C.A.', 'J-11223344-5', 'Av. Libertador, Centro Comercial', 'Maracaibo', '0261-5559012', 'importaciones@unit.com.ve', 'Luis Fernández', 'activo');

-- ##########################################################################
-- 8. TABLA DE COMPRAS
-- ##########################################################################

DROP TABLE IF EXISTS compras;
CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_orden VARCHAR(50) UNIQUE NOT NULL,
    proveedor_id INT NOT NULL,
    fecha_orden DATE NOT NULL,
    fecha_requerida DATE NULL,
    fecha_recibido DATE NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    iva DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    descuento DECIMAL(12,2) DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estado ENUM('cotizacion', 'aprobada', 'enviada', 'recibida_parcial', 'recibida_total', 'anulada') DEFAULT 'cotizacion',
    metodo_pago ENUM('transferencia', 'efectivo', 'cheque', 'credito') DEFAULT 'transferencia',
    condiciones_pago VARCHAR(100) NULL,
    usuario_creacion_id INT NOT NULL,
    usuario_aprobacion_id INT NULL,
    fecha_aprobacion DATETIME NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proveedor_id (proveedor_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_orden (fecha_orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 8.1 TABLA DE DETALLES DE COMPRA
-- ##########################################################################

DROP TABLE IF EXISTS compra_detalles;

CREATE TABLE compra_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compra_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_compra_id (compra_id),
    INDEX idx_producto_id (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 9. TABLA DE CAJA Y MOVIMIENTOS
-- ##########################################################################

DROP TABLE IF EXISTS caja_arqueos;
CREATE TABLE caja_arqueos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_arqueo VARCHAR(50) UNIQUE NOT NULL,
    fecha_apertura DATETIME NOT NULL,
    fecha_cierre DATETIME NULL,
    usuario_apertura_id INT NOT NULL,
    usuario_cierre_id INT NULL,
    monto_inicial DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    monto_ingresos DECIMAL(12,2) DEFAULT 0.00,
    monto_egresos DECIMAL(12,2) DEFAULT 0.00,
    monto_esperado DECIMAL(12,2) DEFAULT 0.00,
    monto_real DECIMAL(12,2) DEFAULT 0.00,
    diferencia DECIMAL(12,2) DEFAULT 0.00,
    estado ENUM('abierta', 'cerrada', 'suspendida') DEFAULT 'abierta',
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_fecha_apertura (fecha_apertura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS caja_movimientos;
CREATE TABLE caja_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arqueo_id INT NOT NULL,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    descripcion TEXT NOT NULL,
    referencia VARCHAR(100) NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'cheque', 'pago_movil') DEFAULT 'efectivo',
    usuario_id INT NOT NULL,
    fecha_movimiento DATETIME DEFAULT CURRENT_TIMESTAMP,
    factura_id INT NULL,
    INDEX idx_arqueo_id (arqueo_id),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha_movimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 10. TABLA DE CONFIGURACIÓN DEL SISTEMA
-- ##########################################################################

DROP TABLE IF EXISTS configuracion_sistema;
CREATE TABLE configuracion_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NULL,
    tipo VARCHAR(50) DEFAULT 'text',
    grupo VARCHAR(50) DEFAULT 'general',
    descripcion VARCHAR(255) NULL,
    editable BOOLEAN DEFAULT TRUE,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_grupo (grupo),
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden) VALUES
('empresa_nombre', 'PIC - Productos Industriales y Comerciales', 'text', 'empresa', 'Nombre de la empresa', 1, 1),
('empresa_rif', 'J-12345678-9', 'text', 'empresa', 'RIF de la empresa', 1, 2),
('empresa_direccion', 'Av. Principal, Zona Industrial, Caracas', 'text', 'empresa', 'Dirección de la empresa', 1, 3),
('empresa_telefono', '0212-5551234', 'text', 'empresa', 'Teléfono de contacto', 1, 4),
('empresa_email', 'info@pic.com.ve', 'email', 'empresa', 'Email de contacto', 1, 5),
('iva_porcentaje', '16', 'number', 'facturacion', 'Porcentaje de IVA aplicado', 1, 10),
('moneda_principal', 'Bs', 'text', 'facturacion', 'Moneda principal del sistema', 1, 11),
('factura_prefijo', 'FAC', 'text', 'facturacion', 'Prefijo para números de factura', 1, 12),
('factura_longitud', '6', 'number', 'facturacion', 'Longitud del correlativo', 1, 13),
('notificaciones_email', '1', 'boolean', 'notificaciones', 'Enviar notificaciones por email', 1, 20),
('notificaciones_whatsapp', '0', 'boolean', 'notificaciones', 'Enviar notificaciones por WhatsApp', 1, 21),
('stock_minimo_alerta', '5', 'number', 'inventario', 'Stock mínimo para alertas', 1, 30),
('modo_mantenimiento', '0', 'boolean', 'sistema', 'Modo mantenimiento del sistema', 1, 40),
('version_sistema', '2.0.0', 'text', 'sistema', 'Versión actual del sistema', 0, 41);

-- ##########################################################################
-- 11. TABLA DE BACKUPS
-- ##########################################################################

DROP TABLE IF EXISTS backups;
CREATE TABLE backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(512) NOT NULL,
    tamanio_bytes BIGINT NOT NULL,
    tipo ENUM('completo', 'estructura', 'datos') DEFAULT 'completo',
    estado ENUM('completado', 'fallido', 'en_progreso') DEFAULT 'completado',
    usuario_id INT NOT NULL,
    descripcion TEXT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_eliminacion DATETIME NULL,
    INDEX idx_fecha (fecha_creacion),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 12. TABLA DE AUDITORÍA (CORREGIDA CON COLUMNAS PARA HISTORIAL)
-- ##########################################################################

DROP TABLE IF EXISTS auditoria_logs;
CREATE TABLE auditoria_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    usuario_nombre VARCHAR(100) NULL,
    usuario_rol VARCHAR(50) NULL,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descripcion TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    tabla_afectada VARCHAR(100) NULL,
    registro_id INT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- NUEVAS COLUMNAS PARA HISTORIAL DE EDICIONES
    edit_count INT NOT NULL DEFAULT 0,
    edit_history TEXT NULL,
    last_edit_by INT NULL,
    last_edit_at DATETIME NULL,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_modulo (modulo),
    INDEX idx_fecha (fecha_creacion),
    INDEX idx_registro (tabla_afectada, registro_id),
    INDEX idx_last_edit_by (last_edit_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 13. PROCEDIMIENTOS ALMACENADOS
-- ##########################################################################

DROP PROCEDURE IF EXISTS sp_generar_numero_pedido;
DELIMITER //
CREATE PROCEDURE sp_generar_numero_pedido(OUT numero_pedido VARCHAR(20))
BEGIN
    DECLARE anio_actual INT;
    DECLARE siguiente INT;
    DECLARE prefijo VARCHAR(10);
    
    SET anio_actual = YEAR(CURDATE());
    SET prefijo = 'PED-';
    
    START TRANSACTION;
    
    SELECT siguiente_valor INTO siguiente 
    FROM secuencias_facturacion 
    WHERE tipo = 'pedido' AND anio = anio_actual
    FOR UPDATE;
    
    IF siguiente IS NULL THEN
        INSERT INTO secuencias_facturacion (tipo, prefijo, siguiente_valor, longitud, anio)
        VALUES ('pedido', prefijo, 2, 6, anio_actual);
        SET siguiente = 1;
    ELSE
        UPDATE secuencias_facturacion 
        SET siguiente_valor = siguiente_valor + 1 
        WHERE tipo = 'pedido' AND anio = anio_actual;
    END IF;
    
    COMMIT;
    
    SET numero_pedido = CONCAT(prefijo, anio_actual, '-', LPAD(siguiente, 6, '0'));
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_generar_numero_factura;
DELIMITER //
CREATE PROCEDURE sp_generar_numero_factura(OUT numero_factura VARCHAR(20))
BEGIN
    DECLARE anio_actual INT;
    DECLARE siguiente INT;
    DECLARE prefijo VARCHAR(10);
    
    SET anio_actual = YEAR(CURDATE());
    SET prefijo = 'FAC-';
    
    START TRANSACTION;
    
    SELECT siguiente_valor INTO siguiente 
    FROM secuencias_facturacion 
    WHERE tipo = 'factura' AND anio = anio_actual
    FOR UPDATE;
    
    IF siguiente IS NULL THEN
        INSERT INTO secuencias_facturacion (tipo, prefijo, siguiente_valor, longitud, anio)
        VALUES ('factura', prefijo, 2, 6, anio_actual);
        SET siguiente = 1;
    ELSE
        UPDATE secuencias_facturacion 
        SET siguiente_valor = siguiente_valor + 1 
        WHERE tipo = 'factura' AND anio = anio_actual;
    END IF;
    
    COMMIT;
    
    SET numero_factura = CONCAT(prefijo, anio_actual, '-', LPAD(siguiente, 6, '0'));
END//
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;

-- ##########################################################################
-- 14. VERIFICACIÓN FINAL
-- ##########################################################################

SELECT '========================================' as '';
SELECT '=== BASE DE DATOS COMPLETADA EXITOSAMENTE ===' as Mensaje;
SELECT '========================================' as '';

SELECT 
    (SELECT COUNT(*) FROM users) as 'Total Usuarios',
    (SELECT COUNT(*) FROM admin_users) as 'Total Administradores',
    (SELECT COUNT(*) FROM products) as 'Total Productos',
    (SELECT COUNT(*) FROM clientes) as 'Total Clientes',
    (SELECT COUNT(*) FROM pedidos) as 'Total Pedidos',
    (SELECT COUNT(*) FROM facturas) as 'Total Facturas',
    (SELECT COUNT(*) FROM proveedores) as 'Total Proveedores',
    (SELECT COUNT(*) FROM historial_stock) as 'Registros Historial Stock';

-- ##########################################################################
-- 15. CORRECCIÓN DE MOVIMIENTOS_INVENTARIO
-- ##########################################################################

-- Eliminar la llave foránea problemática si existe
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'movimientos_inventario' 
                  AND CONSTRAINT_NAME = 'movimientos_inventario_ibfk_2');

SET @sql = IF(@fk_exists = 1, 'ALTER TABLE movimientos_inventario DROP FOREIGN KEY movimientos_inventario_ibfk_2', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modificar la columna usuario_id
ALTER TABLE movimientos_inventario MODIFY COLUMN usuario_id INT NULL;

-- Eliminar cualquier otra llave foránea que pueda causar problemas
SET @fk_exists2 = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                   WHERE CONSTRAINT_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'movimientos_inventario' 
                   AND CONSTRAINT_NAME = 'movimientos_inventario_ibfk_3');

SET @sql2 = IF(@fk_exists2 = 1, 'ALTER TABLE movimientos_inventario DROP FOREIGN KEY movimientos_inventario_ibfk_3', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SELECT '========================================' as '';
SELECT '=== FIN DE LA INSTALACIÓN ===' as MensajeFinal;
SELECT '========================================' as '';