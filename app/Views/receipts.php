<section class="section-shell">
    <article class="panel section-hero">
        <div class="section-hero__header">
            <div class="section-hero__copy">
                <span class="eyebrow">Comprobantes</span>
                <h3>Consulta facturacion y pagos validados en una vista mas clara y ejecutiva.</h3>
                <p>Revisa comprobantes emitidos, cobros recientes y el comportamiento del periodo sin alterar la logica del modulo.</p>
            </div>

            <div class="section-hero__actions">
                <a class="button button--ghost" href="<?= h(url('cash')) ?>">
                    <i class="bi bi-safe2"></i>
                    <span>Ir a caja</span>
                </a>
                <a class="button button--primary" href="<?= h(url('receipts')) ?>">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Actualizar</span>
                </a>
            </div>
        </div>
    </article>

    <section class="stat-grid stat-grid--compact">
        <article class="stat-card dashboard-metric dashboard-metric--primary">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Comprobantes hoy</span>
                <span class="dashboard-metric__icon"><i class="bi bi-receipt"></i></span>
            </div>
            <strong><?= h((string) $receiptCountToday) ?></strong>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--success">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Total comprobado</span>
                <span class="dashboard-metric__icon"><i class="bi bi-cash-coin"></i></span>
            </div>
            <strong><?= h(money($receiptTotalToday)) ?></strong>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--neutral">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Cobros validados</span>
                <span class="dashboard-metric__icon"><i class="bi bi-patch-check"></i></span>
            </div>
            <strong><?= h(money($paymentTotalToday)) ?></strong>
        </article>
    </section>

    <section class="grid grid--two">
        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Facturacion</span>
                    <h3>Ultimos comprobantes</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="7">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$receipts): ?>
                            <tr><td colspan="5" class="muted-cell">No hay comprobantes registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($receipts as $receipt): ?>
                            <tr>
                                <td><?= h($receipt['serie']) ?>-<?= h((string) $receipt['numero']) ?><div class="muted-text"><?= h($receipt['tipo']) ?></div></td>
                                <td><?= h($receipt['codigo_orden']) ?></td>
                                <td><?= h($receipt['cliente'] ?: 'Mostrador') ?></td>
                                <td><?= h(money($receipt['total'])) ?></td>
                                <td><span class="<?= h(badge_class($receipt['estado'])) ?>"><?= h($receipt['estado']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Cobros</span>
                    <h3>Pagos recientes</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="7">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Orden</th>
                            <th>Metodo</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$payments): ?>
                            <tr><td colspan="5" class="muted-cell">No hay pagos registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= h(format_datetime($payment['fecha_pago'])) ?></td>
                                <td><?= h($payment['codigo_orden']) ?></td>
                                <td><?= h($payment['metodo']) ?></td>
                                <td><?= h(money($payment['monto'])) ?></td>
                                <td><span class="<?= h(badge_class($payment['estado'])) ?>"><?= h($payment['estado']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</section>
