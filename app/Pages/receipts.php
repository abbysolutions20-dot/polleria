<?php

$receiptCountToday = (int) $db->value(
    "SELECT COUNT(*)
     FROM comprobantes
     WHERE DATE(fecha_emision) = CURDATE()
       AND estado = 'EMITIDO'"
);

$receiptTotalToday = (float) $db->value(
    "SELECT COALESCE(SUM(total), 0)
     FROM comprobantes
     WHERE DATE(fecha_emision) = CURDATE()
       AND estado = 'EMITIDO'"
);

$paymentTotalToday = (float) $db->value(
    "SELECT COALESCE(SUM(monto), 0)
     FROM pagos
     WHERE DATE(fecha_pago) = CURDATE()
       AND estado = 'VALIDADO'"
);

$receipts = $db->fetchAll(
    "SELECT c.*, tc.codigo, tc.nombre AS tipo, o.codigo AS codigo_orden,
            cl.nombre_razon_social AS cliente,
            u.usuario
     FROM comprobantes c
     INNER JOIN tipos_comprobante tc ON tc.id = c.id_tipo_comprobante
     INNER JOIN ordenes o ON o.id = c.id_orden
     LEFT JOIN clientes cl ON cl.id = c.id_cliente
     LEFT JOIN usuarios u ON u.id = c.id_usuario
     ORDER BY c.id DESC
     LIMIT 25"
);

$payments = $db->fetchAll(
    "SELECT p.*, mp.nombre AS metodo, o.codigo AS codigo_orden, u.usuario
     FROM pagos p
     INNER JOIN metodos_pago mp ON mp.id = p.id_metodo_pago
     INNER JOIN ordenes o ON o.id = p.id_orden
     LEFT JOIN usuarios u ON u.id = p.id_usuario
     ORDER BY p.id DESC
     LIMIT 25"
);

render_page($title, 'receipts', [
    'receiptCountToday' => $receiptCountToday,
    'receiptTotalToday' => $receiptTotalToday,
    'paymentTotalToday' => $paymentTotalToday,
    'receipts' => $receipts,
    'payments' => $payments,
]);
