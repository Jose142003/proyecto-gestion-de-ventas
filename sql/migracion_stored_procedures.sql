-- Stored procedures faltantes para el proceso de compra
-- Crea sp_crear_pedido y sp_agregar_producto_pedido

DROP PROCEDURE IF EXISTS sp_crear_pedido;
DELIMITER //
CREATE PROCEDURE sp_crear_pedido(
    IN p_usuario_id INT,
    IN p_metodo_pago VARCHAR(50),
    IN p_direccion_envio TEXT,
    IN p_telefono VARCHAR(20),
    IN p_nombre_receptor VARCHAR(255),
    IN p_observaciones TEXT,
    OUT o_pedido_id INT,
    OUT o_numero_pedido VARCHAR(20)
)
BEGIN
    DECLARE v_numero VARCHAR(20);

    CALL sp_generar_numero_pedido(v_numero);

    INSERT INTO pedidos (
        usuario_id, numero_pedido, metodo_pago,
        direccion_envio, notas_cliente, observaciones,
        estado, created_at, updated_at
    ) VALUES (
        p_usuario_id, v_numero, p_metodo_pago,
        p_direccion_envio, p_telefono, p_observaciones,
        'pendiente', NOW(), NOW()
    );

    SET o_pedido_id = LAST_INSERT_ID();
    SET o_numero_pedido = v_numero;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_agregar_producto_pedido;
DELIMITER //
CREATE PROCEDURE sp_agregar_producto_pedido(
    IN p_pedido_id INT,
    IN p_producto_id INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_nombre VARCHAR(255);
    DECLARE v_sku VARCHAR(100);
    DECLARE v_categoria VARCHAR(100);

    SELECT price, name, sku, category
    INTO v_precio, v_nombre, v_sku, v_categoria
    FROM products WHERE id = p_producto_id;

    INSERT INTO pedido_detalles (
        pedido_id, producto_id, cantidad,
        precio_unitario, precio_original, subtotal,
        producto_nombre, producto_sku, producto_categoria
    ) VALUES (
        p_pedido_id, p_producto_id, p_cantidad,
        v_precio, v_precio, v_precio * p_cantidad,
        v_nombre, v_sku, v_categoria
    );

    UPDATE products SET stock = stock - p_cantidad
    WHERE id = p_producto_id;
END//
DELIMITER ;
