USE polleria_pos_pro;

INSERT INTO orden_estados_historial(
    id_orden,
    estado_anterior,
    estado_nuevo,
    comentario,
    fecha,
    id_usuario
)
SELECT
    o.id,
    NULL,
    o.estado,
    'Historial inicial recuperado tras correccion de sp_crear_orden',
    o.fecha_creacion,
    o.id_usuario_creador
FROM ordenes o
LEFT JOIN orden_estados_historial oeh
    ON oeh.id_orden = o.id
WHERE oeh.id_orden IS NULL;
