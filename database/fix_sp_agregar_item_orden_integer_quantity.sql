USE polleria_pos_pro;

DROP PROCEDURE IF EXISTS sp_agregar_item_orden;

DELIMITER $$

CREATE PROCEDURE sp_agregar_item_orden(
    IN p_id_orden BIGINT UNSIGNED,
    IN p_id_producto BIGINT UNSIGNED,
    IN p_cantidad DECIMAL(12,2),
    IN p_observacion VARCHAR(255)
)
BEGIN
    DECLARE v_nombre VARCHAR(150);
    DECLARE v_precio DECIMAL(12,2);
    DECLARE v_area BIGINT UNSIGNED;

    SELECT nombre, precio_venta, id_area_preparacion
      INTO v_nombre, v_precio, v_area
    FROM productos
    WHERE id = p_id_producto
      AND activo = 1;

    IF v_nombre IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto no encontrado o inactivo.';
    END IF;

    IF p_cantidad <= 0 OR p_cantidad <> FLOOR(p_cantidad) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad debe ser un numero entero mayor a cero.';
    END IF;

    INSERT INTO orden_detalle(
        id_orden,
        id_producto,
        nombre_producto,
        precio_unitario,
        cantidad,
        subtotal,
        observacion,
        id_area_preparacion
    )
    VALUES (
        p_id_orden,
        p_id_producto,
        v_nombre,
        v_precio,
        FLOOR(p_cantidad),
        ROUND(v_precio * FLOOR(p_cantidad), 2),
        p_observacion,
        v_area
    );

    CALL sp_recalcular_totales_orden(p_id_orden);
END$$

DELIMITER ;
