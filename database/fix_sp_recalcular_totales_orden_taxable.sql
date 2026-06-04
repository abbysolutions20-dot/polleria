USE polleria_pos_pro;

DROP PROCEDURE IF EXISTS sp_recalcular_totales_orden;

DELIMITER $$

CREATE PROCEDURE sp_recalcular_totales_orden(IN p_id_orden BIGINT UNSIGNED)
BEGIN
    DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_subtotal_afecto DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_igv DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_descuento DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_descuento_afecto DECIMAL(12,2) DEFAULT 0.00;

    SELECT
        COALESCE(SUM(od.subtotal), 0),
        COALESCE(SUM(CASE WHEN COALESCE(p.afecto_igv, 0) = 1 THEN od.subtotal ELSE 0 END), 0)
      INTO v_subtotal, v_subtotal_afecto
    FROM orden_detalle od
    LEFT JOIN productos p ON p.id = od.id_producto
    WHERE od.id_orden = p_id_orden
      AND od.estado_item <> 'ANULADO';

    SELECT COALESCE(descuento,0)
      INTO v_descuento
    FROM ordenes
    WHERE id = p_id_orden;

    SET v_descuento = LEAST(v_descuento, v_subtotal);
    SET v_descuento_afecto = CASE
        WHEN v_subtotal > 0 THEN ROUND(v_descuento * (v_subtotal_afecto / v_subtotal), 2)
        ELSE 0.00
    END;
    SET v_igv = ROUND(
        GREATEST(v_subtotal_afecto - v_descuento_afecto, 0)
        - (GREATEST(v_subtotal_afecto - v_descuento_afecto, 0) / 1.18),
        2
    );

    UPDATE ordenes
       SET subtotal = v_subtotal,
           igv = v_igv,
           total = ROUND(v_subtotal - v_descuento, 2),
           fecha_actualizacion = NOW()
     WHERE id = p_id_orden;
END$$

DELIMITER ;

DROP PROCEDURE IF EXISTS sp_recalcular_totales_orden_activos;

DELIMITER $$

CREATE PROCEDURE sp_recalcular_totales_orden_activos()
BEGIN
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_id_orden BIGINT UNSIGNED;

    DECLARE cur_ordenes CURSOR FOR
        SELECT DISTINCT od.id_orden
        FROM orden_detalle od
        INNER JOIN ordenes o ON o.id = od.id_orden
        WHERE o.estado IN ('BORRADOR', 'ABIERTA', 'EN_PREPARACION', 'LISTA', 'SERVIDA', 'COMPLETADA');

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    OPEN cur_ordenes;

    recalc_loop: LOOP
        FETCH cur_ordenes INTO v_id_orden;

        IF v_done = 1 THEN
            LEAVE recalc_loop;
        END IF;

        CALL sp_recalcular_totales_orden(v_id_orden);
    END LOOP;

    CLOSE cur_ordenes;
END$$

DELIMITER ;

CALL sp_recalcular_totales_orden_activos();

DROP PROCEDURE IF EXISTS sp_recalcular_totales_orden_activos;
