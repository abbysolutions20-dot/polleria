<section class="dashboard-shell">
    <article class="panel dashboard-hero">
        <div class="dashboard-hero__header">
            <div class="dashboard-hero__copy">
                <span class="eyebrow">Resumen ejecutivo</span>
                <h3>Supervisa la operacion diaria desde un solo panel.</h3>
                <p>
                    Consulta ventas del dia, estado de caja, ordenes activas y alertas de stock sin salir
                    del flujo principal del sistema.
                </p>
            </div>

            <div class="dashboard-hero__actions">
                <a class="button button--primary" href="<?= h(url('orders')) ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Ir a Ordenes</span>
                </a>
                <a class="button button--ghost" href="<?= h(url('cash')) ?>">
                    <i class="bi bi-cash-stack"></i>
                    <span>Gestionar caja</span>
                </a>
            </div>
        </div>

        <div class="dashboard-hero__stats">
            <div class="dashboard-hero__stat">
                <strong><?= h((string) $paidOrdersToday) ?></strong>
                <span>ordenes pagadas hoy</span>
            </div>
            <div class="dashboard-hero__stat">
                <strong><?= h((string) $openOrders) ?></strong>
                <span>ordenes activas</span>
            </div>
            <div class="dashboard-hero__stat">
                <strong><?= h((string) $lowStockCount) ?></strong>
                <span>alertas de stock</span>
            </div>
            <div class="dashboard-hero__stat">
                <strong><?= $currentTurn ? '#' . h((string) $currentTurn['id']) : '0' ?></strong>
                <span><?= $currentTurn ? 'turno abierto' : 'sin turno activo' ?></span>
            </div>
        </div>
    </article>

    <section class="stat-grid">
        <article class="stat-card dashboard-metric dashboard-metric--primary">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Ventas del dia</span>
                <span class="dashboard-metric__icon"><i class="bi bi-currency-dollar"></i></span>
            </div>
            <strong><?= h(money($salesToday)) ?></strong>
            <p><?= h((string) $paidOrdersToday) ?> ordenes pagadas hoy.</p>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--success">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Ordenes activas</span>
                <span class="dashboard-metric__icon"><i class="bi bi-receipt-cutoff"></i></span>
            </div>
            <strong><?= h((string) $openOrders) ?></strong>
            <p>Pedidos en curso entre salon, cocina y entrega.</p>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--warning">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Alertas de stock</span>
                <span class="dashboard-metric__icon"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
            <strong><?= h((string) $lowStockCount) ?></strong>
            <p>Ingredientes o articulos por debajo del minimo.</p>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--neutral">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Caja actual</span>
                <span class="dashboard-metric__icon"><i class="bi bi-safe2"></i></span>
            </div>
            <strong><?= h(money($cashBalance)) ?></strong>
            <p><?= $currentTurn ? 'Turno abierto #' . h((string) $currentTurn['id']) : 'No hay turno abierto ahora.' ?></p>
        </article>
    </section>

    <section class="grid grid--two">
        <article class="panel dashboard-card">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Operacion reciente</span>
                    <h3>Ultimas ordenes</h3>
                </div>
                <a class="button button--ghost" href="<?= h(url('orders')) ?>">Ver flujo</a>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="5">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Estado</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recentOrders): ?>
                            <tr><td colspan="5" class="muted-cell">Todavia no hay ordenes registradas.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentOrders as $row): ?>
                            <tr>
                                <td><a href="<?= h(url('orders', ['selected' => $row['id']])) ?>"><?= h($row['codigo']) ?></a></td>
                                <td><?= h($row['cliente'] ?: 'Mostrador') ?></td>
                                <td><?= h($row['tipo_servicio']) ?><?= $row['mesa'] ? ' - Mesa ' . h($row['mesa']) : '' ?></td>
                                <td><span class="<?= h(badge_class($row['estado'])) ?>"><?= h($row['estado']) ?></span></td>
                                <td><?= h(money($row['total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel dashboard-card">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Riesgo operativo</span>
                    <h3>Stock critico</h3>
                </div>
                <a class="button button--ghost" href="<?= h(url('inventory')) ?>">Ver inventario</a>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="5">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Item</th>
                            <th>Actual</th>
                            <th>Minimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$lowStockRows): ?>
                            <tr><td colspan="4" class="muted-cell">No hay alertas de stock por ahora.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($lowStockRows as $row): ?>
                            <tr>
                                <td><?= h($row['tipo']) ?></td>
                                <td><?= h($row['nombre']) ?></td>
                                <td><?= h(number_format((float) $row['stock_actual'], 3)) ?></td>
                                <td><?= h(number_format((float) $row['stock_minimo'], 3)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="grid grid--two">
        <article class="panel dashboard-card">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Caja</span>
                    <h3>Estado del turno</h3>
                </div>
                <a class="button button--ghost" href="<?= h(url('cash')) ?>">Gestionar caja</a>
            </div>

            <?php if ($currentTurn): ?>
                <div class="info-stack">
                    <div class="info-row"><span>Turno</span><strong>#<?= h((string) $currentTurn['id']) ?></strong></div>
                    <div class="info-row"><span>Apertura</span><strong><?= h(format_datetime($currentTurn['fecha_apertura'])) ?></strong></div>
                    <div class="info-row"><span>Usuario</span><strong><?= h(trim($currentTurn['apertura_nombres'] . ' ' . ($currentTurn['apertura_apellidos'] ?? ''))) ?></strong></div>
                    <div class="info-row"><span>Monto inicial</span><strong><?= h(money($currentTurn['monto_apertura'])) ?></strong></div>
                    <div class="info-row"><span>Saldo acumulado</span><strong><?= h(money($cashBalance)) ?></strong></div>
                </div>
            <?php else: ?>
                <div class="empty-inline">
                    <p>No hay un turno abierto. Desde la seccion Caja puedes abrir uno en segundos.</p>
                </div>
            <?php endif; ?>
        </article>

        <article class="panel dashboard-card">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Preferencias del cliente</span>
                    <h3>Productos mas vendidos</h3>
                </div>
                <a class="button button--ghost" href="<?= h(url('products')) ?>">Ver carta</a>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="5">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Venta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$topProducts): ?>
                            <tr><td colspan="3" class="muted-cell">Aun no hay ventas pagadas para analizar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($topProducts as $row): ?>
                            <tr>
                                <td><?= h($row['nombre']) ?></td>
                                <td><?= h((string) ((int) $row['cantidad_total'])) ?></td>
                                <td><?= h(money($row['venta_total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</section>
