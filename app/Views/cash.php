<?php
$salesCount = (int) ($salesSummary['total_sales_count'] ?? 0);
$salesAmount = (float) ($salesSummary['total_sales_amount'] ?? 0);
$paymentBase = $salesAmount > 0 ? $salesAmount : 1.0;
$pendingOrdersToPay = (int) ($pendingOrdersToPay ?? 0);
?>

<section class="section-shell cash-shell">
    <article class="panel section-hero cash-unified-hero">
        <div class="cash-unified-hero__header">
            <div class="cash-unified-hero__copy">
                <span class="cash-unified-hero__app"><?= h(app_config('app.name')) ?></span>
                <div class="cash-unified-hero__title-row">
                    <span class="eyebrow">Caja</span>
                    <h3>Control de caja y ventas</h3>
                    <span class="cash-unified-hero__status <?= $currentTurn ? 'cash-unified-hero__status--open' : 'cash-unified-hero__status--closed' ?>">
                        <?= $currentTurn ? 'Caja abierta' : 'Sin caja abierta' ?>
                    </span>
                </div>
                <p>Controla el turno, flujo de efectivo, egresos y ventas desde una sola vista.</p>
            </div>

            <div class="cash-unified-hero__meta">
                <span class="topbar__meta-item">
                    <i class="bi bi-calendar3"></i>
                    <span><?= h(date('d/m/Y')) ?></span>
                </span>
                <span class="topbar__meta-item">
                    <i class="bi bi-clock"></i>
                    <span><?= h(date('H:i')) ?></span>
                </span>
            </div>

            <div class="section-hero__actions cash-unified-hero__actions">
                <a class="button button--ghost" href="<?= h(url('orders')) ?>">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>Ver ordenes</span>
                </a>
                <a class="button button--primary" href="<?= h(url('cash')) ?>">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Actualizar</span>
                </a>
            </div>
        </div>
    </article>

    <section class="stat-grid">
        <article class="stat-card dashboard-metric dashboard-metric--primary">
            <div class="dashboard-metric__head">
                <span class="stat-card__label"><?= h($reportLabel) ?></span>
                <span class="dashboard-metric__icon"><i class="bi bi-safe2"></i></span>
            </div>
            <strong><?= $reportTurn ? '#' . h((string) $reportTurn['id']) : 'Sin turno' ?></strong>
            <p>
                <?= $reportTurn
                    ? h(format_datetime($reportStart)) . ' - ' . h(format_datetime($reportEnd))
                    : 'Sin movimientos registrados todavia.' ?>
            </p>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--success">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Ventas Totales</span>
                <span class="dashboard-metric__icon"><i class="bi bi-cash-stack"></i></span>
            </div>
            <strong><?= h(money($salesAmount)) ?></strong>
            <p><?= h((string) $salesCount) ?> ordenes cobradas en el periodo.</p>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--warning">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Ultima Venta</span>
                <span class="dashboard-metric__icon"><i class="bi bi-clock-history"></i></span>
            </div>
            <strong><?= h($latestSale ? money($latestSale['monto']) : 'Sin ventas') ?></strong>
            <p>
                <?= $latestSale
                    ? h($latestSale['codigo']) . ' - ' . h($latestSale['metodo_pago']) . ' - ' . h(format_datetime($latestSale['fecha_pago']))
                    : 'Aun no se registra una venta en el periodo.' ?>
            </p>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--neutral">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Efectivo Esperado</span>
                <span class="dashboard-metric__icon"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
            <strong><?= h(money($cashFlow['expected_cash'] ?? 0)) ?></strong>
            <p>Inicial + ingresos - egresos.</p>
        </article>
    </section>

    <section class="grid grid--two">
        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow"><?= $currentTurn ? 'Caja activa' : 'Inicio de turno' ?></span>
                    <h3><?= $currentTurn ? 'Resumen del turno abierto' : 'Abrir caja' ?></h3>
                </div>
            </div>

            <?php if ($currentTurn): ?>
                <div class="info-stack">
                    <div class="info-row"><span>Apertura</span><strong><?= h(format_datetime($currentTurn['fecha_apertura'])) ?></strong></div>
                    <div class="info-row"><span>Responsable</span><strong><?= h(trim($currentTurn['apertura_nombres'] . ' ' . ($currentTurn['apertura_apellidos'] ?? ''))) ?></strong></div>
                    <div class="info-row"><span>Monto inicial</span><strong><?= h(money($currentTurn['monto_apertura'])) ?></strong></div>
                    <div class="info-row"><span>Observaciones</span><strong><?= h($currentTurn['observaciones'] ?: 'Sin observaciones') ?></strong></div>
                </div>

                <form method="post" class="form-grid compact-form" data-confirm="Se cerrara la caja actual.">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="close_turn">
                    <input type="hidden" name="id_turno" value="<?= h((string) $currentTurn['id']) ?>">

                    <div class="field field--full">
                        <label for="monto_cierre">Monto de cierre</label>
                        <input id="monto_cierre" name="monto_cierre" type="number" step="0.01" min="0" value="<?= h(number_format((float) ($cashFlow['expected_cash'] ?? 0), 2, '.', '')) ?>" required>
                    </div>

                    <?php if ($pendingOrdersToPay > 0): ?>
                        <div class="empty-inline empty-inline--warning field--full">
                            <p>No puedes cerrar caja porque hay <?= h((string) $pendingOrdersToPay) ?> orden(es) pendientes de pagar.</p>
                        </div>
                    <?php endif; ?>

                    <div class="actions field--full">
                        <button class="button button--danger" type="submit" <?= $pendingOrdersToPay > 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-lock"></i>
                            <span>Cerrar caja</span>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" class="form-grid">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="open_turn">

                    <div class="field field--full">
                        <label for="monto_apertura">Monto de apertura</label>
                        <div class="money-input">
                            <span class="money-input__prefix">S/</span>
                            <input id="monto_apertura" name="monto_apertura" type="number" step="0.01" min="0" value="0.00" required>
                        </div>
                    </div>

                    <div class="field field--full">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3" placeholder="Turno manana, caja principal, etc."></textarea>
                    </div>

                    <div class="actions field--full">
                        <button class="button button--primary" type="submit">
                            <i class="bi bi-unlock"></i>
                            <span>Abrir caja</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </article>

        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Flujo de efectivo</span>
                    <h3>Resumen de caja</h3>
                </div>
            </div>

            <div class="info-stack">
                <div class="info-row"><span>Efectivo inicial</span><strong><?= h(money($cashFlow['opening_cash'] ?? 0)) ?></strong></div>
                <div class="info-row"><span>Ingresos</span><strong><?= h(money($cashFlow['income_total'] ?? 0)) ?></strong></div>
                <div class="info-row"><span>Egresos</span><strong><?= h(money($cashFlow['expense_total'] ?? 0)) ?></strong></div>
                <div class="info-row"><span>Efectivo esperado</span><strong><?= h(money($cashFlow['expected_cash'] ?? 0)) ?></strong></div>
                <div class="info-row"><span>Saldo actual mostrado</span><strong><?= h(money($cashBalance)) ?></strong></div>
            </div>
        </article>
    </section>

    <section class="grid grid--two">
        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Egresos</span>
                    <h3>Registrar gasto</h3>
                </div>
            </div>

            <?php if ($currentTurn): ?>
                <form method="post" class="form-grid">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="add_expense">
                    <input type="hidden" name="id_turno" value="<?= h((string) $currentTurn['id']) ?>">

                    <div class="field">
                        <label for="id_categoria_gasto">Categoria</label>
                        <select id="id_categoria_gasto" name="id_categoria_gasto" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($expenseCategories as $category): ?>
                                <option value="<?= h((string) $category['id']) ?>"><?= h($category['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="monto">Monto</label>
                        <input id="monto" name="monto" type="number" step="0.01" min="0.01" required>
                    </div>

                    <div class="field field--full">
                        <label for="descripcion">Descripcion</label>
                        <input id="descripcion" name="descripcion" type="text" required placeholder="Movilidad, utiles, compra menor...">
                    </div>

                    <div class="actions field--full">
                        <button class="button button--primary" type="submit">
                            <i class="bi bi-wallet2"></i>
                            <span>Registrar gasto</span>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-inline">
                    <p>Primero abre caja para poder registrar egresos en el turno actual.</p>
                </div>
            <?php endif; ?>

            <div class="table-wrap table-wrap--tight">
                <table class="table" data-table-paginate="6">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Categoria</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$expenses): ?>
                            <tr><td colspan="3" class="muted-cell">No hay gastos en el periodo.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?= h(format_datetime($expense['fecha'])) ?></td>
                                <td>
                                    <?= h($expense['categoria']) ?>
                                    <div class="muted-text"><?= h($expense['descripcion']) ?></div>
                                </td>
                                <td><?= h(money($expense['monto'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Metodo de pago</span>
                    <h3>Distribucion por tipo</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="6">
                    <thead>
                        <tr>
                            <th>Metodo</th>
                            <th>Operaciones</th>
                            <th>Monto</th>
                            <th>Participacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$paymentDistribution): ?>
                            <tr><td colspan="4" class="muted-cell">No hay pagos en el periodo.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($paymentDistribution as $paymentRow): ?>
                            <tr>
                                <td><?= h($paymentRow['nombre']) ?></td>
                                <td><?= h((string) ((int) $paymentRow['payment_count'])) ?></td>
                                <td><?= h(money($paymentRow['payment_amount'])) ?></td>
                                <td><?= h(number_format((((float) $paymentRow['payment_amount']) / $paymentBase) * 100, 1)) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="grid grid--two">
        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Ventas por hora</span>
                    <h3>Concentracion de ventas</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="6">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Ventas</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$hourlySales): ?>
                            <tr><td colspan="3" class="muted-cell">No hay ventas registradas por hora en el periodo.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($hourlySales as $hourRow): ?>
                            <tr>
                                <td><?= h($hourRow['hour_block']) ?></td>
                                <td><?= h((string) ((int) $hourRow['sales_count'])) ?></td>
                                <td><?= h(money($hourRow['sales_amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Movimientos</span>
                    <h3>Bitacora de caja</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="6">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Origen</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$movements): ?>
                            <tr><td colspan="4" class="muted-cell">Sin movimientos registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><?= h(format_datetime($movement['fecha'])) ?></td>
                                <td><span class="<?= h(badge_class($movement['tipo_movimiento'])) ?>"><?= h($movement['tipo_movimiento']) ?></span></td>
                                <td>
                                    <?= h($movement['origen']) ?>
                                    <div class="muted-text"><?= h($movement['descripcion']) ?></div>
                                </td>
                                <td><?= h(money($movement['monto'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Ordenes</span>
                <h3>Tabla completa del periodo</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" data-table-paginate="8">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Codigo</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                        <th>Metodo pago</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$ordersReport): ?>
                        <tr><td colspan="7" class="muted-cell">No hay ordenes en el periodo seleccionado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($ordersReport as $orderRow): ?>
                        <tr>
                            <td><?= h(format_datetime($orderRow['fecha_creacion'])) ?></td>
                            <td>
                                <?= h($orderRow['codigo']) ?>
                                <div class="muted-text"><?= h($orderRow['creador'] ?: 'Sin usuario') ?></div>
                            </td>
                            <td><?= h($orderRow['cliente'] ?: 'Mostrador') ?></td>
                            <td><?= h($orderRow['tipo_servicio']) ?></td>
                            <td><span class="<?= h(badge_class($orderRow['estado'])) ?>"><?= h($orderRow['estado']) ?></span></td>
                            <td>
                                <?= h($orderRow['metodo_pago']) ?>
                                <div class="muted-text"><?= h($orderRow['fecha_pago'] ? format_datetime($orderRow['fecha_pago']) : 'Sin cobro') ?></div>
                            </td>
                            <td><?= h(money($orderRow['total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Historial</span>
                <h3>Ultimos turnos</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" data-table-paginate="6">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Apertura</th>
                        <th>Estado</th>
                        <th>Montos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$closedTurns): ?>
                        <tr><td colspan="4" class="muted-cell">No hay turnos registrados todavia.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($closedTurns as $turn): ?>
                        <tr>
                            <td>#<?= h((string) $turn['id']) ?></td>
                            <td><?= h(format_datetime($turn['fecha_apertura'])) ?></td>
                            <td><span class="<?= h(badge_class($turn['estado'])) ?>"><?= h($turn['estado']) ?></span></td>
                            <td>
                                <div>Apertura: <?= h(money($turn['monto_apertura'])) ?></div>
                                <div class="muted-text">Cierre: <?= h(money($turn['monto_cierre'] ?? 0)) ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
