<?php

$currentUser = $auth->user();
$sucursalId = (int) ($currentUser['id_sucursal'] ?? 1);
$currentTimestamp = new DateTimeImmutable('now');

$formatElapsed = static function (?string $value) use ($currentTimestamp): string {
    if (!$value) {
        return '--';
    }

    try {
        $startedAt = new DateTimeImmutable($value);
    } catch (Throwable) {
        return '--';
    }

    $seconds = max(0, $currentTimestamp->getTimestamp() - $startedAt->getTimestamp());

    if ($seconds < 60) {
        return $seconds . 's';
    }

    $minutes = intdiv($seconds, 60);

    if ($minutes < 60) {
        return $minutes . 'm';
    }

    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;

    return $remainingMinutes > 0
        ? $hours . 'h ' . $remainingMinutes . 'm'
        : $hours . 'h';
};

$toIsoTimestamp = static function (?string $value): ?string {
    if (!$value) {
        return null;
    }

    try {
        return (new DateTimeImmutable($value))->format(DateTimeInterface::ATOM);
    } catch (Throwable) {
        return null;
    }
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);

        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === 'toggle_item_ready') {
            $comandaId = (int) ($_POST['id_comanda'] ?? 0);
            $comandaDetailId = (int) ($_POST['id_comanda_detalle'] ?? 0);

            if ($comandaId <= 0 || $comandaDetailId <= 0) {
                throw new RuntimeException('Selecciona un plato valido para actualizar.');
            }

            $orderService->toggleKitchenItemReady($comandaId, $comandaDetailId);
            $flash->success('Estado del plato actualizado en cocina.');
        } elseif ($action === 'mark_ticket_ready') {
            $comandaId = (int) ($_POST['id_comanda'] ?? 0);

            if ($comandaId <= 0) {
                throw new RuntimeException('Selecciona un ticket valido.');
            }

            $result = $orderService->markKitchenTicketReady($comandaId, (int) $auth->id());
            $orderCode = trim((string) ($result['codigo_orden'] ?? ''));
            $becameReady = !empty($result['order_became_ready']);

            if ($becameReady) {
                $flash->success(
                    $orderCode !== ''
                        ? 'Pedido ' . $orderCode . ' listo y notificado correctamente.'
                        : 'Pedido listo y notificado correctamente.'
                );
            } else {
                $flash->success(
                    $orderCode !== ''
                        ? 'Ticket de ' . $orderCode . ' marcado como listo.'
                        : 'Ticket marcado como listo.'
                );
            }
        } else {
            throw new RuntimeException('Accion no reconocida en cocina.');
        }
    } catch (Throwable $exception) {
        $flash->error($exception->getMessage());
    }

    redirect('kitchen');
}

$commandas = $db->fetchAll(
    "SELECT c.id,
            c.numero_comanda,
            c.estado,
            c.fecha_emision,
            ap.nombre AS area,
            o.id AS id_orden,
            o.codigo AS codigo_orden,
            o.tipo_servicio,
            o.cantidad_personas,
            m.numero AS mesa,
            cli.nombre_razon_social AS cliente,
            CONCAT(u.nombres, ' ', COALESCE(u.apellidos, '')) AS usuario
     FROM comandas c
     INNER JOIN ordenes o ON o.id = c.id_orden
     INNER JOIN areas_preparacion ap ON ap.id = c.id_area_preparacion
     LEFT JOIN mesas m ON m.id = o.id_mesa
     LEFT JOIN clientes cli ON cli.id = o.id_cliente
     LEFT JOIN usuarios u ON u.id = c.id_usuario
     WHERE o.id_sucursal = :sucursal
       AND o.estado <> 'ANULADA'
       AND c.estado IN ('EMITIDA', 'IMPRESA', 'EN_PROCESO')
       AND EXISTS (
           SELECT 1
           FROM comanda_detalle cd
           WHERE cd.id_comanda = c.id
             AND cd.estado NOT IN ('ENTREGADO', 'ANULADO')
       )
     ORDER BY c.fecha_emision ASC, c.id ASC",
    ['sucursal' => $sucursalId]
);

$itemsByComanda = [];

if ($commandas) {
    $detailParams = [];
    $placeholders = [];

    foreach (array_values($commandas) as $index => $comanda) {
        $key = 'comanda_' . $index;
        $placeholders[] = ':' . $key;
        $detailParams[$key] = (int) $comanda['id'];
    }

    $detailRows = $db->fetchAll(
        "SELECT cd.id,
                cd.id_comanda,
                cd.id_orden_detalle,
                cd.cantidad,
                cd.estado,
                cd.observacion,
                od.nombre_producto
         FROM comanda_detalle cd
         INNER JOIN orden_detalle od ON od.id = cd.id_orden_detalle
         WHERE cd.id_comanda IN (" . implode(', ', $placeholders) . ")
           AND cd.estado <> 'ANULADO'
         ORDER BY cd.id_comanda ASC,
                  CASE WHEN cd.estado IN ('LISTO', 'ENTREGADO') THEN 1 ELSE 0 END ASC,
                  cd.id ASC",
        $detailParams
    );

    foreach ($detailRows as $detailRow) {
        $itemsByComanda[(int) $detailRow['id_comanda']][] = $detailRow;
    }
}

$preparedTickets = [];
$totalItems = 0;
$readyItems = 0;

foreach ($commandas as $comanda) {
    $serviceType = (string) ($comanda['tipo_servicio'] ?? 'MESA');
    $items = $itemsByComanda[(int) $comanda['id']] ?? [];

    if ($items === []) {
        continue;
    }

    $readyCount = 0;

    foreach ($items as &$item) {
        $itemState = (string) ($item['estado'] ?? 'PENDIENTE');
        $item['is_ready'] = in_array($itemState, ['LISTO', 'ENTREGADO'], true);
        $item['status_label'] = $item['is_ready'] ? 'Listo' : 'Preparando';
        $item['qty_label'] = (string) ((int) round((float) ($item['cantidad'] ?? 0)));

        if ($item['is_ready']) {
            $readyCount++;
        }
    }
    unset($item);

    $userName = trim((string) ($comanda['usuario'] ?? ''));
    $userParts = $userName !== '' ? preg_split('/\s+/', $userName) : [];

    $preparedTickets[] = [
        'id' => (int) $comanda['id'],
        'id_orden' => (int) $comanda['id_orden'],
        'codigo_orden' => (string) ($comanda['codigo_orden'] ?: $comanda['numero_comanda']),
        'numero_comanda' => (string) $comanda['numero_comanda'],
        'area' => (string) ($comanda['area'] ?? 'Cocina'),
        'items' => $items,
        'item_count' => count($items),
        'ready_count' => $readyCount,
        'all_items_ready' => count($items) > 0 && $readyCount === count($items),
        'elapsed_label' => $formatElapsed((string) ($comanda['fecha_emision'] ?? '')),
        'started_at_iso' => $toIsoTimestamp((string) ($comanda['fecha_emision'] ?? '')),
        'created_label' => format_datetime((string) ($comanda['fecha_emision'] ?? ''), 'h:i a'),
        'service_reference' => match ($serviceType) {
            'DELIVERY' => $comanda['cliente'] ?: 'delivery',
            'LLEVAR' => $comanda['cliente'] ?: 'para llevar',
            default => $comanda['mesa'] ? 'mesa #' . $comanda['mesa'] : 'mesa',
        },
        'short_user' => trim((string) ($userParts[0] ?? '')) ?: 'Sistema',
    ];

    $totalItems += count($items);
    $readyItems += $readyCount;
}

render_page($title, 'kitchen', [
    'csrf' => $csrf,
    'commandas' => $preparedTickets,
    'ticketsCount' => count($preparedTickets),
    'totalItems' => $totalItems,
    'readyItems' => $readyItems,
    'currentTime' => $currentTimestamp->format('h:i a'),
]);
