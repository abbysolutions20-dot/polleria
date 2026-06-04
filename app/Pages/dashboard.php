<?php

$currentUser = $auth->user();
$sucursalId = (int) ($currentUser['id_sucursal'] ?? 1);

$salesToday = (float) $db->value(
    "SELECT COALESCE(SUM(total), 0)
     FROM ordenes
     WHERE id_sucursal = :sucursal
       AND estado = 'PAGADA'
       AND DATE(fecha_actualizacion) = CURDATE()",
    ['sucursal' => $sucursalId]
);

$paidOrdersToday = (int) $db->value(
    "SELECT COUNT(*)
     FROM ordenes
     WHERE id_sucursal = :sucursal
       AND estado = 'PAGADA'
       AND DATE(fecha_actualizacion) = CURDATE()",
    ['sucursal' => $sucursalId]
);

$openOrders = (int) $db->value(
    "SELECT COUNT(*)
     FROM ordenes
     WHERE id_sucursal = :sucursal
       AND estado IN ('ABIERTA', 'EN_PREPARACION', 'LISTA', 'SERVIDA')",
    ['sucursal' => $sucursalId]
);

$lowStockCount = (int) $db->value(
    "SELECT COUNT(*)
     FROM (
        SELECT id FROM ingredientes WHERE activo = 1 AND stock_actual <= stock_minimo
        UNION ALL
        SELECT id FROM articulos_inventario WHERE activo = 1 AND stock_actual <= stock_minimo
     ) AS alertas"
);

$currentTurn = $db->fetch(
    "SELECT tc.*, ua.nombres AS apertura_nombres, ua.apellidos AS apertura_apellidos
     FROM turnos_caja tc
     INNER JOIN usuarios ua ON ua.id = tc.id_usuario_apertura
     WHERE tc.id_sucursal = :sucursal
       AND tc.estado = 'ABIERTO'
     ORDER BY tc.id DESC
     LIMIT 1",
    ['sucursal' => $sucursalId]
);

$cashBalance = 0.0;

if ($currentTurn) {
    $cashBalance = (float) $db->value(
        "SELECT COALESCE(SUM(CASE WHEN tipo_movimiento = 'INGRESO' THEN monto ELSE -monto END), 0)
         FROM movimientos_caja
         WHERE id_turno_caja = :turno",
        ['turno' => $currentTurn['id']]
    );
}

$recentOrders = $db->fetchAll(
    "SELECT o.id, o.codigo, o.tipo_servicio, o.estado, o.total, o.fecha_creacion,
            c.nombre_razon_social AS cliente,
            m.numero AS mesa
     FROM ordenes o
     LEFT JOIN clientes c ON c.id = o.id_cliente
     LEFT JOIN mesas m ON m.id = o.id_mesa
     WHERE o.id_sucursal = :sucursal
     ORDER BY o.id DESC
     LIMIT 8",
    ['sucursal' => $sucursalId]
);

$lowStockRows = $db->fetchAll(
    "SELECT *
     FROM (
        SELECT 'INGREDIENTE' AS tipo, nombre, stock_actual, stock_minimo, costo_unitario
        FROM ingredientes
        WHERE activo = 1 AND stock_actual <= stock_minimo
        UNION ALL
        SELECT 'ARTICULO' AS tipo, nombre, stock_actual, stock_minimo, costo_unitario
        FROM articulos_inventario
        WHERE activo = 1 AND stock_actual <= stock_minimo
     ) AS alertas
     ORDER BY stock_actual ASC
     LIMIT 6"
);

$topProducts = $db->fetchAll(
    "SELECT p.nombre, CAST(SUM(od.cantidad) AS UNSIGNED) AS cantidad_total, SUM(od.subtotal) AS venta_total
     FROM orden_detalle od
     INNER JOIN ordenes o ON o.id = od.id_orden
     INNER JOIN productos p ON p.id = od.id_producto
     WHERE o.id_sucursal = :sucursal
       AND o.estado = 'PAGADA'
     GROUP BY p.id, p.nombre
     ORDER BY cantidad_total DESC
     LIMIT 5",
    ['sucursal' => $sucursalId]
);

render_page($title, 'dashboard', [
    'salesToday' => $salesToday,
    'paidOrdersToday' => $paidOrdersToday,
    'openOrders' => $openOrders,
    'lowStockCount' => $lowStockCount,
    'currentTurn' => $currentTurn,
    'cashBalance' => $cashBalance,
    'recentOrders' => $recentOrders,
    'lowStockRows' => $lowStockRows,
    'topProducts' => $topProducts,
]);
