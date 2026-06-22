<?php
require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS envios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        pedido_numero VARCHAR(20) NOT NULL,
        transportista VARCHAR(100) NOT NULL DEFAULT '',
        numero_guia VARCHAR(100) NOT NULL DEFAULT '',
        url_rastreo VARCHAR(500) NOT NULL DEFAULT '',
        fecha_envio DATETIME DEFAULT NULL,
        fecha_estimada_entrega DATETIME DEFAULT NULL,
        fecha_entrega DATETIME DEFAULT NULL,
        estado ENUM('preparando','en_transito','en_reparto','entregado','fallido') DEFAULT 'preparando',
        notas TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
        UNIQUE KEY unique_pedido_envio (pedido_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla envios OK\n";
    
    foreach ([
        'costo_envio DECIMAL(10,2) DEFAULT 0.00 AFTER direccion_envio',
        'transportista VARCHAR(100) DEFAULT \'\' AFTER costo_envio',
        'numero_guia VARCHAR(100) DEFAULT \'\' AFTER transportista'
    ] as $col) {
        try {
            $pdo->exec("ALTER TABLE pedidos ADD COLUMN $col");
            echo "Columna agregada: $col\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) throw $e;
            echo "Columna ya existe, saltando\n";
        }
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS resenas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto_id INT NOT NULL,
        usuario_id INT NOT NULL,
        pedido_id INT DEFAULT NULL,
        puntuacion TINYINT NOT NULL,
        titulo VARCHAR(255) DEFAULT '',
        comentario TEXT,
        es_compra_verificada TINYINT(1) DEFAULT 0,
        moderado TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (producto_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL,
        UNIQUE KEY unique_resena_usuario_producto (producto_id, usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla resenas OK\n";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS envios_historial (
        id INT AUTO_INCREMENT PRIMARY KEY,
        envio_id INT NOT NULL,
        estado_anterior VARCHAR(50) DEFAULT '',
        estado_nuevo VARCHAR(50) NOT NULL,
        ubicacion VARCHAR(255) DEFAULT '',
        descripcion TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (envio_id) REFERENCES envios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla envios_historial OK\n";
    
    $pdo->exec("CREATE OR REPLACE VIEW v_producto_rating AS
        SELECT p.id AS producto_id, p.name,
            COALESCE(AVG(r.puntuacion), 0) AS rating_promedio,
            COUNT(r.id) AS total_resenas,
            COUNT(CASE WHEN r.puntuacion = 5 THEN 1 END) AS cinco,
            COUNT(CASE WHEN r.puntuacion = 4 THEN 1 END) AS cuatro,
            COUNT(CASE WHEN r.puntuacion = 3 THEN 1 END) AS tres,
            COUNT(CASE WHEN r.puntuacion = 2 THEN 1 END) AS dos,
            COUNT(CASE WHEN r.puntuacion = 1 THEN 1 END) AS uno
        FROM products p
        LEFT JOIN resenas r ON p.id = r.producto_id AND r.moderado = 0
        GROUP BY p.id, p.name");
    echo "Vista v_producto_rating OK\n";
    
    try {
        $pdo->exec("ALTER TABLE products ADD FULLTEXT INDEX ft_productos_busqueda (name, description)");
        echo "Fulltext index OK\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) throw $e;
        echo "Fulltext index ya existe\n";
    }
    
    echo "\nMigracion completada exitosamente!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
