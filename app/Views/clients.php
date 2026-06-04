<section class="section-shell crud-shell">
    <article class="panel section-hero">
        <div class="section-hero__header">
            <div class="section-hero__copy">
                <span class="eyebrow">Clientes</span>
                <h3>Gestiona tu base comercial con una vista mas clara y ordenada.</h3>
                <p>Registra clientes, edita sus datos y localiza rapidamente documentos, nombres o telefonos.</p>
            </div>

            <div class="section-hero__actions">
                <a class="button button--primary" href="<?= h(url('clients')) ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Nuevo cliente</span>
                </a>
            </div>
        </div>
    </article>

    <section class="crud-layout">
        <article class="panel crud-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow"><?= $editId ? 'Editar registro' : 'Nuevo registro' ?></span>
                    <h3><?= $editId ? 'Actualizar cliente' : 'Registrar cliente' ?></h3>
                </div>
            </div>

            <form method="post" class="form-grid">
                <?= $csrf->input() ?>
                <input type="hidden" name="action" value="save_client">
                <input type="hidden" name="id" value="<?= h((string) ($clientForm['id'] ?? 0)) ?>">

                <div class="field">
                    <label for="tipo_documento">Tipo de documento</label>
                    <select id="tipo_documento" name="tipo_documento">
                        <?php foreach (['DNI', 'RUC', 'CE', 'PASAPORTE', 'OTRO'] as $documentType): ?>
                            <option value="<?= h($documentType) ?>" <?= ($clientForm['tipo_documento'] ?? 'DNI') === $documentType ? 'selected' : '' ?>>
                                <?= h($documentType) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="numero_documento">Numero</label>
                    <input id="numero_documento" name="numero_documento" type="text" required value="<?= h($clientForm['numero_documento'] ?? '') ?>">
                </div>

                <div class="field field--full">
                    <label for="nombre_razon_social">Nombre o razon social</label>
                    <input id="nombre_razon_social" name="nombre_razon_social" type="text" required value="<?= h($clientForm['nombre_razon_social'] ?? '') ?>">
                </div>

                <div class="field field--full">
                    <label for="direccion">Direccion</label>
                    <input id="direccion" name="direccion" type="text" value="<?= h($clientForm['direccion'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="telefono">Telefono</label>
                    <input id="telefono" name="telefono" type="text" value="<?= h($clientForm['telefono'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="correo">Correo</label>
                    <input id="correo" name="correo" type="email" value="<?= h($clientForm['correo'] ?? '') ?>">
                </div>

                <div class="actions field--full">
                    <button class="button button--primary" type="submit">
                        <i class="bi bi-floppy"></i>
                        <span><?= $editId ? 'Guardar cambios' : 'Registrar cliente' ?></span>
                    </button>
                    <?php if ($editId): ?>
                        <a class="button button--ghost" href="<?= h(url('clients')) ?>">
                            <i class="bi bi-x-circle"></i>
                            <span>Cancelar edicion</span>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </article>

        <article class="panel crud-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Base comercial</span>
                    <h3>Clientes registrados</h3>
                </div>
            </div>

            <form method="get" class="toolbar toolbar--compact toolbar-card">
                <input type="hidden" name="page" value="clients">
                <div class="field field--grow">
                    <label for="search-client">Buscar</label>
                    <input id="search-client" name="search" type="text" value="<?= h($search) ?>" placeholder="Documento, nombre o telefono">
                </div>
                <div class="toolbar__actions">
                    <button class="button button--primary" type="submit">
                        <i class="bi bi-search"></i>
                        <span>Buscar</span>
                    </button>
                    <a class="button button--ghost" href="<?= h(url('clients')) ?>">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Limpiar</span>
                    </a>
                </div>
            </form>

            <div class="table-wrap">
                <table class="table" data-table-paginate="7">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Contacto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$clients): ?>
                            <tr><td colspan="4" class="muted-cell">No hay clientes que mostrar.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <div class="record-stack">
                                        <strong><?= h($client['tipo_documento']) ?> <?= h($client['numero_documento']) ?></strong>
                                        <span class="muted-text">Documento principal</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="record-stack">
                                        <strong><?= h($client['nombre_razon_social']) ?></strong>
                                        <span class="muted-text"><?= h($client['direccion']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="record-stack">
                                        <span><?= h($client['telefono']) ?: 'Sin telefono' ?></span>
                                        <span class="muted-text"><?= h($client['correo']) ?: 'Sin correo' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <a class="button button--ghost button--small" href="<?= h(url('clients', ['edit' => $client['id']])) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Editar</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</section>
