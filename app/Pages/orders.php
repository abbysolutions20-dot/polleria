<?php

$currentUser = $auth->user();
$sucursalId = (int) ($currentUser['id_sucursal'] ?? 1);
$isCashier = $auth->hasRole('CAJERO');
$canAccessDashboard = $auth->can('dashboard.ver');
$canAccessKitchen = $auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'COCINA']);
$canCreateOrdersByRole = $auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR', 'MOZO']);
$canManageOrder = $auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR', 'MOZO']);
$canRegisterPayment = $auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR', 'CAJERO']);
$showCreateBuilder = ((string) ($_GET['create'] ?? '')) === '1';
$selectedOrderId = isset($_GET['selected']) ? (int) $_GET['selected'] : 0;

$currentTurn = $db->fetch(
    "SELECT id, fecha_apertura
     FROM turnos_caja
     WHERE id_sucursal = :turno_sucursal
       AND estado = 'ABIERTO'
     ORDER BY id DESC
     LIMIT 1",
    ['turno_sucursal' => $sucursalId]
);

$hasOpenTurn = $currentTurn !== null;
$canCreateOrders = $canCreateOrdersByRole && $hasOpenTurn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $showCreateBuilder && $canCreateOrdersByRole && !$hasOpenTurn) {
    $flash->error('Debes abrir un turno de caja antes de crear ordenes.');
    redirect('orders');
}
$orderForm = [
    'tipo_servicio' => 'MESA',
    'id_mesa' => '',
    'id_cliente' => '',
    'cantidad_personas' => '1',
    'observaciones' => '',
];
$draftItems = [];

$normalizePaymentMethod = static function (string $value): string {
    $normalized = strtoupper(trim($value));
    $normalized = str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
        ['A', 'E', 'I', 'O', 'U', 'U', 'N'],
        $normalized
    );

    return preg_replace('/[^A-Z0-9]+/', '', $normalized) ?: '';
};

$normalizeDraftItems = static function (mixed $items): array {
    if (!is_array($items)) {
        throw new RuntimeException('La orden recibida no tiene un formato valido.');
    }

    $normalized = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        $quantityRaw = trim((string) ($item['quantity'] ?? ''));
        $note = trim((string) ($item['note'] ?? ''));

        if ($productId <= 0 || $quantityRaw === '' || !preg_match('/^\d+$/', $quantityRaw)) {
            throw new RuntimeException('La orden contiene platos o cantidades invalidas.');
        }

        $quantity = (int) $quantityRaw;

        if ($quantity <= 0) {
            throw new RuntimeException('Cada plato debe tener una cantidad entera mayor a cero.');
        }

        if (!isset($normalized[$productId])) {
            $normalized[$productId] = [
                'product_id' => $productId,
                'quantity' => 0,
                'note' => '',
            ];
        }

        $normalized[$productId]['quantity'] += $quantity;

        if ($note !== '') {
            $normalized[$productId]['note'] = $note;
        }
    }

    if ($normalized === []) {
        throw new RuntimeException('Agrega al menos un plato antes de crear la orden.');
    }

    return array_values($normalized);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showCreateBuilder = $canCreateOrders;
    $action = (string) ($_POST['action'] ?? '');
    $orderForm = [
        'tipo_servicio' => (string) ($_POST['tipo_servicio'] ?? 'MESA'),
        'id_mesa' => trim((string) ($_POST['id_mesa'] ?? '')),
        'id_cliente' => trim((string) ($_POST['id_cliente'] ?? '')),
        'cantidad_personas' => (string) max(1, (int) ($_POST['cantidad_personas'] ?? 1)),
        'observaciones' => trim((string) ($_POST['observaciones'] ?? '')),
    ];

    $itemsJson = trim((string) ($_POST['items_json'] ?? ''));

    if ($itemsJson !== '') {
        try {
            $draftItems = $normalizeDraftItems(json_decode($itemsJson, true, 512, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            $draftItems = [];
        }
    }

    if ($action === 'create_inline_order') {
        try {
            if (!$canCreateOrdersByRole) {
                throw new RuntimeException('Tu perfil no puede crear ordenes desde esta pantalla.');
            }

            if (!$hasOpenTurn) {
                throw new RuntimeException('Debes abrir un turno de caja antes de crear ordenes.');
            }

            $csrf->guard($_POST['_token'] ?? null);

            $serviceType = strtoupper($orderForm['tipo_servicio']);
            $mesaId = (int) $orderForm['id_mesa'];
            $clientId = (int) $orderForm['id_cliente'];
            $people = max(1, (int) $orderForm['cantidad_personas']);
            $notes = $orderForm['observaciones'];

            if (!in_array($serviceType, ['MESA', 'LLEVAR', 'DELIVERY'], true)) {
                throw new RuntimeException('Selecciona un tipo de servicio valido.');
            }

            if ($serviceType === 'MESA' && $mesaId <= 0) {
                throw new RuntimeException('Selecciona una mesa para el servicio en salon.');
            }

            if ($itemsJson === '') {
                throw new RuntimeException('Agrega al menos un plato antes de crear la orden.');
            }

            $draftItems = $normalizeDraftItems(json_decode($itemsJson, true, 512, JSON_THROW_ON_ERROR));

            $orderId = $db->transaction(function () use (
                $orderService,
                $sucursalId,
                $serviceType,
                $mesaId,
                $clientId,
                $people,
                $notes,
                $draftItems,
                $auth
            ): int {
                $created = $orderService->createOrder(
                    $sucursalId,
                    $serviceType === 'MESA' ? $mesaId : null,
                    $clientId > 0 ? $clientId : null,
                    (int) $auth->id(),
                    $serviceType,
                    $people,
                    $notes
                );

                $orderId = (int) ($created['id_orden'] ?? 0);

                if ($orderId <= 0) {
                    throw new RuntimeException('No se pudo crear la orden.');
                }

                foreach ($draftItems as $draftItem) {
                    $orderService->addItem(
                        $orderId,
                        (int) $draftItem['product_id'],
                        (int) $draftItem['quantity'],
                        $draftItem['note'] !== '' ? (string) $draftItem['note'] : null
                    );
                }

                $orderService->sendOrderToKitchen($orderId, (int) $auth->id());

                return $orderId;
            });

            $flash->success('Orden creada y enviada a cocina correctamente.');
            redirect('orders');
        } catch (JsonException) {
            $flash->error('No se pudieron leer los platos seleccionados. Vuelve a intentarlo.');
        } catch (Throwable $exception) {
            $flash->error($exception->getMessage());
        }
    } elseif (in_array($action, ['add_selected_item', 'remove_selected_item', 'mark_order_ready', 'quick_pay_order'], true)) {
        $redirectParams = [];
        $targetOrderId = (int) ($_POST['id_orden'] ?? 0);

        if ($targetOrderId > 0) {
            $redirectParams['selected'] = $targetOrderId;
        }

        try {
            $csrf->guard($_POST['_token'] ?? null);

            if ($targetOrderId <= 0) {
                throw new RuntimeException('Selecciona una orden valida.');
            }

            $targetOrder = $db->fetch(
                "SELECT id, id_sucursal, estado, total
                 FROM ordenes
                 WHERE id = :orden
                   AND id_sucursal = :sucursal
                 LIMIT 1",
                [
                    'orden' => $targetOrderId,
                    'sucursal' => $sucursalId,
                ]
            );

            if (!$targetOrder) {
                throw new RuntimeException('La orden seleccionada no existe en esta sucursal.');
            }

            if ($action === 'add_selected_item') {
                if (!$canManageOrder) {
                    throw new RuntimeException('Tu perfil no puede modificar esta orden.');
                }

                if (in_array((string) $targetOrder['estado'], ['PAGADA', 'ANULADA'], true)) {
                    throw new RuntimeException('La orden ya no admite cambios operativos.');
                }

                $productId = (int) ($_POST['id_producto'] ?? 0);

                if ($productId <= 0) {
                    throw new RuntimeException('Selecciona un plato valido para agregar.');
                }

                $orderService->addItem($targetOrderId, $productId, 1, null);
                $orderService->sendOrderToKitchen($targetOrderId, (int) $auth->id());
                $flash->success('Producto agregado a la orden y enviado a cocina.');
            } elseif ($action === 'remove_selected_item') {
                if (!$canManageOrder) {
                    throw new RuntimeException('Tu perfil no puede modificar esta orden.');
                }

                if (in_array((string) $targetOrder['estado'], ['PAGADA', 'ANULADA'], true)) {
                    throw new RuntimeException('La orden ya no admite cambios operativos.');
                }

                $itemId = (int) ($_POST['id_orden_detalle'] ?? 0);

                if ($itemId <= 0) {
                    throw new RuntimeException('Selecciona un plato valido para eliminar.');
                }

                $orderService->removeItem($targetOrderId, $itemId);
                $flash->success('Producto eliminado de la orden.');
            } elseif ($action === 'mark_order_ready') {
                if (!$canManageOrder) {
                    throw new RuntimeException('Tu perfil no puede actualizar esta orden.');
                }

                if (in_array((string) $targetOrder['estado'], ['PAGADA', 'ANULADA'], true)) {
                    throw new RuntimeException('La orden ya no admite cambios operativos.');
                }

                if ((string) $targetOrder['estado'] === 'LISTA') {
                    throw new RuntimeException('La orden ya esta marcada como lista.');
                }

                $orderService->changeStatus(
                    $targetOrderId,
                    'LISTA',
                    (int) $auth->id(),
                    'Orden marcada como lista desde el panel de ordenes.'
                );

                $flash->success('La orden fue marcada como lista.');
            } elseif ($action === 'quick_pay_order') {
                if (!$canRegisterPayment) {
                    throw new RuntimeException('Tu perfil no puede registrar pagos en esta orden.');
                }

                if (in_array((string) $targetOrder['estado'], ['PAGADA', 'ANULADA'], true)) {
                    throw new RuntimeException('La orden ya no admite cobros.');
                }

                $openTurn = $db->fetch(
                    "SELECT id
                     FROM turnos_caja
                     WHERE id_sucursal = :sucursal
                       AND estado = 'ABIERTO'
                     ORDER BY id DESC
                     LIMIT 1",
                    ['sucursal' => $targetOrder['id_sucursal']]
                );

                if (!$openTurn) {
                    throw new RuntimeException('Necesitas una caja abierta para registrar el pago.');
                }

                $existingPayment = (int) $db->value(
                    "SELECT COUNT(*)
                     FROM pagos
                     WHERE id_orden = :orden
                       AND estado IN ('REGISTRADO', 'VALIDADO')",
                    ['orden' => $targetOrderId]
                );

                if ($existingPayment > 0) {
                    throw new RuntimeException('La orden ya tiene un pago registrado.');
                }

                $selectedPaymentMethodId = (int) ($_POST['id_metodo_pago'] ?? 0);
                $selectedPaymentMethodKey = $normalizePaymentMethod((string) ($_POST['payment_method_key'] ?? ''));

                $selectedMethod = null;

                if ($selectedPaymentMethodId > 0) {
                    $selectedMethod = $db->fetch(
                        "SELECT id, nombre
                         FROM metodos_pago
                         WHERE id = :metodo
                           AND activo = 1
                         LIMIT 1",
                        ['metodo' => $selectedPaymentMethodId]
                    );
                }

                if (!$selectedMethod && $selectedPaymentMethodKey !== '') {
                    $activeMethods = $db->fetchAll(
                        "SELECT id, nombre
                         FROM metodos_pago
                         WHERE activo = 1
                         ORDER BY id"
                    );

                    foreach ($activeMethods as $activeMethod) {
                        $normalizedActiveMethod = $normalizePaymentMethod((string) ($activeMethod['nombre'] ?? ''));

                        $matchesSelectedKey = match ($selectedPaymentMethodKey) {
                            'EFECTIVO' => in_array($normalizedActiveMethod, ['EFECTIVO', 'CASH'], true),
                            'TARJETA' => str_contains($normalizedActiveMethod, 'TARJETA') || str_contains($normalizedActiveMethod, 'CARD'),
                            'YAPE' => str_contains($normalizedActiveMethod, 'YAPE'),
                            'PLIN' => str_contains($normalizedActiveMethod, 'PLIN'),
                            'TRANSFERENCIA' => str_contains($normalizedActiveMethod, 'TRANSFER'),
                            'MIXTO' => str_contains($normalizedActiveMethod, 'MIXTO'),
                            default => false,
                        };

                        if ($matchesSelectedKey) {
                            $selectedMethod = [
                                'id' => (int) $activeMethod['id'],
                                'nombre' => (string) $activeMethod['nombre'],
                            ];
                            break;
                        }
                    }
                }

                if (!$selectedMethod && $selectedPaymentMethodKey !== '') {
                    throw new RuntimeException('El metodo de pago seleccionado no esta configurado como activo.');
                }

                if (!$selectedMethod) {
                    $selectedMethod = $db->fetch(
                        "SELECT id, nombre
                         FROM metodos_pago
                         WHERE activo = 1
                         ORDER BY CASE WHEN UPPER(nombre) IN ('EFECTIVO', 'CASH') THEN 0 ELSE 1 END, id
                         LIMIT 1"
                    );
                }

                if (!$selectedMethod) {
                    throw new RuntimeException('No hay metodos de pago activos para registrar el cobro.');
                }

                $orderService->registerPayment(
                    $targetOrderId,
                    (int) $openTurn['id'],
                    (int) $selectedMethod['id'],
                    (float) $targetOrder['total'],
                    (float) $targetOrder['total'],
                    null,
                    'Pago completo registrado desde el panel de ordenes.',
                    (int) $auth->id()
                );

                $flash->success('Pago completo registrado con ' . $selectedMethod['nombre'] . '.');
                $redirectParams = [];
            }
        } catch (Throwable $exception) {
            $flash->error($exception->getMessage());
        }

        redirect('orders', $redirectParams);
    }
}

if (!$canCreateOrders) {
    $showCreateBuilder = false;
}

$reportStart = $currentTurn['fecha_apertura'] ?? date('Y-m-d 00:00:00');
$reportEnd = date('Y-m-d H:i:s');
$reportTitle = $currentTurn ? 'Ordenes del turno' : 'Ordenes de hoy';
$reportSubtitle = $currentTurn
    ? 'Abierta el ' . format_datetime($currentTurn['fecha_apertura'], 'd/m/y, h:i A')
    : 'Sin caja abierta. Mostrando pedidos del dia actual.';

$orderSummary = $db->fetch(
    "SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(CASE WHEN estado = 'PAGADA' THEN 1 ELSE 0 END), 0) AS paid_orders,
        COALESCE(SUM(CASE WHEN estado IN ('BORRADOR', 'ABIERTA', 'EN_PREPARACION', 'LISTA', 'SERVIDA', 'COMPLETADA') THEN 1 ELSE 0 END), 0) AS pending_payment,
        COALESCE(SUM(CASE WHEN estado = 'PAGADA' THEN total ELSE 0 END), 0) AS total_sales
     FROM ordenes
     WHERE id_sucursal = :summary_sucursal
       AND fecha_creacion BETWEEN :summary_start AND :summary_end",
    [
        'summary_sucursal' => $sucursalId,
        'summary_start' => $reportStart,
        'summary_end' => $reportEnd,
    ]
) ?: [
    'total_orders' => 0,
    'paid_orders' => 0,
    'pending_payment' => 0,
    'total_sales' => 0,
];

$totalOrdersCount = (int) ($orderSummary['total_orders'] ?? 0);
$paidOrdersCount = (int) ($orderSummary['paid_orders'] ?? 0);
$pendingOrdersCount = (int) ($orderSummary['pending_payment'] ?? 0);
$totalSalesAmount = (float) ($orderSummary['total_sales'] ?? 0);
$paidOrdersPercent = $totalOrdersCount > 0 ? round(($paidOrdersCount / $totalOrdersCount) * 100) : 0;
$pendingOrdersPercent = $totalOrdersCount > 0 ? round(($pendingOrdersCount / $totalOrdersCount) * 100) : 0;
$averageTicket = $paidOrdersCount > 0 ? $totalSalesAmount / $paidOrdersCount : 0.0;

$clients = $db->fetchAll(
    "SELECT id, nombre_razon_social, numero_documento
     FROM clientes
     WHERE activo = 1
     ORDER BY nombre_razon_social"
);

$tables = $db->fetchAll(
    "SELECT m.id, m.numero, m.capacidad, zm.nombre AS zona
     FROM mesas m
     LEFT JOIN zonas_mesa zm ON zm.id = m.id_zona
     WHERE m.id_sucursal = :table_sucursal
       AND m.activo = 1
       AND m.estado = 'LIBRE'
     ORDER BY m.numero",
    ['table_sucursal' => $sucursalId]
);

$productCategories = $db->fetchAll(
    "SELECT DISTINCT cp.id, cp.nombre
     FROM categorias_producto cp
     INNER JOIN productos p ON p.id_categoria = cp.id
     WHERE p.activo = 1
     ORDER BY nombre"
);

$products = $db->fetchAll(
    "SELECT p.id,
            p.nombre,
            p.descripcion,
            p.precio_venta,
            p.tipo_producto,
            p.afecto_igv,
            p.id_categoria,
            cp.nombre AS categoria,
            ap.nombre AS area_preparacion,
            COALESCE(pop.total_vendido, 0) AS total_vendido
     FROM productos p
     INNER JOIN categorias_producto cp ON cp.id = p.id_categoria
     LEFT JOIN areas_preparacion ap ON ap.id = p.id_area_preparacion
     LEFT JOIN (
        SELECT od.id_producto, SUM(od.cantidad) AS total_vendido
        FROM orden_detalle od
        INNER JOIN ordenes o ON o.id = od.id_orden
        WHERE od.estado_item <> 'ANULADO'
          AND o.id_sucursal = :pop_sucursal
        GROUP BY od.id_producto
     ) pop ON pop.id_producto = p.id
     WHERE p.activo = 1
     ORDER BY COALESCE(pop.total_vendido, 0) DESC, p.nombre",
    ['pop_sucursal' => $sucursalId]
);

usort($products, static function (array $left, array $right): int {
    $categoryComparison = strcasecmp((string) ($left['categoria'] ?? ''), (string) ($right['categoria'] ?? ''));

    if ($categoryComparison !== 0) {
        return $categoryComparison;
    }

    $salesComparison = ((float) ($right['total_vendido'] ?? 0)) <=> ((float) ($left['total_vendido'] ?? 0));

    if ($salesComparison !== 0) {
        return $salesComparison;
    }

    return strcasecmp((string) ($left['nombre'] ?? ''), (string) ($right['nombre'] ?? ''));
});

$popularProducts = array_values(
    array_filter(
        $products,
        static fn (array $product): bool => (float) ($product['total_vendido'] ?? 0) > 0
    )
);

if ($popularProducts === []) {
    $popularProducts = $products;
}

$popularProducts = array_slice($popularProducts, 0, 8);

$tableOrders = $db->fetchAll(
    "SELECT o.id, o.codigo, o.estado, o.total, o.fecha_creacion, o.fecha_cierre,
            o.id_mesa, o.id_cliente, o.cantidad_personas, o.observaciones,
            o.tipo_servicio,
            m.numero AS mesa,
            c.nombre_razon_social AS cliente,
            CONCAT(u.nombres, ' ', COALESCE(u.apellidos, '')) AS creador
     FROM ordenes o
     LEFT JOIN mesas m ON m.id = o.id_mesa
     LEFT JOIN clientes c ON c.id = o.id_cliente
     LEFT JOIN usuarios u ON u.id = o.id_usuario_creador
     WHERE o.id_sucursal = :mesa_sucursal
       AND o.estado IN ('BORRADOR', 'ABIERTA', 'EN_PREPARACION', 'LISTA', 'SERVIDA', 'COMPLETADA')
     ORDER BY FIELD(o.estado, 'LISTA', 'EN_PREPARACION', 'ABIERTA', 'BORRADOR', 'SERVIDA', 'COMPLETADA'), o.id DESC",
    [
        'mesa_sucursal' => $sucursalId,
    ]
);

$orderItemsByOrder = [];

if ($tableOrders) {
    $detailParams = [];
    $placeholders = [];

    foreach (array_values($tableOrders) as $index => $tableOrder) {
        $key = 'order_' . $index;
        $placeholders[] = ':' . $key;
        $detailParams[$key] = (int) $tableOrder['id'];
    }

    $orderItemRows = $db->fetchAll(
        "SELECT od.id,
                od.id_orden,
                od.id_producto,
                od.nombre_producto,
                od.cantidad,
                od.subtotal,
                od.precio_unitario,
                od.estado_item,
                od.observacion,
                COALESCE(cp.nombre, 'Carta') AS categoria,
                COALESCE(ap.nombre, 'Sin area') AS area_preparacion,
                COALESCE(p.afecto_igv, 0) AS afecto_igv
         FROM orden_detalle od
         LEFT JOIN productos p ON p.id = od.id_producto
         LEFT JOIN categorias_producto cp ON cp.id = p.id_categoria
         LEFT JOIN areas_preparacion ap ON ap.id = od.id_area_preparacion
         WHERE od.estado_item <> 'ANULADO'
           AND od.id_orden IN (" . implode(', ', $placeholders) . ")
         ORDER BY od.id_orden DESC, od.id ASC",
        $detailParams
    );

    foreach ($orderItemRows as $itemRow) {
        $orderItemsByOrder[(int) $itemRow['id_orden']][] = $itemRow;
    }
}

foreach ($tableOrders as &$tableOrder) {
    $items = $orderItemsByOrder[(int) $tableOrder['id']] ?? [];
    $state = (string) ($tableOrder['estado'] ?? '');

    $tableOrder['items'] = $items;
    $tableOrder['item_count'] = count($items);
    $tableOrder['status_label'] = match ($state) {
        'PAGADA', 'COMPLETADA' => 'Completado',
        'SERVIDA' => 'Servido',
        'LISTA' => 'Lista',
        'EN_PREPARACION' => 'En preparacion',
        'ABIERTA' => 'Abierta',
        'BORRADOR' => 'Borrador',
        'ANULADA' => 'Anulada',
        default => ucfirst(strtolower(str_replace('_', ' ', $state))),
    };
    $tableOrder['status_badge_class'] = badge_class(match ($state) {
        'PAGADA' => 'VALIDADO',
        'COMPLETADA' => 'LISTA',
        default => $state,
    });
    $tableOrder['service_label'] = match ((string) ($tableOrder['tipo_servicio'] ?? 'MESA')) {
        'DELIVERY' => 'Delivery',
        'LLEVAR' => 'Para llevar',
        default => 'Mesa',
    };
    $tableOrder['service_icon'] = match ((string) ($tableOrder['tipo_servicio'] ?? 'MESA')) {
        'DELIVERY' => 'bike',
        'LLEVAR' => 'bag',
        default => 'table',
    };
    $tableOrder['service_reference'] = match ((string) ($tableOrder['tipo_servicio'] ?? 'MESA')) {
        'DELIVERY' => $tableOrder['cliente'] ?: 'Delivery',
        'LLEVAR' => $tableOrder['cliente'] ?: 'Mostrador',
        default => $tableOrder['mesa'] ? 'Mesa #' . $tableOrder['mesa'] : 'Mesa',
    };
    $tableOrder['payment_label'] = $state === 'PAGADA' ? 'Pagado' : 'Pendiente';
    $tableOrder['payment_badge_class'] = $state === 'PAGADA'
        ? 'badge badge--accent'
        : 'badge badge--warning';
    $tableOrder['is_selected'] = (int) $tableOrder['id'] === $selectedOrderId;
}
unset($tableOrder);

$selectedOrder = null;

if (!$showCreateBuilder && $selectedOrderId > 0) {
    foreach ($tableOrders as $tableOrder) {
        if ((int) $tableOrder['id'] === $selectedOrderId) {
            $selectedOrder = $tableOrder;
            break;
        }
    }
}

$showSelectedEditor = !$showCreateBuilder && $selectedOrder !== null && $canManageOrder;

$activePaymentMethods = $db->fetchAll(
    "SELECT id, nombre
     FROM metodos_pago
     WHERE activo = 1
     ORDER BY id"
);

$paymentMethodCatalog = [
    'EFECTIVO' => null,
    'TARJETA' => null,
    'YAPE' => null,
    'PLIN' => null,
    'TRANSFERENCIA' => null,
    'MIXTO' => null,
];

foreach ($activePaymentMethods as $paymentMethod) {
    $normalizedName = $normalizePaymentMethod((string) ($paymentMethod['nombre'] ?? ''));

    if ($normalizedName === '') {
        continue;
    }

    $matchesKey = match (true) {
        in_array($normalizedName, ['EFECTIVO', 'CASH'], true) => 'EFECTIVO',
        str_contains($normalizedName, 'TARJETA') || str_contains($normalizedName, 'CARD') => 'TARJETA',
        str_contains($normalizedName, 'YAPE') => 'YAPE',
        str_contains($normalizedName, 'PLIN') => 'PLIN',
        str_contains($normalizedName, 'TRANSFER') => 'TRANSFERENCIA',
        str_contains($normalizedName, 'MIXTO') => 'MIXTO',
        default => null,
    };

    if (
        $matchesKey !== null
        && (!is_array($paymentMethodCatalog[$matchesKey]) || !isset($paymentMethodCatalog[$matchesKey]['id']))
    ) {
        $paymentMethodCatalog[$matchesKey] = [
            'id' => (int) $paymentMethod['id'],
            'nombre' => (string) $paymentMethod['nombre'],
        ];
    }
}

render_page($title, 'orders', [
    'csrf' => $csrf,
    'currentTurn' => $currentTurn,
    'clients' => $clients,
    'tables' => $tables,
    'productCategories' => $productCategories,
    'products' => $products,
    'popularProducts' => $popularProducts,
    'tableOrders' => $tableOrders,
    'reportTitle' => $reportTitle,
    'reportSubtitle' => $reportSubtitle,
    'totalOrdersCount' => $totalOrdersCount,
    'paidOrdersCount' => $paidOrdersCount,
    'pendingOrdersCount' => $pendingOrdersCount,
    'totalSalesAmount' => $totalSalesAmount,
    'paidOrdersPercent' => $paidOrdersPercent,
    'pendingOrdersPercent' => $pendingOrdersPercent,
    'averageTicket' => $averageTicket,
    'selectedOrder' => $selectedOrder,
    'showSelectedEditor' => $showSelectedEditor,
    'paymentMethodCatalog' => $paymentMethodCatalog,
    'showCreateBuilder' => $showCreateBuilder,
    'canAccessDashboard' => $canAccessDashboard,
    'canAccessKitchen' => $canAccessKitchen,
    'canCreateOrdersByRole' => $canCreateOrdersByRole,
    'canCreateOrders' => $canCreateOrders,
    'hasOpenTurn' => $hasOpenTurn,
    'canManageOrder' => $canManageOrder,
    'canRegisterPayment' => $canRegisterPayment,
    'isCashier' => $isCashier,
    'orderForm' => $orderForm,
    'draftItems' => $draftItems,
]);
