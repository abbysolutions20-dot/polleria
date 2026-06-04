<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class OrderService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createOrder(
        int $sucursalId,
        ?int $mesaId,
        ?int $clientId,
        int $userId,
        string $serviceType,
        int $people,
        ?string $notes
    ): array {
        $rows = $this->db->call('sp_crear_orden', [
            $sucursalId,
            $mesaId,
            $clientId,
            $userId,
            $serviceType,
            $people,
            $notes ?: null,
        ]);

        return $rows[0] ?? [];
    }

    public function addItem(int $orderId, int $productId, int $quantity, ?string $note): void
    {
        $this->db->call('sp_agregar_item_orden', [
            $orderId,
            $productId,
            $quantity,
            $note ?: null,
        ]);
    }

    public function sendOrderToKitchen(int $orderId, int $userId): void
    {
        $sync = function (Database $db) use ($orderId, $userId): void {
            $order = $db->fetch(
                "SELECT id, estado
                 FROM ordenes
                 WHERE id = :orden
                 LIMIT 1",
                ['orden' => $orderId]
            );

            if (!$order) {
                throw new \RuntimeException('La orden creada no existe.');
            }

            $itemsWithoutArea = (int) $db->value(
                "SELECT COUNT(*)
                 FROM orden_detalle
                 WHERE id_orden = :orden
                   AND estado_item <> 'ANULADO'
                   AND id_area_preparacion IS NULL",
                ['orden' => $orderId]
            );

            if ($itemsWithoutArea > 0) {
                throw new \RuntimeException('Todos los platos de la orden deben tener area de preparacion para enviarse a cocina.');
            }

            $areas = $db->fetchAll(
                "SELECT DISTINCT id_area_preparacion
                 FROM orden_detalle
                 WHERE id_orden = :orden
                   AND estado_item <> 'ANULADO'
                   AND id_area_preparacion IS NOT NULL
                 ORDER BY id_area_preparacion",
                ['orden' => $orderId]
            );

            if ($areas === []) {
                throw new \RuntimeException('La orden no tiene platos listos para enviarse a cocina.');
            }

            $activeComandas = $db->fetchAll(
                "SELECT id, id_area_preparacion
                 FROM comandas
                 WHERE id_orden = :orden
                   AND estado NOT IN ('ANULADA', 'ATENDIDA')
                 ORDER BY id DESC",
                ['orden' => $orderId]
            );

            $comandaByArea = [];

            foreach ($activeComandas as $activeComanda) {
                $areaId = (int) ($activeComanda['id_area_preparacion'] ?? 0);
                $comandaId = (int) ($activeComanda['id'] ?? 0);

                if ($areaId > 0 && $comandaId > 0 && !isset($comandaByArea[$areaId])) {
                    $comandaByArea[$areaId] = $comandaId;
                }
            }

            if (!in_array((string) ($order['estado'] ?? ''), ['EN_PREPARACION', 'PAGADA', 'ANULADA'], true)) {
                $db->call('sp_cambiar_estado_orden', [
                    $orderId,
                    'EN_PREPARACION',
                    $userId,
                    'Orden enviada automaticamente a cocina.',
                ]);
            }

            foreach ($areas as $area) {
                $areaId = (int) ($area['id_area_preparacion'] ?? 0);

                if ($areaId <= 0) {
                    continue;
                }

                $comandaId = $comandaByArea[$areaId] ?? 0;

                if ($comandaId <= 0) {
                    $number = sprintf('COM-%s-%d-%d', date('YmdHis'), $areaId, $orderId);

                    $db->execute(
                        "INSERT INTO comandas(id_orden, numero_comanda, id_area_preparacion, id_usuario)
                         VALUES (:orden, :numero, :area, :usuario)",
                        [
                            'orden' => $orderId,
                            'numero' => $number,
                            'area' => $areaId,
                            'usuario' => $userId,
                        ]
                    );

                    $comandaId = (int) $db->pdo()->lastInsertId();
                    $comandaByArea[$areaId] = $comandaId;
                }

                $db->execute(
                    "INSERT INTO comanda_detalle(id_comanda, id_orden_detalle, cantidad, observacion)
                     SELECT :comanda, od.id, od.cantidad, od.observacion
                     FROM orden_detalle od
                     WHERE od.id_orden = :orden
                       AND od.id_area_preparacion = :area
                       AND od.estado_item <> 'ANULADO'
                       AND NOT EXISTS (
                           SELECT 1
                           FROM comanda_detalle cd
                           WHERE cd.id_orden_detalle = od.id
                       )",
                    [
                        'comanda' => $comandaId,
                        'orden' => $orderId,
                        'area' => $areaId,
                    ]
                );

                $db->execute(
                    "UPDATE comandas
                     SET estado = 'EN_PROCESO'
                     WHERE id = :comanda
                       AND estado IN ('EMITIDA', 'IMPRESA', 'EN_PROCESO')",
                    ['comanda' => $comandaId]
                );
            }

            $db->execute(
                "UPDATE orden_detalle
                 SET estado_item = 'PREPARACION'
                 WHERE id_orden = :orden
                   AND estado_item NOT IN ('ANULADO', 'LISTO', 'SERVIDO')",
                ['orden' => $orderId]
            );
        };

        if ($this->db->pdo()->inTransaction()) {
            $sync($this->db);

            return;
        }

        $this->db->transaction($sync);
    }

    public function removeItem(int $orderId, int $itemId): void
    {
        $this->db->transaction(function (Database $db) use ($orderId, $itemId): void {
            $item = $db->fetch(
                "SELECT id, estado_item
                 FROM orden_detalle
                 WHERE id = :item
                   AND id_orden = :orden
                 LIMIT 1",
                [
                    'item' => $itemId,
                    'orden' => $orderId,
                ]
            );

            if (!$item) {
                throw new \RuntimeException('El producto seleccionado no pertenece a esta orden.');
            }

            if (($item['estado_item'] ?? '') === 'ANULADO') {
                throw new \RuntimeException('El producto ya fue eliminado de la orden.');
            }

            $db->execute(
                "UPDATE orden_detalle
                 SET estado_item = 'ANULADO'
                 WHERE id = :item
                   AND id_orden = :orden",
                [
                    'item' => $itemId,
                    'orden' => $orderId,
                ]
            );

            $db->execute(
                "UPDATE comanda_detalle cd
                 INNER JOIN comandas c ON c.id = cd.id_comanda
                 SET cd.estado = 'ANULADO'
                 WHERE cd.id_orden_detalle = :item
                   AND c.id_orden = :orden
                   AND cd.estado <> 'ANULADO'",
                [
                    'item' => $itemId,
                    'orden' => $orderId,
                ]
            );
        });
    }

    public function applyDiscount(int $orderId, float $discount): void
    {
        $this->db->call('sp_aplicar_descuento_orden', [$orderId, $discount]);
    }

    public function changeStatus(int $orderId, string $status, int $userId, ?string $comment): void
    {
        $this->db->call('sp_cambiar_estado_orden', [
            $orderId,
            $status,
            $userId,
            $comment ?: null,
        ]);
    }

    public function generateComanda(int $orderId, int $areaId, int $userId): void
    {
        $this->db->call('sp_generar_comanda', [
            $orderId,
            $areaId,
            $userId,
        ]);
    }

    public function toggleKitchenItemReady(int $comandaId, int $comandaDetailId): void
    {
        $this->db->transaction(function (Database $db) use ($comandaId, $comandaDetailId): void {
            $item = $db->fetch(
                "SELECT cd.id,
                        cd.estado,
                        cd.id_orden_detalle,
                        c.id AS id_comanda,
                        c.estado AS estado_comanda
                 FROM comanda_detalle cd
                 INNER JOIN comandas c ON c.id = cd.id_comanda
                 WHERE cd.id = :detalle
                   AND c.id = :comanda
                 LIMIT 1",
                [
                    'detalle' => $comandaDetailId,
                    'comanda' => $comandaId,
                ]
            );

            if (!$item) {
                throw new \RuntimeException('El plato seleccionado ya no existe en la comanda.');
            }

            if (in_array((string) ($item['estado_comanda'] ?? ''), ['ATENDIDA', 'ANULADA'], true)) {
                throw new \RuntimeException('La comanda ya no admite cambios.');
            }

            $currentState = (string) ($item['estado'] ?? 'PENDIENTE');

            if ($currentState === 'ANULADO') {
                throw new \RuntimeException('El producto seleccionado ya no esta disponible en cocina.');
            }

            $isReady = in_array($currentState, ['LISTO', 'ENTREGADO'], true);
            $nextComandaState = $isReady ? 'PREPARANDO' : 'LISTO';
            $nextOrderItemState = $isReady ? 'PREPARACION' : 'LISTO';

            $db->execute(
                "UPDATE comanda_detalle
                 SET estado = :estado
                 WHERE id = :detalle
                   AND id_comanda = :comanda",
                [
                    'estado' => $nextComandaState,
                    'detalle' => $comandaDetailId,
                    'comanda' => $comandaId,
                ]
            );

            $db->execute(
                "UPDATE orden_detalle
                 SET estado_item = :estado
                 WHERE id = :detalle_orden",
                [
                    'estado' => $nextOrderItemState,
                    'detalle_orden' => $item['id_orden_detalle'],
                ]
            );

            $db->execute(
                "UPDATE comandas
                 SET estado = 'EN_PROCESO'
                 WHERE id = :comanda
                   AND estado IN ('EMITIDA', 'IMPRESA', 'EN_PROCESO')",
                ['comanda' => $comandaId]
            );
        });
    }

    public function markKitchenTicketReady(int $comandaId, int $userId): array
    {
        return $this->db->transaction(function (Database $db) use ($comandaId, $userId): array {
            $comanda = $db->fetch(
                "SELECT c.id,
                        c.id_orden,
                        c.estado,
                        o.estado AS estado_orden,
                        o.codigo AS codigo_orden
                 FROM comandas c
                 INNER JOIN ordenes o ON o.id = c.id_orden
                 WHERE c.id = :comanda
                 LIMIT 1",
                ['comanda' => $comandaId]
            );

            if (!$comanda) {
                throw new \RuntimeException('La comanda seleccionada no existe.');
            }

            if (in_array((string) ($comanda['estado'] ?? ''), ['ATENDIDA', 'ANULADA'], true)) {
                throw new \RuntimeException('La comanda ya fue cerrada.');
            }

            $pendingItems = (int) $db->value(
                "SELECT COUNT(*)
                 FROM comanda_detalle
                 WHERE id_comanda = :comanda
                   AND estado NOT IN ('LISTO', 'ENTREGADO', 'ANULADO')",
                ['comanda' => $comandaId]
            );

            if ($pendingItems > 0) {
                throw new \RuntimeException('Marca todos los platos como listos antes de cerrar el ticket.');
            }

            $db->execute(
                "UPDATE comanda_detalle
                 SET estado = 'ENTREGADO'
                 WHERE id_comanda = :comanda
                   AND estado = 'LISTO'",
                ['comanda' => $comandaId]
            );

            $db->execute(
                "UPDATE comandas
                 SET estado = 'ATENDIDA'
                 WHERE id = :comanda",
                ['comanda' => $comandaId]
            );

            $remainingPreparation = (int) $db->value(
                "SELECT COUNT(*)
                 FROM comanda_detalle cd
                 INNER JOIN comandas c ON c.id = cd.id_comanda
                 WHERE c.id_orden = :orden
                   AND c.estado <> 'ANULADA'
                   AND cd.estado NOT IN ('ENTREGADO', 'ANULADO')",
                ['orden' => $comanda['id_orden']]
            );

            if ($remainingPreparation === 0 && (string) ($comanda['estado_orden'] ?? '') === 'EN_PREPARACION') {
                $db->execute(
                    "UPDATE ordenes
                     SET estado = 'LISTA',
                         fecha_actualizacion = NOW()
                     WHERE id = :orden",
                    ['orden' => $comanda['id_orden']]
                );

                $db->execute(
                    "INSERT INTO orden_estados_historial(id_orden, estado_anterior, estado_nuevo, comentario, id_usuario)
                     VALUES (:orden, 'EN_PREPARACION', 'LISTA', 'Cocina marco la comanda como lista.', :usuario)",
                    [
                        'orden' => $comanda['id_orden'],
                        'usuario' => $userId,
                    ]
                );

                return [
                    'codigo_orden' => (string) ($comanda['codigo_orden'] ?? ''),
                    'order_became_ready' => true,
                ];
            }

            return [
                'codigo_orden' => (string) ($comanda['codigo_orden'] ?? ''),
                'order_became_ready' => false,
            ];
        });
    }

    public function registerPayment(
        int $orderId,
        int $turnId,
        int $paymentMethodId,
        float $amount,
        ?float $amountReceived,
        ?string $operationNumber,
        ?string $notes,
        int $userId
    ): array {
        $rows = $this->db->call('sp_registrar_pago_orden', [
            $orderId,
            $turnId,
            $paymentMethodId,
            $amount,
            $amountReceived,
            $operationNumber ?: null,
            $notes ?: null,
            $userId,
        ]);

        return $rows[0] ?? [];
    }

    public function emitReceipt(
        int $orderId,
        int $paymentId,
        ?int $clientId,
        int $receiptTypeId,
        int $userId
    ): array {
        $rows = $this->db->call('sp_emitir_comprobante', [
            $orderId,
            $paymentId,
            $clientId,
            $receiptTypeId,
            $userId,
        ]);

        return $rows[0] ?? [];
    }
}
