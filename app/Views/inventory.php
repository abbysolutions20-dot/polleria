<section class="section-shell">
    <article class="panel section-hero">
        <div class="section-hero__header">
            <div class="section-hero__copy">
                <span class="eyebrow">Inventario</span>
                <h3>Supervisa alertas, stock critico y movimientos recientes con una vista mas institucional.</h3>
                <p>Consulta rapidamente la situacion del inventario sin cambiar la logica actual del modulo ni sus consultas.</p>
            </div>

            <div class="section-hero__actions">
                <a class="button button--ghost" href="<?= h(url('products')) ?>">
                    <i class="bi bi-journal-text"></i>
                    <span>Ver carta</span>
                </a>
                <a class="button button--primary" href="<?= h(url('inventory')) ?>">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Actualizar</span>
                </a>
            </div>
        </div>
    </article>

    <section class="stat-grid stat-grid--compact">
        <article class="stat-card dashboard-metric dashboard-metric--primary">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Ingredientes</span>
                <span class="dashboard-metric__icon"><i class="bi bi-box-seam"></i></span>
            </div>
            <strong><?= h((string) $totalIngredients) ?></strong>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--success">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Articulos</span>
                <span class="dashboard-metric__icon"><i class="bi bi-archive"></i></span>
            </div>
            <strong><?= h((string) $totalArticles) ?></strong>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--warning">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Stock critico</span>
                <span class="dashboard-metric__icon"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
            <strong><?= h((string) $lowStockCount) ?></strong>
        </article>
        <article class="stat-card dashboard-metric dashboard-metric--neutral">
            <div class="dashboard-metric__head">
                <span class="stat-card__label">Valor valorizado</span>
                <span class="dashboard-metric__icon"><i class="bi bi-graph-up"></i></span>
            </div>
            <strong><?= h(money($inventoryValue)) ?></strong>
        </article>
    </section>

    <section class="grid grid--two">
        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Alertas</span>
                    <h3>Items por debajo del minimo</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="7">
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
                            <tr><td colspan="4" class="muted-cell">No hay alertas activas.</td></tr>
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

        <article class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Kardex</span>
                    <h3>Movimientos recientes</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table" data-table-paginate="7">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Item</th>
                            <th>Movimiento</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$kardex): ?>
                            <tr><td colspan="4" class="muted-cell">No hay movimientos recientes para mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($kardex as $row): ?>
                            <tr>
                                <td><?= h(format_datetime($row['fecha'])) ?></td>
                                <td><?= h($row['item_nombre'] ?: 'Item eliminado') ?></td>
                                <td><?= h($row['tipo_movimiento']) ?><div class="muted-text"><?= h($row['origen']) ?></div></td>
                                <td><?= h(number_format((float) $row['cantidad'], 3)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</section>
