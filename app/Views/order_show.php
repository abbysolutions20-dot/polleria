<?php
$icons = [
    'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="M20 20l-4.35-4.35"></path></svg>',
    'dish' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3v7"></path><path d="M11 3v7"></path><path d="M8 7H5.5A2.5 2.5 0 0 1 3 4.5V3"></path><path d="M11 7h2.5A2.5 2.5 0 0 0 16 4.5V3"></path><path d="M9.5 10v11"></path><path d="M19 3c-1.7 2-2.5 4.2-2.5 6.7V21"></path></svg>',
    'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M9 7V5h6v2"></path><path d="M7 7l1 12h8l1-12"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>',
];
$icon = static function (string $name) use ($icons): string {
    return $icons[$name] ?? '';
};

$orderCode = $order['codigo'] ?? ('#' . (string) ($order['id'] ?? ''));
?>
<section class="section-shell order-detail-shell">
    <section class="grid grid--wide grid--order-detail">
    <?php if ($canManageOrder): ?>
        <article class="panel panel--stacked crud-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Productos</span>
                    <h3>Agregar platos</h3>
                    <div class="muted-text"><?= h($orderCode) ?></div>
                </div>
                <a class="button button--ghost button--small" href="<?= h(url('orders')) ?>">Volver</a>
            </div>

            <form method="post" class="form-grid compact-form order-item-picker" data-product-picker>
                <?= $csrf->input() ?>
                <input type="hidden" name="action" value="add_item">
                <input type="hidden" name="id_producto" value="" data-picker-product-input>
                <input type="hidden" name="cantidad" value="1" data-picker-quantity>
                <input type="hidden" name="observacion" value="" data-picker-note>

                <div class="order-item-picker__toolbar field--full">
                    <div class="field field--full">
                        <label class="sr-only" for="order-product-search">Buscar plato</label>
                        <div class="pos-search">
                            <span class="pos-search__icon icon"><?= $icon('search') ?></span>
                            <input
                                id="order-product-search"
                                type="search"
                                placeholder="Buscar productos..."
                                autocomplete="off"
                                data-picker-search
                            >
                        </div>
                    </div>

                    <div class="order-item-picker__categories pos-builder__categories field--full" role="tablist" aria-label="Categorias para agregar">
                        <button class="pos-chip is-active" type="button" data-picker-category="" aria-pressed="true">
                            Todos
                        </button>
                        <?php foreach ($productCategories as $category): ?>
                            <button
                                class="pos-chip"
                                type="button"
                                data-picker-category="<?= h((string) $category['id']) ?>"
                                aria-pressed="false"
                            >
                                <?= h($category['nombre']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pos-product-grid order-item-picker__grid field--full">
                    <?php foreach ($products as $product): ?>
                        <button
                            type="button"
                            class="product-card"
                            data-picker-product
                            data-product-id="<?= h((string) $product['id']) ?>"
                            data-product-name="<?= h($product['nombre']) ?>"
                            data-product-price="<?= h((string) $product['precio_venta']) ?>"
                            data-product-category-id="<?= h((string) $product['id_categoria']) ?>"
                            data-product-category="<?= h($product['categoria']) ?>"
                            data-product-area="<?= h($product['area_preparacion'] ?? '') ?>"
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
                    <div class="empty-inline field--full">
                        <p>No hay platos activos disponibles para agregar a esta orden.</p>
                    </div>
                <?php endif; ?>
            </form>
        </article>
    <?php endif; ?>

    <article class="panel panel--detail crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Items</span>
                <h3>Detalle del pedido</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" data-table-paginate="8">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Area</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                        <th>Estado</th>
                        <?php if ($canRemoveItems): ?>
                            <th>Accion</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$items): ?>
                        <tr><td colspan="<?= $canRemoveItems ? '7' : '6' ?>" class="muted-cell">La orden todavia no tiene items.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= h($item['nombre_producto']) ?></strong>
                                <div class="muted-text"><?= h($item['observacion']) ?></div>
                            </td>
                            <td><?= h($item['area'] ?: 'Sin area') ?></td>
                            <td><?= h((string) ((int) round((float) $item['cantidad']))) ?></td>
                            <td><?= h(money($item['precio_unitario'])) ?></td>
                            <td><?= h(money($item['subtotal'])) ?></td>
                            <td><span class="<?= h(badge_class($item['estado_item'])) ?>"><?= h($item['estado_item']) ?></span></td>
                            <?php if ($canRemoveItems): ?>
                                <td>
                                    <?php if ($item['estado_item'] !== 'ANULADO'): ?>
                                        <form method="post" class="table-action-form" data-confirm="Quitar este producto de la orden?">
                                            <?= $csrf->input() ?>
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="id_orden_detalle" value="<?= h((string) $item['id']) ?>">
                                            <button class="button button--danger button--small table-action-button" type="submit" title="Eliminar producto">
                                                <span class="icon icon--tiny"><?= $icon('trash') ?></span>
                                                <span>Eliminar</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted-text">Eliminado</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
</section>
