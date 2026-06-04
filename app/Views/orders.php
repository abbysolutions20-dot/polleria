<?php

$selectedOrder = $selectedOrder ?? null;
$showSelectedEditor = $showSelectedEditor ?? false;
$paymentMethodCatalog = $paymentMethodCatalog ?? [];
$canCreateOrdersByRole = $canCreateOrdersByRole ?? ($canCreateOrders ?? false);
$hasOpenTurn = $hasOpenTurn ?? (bool) ($currentTurn ?? null);
$draftItemsJson = json_encode($draftItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$serviceLabels = [
    'MESA' => 'Mesa',
    'LLEVAR' => 'Para llevar',
    'DELIVERY' => 'Delivery',
];
$serviceIcons = [
    'MESA' => 'table',
    'LLEVAR' => 'bag',
    'DELIVERY' => 'bike',
];
$advancedOpen = $orderForm['id_cliente'] !== ''
    || $orderForm['observaciones'] !== ''
    || (int) ($orderForm['cantidad_personas'] ?? 1) > 1;
$icons = [
    'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="M20 20l-4.35-4.35"></path></svg>',
    'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
    'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10l3 3v13H7z"></path><path d="M7 7H4v13h13"></path><path d="M10 11h7"></path><path d="M10 15h7"></path></svg>',
    'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>',
    'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>',
    'money' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="3"></rect><circle cx="12" cy="12" r="2.5"></circle><path d="M7 12h.01"></path><path d="M17 12h.01"></path></svg>',
    'dish' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3v7"></path><path d="M11 3v7"></path><path d="M8 7H5.5A2.5 2.5 0 0 1 3 4.5V3"></path><path d="M11 7h2.5A2.5 2.5 0 0 0 16 4.5V3"></path><path d="M9.5 10v11"></path><path d="M19 3c-1.7 2-2.5 4.2-2.5 6.7V21"></path></svg>',
    'basket' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 10h14l-1.2 8.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8z"></path><path d="M9 10V8a3 3 0 0 1 6 0v2"></path></svg>',
    'table' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M6 7v10"></path><path d="M18 7v10"></path><path d="M10 7v4"></path><path d="M14 7v4"></path><path d="M3 17h18"></path></svg>',
    'bag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9h12l-1 10a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2z"></path><path d="M9 9V7a3 3 0 0 1 6 0v2"></path></svg>',
    'bike' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="17" r="3"></circle><circle cx="18" cy="17" r="3"></circle><path d="M6 17l4-8h4"></path><path d="M12 9l2 4h4"></path><path d="M11 6h3"></path></svg>',
    'sliders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h9"></path><path d="M17 6h3"></path><path d="M10 6a2 2 0 1 0 4 0a2 2 0 1 0-4 0"></path><path d="M4 12h3"></path><path d="M11 12h9"></path><path d="M7 12a2 2 0 1 0 4 0a2 2 0 1 0-4 0"></path><path d="M4 18h11"></path><path d="M19 18h1"></path><path d="M15 18a2 2 0 1 0 4 0a2 2 0 1 0-4 0"></path></svg>',
    'layers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4l8 4l-8 4l-8-4z"></path><path d="M4 12l8 4l8-4"></path><path d="M4 16l8 4l8-4"></path></svg>',
    'chevron' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5l5-5"></path></svg>',
    'history' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v5h5"></path><path d="M12 7v5l3 2"></path></svg>',
    'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M9 7V5h6v2"></path><path d="M7 7l1 12h8l1-12"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>',
    'print' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V4h10v4"></path><path d="M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"></path><path d="M7 14h10v6H7z"></path><path d="M17 12h.01"></path></svg>',
    'cash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="10" rx="2"></rect><circle cx="12" cy="12" r="2.2"></circle><path d="M7 12h.01"></path><path d="M17 12h.01"></path></svg>',
    'card' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="M3 10h18"></path><path d="M7 15h3"></path></svg>',
    'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.5"></rect><path d="M11 18h2"></path></svg>',
    'bank' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9l9-5l9 5"></path><path d="M5 10v7"></path><path d="M10 10v7"></path><path d="M14 10v7"></path><path d="M19 10v7"></path><path d="M3 19h18"></path></svg>',
    'mix' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10"></path><path d="M14 17h6"></path><path d="M8 4l-4 3l4 3"></path><path d="M16 14l4 3l-4 3"></path></svg>',
    'arrow-left' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"></path><path d="M12 19l-7-7l7-7"></path></svg>',
    'camera' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4z"></path><circle cx="12" cy="13.5" r="3"></circle></svg>',
    'image' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="M8 13l3-3l3 4l2-2l4 5"></path><circle cx="9" cy="9" r="1"></circle></svg>',
    'receipt' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v18l-2-1l-2 1l-2-1l-2 1l-2-1z"></path><path d="M9 8h6"></path><path d="M9 12h6"></path><path d="M9 16h4"></path></svg>',
];
$icon = static function (string $name) use ($icons): string {
    return $icons[$name] ?? '';
};

$formatOrderCode = static function (string $code): string {
    if (preg_match('/(\d+)$/', $code, $matches)) {
        return 'ORD-' . str_pad(substr($matches[1], -4), 4, '0', STR_PAD_LEFT);
    }

    return $code;
};

$paymentModalButtons = [
    ['key' => 'EFECTIVO', 'label' => 'Efectivo', 'icon' => 'cash'],
    ['key' => 'TARJETA', 'label' => 'Tarjeta', 'icon' => 'card'],
    ['key' => 'YAPE', 'label' => 'Yape', 'icon' => 'phone'],
    ['key' => 'PLIN', 'label' => 'Plin', 'icon' => 'phone'],
    ['key' => 'TRANSFERENCIA', 'label' => 'Transferencia', 'icon' => 'bank'],
    ['key' => 'MIXTO', 'label' => 'Mixto', 'icon' => 'mix'],
];

$selectedOrderItemCounts = [];
$selectedOrderSubtotal = 0.0;
$selectedOrderTaxableBase = 0.0;
$selectedEditorAdvancedOpen = false;

if ($showSelectedEditor && $selectedOrder) {
    foreach (($selectedOrder['items'] ?? []) as $selectedItem) {
        $productId = (int) ($selectedItem['id_producto'] ?? 0);
        $quantity = (int) round((float) ($selectedItem['cantidad'] ?? 0));
        $lineSubtotal = (float) ($selectedItem['subtotal'] ?? 0);

        $selectedOrderSubtotal += $lineSubtotal;

        if (!empty($selectedItem['afecto_igv'])) {
            $selectedOrderTaxableBase += $lineSubtotal;
        }

        if ($productId > 0) {
            $selectedOrderItemCounts[$productId] = ($selectedOrderItemCounts[$productId] ?? 0) + max(0, $quantity);
        }
    }

    $selectedEditorAdvancedOpen = !empty($selectedOrder['id_cliente'])
        || !empty($selectedOrder['observaciones'])
        || (int) ($selectedOrder['cantidad_personas'] ?? 1) > 1;
}
?>
<section class="orders-shell<?= ($showCreateBuilder || $showSelectedEditor) ? ' orders-shell--builder' : '' ?>">
    <?php if (!$showCreateBuilder && !$showSelectedEditor): ?>
        <div class="orders-hero" id="orders-summary">
            <div class="orders-hero__header">
                <div>
                    <span class="eyebrow"><?= $isCashier ? 'Caja' : 'Ordenes' ?></span>
                    <h3><?= h($isCashier ? 'Ordenes para cobrar' : $reportTitle) ?></h3>
                    <div class="orders-hero__subtitle">
                        <span><?= h($isCashier ? 'Selecciona una orden para registrar el pago desde caja.' : $reportSubtitle) ?></span>
                        <?php if ($currentTurn): ?>
                            <span class="orders-hero__pill">Caja abierta</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="orders-hero__actions">
                        <?php if ($canAccessDashboard): ?>
                            <a class="orders-hero__control orders-hero__control--icon" href="<?= h(url('dashboard')) ?>" aria-label="Resumen">
                                <span class="icon"><?= $icon('sliders') ?></span>
                            </a>
                        <?php endif; ?>

                        <a class="orders-hero__control orders-hero__control--icon" href="#orders-list" aria-label="Pedidos">
                            <span class="icon"><?= $icon('layers') ?></span>
                        </a>

                        <?php if ($canAccessKitchen): ?>
                            <a
                                class="orders-hero__control orders-hero__control--mobile-icon"
                                href="<?= h(url('kitchen')) ?>"
                                aria-label="Cocina"
                                title="Cocina"
                            >
                                <span class="icon"><?= $icon('dish') ?></span>
                                <span class="orders-hero__control-text">Cocina</span>
                            </a>
                        <?php endif; ?>

                        <a
                            class="orders-hero__control orders-hero__control--mobile-icon"
                            href="#orders-list"
                            aria-label="Historial"
                            title="Historial"
                        >
                            <span class="icon"><?= $icon('history') ?></span>
                            <span class="orders-hero__control-text">Historial</span>
                        </a>

                        <?php if ($canCreateOrders): ?>
                            <a
                                class="orders-hero__control orders-hero__control--primary orders-hero__control--mobile-icon"
                                href="<?= h(url('orders', ['create' => '1'])) ?>"
                                data-order-builder-trigger
                                aria-controls="order-builder"
                                aria-expanded="<?= $showCreateBuilder ? 'true' : 'false' ?>"
                                aria-label="Crear orden"
                                title="Crear orden"
                            >
                                <span class="icon"><?= $icon('plus') ?></span>
                                <span class="orders-hero__control-text">Crear orden</span>
                            </a>
                        <?php elseif ($canCreateOrdersByRole && !$hasOpenTurn): ?>
                            <span
                                class="orders-hero__control orders-hero__control--primary orders-hero__control--mobile-icon is-disabled"
                                aria-disabled="true"
                                title="Abre caja para crear ordenes"
                            >
                                <span class="icon"><?= $icon('plus') ?></span>
                                <span class="orders-hero__control-text">Crear orden</span>
                            </span>
                        <?php endif; ?>
                </div>
            </div>

            <?php if ($canCreateOrdersByRole && !$hasOpenTurn): ?>
                <div class="empty-inline empty-inline--warning orders-turn-warning">
                    <p>Abre un turno de caja para poder crear nuevas ordenes.</p>
                </div>
            <?php endif; ?>

            <section class="stat-grid orders-stats">
                <article class="stat-card orders-stats__card">
                    <div class="orders-stats__head">
                        <span class="stat-card__label">Total Ordenes</span>
                        <span class="orders-stats__icon icon icon--accent-soft"><?= $icon('orders') ?></span>
                    </div>
                    <strong><?= h((string) $totalOrdersCount) ?></strong>
                </article>
                <article class="stat-card orders-stats__card">
                    <div class="orders-stats__head">
                        <span class="stat-card__label">Pagadas</span>
                        <span class="orders-stats__icon icon icon--success-soft"><?= $icon('check') ?></span>
                    </div>
                    <strong><?= h((string) $paidOrdersCount) ?></strong>
                    <p><?= h((string) $paidOrdersPercent) ?>% del total</p>
                </article>
                <article class="stat-card orders-stats__card">
                    <div class="orders-stats__head">
                        <span class="stat-card__label">Pago Pendiente</span>
                        <span class="orders-stats__icon icon icon--warning-soft"><?= $icon('clock') ?></span>
                    </div>
                    <strong><?= h((string) $pendingOrdersCount) ?></strong>
                    <p><?= h((string) $pendingOrdersPercent) ?>% del total</p>
                </article>
                <article class="stat-card orders-stats__card">
                    <div class="orders-stats__head">
                        <span class="stat-card__label">Total Ventas</span>
                        <span class="orders-stats__icon icon icon--accent-soft"><?= $icon('money') ?></span>
                    </div>
                    <strong><?= h(money($totalSalesAmount)) ?></strong>
                    <p>Prom: <?= h(money($averageTicket)) ?></p>
                </article>
            </section>
        </div>
    <?php endif; ?>

    <?php if ($canCreateOrders): ?>
        <section
            id="order-builder"
            class="panel pos-builder<?= $showCreateBuilder ? ' is-open' : '' ?>"
            data-pos-builder
            <?= $showCreateBuilder ? '' : 'hidden' ?>
        >
        <div class="pos-builder__grid">
            <div class="pos-builder__catalog">
                <div class="pos-builder__toolbar">
                    <div class="field field--full">
                        <label class="sr-only" for="pos-search">Buscar plato</label>
                        <div class="pos-search">
                            <span class="pos-search__icon icon"><?= $icon('search') ?></span>
                            <input
                                id="pos-search"
                                type="search"
                                placeholder="Buscar productos..."
                                autocomplete="off"
                                data-pos-search
                            >
                        </div>
                    </div>

                    <div class="pos-builder__categories" role="tablist" aria-label="Categorias de la carta">
                        <button class="pos-chip is-active" type="button" data-category-filter="" aria-pressed="true">
                            Todos
                        </button>
                        <?php foreach ($productCategories as $categoryItem): ?>
                            <button
                                class="pos-chip"
                                type="button"
                                data-category-filter="<?= h((string) $categoryItem['id']) ?>"
                                aria-pressed="false"
                            >
                                <?= h($categoryItem['nombre']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pos-product-grid" data-pos-product-grid>
                    <?php foreach ($products as $product): ?>
                        <button
                            type="button"
                            class="product-card"
                            data-product-card
                            data-product-item
                            data-product-id="<?= h((string) $product['id']) ?>"
                            data-product-name="<?= h($product['nombre']) ?>"
                            data-product-price="<?= h((string) $product['precio_venta']) ?>"
                            data-product-category-id="<?= h((string) $product['id_categoria']) ?>"
                            data-product-category="<?= h($product['categoria']) ?>"
                            data-product-area="<?= h($product['area_preparacion'] ?? '') ?>"
                            data-product-taxable="<?= h((string) (int) ($product['afecto_igv'] ?? 0)) ?>"
                        >
                            <span class="product-card__icon icon icon--dish"><?= $icon('dish') ?></span>
                            <span class="product-card__body">
                                <span class="product-card__category"><?= h($product['categoria']) ?></span>
                                <strong class="product-card__name"><?= h($product['nombre']) ?></strong>
                                <span class="product-card__price"><?= h(money($product['precio_venta'])) ?></span>
                                <span class="product-card__meta"><?= h($product['area_preparacion'] ?: 'Sin area') ?></span>
                                <span class="product-card__meta product-card__meta--orders">
                                    <?= h((string) ((int) round((float) $product['total_vendido']))) ?> pedidos
                                </span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if (!$products): ?>
                    <div class="empty-inline">
                        <p>No hay platos activos disponibles para crear una orden.</p>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="pos-cart">
                <form method="post" class="pos-cart__form" data-inline-order-form>
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="create_inline_order">
                    <input type="hidden" name="items_json" value="<?= h($draftItemsJson ?: '[]') ?>" data-pos-items-input>

                    <div class="pos-cart__header">
                        <div class="pos-cart__title">
                            <span class="pos-cart__title-icon icon icon--accent-soft"><?= $icon('basket') ?></span>
                            <div>
                                <h3>Orden actual</h3>
                                <p class="muted-text">Selecciona platos y arma el pedido sin salir de esta pantalla.</p>
                            </div>
                        </div>
                        <span class="pos-cart__count" data-pos-item-count>0 item(s)</span>
                    </div>

                    <div class="pos-service-switch" data-pos-service-switch>
                        <?php foreach ($serviceLabels as $serviceValue => $serviceLabel): ?>
                            <button
                                type="button"
                                class="pos-service-switch__button<?= $orderForm['tipo_servicio'] === $serviceValue ? ' is-active' : '' ?>"
                                data-service-value="<?= h($serviceValue) ?>"
                                aria-pressed="<?= $orderForm['tipo_servicio'] === $serviceValue ? 'true' : 'false' ?>"
                            >
                                <span class="icon"><?= $icon($serviceIcons[$serviceValue]) ?></span>
                                <span><?= h($serviceLabel) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <input
                        type="hidden"
                        name="tipo_servicio"
                        value="<?= h($orderForm['tipo_servicio']) ?>"
                        data-pos-service-input
                    >

                    <div class="field" data-pos-table-field <?= $orderForm['tipo_servicio'] === 'MESA' ? '' : 'hidden' ?>>
                        <label class="sr-only" for="id_mesa">Seleccionar mesa</label>
                        <select id="id_mesa" name="id_mesa">
                            <option value="">Seleccionar mesa...</option>
                            <?php foreach ($tables as $table): ?>
                                <option
                                    value="<?= h((string) $table['id']) ?>"
                                    <?= $orderForm['id_mesa'] === (string) $table['id'] ? 'selected' : '' ?>
                                >
                                    Mesa <?= h($table['numero']) ?> - <?= h($table['zona'] ?: 'Sin zona') ?> - <?= h((string) $table['capacidad']) ?> pax
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <details class="pos-advanced"<?= $advancedOpen ? ' open' : '' ?>>
                        <summary>
                            <span class="pos-advanced__label">
                                <span class="icon"><?= $icon('sliders') ?></span>
                                <span>Opciones avanzadas</span>
                            </span>
                            <span class="pos-advanced__chevron icon"><?= $icon('chevron') ?></span>
                        </summary>
                        <div class="pos-advanced__body">
                            <div class="field">
                                <label for="id_cliente">Cliente</label>
                                <select id="id_cliente" name="id_cliente">
                                    <option value="">Mostrador / sin cliente</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option
                                            value="<?= h((string) $client['id']) ?>"
                                            <?= $orderForm['id_cliente'] === (string) $client['id'] ? 'selected' : '' ?>
                                        >
                                            <?= h($client['nombre_razon_social']) ?> - <?= h($client['numero_documento']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="cantidad_personas">Personas</label>
                                <input
                                    id="cantidad_personas"
                                    name="cantidad_personas"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value="<?= h($orderForm['cantidad_personas']) ?>"
                                >
                            </div>

                            <div class="field field--full">
                                <label for="observaciones">Observaciones</label>
                                <textarea
                                    id="observaciones"
                                    name="observaciones"
                                    rows="3"
                                    placeholder="Indicaciones de cocina, delivery o comentarios internos."
                                ><?= h($orderForm['observaciones']) ?></textarea>
                            </div>
                        </div>
                    </details>

                    <div class="pos-cart__items" data-pos-cart-list>
                        <div class="pos-cart__empty">
                            <span class="pos-cart__empty-icon icon icon--accent-soft"><?= $icon('basket') ?></span>
                            <strong>La orden esta vacia</strong>
                            <span>Haz clic en un plato para empezar a armar el pedido.</span>
                        </div>
                    </div>

                    <div class="pos-cart__summary">
                        <div class="pos-cart__summary-row">
                            <span>Subtotal</span>
                            <strong data-pos-subtotal><?= h(money(0)) ?></strong>
                        </div>
                        <div class="pos-cart__summary-row">
                            <span>IGV (18%)</span>
                            <strong data-pos-tax><?= h(money(0)) ?></strong>
                        </div>
                        <div class="pos-cart__summary-row pos-cart__summary-row--total">
                            <span>Total</span>
                            <strong data-pos-total><?= h(money(0)) ?></strong>
                        </div>
                    </div>

                    <button class="button button--primary button--full" type="submit" data-pos-submit>
                        <span class="icon"><?= $icon('plus') ?></span>
                        <span>Crear orden</span>
                    </button>
                </form>
            </aside>
        </div>
        </section>
    <?php endif; ?>

    <?php if ($showSelectedEditor && $selectedOrder): ?>
        <?php
        $selectedServiceType = strtoupper((string) ($selectedOrder['tipo_servicio'] ?? 'MESA'));
        $selectedState = (string) ($selectedOrder['estado'] ?? '');
        $selectedTax = $selectedOrderTaxableBase > 0 ? $selectedOrderTaxableBase - ($selectedOrderTaxableBase / 1.18) : 0.0;
        $selectedDisplayCode = $formatOrderCode((string) ($selectedOrder['codigo'] ?? ''));
        $selectedStatusLabel = (string) ($selectedOrder['status_label'] ?? ucfirst(strtolower(str_replace('_', ' ', $selectedState))));
        $selectedCreatedAt = (string) ($selectedOrder['fecha_creacion'] ?? '');
        $readyDisabled = !$canManageOrder
            || in_array($selectedState, ['LISTA', 'SERVIDA', 'COMPLETADA', 'PAGADA', 'ANULADA'], true)
            || empty($selectedOrder['items']);
        $payDisabled = !$canRegisterPayment
            || !$currentTurn
            || in_array($selectedState, ['PAGADA', 'ANULADA'], true);
        ?>
        <section class="panel pos-builder pos-builder--selected-order is-open" id="selected-order-editor" data-selected-order-editor>
            <form method="post" class="visually-hidden" data-selected-order-add-form>
                <?= $csrf->input() ?>
                <input type="hidden" name="action" value="add_selected_item">
                <input type="hidden" name="id_orden" value="<?= h((string) $selectedOrder['id']) ?>">
                <input type="hidden" name="id_producto" value="" data-selected-order-product-input>
            </form>

            <div class="pos-builder__grid">
                <div class="pos-builder__catalog">
                    <div class="pos-builder__catalog-head">
                        <div>
                            <span class="eyebrow">Editar orden</span>
                            <h3><?= h($selectedOrder['codigo']) ?></h3>
                            <p class="muted-text">Selecciona platos para agregar mas items sin salir de esta pantalla.</p>
                        </div>
                        <a class="button button--ghost button--small" href="<?= h(url('orders')) ?>">
                            <span>Volver</span>
                        </a>
                    </div>

                    <div class="pos-builder__toolbar">
                        <div class="field field--full">
                            <label class="sr-only" for="selected-order-search">Buscar plato</label>
                            <div class="pos-search">
                                <span class="pos-search__icon icon"><?= $icon('search') ?></span>
                                <input
                                    id="selected-order-search"
                                    type="search"
                                    placeholder="Buscar productos..."
                                    autocomplete="off"
                                    data-selected-order-search
                                >
                            </div>
                        </div>

                        <div class="pos-builder__categories" role="tablist" aria-label="Categorias disponibles">
                            <button class="pos-chip is-active" type="button" data-selected-order-category="" aria-pressed="true">
                                Todos
                            </button>
                            <?php foreach ($productCategories as $categoryItem): ?>
                                <button
                                    class="pos-chip"
                                    type="button"
                                    data-selected-order-category="<?= h((string) $categoryItem['id']) ?>"
                                    aria-pressed="false"
                                >
                                    <?= h($categoryItem['nombre']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pos-product-grid" data-selected-order-product-grid>
                        <?php foreach ($products as $product): ?>
                            <?php $selectedQuantity = (int) ($selectedOrderItemCounts[(int) $product['id']] ?? 0); ?>
                            <button
                                type="button"
                                class="product-card"
                                data-selected-order-product
                                data-product-id="<?= h((string) $product['id']) ?>"
                                data-product-name="<?= h($product['nombre']) ?>"
                                data-product-category-id="<?= h((string) $product['id_categoria']) ?>"
                                data-product-category="<?= h($product['categoria']) ?>"
                                data-product-area="<?= h($product['area_preparacion'] ?? '') ?>"
                                data-selected-qty="<?= $selectedQuantity > 0 ? h((string) $selectedQuantity) : '' ?>"
                            >
                                <span class="product-card__icon icon icon--dish"><?= $icon('dish') ?></span>
                                <span class="product-card__body">
                                    <span class="product-card__category"><?= h($product['categoria']) ?></span>
                                    <strong class="product-card__name"><?= h($product['nombre']) ?></strong>
                                    <span class="product-card__price"><?= h(money($product['precio_venta'])) ?></span>
                                    <span class="product-card__meta"><?= h($product['area_preparacion'] ?: 'Sin area') ?></span>
                                    <span class="product-card__meta product-card__meta--orders">
                                        <?= h((string) ((int) round((float) $product['total_vendido']))) ?> pedidos
                                    </span>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <aside class="pos-cart pos-cart--selected-order">
                    <div class="pos-cart__header">
                        <div class="pos-cart__title">
                            <span class="pos-cart__title-icon icon icon--accent-soft"><?= $icon('basket') ?></span>
                            <div>
                                <h3>Orden actual</h3>
                                <p class="muted-text">Agrega o quita platos desde este panel operativo.</p>
                            </div>
                        </div>
                        <span class="pos-cart__count"><?= h((string) ((int) $selectedOrder['item_count'])) ?> item(s)</span>
                    </div>

                    <div class="pos-service-switch pos-service-switch--readonly">
                        <?php foreach ($serviceLabels as $serviceValue => $serviceLabel): ?>
                            <button
                                type="button"
                                class="pos-service-switch__button<?= $selectedServiceType === $serviceValue ? ' is-active' : '' ?>"
                                disabled
                            >
                                <span class="icon"><?= $icon($serviceIcons[$serviceValue]) ?></span>
                                <span><?= h($serviceLabel) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="field">
                        <label class="sr-only" for="selected-order-reference">Referencia</label>
                        <input
                            id="selected-order-reference"
                            type="text"
                            value="<?= h($selectedOrder['service_reference']) ?>"
                            readonly
                        >
                    </div>

                    <details class="pos-advanced"<?= $selectedEditorAdvancedOpen ? ' open' : '' ?>>
                        <summary>
                            <span class="pos-advanced__label">
                                <span class="icon"><?= $icon('sliders') ?></span>
                                <span>Opciones avanzadas</span>
                            </span>
                            <span class="pos-advanced__chevron icon"><?= $icon('chevron') ?></span>
                        </summary>
                        <div class="pos-advanced__body">
                            <div class="field">
                                <label>Cliente</label>
                                <input type="text" value="<?= h($selectedOrder['cliente'] ?: 'Mostrador / sin cliente') ?>" readonly>
                            </div>

                            <div class="field">
                                <label>Personas</label>
                                <input type="text" value="<?= h((string) max(1, (int) ($selectedOrder['cantidad_personas'] ?? 1))) ?>" readonly>
                            </div>

                            <div class="field field--full">
                                <label>Observaciones</label>
                                <textarea rows="3" readonly><?= h($selectedOrder['observaciones'] ?: 'Sin observaciones registradas.') ?></textarea>
                            </div>
                        </div>
                    </details>

                    <div class="pos-cart__items">
                        <?php if (empty($selectedOrder['items'])): ?>
                            <div class="pos-cart__empty">
                                <span class="pos-cart__empty-icon icon icon--accent-soft"><?= $icon('basket') ?></span>
                                <strong>La orden esta vacia</strong>
                                <span>Selecciona un plato para agregarlo a esta orden.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($selectedOrder['items'] as $item): ?>
                                <article class="pos-cart-item pos-cart-item--server">
                                    <div class="pos-cart-item__main">
                                        <div class="pos-cart-item__title">
                                            <span class="pos-cart-item__icon icon icon--dish"><?= $icon('dish') ?></span>
                                            <span class="pos-cart-item__badge"><?= h((string) ((int) round((float) $item['cantidad']))) ?>x</span>
                                            <div>
                                                <strong><?= h($item['nombre_producto']) ?></strong>
                                                <span><?= h($item['categoria'] ?? 'Carta') ?></span>
                                            </div>
                                        </div>
                                        <div class="pos-cart-item__side">
                                            <strong class="pos-cart-item__price"><?= h(money($item['subtotal'])) ?></strong>
                                            <?php if ($canManageOrder && !in_array($selectedState, ['PAGADA', 'ANULADA'], true)): ?>
                                                <form method="post" data-confirm="Se eliminara este plato de la orden. Deseas continuar?" class="pos-cart-item__remove-form">
                                                    <?= $csrf->input() ?>
                                                    <input type="hidden" name="action" value="remove_selected_item">
                                                    <input type="hidden" name="id_orden" value="<?= h((string) $selectedOrder['id']) ?>">
                                                    <input type="hidden" name="id_orden_detalle" value="<?= h((string) $item['id']) ?>">
                                                    <button class="pos-cart-item__remove pos-cart-item__remove--compact" type="submit" aria-label="Eliminar <?= h($item['nombre_producto']) ?>">
                                                        <?= $icon('trash') ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="pos-cart-item__controls">
                                        <div class="pos-cart-item__meta">
                                            <span><?= h($item['area_preparacion'] ?? 'Sin area') ?></span>
                                            <span class="<?= h(badge_class((string) ($item['estado_item'] ?? 'PENDIENTE'))) ?>">
                                                <?= h((string) ($item['estado_item'] ?? 'PENDIENTE')) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if (!empty($item['observacion'])): ?>
                                        <p class="pos-cart-item__note"><?= h($item['observacion']) ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="pos-cart__summary">
                        <div class="pos-cart__summary-row">
                            <span>Subtotal</span>
                            <strong><?= h(money($selectedOrderSubtotal)) ?></strong>
                        </div>
                        <div class="pos-cart__summary-row">
                            <span>IGV (18%)</span>
                            <strong><?= h(money($selectedTax)) ?></strong>
                        </div>
                        <div class="pos-cart__summary-row pos-cart__summary-row--total">
                            <span>Total</span>
                            <strong><?= h(money($selectedOrder['total'])) ?></strong>
                        </div>
                    </div>

                    <div class="selected-order-editor__actions">
                        <button
                            class="button button--primary button--full"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#quickPayModal"
                            <?= $payDisabled ? 'disabled' : '' ?>
                        >
                            <span>Pagar completo <?= h(money($selectedOrder['total'])) ?></span>
                        </button>

                        <form method="post" data-confirm="La orden quedara marcada como lista. Deseas continuar?">
                            <?= $csrf->input() ?>
                            <input type="hidden" name="action" value="mark_order_ready">
                            <input type="hidden" name="id_orden" value="<?= h((string) $selectedOrder['id']) ?>">
                            <button class="button button--ghost button--full" type="submit" <?= $readyDisabled ? 'disabled' : '' ?>>
                                <span>Todo listo</span>
                            </button>
                        </form>

                        <button class="button button--ghost button--full selected-order-editor__print" type="button" data-print-selected-order>
                            <span class="icon"><?= $icon('print') ?></span>
                            <span>Imprimir</span>
                        </button>
                    </div>
                </aside>
            </div>
        </section>

        <div class="order-print-sheet" data-print-selected-order-sheet aria-hidden="true">
            <article class="order-print-card">
                <div class="order-print-card__bar"></div>
                <header class="order-print-card__header">
                    <strong class="order-print-card__code"><?= h($selectedDisplayCode) ?></strong>
                    <span class="order-print-card__badge"><?= h($selectedStatusLabel) ?></span>
                </header>
                <div class="order-print-card__amount-row">
                    <strong class="order-print-card__amount"><?= h(money($selectedOrder['total'])) ?></strong>
                    <span class="order-print-card__time"><?= h($selectedCreatedAt !== '' ? format_datetime($selectedCreatedAt, 'h:i a') : '') ?></span>
                </div>
                <div class="order-print-card__meta-row">
                    <span><?= h($selectedOrder['service_reference']) ?></span>
                    <span class="order-print-card__badge order-print-card__badge--warning"><?= h($selectedOrder['payment_label'] ?? 'Pendiente') ?></span>
                </div>
                <section class="order-print-card__items">
                    <span class="order-print-card__section-title">Platos</span>
                    <?php foreach (($selectedOrder['items'] ?? []) as $printItem): ?>
                        <div class="order-print-card__item">
                            <span><?= h((string) ((int) round((float) ($printItem['cantidad'] ?? 0)))) ?>x <?= h($printItem['nombre_producto']) ?></span>
                            <strong><?= h(money($printItem['subtotal'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </section>
                <footer class="order-print-card__footer">
                    <span><?= h((string) ((int) $selectedOrder['item_count'])) ?> item(s)</span>
                    <span><?= h($selectedOrder['creador'] ?: 'Sin usuario') ?></span>
                    <span><?= h($selectedCreatedAt !== '' ? format_datetime($selectedCreatedAt, 'd/m/Y') : '') ?></span>
                </footer>
            </article>
        </div>

        <div class="modal fade payment-method-modal" id="quickPayModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered payment-method-modal__dialog">
                <div class="modal-content payment-method-modal__content">
                    <div class="payment-method-modal__hero">
                        <button type="button" class="payment-method-modal__close btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        <span class="payment-method-modal__eyebrow"><?= h($selectedOrder['codigo']) ?></span>
                        <span class="payment-method-modal__label">Total a cobrar</span>
                        <strong class="payment-method-modal__amount"><?= h(money($selectedOrder['total'])) ?></strong>
                    </div>

                    <div class="payment-method-modal__body">
                        <form method="post" class="payment-method-modal__form" data-payment-flow-form>
                            <?= $csrf->input() ?>
                            <input type="hidden" name="action" value="quick_pay_order">
                            <input type="hidden" name="id_orden" value="<?= h((string) $selectedOrder['id']) ?>">
                            <input type="hidden" name="payment_method_key" value="" data-payment-flow-method>

                            <div class="payment-method-step" data-payment-step="methods">
                                <div class="payment-method-grid">
                                    <?php foreach ($paymentModalButtons as $paymentButton): ?>
                                        <?php
                                        $paymentButtonConfig = $paymentMethodCatalog[$paymentButton['key']] ?? null;
                                        $isAvailable = is_array($paymentButtonConfig) && (int) ($paymentButtonConfig['id'] ?? 0) > 0;
                                        ?>
                                        <button
                                            class="payment-method-chip<?= $isAvailable ? ' is-available' : ' is-unconfigured' ?>"
                                            type="button"
                                            data-payment-option
                                            data-payment-key="<?= h($paymentButton['key']) ?>"
                                            data-payment-label="<?= h($paymentButton['label']) ?>"
                                            title="<?= $isAvailable ? 'Continuar con ' . $paymentButton['label'] : $paymentButton['label'] . ' no esta configurado' ?>"
                                            <?= $isAvailable ? '' : 'disabled' ?>
                                        >
                                            <span class="icon"><?= $icon($paymentButton['icon']) ?></span>
                                            <span><?= h($paymentButton['label']) ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="payment-method-modal__footer">
                                    <span>Total de consumo</span>
                                    <strong><?= h(money($selectedOrder['total'])) ?></strong>
                                </div>
                            </div>

                            <div class="payment-detail-panel" data-payment-step="detail" hidden>
                                <div class="payment-detail-header">
                                    <button class="payment-detail-header__back" type="button" data-payment-back aria-label="Volver a metodos">
                                        <span class="icon"><?= $icon('arrow-left') ?></span>
                                    </button>
                                    <div class="payment-detail-header__title">
                                        <strong data-payment-selected-label>Metodo</strong>
                                        <span><?= h($selectedDisplayCode) ?></span>
                                    </div>
                                    <strong class="payment-detail-header__amount"><?= h(money($selectedOrder['total'])) ?></strong>
                                </div>

                                <section class="payment-evidence-box">
                                    <span class="payment-detail-label">Evidencia de pago <small>(Opcional)</small></span>
                                    <div class="payment-evidence-actions">
                                        <button type="button" class="payment-evidence-button">
                                            <span class="icon"><?= $icon('camera') ?></span>
                                            <span>Foto</span>
                                        </button>
                                        <button type="button" class="payment-evidence-button">
                                            <span class="icon"><?= $icon('image') ?></span>
                                            <span>Galeria</span>
                                        </button>
                                    </div>
                                </section>

                                <section class="payment-receipt-box">
                                    <span class="payment-receipt-question">Emitir comprobante?</span>
                                    <div class="payment-receipt-grid">
                                        <label class="payment-receipt-card">
                                            <input type="radio" name="receipt_type" value="Nota de Venta" data-payment-receipt-option>
                                            <span class="icon"><?= $icon('receipt') ?></span>
                                            <strong>Nota de Venta</strong>
                                        </label>
                                        <label class="payment-receipt-card">
                                            <input type="radio" name="receipt_type" value="Boleta" data-payment-receipt-option checked>
                                            <span class="icon"><?= $icon('receipt') ?></span>
                                            <strong>Boleta</strong>
                                        </label>
                                        <label class="payment-receipt-card">
                                            <input type="radio" name="receipt_type" value="Factura" data-payment-receipt-option>
                                            <span class="icon"><?= $icon('receipt') ?></span>
                                            <strong>Factura</strong>
                                        </label>
                                    </div>

                                    <div class="payment-receipt-note" data-payment-receipt-note>
                                        Boleta Simple (&lt; S/ 700) - Datos opcionales
                                    </div>

                                    <div class="payment-customer-fields">
                                        <div class="field field--full">
                                            <label for="payment_document_type">Tipo de documento</label>
                                            <select id="payment_document_type" name="document_type">
                                                <option value="DNI">DNI</option>
                                                <option value="RUC">RUC</option>
                                                <option value="CE">Carnet de extranjeria</option>
                                            </select>
                                        </div>
                                        <div class="field field--full payment-document-row">
                                            <label for="payment_document_number">N de documento</label>
                                            <div class="payment-document-input">
                                                <input id="payment_document_number" name="document_number" type="text" inputmode="numeric" placeholder="12345678">
                                                <button type="button" aria-label="Buscar documento">
                                                    <span class="icon"><?= $icon('search') ?></span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="field field--full">
                                            <label for="payment_customer_name">Nombre</label>
                                            <input id="payment_customer_name" name="customer_name" type="text" placeholder="Opcional">
                                        </div>
                                    </div>
                                </section>

                                <button class="button button--primary button--full payment-confirm-button" type="submit">
                                    <span class="icon"><?= $icon('receipt') ?></span>
                                    <span>Cobrar y Emitir <span data-payment-receipt-label>Boleta</span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$showCreateBuilder && !$showSelectedEditor): ?>
        <section class="panel" id="orders-list">
            <div class="panel__header">
                <div>
                    <span class="eyebrow"><?= $isCashier ? 'Cobro' : 'Pedidos' ?></span>
                    <h3><?= h($isCashier ? 'Ordenes pendientes de pago' : 'Ordenes pendientes y en preparacion') ?></h3>
                    <p class="muted-text">
                        <?= h($isCashier ? 'Abre una orden para registrar el pago desde caja.' : 'Consulta las ordenes activas pendientes de atencion o cobro.') ?>
                    </p>
                </div>
            </div>

            <?php if (!$tableOrders): ?>
                <div class="empty-inline">
                    <p><?= h($isCashier ? 'No hay ordenes pendientes de pago.' : 'No hay ordenes pendientes o en preparacion.') ?></p>
                </div>
            <?php else: ?>
                <div class="order-cards order-cards--pending">
                    <?php foreach ($tableOrders as $order): ?>
                        <article
                            class="order-card<?= !empty($order['is_selected']) ? ' is-selected' : '' ?>"
                            data-order-select-url="<?= h(url('orders', ['selected' => $order['id']])) ?>"
                            tabindex="0"
                            role="button"
                            aria-label="Ver detalle de <?= h($formatOrderCode((string) $order['codigo'])) ?>"
                        >
                            <div class="order-card__header">
                                <div class="order-card__identity">
                                    <a class="order-card__code" href="<?= h(url('orders', ['selected' => $order['id']])) ?>">
                                        <?= h($formatOrderCode((string) $order['codigo'])) ?>
                                    </a>
                                </div>
                                <span class="<?= h($order['status_badge_class']) ?>"><?= h($order['status_label']) ?></span>
                            </div>

                            <div class="order-card__amount-row">
                                <strong class="order-card__amount"><?= h(money($order['total'])) ?></strong>
                                <span class="order-card__time">
                                    <span class="icon icon--tiny"><?= $icon('clock') ?></span>
                                    <span><?= h(format_datetime($order['fecha_creacion'], 'h:i a')) ?></span>
                                </span>
                            </div>

                            <div class="order-card__meta-row">
                                <span class="order-card__mesa">
                                    <span class="icon icon--tiny"><?= $icon($order['service_icon']) ?></span>
                                    <span><?= h($order['service_reference']) ?></span>
                                </span>
                                <span class="<?= h($order['payment_badge_class']) ?>"><?= h($order['payment_label']) ?></span>
                            </div>

                            <div class="order-card__detail">
                                <span class="order-card__detail-label">PLATOS</span>

                                <?php if (empty($order['items'])): ?>
                                    <div class="muted-text">Sin items registrados.</div>
                                <?php else: ?>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="order-card__item-row">
                                            <span>
                                                <?= h((string) ((int) round((float) $item['cantidad']))) ?>x
                                                <?= h($item['nombre_producto']) ?>
                                            </span>
                                            <span><?= h(money($item['subtotal'])) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="order-card__footer">
                                <span><?= h((string) ((int) $order['item_count'])) ?> item(s)</span>
                                <span><?= h($order['creador'] ?: 'Sin usuario') ?></span>
                                <span><?= h(format_datetime($order['fecha_creacion'], 'd/m/Y')) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
