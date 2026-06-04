<section class="section-shell">
    <article class="panel section-hero order-create-hero">
        <div class="section-hero__header">
            <div class="section-hero__copy">
                <span class="eyebrow">Ordenes</span>
                <h3>Nueva orden</h3>
                <p>Genera un nuevo pedido con una vista mas clara, mejor separacion de campos y una experiencia consistente con el resto del panel.</p>
            </div>

            <div class="section-hero__actions">
                <a class="button button--ghost" href="<?= h(url('orders')) ?>">Volver a Ordenes</a>
            </div>
        </div>
    </article>

    <section class="crud-layout order-create-layout">
        <article class="panel crud-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Captura</span>
                    <h3>Datos del pedido</h3>
                </div>
            </div>

            <form method="post" class="form-grid" data-order-form>
                <?= $csrf->input() ?>

                <div class="field">
                    <label for="tipo_servicio">Tipo de servicio</label>
                    <select id="tipo_servicio" name="tipo_servicio" data-service-selector>
                        <?php foreach (['MESA', 'LLEVAR', 'DELIVERY'] as $serviceType): ?>
                            <option value="<?= h($serviceType) ?>"><?= h($serviceType) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field" data-table-field>
                    <label for="id_mesa">Mesa</label>
                    <select id="id_mesa" name="id_mesa">
                        <option value="">Selecciona una mesa</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?= h((string) $table['id']) ?>">
                                Mesa <?= h($table['numero']) ?> - <?= h($table['zona'] ?: 'Sin zona') ?> - <?= h((string) $table['capacidad']) ?> pax
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="id_cliente">Cliente</label>
                    <select id="id_cliente" name="id_cliente">
                        <option value="">Mostrador / sin cliente</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= h((string) $client['id']) ?>">
                                <?= h($client['nombre_razon_social']) ?> - <?= h($client['numero_documento']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="cantidad_personas">Personas</label>
                    <input id="cantidad_personas" name="cantidad_personas" type="number" min="1" step="1" value="1">
                </div>

                <div class="field field--full">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="4" placeholder="Preferencias, indicaciones de cocina, delivery u observaciones internas."></textarea>
                </div>

                <div class="actions field--full">
                    <button class="button button--primary" type="submit">Crear orden</button>
                </div>
            </form>
        </article>

        <article class="panel crud-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Guia rapida</span>
                    <h3>Buenas practicas del registro</h3>
                </div>
            </div>

            <div class="stack">
                <div class="crud-panel__note">
                    Usa <strong>Mesa</strong> cuando el pedido quede vinculado al salon, <strong>Para llevar</strong> para recojo y <strong>Delivery</strong> para reparto externo.
                </div>

                <div class="info-row">
                    <strong>Cliente opcional</strong>
                    <div class="muted-text">Puedes crear la orden sin cliente y asociarlo despues solo si necesitas comprobante o seguimiento.</div>
                </div>

                <div class="info-row">
                    <strong>Capacidad y mesa</strong>
                    <div class="muted-text">Cuando el servicio es en mesa, selecciona una mesa operativa para mantener la ocupacion sincronizada.</div>
                </div>

                <div class="info-row">
                    <strong>Observaciones</strong>
                    <div class="muted-text">Registra notas utiles para cocina, despacho o caja sin alterar el flujo principal del pedido.</div>
                </div>
            </div>
        </article>
    </section>
</section>
