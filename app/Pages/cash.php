<?php

$currentUser = $auth->user();
$sucursalId = (int) ($currentUser['id_sucursal'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'open_turn') {
            $amount = (float) ($_POST['monto_apertura'] ?? 0);
            $notes = trim((string) ($_POST['observaciones'] ?? ''));

            $db->call('sp_abrir_turno_caja', [
                $sucursalId,
                $auth->id(),
                $amount,
                $notes ?: null,
            ]);

            $flash->success('Caja abierta correctamente.');
        } elseif ($action === 'close_turn') {
            $turnId = (int) ($_POST['id_turno'] ?? 0);
            $amount = (float) ($_POST['monto_cierre'] ?? 0);

            if ($turnId <= 0) {
                throw new RuntimeException('No hay un turno abierto para cerrar.');
            }

            $pendingOrdersToPay = (int) $db->value(
                "SELECT COUNT(*)
                 FROM ordenes
                 WHERE id_sucursal = :sucursal
                   AND estado IN ('BORRADOR', 'ABIERTA', 'EN_PREPARACION', 'LISTA', 'SERVIDA', 'COMPLETADA')",
                ['sucursal' => $sucursalId]
            );

            if ($pendingOrdersToPay > 0) {
                throw new RuntimeException('No puedes cerrar caja: hay ' . $pendingOrdersToPay . ' orden(es) pendientes de pagar.');
            }

            $db->call('sp_cerrar_turno_caja', [
                $turnId,
                $auth->id(),
                $amount,
            ]);

            $flash->success('Caja cerrada correctamente.');
        } elseif ($action === 'add_expense') {
            $turnId = (int) ($_POST['id_turno'] ?? 0);
            $categoryId = (int) ($_POST['id_categoria_gasto'] ?? 0);
            $description = trim((string) ($_POST['descripcion'] ?? ''));
            $amount = (float) ($_POST['monto'] ?? 0);

            if ($turnId <= 0 || $categoryId <= 0 || $description === '' || $amount <= 0) {
                throw new RuntimeException('Completa la informacion del gasto antes de registrar.');
            }

            $db->execute(
                "INSERT INTO gastos (id_turno_caja, id_categoria_gasto, descripcion, monto, id_usuario, estado)
                 VALUES (:turno, :categoria, :descripcion, :monto, :usuario, 'APROBADO')",
                [
                    'turno' => $turnId,
                    'categoria' => $categoryId,
                    'descripcion' => $description,
                    'monto' => $amount,
                    'usuario' => $auth->id(),
                ]
            );

            $flash->success('Gasto registrado correctamente.');
        } else {
            throw new RuntimeException('Accion no reconocida.');
        }

        redirect('cash');
    } catch (Throwable $exception) {
        $flash->error($exception->getMessage());
        redirect('cash');
    }
}

$turnBaseQuery = "SELECT tc.*, ua.nombres AS apertura_nombres, ua.apellidos AS apertura_apellidos,
                         uc.nombres AS cierre_nombres, uc.apellidos AS cierre_apellidos
                  FROM turnos_caja tc
                  INNER JOIN usuarios ua ON ua.id = tc.id_usuario_apertura
                  LEFT JOIN usuarios uc ON uc.id = tc.id_usuario_cierre
                  WHERE tc.id_sucursal = :sucursal_turno";

$currentTurn = $db->fetch(
    $turnBaseQuery . "
       AND tc.estado = 'ABIERTO'
     ORDER BY tc.id DESC
     LIMIT 1",
    ['sucursal_turno' => $sucursalId]
);

$latestTurn = $db->fetch(
    $turnBaseQuery . "
     ORDER BY tc.id DESC
     LIMIT 1",
    ['sucursal_turno' => $sucursalId]
);

$reportTurn = $currentTurn ?: $latestTurn;
$reportStart = $reportTurn['fecha_apertura'] ?? date('Y-m-d 00:00:00');
$reportEnd = $currentTurn
    ? date('Y-m-d H:i:s')
    : ($reportTurn['fecha_cierre'] ?? date('Y-m-d H:i:s'));
$reportLabel = $currentTurn ? 'Turno actual' : ($reportTurn ? 'Ultimo turno' : 'Hoy');
$reportTurnId = $reportTurn ? (int) $reportTurn['id'] : null;

$pendingOrdersToPay = $currentTurn
    ? (int) $db->value(
        "SELECT COUNT(*)
         FROM ordenes
         WHERE id_sucursal = :sucursal
           AND estado IN ('BORRADOR', 'ABIERTA', 'EN_PREPARACION', 'LISTA', 'SERVIDA', 'COMPLETADA')",
        ['sucursal' => $sucursalId]
    )
    : 0;

$cashFlow = [
    'opening_cash' => 0.0,
    'income_total' => 0.0,
    'expense_total' => 0.0,
    'expected_cash' => 0.0,
];

if ($reportTurnId) {
    $cashFlow = $db->fetch(
        "SELECT
            COALESCE(MAX(tc.monto_apertura), 0) AS opening_cash,
            COALESCE(SUM(CASE
                WHEN mc.tipo_movimiento = 'INGRESO' AND mc.origen <> 'APERTURA' THEN mc.monto
                ELSE 0
            END), 0) AS income_total,
            COALESCE(SUM(CASE
                WHEN mc.tipo_movimiento = 'EGRESO' AND mc.origen <> 'CIERRE' THEN mc.monto
                ELSE 0
            END), 0) AS expense_total
         FROM turnos_caja tc
         LEFT JOIN movimientos_caja mc ON mc.id_turno_caja = tc.id
         WHERE tc.id = :turno_cash",
        ['turno_cash' => $reportTurnId]
    ) ?: $cashFlow;
}

$cashFlow['opening_cash'] = (float) ($cashFlow['opening_cash'] ?? 0);
$cashFlow['income_total'] = (float) ($cashFlow['income_total'] ?? 0);
$cashFlow['expense_total'] = (float) ($cashFlow['expense_total'] ?? 0);
$cashFlow['expected_cash'] = $cashFlow['opening_cash'] + $cashFlow['income_total'] - $cashFlow['expense_total'];
$cashBalance = $cashFlow['expected_cash'];

$salesParams = [
    'sales_sucursal' => $sucursalId,
    'sales_inicio' => $reportStart,
    'sales_fin' => $reportEnd,
];

$salesScope = "o.id_sucursal = :sales_sucursal
    AND p.estado IN ('REGISTRADO', 'VALIDADO')
    AND p.fecha_pago BETWEEN :sales_inicio AND :sales_fin";

if ($reportTurnId) {
    $salesScope .= " AND p.id_turno_caja = :sales_turno";
    $salesParams['sales_turno'] = $reportTurnId;
}

$salesSummary = $db->fetch(
    "SELECT
        COUNT(*) AS total_sales_count,
        COALESCE(SUM(p.monto), 0) AS total_sales_amount
     FROM pagos p
     INNER JOIN ordenes o ON o.id = p.id_orden
     WHERE {$salesScope}",
    $salesParams
) ?: [
    'total_sales_count' => 0,
    'total_sales_amount' => 0,
];

$latestSale = $db->fetch(
    "SELECT
        o.id,
        o.codigo,
        o.total,
        p.monto,
        p.fecha_pago,
        mp.nombre AS metodo_pago,
        c.nombre_razon_social AS cliente
     FROM pagos p
     INNER JOIN ordenes o ON o.id = p.id_orden
     LEFT JOIN metodos_pago mp ON mp.id = p.id_metodo_pago
     LEFT JOIN clientes c ON c.id = o.id_cliente
     WHERE {$salesScope}
     ORDER BY p.fecha_pago DESC, p.id DESC
     LIMIT 1",
    $salesParams
);

$hourlySales = $db->fetchAll(
    "SELECT
        DATE_FORMAT(p.fecha_pago, '%H:00') AS hour_block,
        COUNT(*) AS sales_count,
        COALESCE(SUM(p.monto), 0) AS sales_amount
     FROM pagos p
     INNER JOIN ordenes o ON o.id = p.id_orden
     WHERE {$salesScope}
     GROUP BY DATE_FORMAT(p.fecha_pago, '%H:00')
     ORDER BY hour_block",
    $salesParams
);

$paymentDistribution = $db->fetchAll(
    "SELECT
        mp.id,
        mp.nombre,
        COALESCE(stats.payment_count, 0) AS payment_count,
        COALESCE(stats.payment_amount, 0) AS payment_amount
     FROM metodos_pago mp
     LEFT JOIN (
        SELECT
            p.id_metodo_pago,
            COUNT(*) AS payment_count,
            COALESCE(SUM(p.monto), 0) AS payment_amount
        FROM pagos p
        INNER JOIN ordenes o ON o.id = p.id_orden
        WHERE {$salesScope}
        GROUP BY p.id_metodo_pago
     ) AS stats ON stats.id_metodo_pago = mp.id
     WHERE mp.activo = 1
     ORDER BY CASE
        WHEN mp.nombre = 'EFECTIVO' THEN 0
        WHEN mp.nombre = 'TRANSFERENCIA' THEN 1
        ELSE 2
     END, mp.nombre",
    $salesParams
);

$expenseCategories = $db->fetchAll(
    "SELECT id, nombre
     FROM categorias_gasto
     WHERE activo = 1
     ORDER BY nombre"
);

$movementParams = ['movement_sucursal' => $sucursalId];
$movementQuery = "SELECT mc.*, u.usuario
                  FROM movimientos_caja mc
                  LEFT JOIN usuarios u ON u.id = mc.id_usuario
                  INNER JOIN turnos_caja tc ON tc.id = mc.id_turno_caja
                  WHERE tc.id_sucursal = :movement_sucursal";

if ($reportTurnId) {
    $movementQuery .= " AND mc.id_turno_caja = :movement_turno";
    $movementParams['movement_turno'] = $reportTurnId;
} else {
    $movementQuery .= " AND mc.fecha BETWEEN :movement_inicio AND :movement_fin";
    $movementParams['movement_inicio'] = $reportStart;
    $movementParams['movement_fin'] = $reportEnd;
}

$movementQuery .= " ORDER BY mc.id DESC LIMIT 25";
$movements = $db->fetchAll($movementQuery, $movementParams);

$expenseParams = ['expense_sucursal' => $sucursalId];
$expenseQuery = "SELECT g.*, cg.nombre AS categoria, u.usuario
                 FROM gastos g
                 INNER JOIN categorias_gasto cg ON cg.id = g.id_categoria_gasto
                 LEFT JOIN usuarios u ON u.id = g.id_usuario
                 INNER JOIN turnos_caja tc ON tc.id = g.id_turno_caja
                 WHERE tc.id_sucursal = :expense_sucursal";

if ($reportTurnId) {
    $expenseQuery .= " AND g.id_turno_caja = :expense_turno";
    $expenseParams['expense_turno'] = $reportTurnId;
} else {
    $expenseQuery .= " AND g.fecha BETWEEN :expense_inicio AND :expense_fin";
    $expenseParams['expense_inicio'] = $reportStart;
    $expenseParams['expense_fin'] = $reportEnd;
}

$expenseQuery .= " ORDER BY g.id DESC LIMIT 15";
$expenses = $db->fetchAll($expenseQuery, $expenseParams);

$ordersReport = $db->fetchAll(
    "SELECT
        o.id,
        o.codigo,
        o.fecha_creacion,
        o.fecha_cierre,
        o.tipo_servicio,
        o.estado,
        o.total,
        c.nombre_razon_social AS cliente,
        CONCAT(u.nombres, ' ', COALESCE(u.apellidos, '')) AS creador,
        lp.fecha_pago,
        COALESCE(mp.nombre, 'SIN PAGO') AS metodo_pago
     FROM ordenes o
     LEFT JOIN clientes c ON c.id = o.id_cliente
     LEFT JOIN usuarios u ON u.id = o.id_usuario_creador
     LEFT JOIN (
        SELECT
            p1.id_orden,
            p1.id_metodo_pago,
            p1.fecha_pago
        FROM pagos p1
        INNER JOIN (
            SELECT id_orden, MAX(id) AS last_payment_id
            FROM pagos
            WHERE estado IN ('REGISTRADO', 'VALIDADO')
            GROUP BY id_orden
        ) AS p2 ON p2.last_payment_id = p1.id
     ) AS lp ON lp.id_orden = o.id
     LEFT JOIN metodos_pago mp ON mp.id = lp.id_metodo_pago
     WHERE o.id_sucursal = :orders_sucursal
       AND o.fecha_creacion BETWEEN :orders_inicio AND :orders_fin
     ORDER BY o.fecha_creacion DESC, o.id DESC",
    [
        'orders_sucursal' => $sucursalId,
        'orders_inicio' => $reportStart,
        'orders_fin' => $reportEnd,
    ]
);

$closedTurns = $db->fetchAll(
    "SELECT tc.*
     FROM turnos_caja tc
     WHERE tc.id_sucursal = :closed_sucursal
     ORDER BY tc.id DESC
     LIMIT 8",
    ['closed_sucursal' => $sucursalId]
);

render_page($title, 'cash', [
    'csrf' => $csrf,
    'currentTurn' => $currentTurn,
    'pendingOrdersToPay' => $pendingOrdersToPay,
    'reportTurn' => $reportTurn,
    'reportLabel' => $reportLabel,
    'reportStart' => $reportStart,
    'reportEnd' => $reportEnd,
    'cashBalance' => $cashBalance,
    'cashFlow' => $cashFlow,
    'salesSummary' => $salesSummary,
    'latestSale' => $latestSale,
    'hourlySales' => $hourlySales,
    'paymentDistribution' => $paymentDistribution,
    'expenseCategories' => $expenseCategories,
    'movements' => $movements,
    'expenses' => $expenses,
    'ordersReport' => $ordersReport,
    'closedTurns' => $closedTurns,
]);
