<?php

$totalIngredients = (int) $db->value("SELECT COUNT(*) FROM ingredientes WHERE activo = 1");
$totalArticles = (int) $db->value("SELECT COUNT(*) FROM articulos_inventario WHERE activo = 1");
$lowStockCount = (int) $db->value(
    "SELECT COUNT(*)
     FROM (
        SELECT id FROM ingredientes WHERE activo = 1 AND stock_actual <= stock_minimo
        UNION ALL
        SELECT id FROM articulos_inventario WHERE activo = 1 AND stock_actual <= stock_minimo
     ) AS alertas"
);

$inventoryValue = (float) $db->value(
    "SELECT COALESCE(SUM(stock_actual * costo_unitario), 0)
     FROM (
        SELECT stock_actual, costo_unitario FROM ingredientes WHERE activo = 1
        UNION ALL
        SELECT stock_actual, costo_unitario FROM articulos_inventario WHERE activo = 1
     ) AS stocks"
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
     ORDER BY stock_actual ASC, stock_minimo DESC
     LIMIT 20"
);

$kardex = $db->fetchAll(
    "SELECT k.*, 
            CASE
                WHEN k.tipo_item = 'INGREDIENTE' THEN i.nombre
                ELSE a.nombre
            END AS item_nombre
     FROM kardex_movimientos k
     LEFT JOIN ingredientes i
        ON k.tipo_item = 'INGREDIENTE'
       AND i.id = k.id_item
     LEFT JOIN articulos_inventario a
        ON k.tipo_item = 'ARTICULO'
       AND a.id = k.id_item
     ORDER BY k.id DESC
     LIMIT 20"
);

render_page($title, 'inventory', [
    'totalIngredients' => $totalIngredients,
    'totalArticles' => $totalArticles,
    'lowStockCount' => $lowStockCount,
    'inventoryValue' => $inventoryValue,
    'lowStockRows' => $lowStockRows,
    'kardex' => $kardex,
]);
