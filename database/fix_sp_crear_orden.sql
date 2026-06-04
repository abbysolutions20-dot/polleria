USE polleria_pos_pro;

DROP PROCEDURE IF EXISTS sp_crear_orden;

DELIMITER $$

CREATE PROCEDURE sp_crear_orden(
    IN p_id_sucursal BIGINT UNSIGNED,
    IN p_id_mesa BIGINT UNSIGNED,
    IN p_id_cliente BIGINT UNSIGNED,
    IN p_id_usuario BIGINT UNSIGNED,
    IN p_tipo_servicio VARCHAR(20),
    IN p_cantidad_personas INT,
    IN p_observaciones VARCHAR(255)
)
BEGIN
    DECLARE v_correlativo BIGINT DEFAULT 1;
    DECLARE v_codigo VARCHAR(50);
    DECLARE v_id_orden BIGINT UNSIGNED;

    SELECT COALESCE(MAX(numero_correlativo),0)+1
      INTO v_correlativo
    FROM ordenes
    WHERE id_sucursal = p_id_sucursal;

    SET v_codigo = CONCAT('ORD-', LPAD(p_id_sucursal,2,'0'), '-', LPAD(v_correlativo,8,'0'));

    INSERT INTO ordenes(
        codigo, numero_correlativo, id_sucursal, id_mesa, id_cliente, id_usuario_creador,
        tipo_servicio, cantidad_personas, observaciones, estado
    )
    VALUES(
        v_codigo, v_correlativo, p_id_sucursal, p_id_mesa, p_id_cliente, p_id_usuario,
        p_tipo_servicio, p_cantidad_personas, p_observaciones, 'ABIERTA'
    );

    SET v_id_orden = LAST_INSERT_ID();

    IF p_id_mesa IS NOT NULL THEN
        UPDATE mesas SET estado = 'OCUPADA' WHERE id = p_id_mesa;
        INSERT INTO historial_mesas(id_mesa, estado_anterior, estado_nuevo, motivo, id_orden, id_usuario)
        VALUES (p_id_mesa, 'LIBRE', 'OCUPADA', 'Apertura de orden', v_id_orden, p_id_usuario);
    END IF;

    INSERT INTO orden_estados_historial(id_orden, estado_anterior, estado_nuevo, comentario, id_usuario)
    VALUES (v_id_orden, NULL, 'ABIERTA', 'Orden creada', p_id_usuario);

    SELECT v_id_orden AS id_orden, v_codigo AS codigo_orden;
END$$

DELIMITER ;
