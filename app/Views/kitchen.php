<?php
$icons = [
    'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>',
    'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>',
    'spark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v4"></path><path d="M12 17v4"></path><path d="M4.9 4.9l2.8 2.8"></path><path d="M16.3 16.3l2.8 2.8"></path><path d="M3 12h4"></path><path d="M17 12h4"></path><path d="M4.9 19.1l2.8-2.8"></path><path d="M16.3 7.7l2.8-2.8"></path></svg>',
];
$icon = static function (string $name) use ($icons): string {
    return $icons[$name] ?? '';
};
?>
<section class="kitchen-shell">
    <header class="kitchen-topbar">
        <div class="kitchen-topbar__main">
            <div class="kitchen-topbar__title">
                <span class="kitchen-topbar__accent"></span>
                <div>
                    <span class="eyebrow">Produccion</span>
                    <h3>Cocina</h3>
                </div>
            </div>

            <div class="kitchen-topbar__meta">
                <span class="kitchen-meta-pill">Tickets <?= h((string) $ticketsCount) ?></span>
                <span class="kitchen-meta-pill"><?= h($currentTime) ?></span>
                <span class="kitchen-meta-pill kitchen-meta-pill--success">
                    <span class="kitchen-meta-pill__dot"></span>
                    <span>Conectado</span>
                </span>
            </div>
        </div>

        <div class="kitchen-topbar__summary">
            <span>Solo se muestran pedidos en preparacion.</span>
            <span><?= h((string) $readyItems) ?> / <?= h((string) $totalItems) ?> productos listos</span>
        </div>
    </header>

    <?php if (!$commandas): ?>
        <section class="panel">
            <div class="empty-inline">
                <p>No hay tickets en preparacion por el momento.</p>
            </div>
        </section>
    <?php else: ?>
        <section class="kitchen-board" aria-label="Tickets de cocina en preparacion">
            <?php foreach ($commandas as $comanda): ?>
                <article class="kitchen-ticket">
                    <header class="kitchen-ticket__header">
                        <div class="kitchen-ticket__identity">
                            <strong><?= h($comanda['codigo_orden']) ?></strong>
                            <span><?= h($comanda['service_reference']) ?></span>
                        </div>

                        <div class="kitchen-ticket__stats">
                            <span
                                class="kitchen-ticket__timer"
                                data-kitchen-timer
                                <?= !empty($comanda['started_at_iso']) ? 'data-started-at="' . h($comanda['started_at_iso']) . '"' : '' ?>
                            >
                                <span class="icon icon--tiny"><?= $icon('clock') ?></span>
                                <span data-kitchen-timer-label><?= h($comanda['elapsed_label']) ?></span>
                            </span>
                            <span><?= h($comanda['short_user']) ?></span>
                        </div>
                    </header>

                    <div class="kitchen-ticket__body">
                        <div class="kitchen-ticket__subhead">
                            <span><?= h(strtoupper($comanda['area'])) ?></span>
                            <span><?= h((string) $comanda['ready_count']) ?> / <?= h((string) $comanda['item_count']) ?> listos</span>
                        </div>

                        <div class="kitchen-ticket__items">
                            <?php foreach ($comanda['items'] as $item): ?>
                                <form class="kitchen-ticket__item-form" method="post">
                                    <?= $csrf->input() ?>
                                    <input type="hidden" name="action" value="toggle_item_ready">
                                    <input type="hidden" name="id_comanda" value="<?= h((string) $comanda['id']) ?>">
                                    <input type="hidden" name="id_comanda_detalle" value="<?= h((string) $item['id']) ?>">

                                    <button
                                        class="kitchen-ticket__item <?= $item['is_ready'] ? 'is-ready' : '' ?>"
                                        type="submit"
                                        title="<?= $item['is_ready'] ? 'Marcar como pendiente' : 'Marcar como listo' ?>"
                                    >
                                        <span class="kitchen-ticket__qty"><?= h($item['qty_label']) ?>x</span>

                                        <span class="kitchen-ticket__content">
                                            <strong><?= h($item['nombre_producto']) ?></strong>
                                            <span>
                                                <?= h($item['observacion'] ?: $item['status_label']) ?>
                                            </span>
                                        </span>

                                        <span class="kitchen-ticket__toggle" aria-hidden="true">
                                            <?= $item['is_ready'] ? $icon('check') : $icon('spark') ?>
                                        </span>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <footer class="kitchen-ticket__footer">
                        <div class="kitchen-ticket__footer-meta">
                            <span><?= h($comanda['created_label']) ?></span>
                            <span><?= h($comanda['numero_comanda']) ?></span>
                        </div>

                        <form method="post">
                            <?= $csrf->input() ?>
                            <input type="hidden" name="action" value="mark_ticket_ready">
                            <input type="hidden" name="id_comanda" value="<?= h((string) $comanda['id']) ?>">
                            <button
                                class="kitchen-ticket__ready-button"
                                type="submit"
                                <?= $comanda['all_items_ready'] ? '' : 'disabled' ?>
                            >
                                <span class="icon icon--tiny"><?= $icon('check') ?></span>
                                <span>LISTO</span>
                            </button>
                        </form>
                    </footer>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>
