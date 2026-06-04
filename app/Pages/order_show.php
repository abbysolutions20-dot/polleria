<?php

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($orderId <= 0) {
    $flash->error('Selecciona una orden valida.');
    redirect('orders');
}

$loadOrder = static function () use ($db, $orderId): ?array {
    return $db->fetch(
        "SELECT o.*,
                c.nombre_razon_social AS cliente,
                c.numero_documento AS cliente_documento,
                m.numero AS mesa,
                CONCAT(u.nombres, ' ', COALESCE(u.apellidos, '')) AS creador
         FROM ordenes o
         LEFT JOIN clientes c ON c.id = o.id_cliente
         LEFT JOIN mesas m ON m.id = o.id_mesa
         LEFT JOIN usuarios u ON u.id = o.id_usuario_creador
         WHERE o.id = :id
         LIMIT 1",
        ['id' => $orderId]
    );
};

$order = $loadOrder();

if (!$order) {
    $flash->error('La orden solicitada no existe.');
    redirect('orders');
}

$isCashier = $auth->hasRole('CAJERO');
$canManageOrder = $auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR', 'MOZO']);
$canRegisterPayment = $auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR', 'CAJERO']);
$canEmitReceipt = $auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR']);
$canOfferReceiptOnPayment = $isCashier;
$canRemoveItems = $canManageOrder && !in_array($order['estado'], ['PAGADA', 'ANULADA'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);

        $action = (string) ($_POST['action'] ?? '');

        if (in_array($order['estado'], ['PAGADA', 'ANULADA'], true) && $action !== 'emit_receipt') {
            throw new RuntimeException('La orden ya no admite cambios operativos.');
        }

        if (in_array($action, ['add_item', 'remove_item', 'apply_discount', 'change_status', 'generate_comanda'], true) && !$canManageOrder) {
            throw new RuntimeException('Tu perfil no puede modificar esta orden.');
        }

        if ($action === 'register_payment' && !$canRegisterPayment) {
            throw new RuntimeException('Tu perfil no puede registrar pagos en esta orden.');
        }

        if ($action === 'emit_receipt' && !$canEmitReceipt) {
            throw new RuntimeException('Tu perfil no puede emitir comprobantes.');
        }

        if ($action === 'add_item') {
            $productId = (int) ($_POST['id_producto'] ?? 0);
            $quantityRaw = trim((string) ($_POST['cantidad'] ?? ''));
            $note = trim((string) ($_POST['observacion'] ?? ''));

            if ($productId <= 0 || $quantityRaw === '' || !preg_match('/^\d+$/', $quantityRaw)) {
                throw new RuntimeException('Selecciona un producto y una cantidad entera valida.');
            }

            $quantity = (int) $quantityRaw;

            if ($quantity <= 0) {
                throw new RuntimeException('La cantidad debe ser un numero entero mayor a cero.');
            }

            $orderService->addItem($orderId, $productId, $quantity, $note);
            $flash->success('Producto agregado a la orden.');
        } elseif ($action === 'remove_item') {
            if (!$canRemoveItems) {
                throw new RuntimeException('Esta orden ya no permite eliminar productos.');
            }

            $itemId = (int) ($_POST['id_orden_detalle'] ?? 0);

            if ($itemId <= 0) {
                throw new RuntimeException('Selecciona un producto valido para eliminar.');
            }

            $orderService->removeItem($orderId, $itemId);
            $flash->success('Producto eliminado de la orden.');
        } elseif ($action === 'apply_discount') {
            $discount = (float) ($_POST['descuento'] ?? 0);
            $orderService->applyDiscount($orderId, $discount);
            $flash->success('Descuento aplicado correctamente.');
        } elseif ($action === 'change_status') {
            $status = (string) ($_POST['estado'] ?? '');
            $comment = trim((string) ($_POST['comentario'] ?? ''));
            $allowedStatuses = ['ABIERTA', 'EN_PREPARACION', 'LISTA', 'SERVIDA', 'COMPLETADA', 'ANULADA'];

            if (!in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Selecciona un estado valido para la orden.');
            }

            $orderService->changeStatus($orderId, $status, (int) $auth->id(), $comment);
            $flash->success('Estado actualizado correctamente.');
        } elseif ($action === 'generate_comanda') {
            $areaId = (int) ($_POST['id_area_preparacion'] ?? 0);

            if ($areaId <= 0) {
                throw new RuntimeException('Selecciona un area de preparacion.');
            }

            $orderService->generateComanda($orderId, $areaId, (int) $auth->id());
            $flash->success('Comanda generada correctamente.');
        } elseif ($action === 'register_payment') {
            $openTurn = $db->fetch(
                "SELECT id
                 FROM turnos_caja
                 WHERE id_sucursal = :sucursal
                   AND estado = 'ABIERTO'
                 ORDER BY id DESC
                 LIMIT 1",
                ['sucursal' => $order['id_sucursal']]
            );

            if (!$openTurn) {
                throw new RuntimeException('Necesitas una caja abierta para registrar el pago.');
            }

            $existingPayment = (int) $db->value(
                "SELECT COUNT(*)
                 FROM pagos
                 WHERE id_orden = :orden
                   AND estado IN ('REGISTRADO', 'VALIDADO')",
                ['orden' => $orderId]
            );

            if ($existingPayment > 0) {
                throw new RuntimeException('La orden ya tiene un pago registrado.');
            }

            $methodId = (int) ($_POST['id_metodo_pago'] ?? 0);
            $amount = (float) ($_POST['monto'] ?? 0);
            $amountReceived = $_POST['monto_recibido'] !== '' ? (float) $_POST['monto_recibido'] : null;
            $operationNumber = trim((string) ($_POST['numero_operacion'] ?? ''));
            $notes = trim((string) ($_POST['observaciones_pago'] ?? ''));
            $shouldEmitReceiptOnPayment = $canOfferReceiptOnPayment && ($_POST['emitir_recibo'] ?? '') === '1';
            $paymentReceiptTypeId = $shouldEmitReceiptOnPayment ? (int) ($_POST['id_tipo_comprobante_pago'] ?? 0) : 0;

            if ($methodId <= 0 || $amount <= 0) {
                throw new RuntimeException('Completa el metodo y el monto del pago.');
            }

            if ($shouldEmitReceiptOnPayment && $paymentReceiptTypeId <= 0) {
                throw new RuntimeException('Selecciona el tipo de recibo que deseas emitir.');
            }

            if ($shouldEmitReceiptOnPayment) {
                $existingReceipt = (int) $db->value(
                    "SELECT COUNT(*)
                     FROM comprobantes
                     WHERE id_orden = :orden
                       AND estado = 'EMITIDO'",
                    ['orden' => $orderId]
                );

                if ($existingReceipt > 0) {
                    throw new RuntimeException('La orden ya tiene un comprobante emitido.');
                }
            }

            $payment = $orderService->registerPayment(
                $orderId,
                (int) $openTurn['id'],
                $methodId,
                $amount,
                $amountReceived,
                $operationNumber,
                $notes,
                (int) $auth->id()
            );

            $paymentId = (int) ($payment['id_pago_registrado'] ?? 0);
            $successMessage = 'Pago validado. ID generado: ' . $paymentId;

            if ($shouldEmitReceiptOnPayment) {
                if ($paymentId <= 0) {
                    $flash->success($successMessage);
                    $flash->info('El pago se registro, pero no se pudo identificar el pago para emitir el recibo.');
                    redirect('orders');
                }

                try {
                    $receipt = $orderService->emitReceipt(
                        $orderId,
                        $paymentId,
                        isset($order['id_cliente']) && (int) $order['id_cliente'] > 0 ? (int) $order['id_cliente'] : null,
                        $paymentReceiptTypeId,
                        (int) $auth->id()
                    );

                    $receiptCode = trim((string) (($receipt['serie'] ?? '') . '-' . ($receipt['numero'] ?? '')), '-');
                    $successMessage = $receiptCode !== ''
                        ? 'Pago validado y recibo emitido: ' . $receiptCode
                        : 'Pago validado y recibo emitido correctamente.';
                } catch (Throwable $receiptException) {
                    $flash->success($successMessage);
                    $flash->info('El pago se registro, pero no se pudo emitir el recibo: ' . $receiptException->getMessage());
                    redirect('orders');
                }
            }

            $flash->success($successMessage);
            redirect('orders');
        } elseif ($action === 'emit_receipt') {
            $paymentId = (int) ($_POST['id_pago'] ?? 0);
            $receiptTypeId = (int) ($_POST['id_tipo_comprobante'] ?? 0);
            $clientId = (int) ($_POST['id_cliente'] ?? 0);

            if ($order['estado'] !== 'PAGADA') {
                throw new RuntimeException('Solo puedes emitir comprobante sobre una orden pagada.');
            }

            $existingReceipt = (int) $db->value(
                "SELECT COUNT(*)
                 FROM comprobantes
                 WHERE id_orden = :orden
                   AND estado = 'EMITIDO'",
                ['orden' => $orderId]
            );

            if ($existingReceipt > 0) {
                throw new RuntimeException('La orden ya tiene un comprobante emitido.');
            }

            if ($paymentId <= 0 || $receiptTypeId <= 0) {
                throw new RuntimeException('Selecciona el pago y el tipo de comprobante.');
            }

            $receipt = $orderService->emitReceipt(
                $orderId,
                $paymentId,
                $clientId > 0 ? $clientId : null,
                $receiptTypeId,
                (int) $auth->id()
            );

            $flash->success('Comprobante emitido: ' . (($receipt['serie'] ?? '') . '-' . ($receipt['numero'] ?? '')));
        } else {
            throw new RuntimeException('Accion no reconocida.');
        }
    } catch (Throwable $exception) {
        $flash->error($exception->getMessage());
    }

    redirect('order_show', ['id' => $orderId]);
}

$order = $loadOrder();
$title = 'Orden ' . $order['codigo'];

$items = $db->fetchAll(
    "SELECT od.*, ap.nombre AS area
     FROM orden_detalle od
     LEFT JOIN areas_preparacion ap ON ap.id = od.id_area_preparacion
     WHERE od.id_orden = :id
     ORDER BY od.id",
    ['id' => $orderId]
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
    ['pop_sucursal' => $order['id_sucursal']]
);

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

$areas = $db->fetchAll(
    "SELECT DISTINCT ap.id, ap.nombre
     FROM orden_detalle od
     INNER JOIN areas_preparacion ap ON ap.id = od.id_area_preparacion
     WHERE od.id_orden = :id
       AND od.estado_item <> 'ANULADO'
     ORDER BY ap.nombre",
    ['id' => $orderId]
);

$paymentMethods = $db->fetchAll(
    "SELECT id, nombre, requiere_evidencia
     FROM metodos_pago
     WHERE activo = 1
     ORDER BY nombre"
);

$payments = $db->fetchAll(
    "SELECT p.*, mp.nombre AS metodo
     FROM pagos p
     INNER JOIN metodos_pago mp ON mp.id = p.id_metodo_pago
     WHERE p.id_orden = :id
     ORDER BY p.id DESC",
    ['id' => $orderId]
);

$receipts = $db->fetchAll(
    "SELECT c.*, tc.nombre AS tipo
     FROM comprobantes c
     INNER JOIN tipos_comprobante tc ON tc.id = c.id_tipo_comprobante
     WHERE c.id_orden = :id
     ORDER BY c.id DESC",
    ['id' => $orderId]
);

$receiptTypes = $db->fetchAll(
    "SELECT id, codigo, nombre, serie
     FROM tipos_comprobante
     WHERE activo = 1
     ORDER BY id"
);

$paymentReceiptTypes = array_values(array_filter(
    $receiptTypes,
    static fn (array $receiptType): bool => in_array((string) ($receiptType['codigo'] ?? ''), ['NV', 'BOL'], true)
));

if ($paymentReceiptTypes === []) {
    $paymentReceiptTypes = $receiptTypes;
}

$clients = $db->fetchAll(
    "SELECT id, nombre_razon_social, numero_documento
     FROM clientes
     WHERE activo = 1
     ORDER BY nombre_razon_social"
);

$history = $db->fetchAll(
    "SELECT oeh.*, u.usuario
     FROM orden_estados_historial oeh
     LEFT JOIN usuarios u ON u.id = oeh.id_usuario
     WHERE oeh.id_orden = :id
     ORDER BY oeh.id DESC
     LIMIT 12",
    ['id' => $orderId]
);

$commandas = $db->fetchAll(
    "SELECT c.*, ap.nombre AS area
     FROM comandas c
     INNER JOIN areas_preparacion ap ON ap.id = c.id_area_preparacion
     WHERE c.id_orden = :id
     ORDER BY c.id DESC",
    ['id' => $orderId]
);

render_page($title, 'order_show', [
    'csrf' => $csrf,
    'order' => $order,
    'items' => $items,
    'productCategories' => $productCategories,
    'products' => $products,
    'popularProducts' => $popularProducts,
    'areas' => $areas,
    'paymentMethods' => $paymentMethods,
    'payments' => $payments,
    'receipts' => $receipts,
    'receiptTypes' => $receiptTypes,
    'clients' => $clients,
    'history' => $history,
    'commandas' => $commandas,
    'isCashier' => $isCashier,
    'canManageOrder' => $canManageOrder,
    'canRegisterPayment' => $canRegisterPayment,
    'canEmitReceipt' => $canEmitReceipt,
    'canOfferReceiptOnPayment' => $canOfferReceiptOnPayment,
    'paymentReceiptTypes' => $paymentReceiptTypes,
    'canRemoveItems' => $canRemoveItems,
]);
