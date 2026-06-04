<section class="section-shell">
    <div class="section-tabs products-tabs">
        <a class="section-tab <?= $section === 'platos' ? 'is-active' : '' ?>" href="<?= h(url('products', ['section' => 'platos'])) ?>">Platos</a>
        <a class="section-tab <?= $section === 'articulos' ? 'is-active' : '' ?>" href="<?= h(url('products', ['section' => 'articulos'])) ?>">Articulos</a>
        <a class="button button--primary products-tabs__create" href="<?= h(url('products', ['section' => 'articulos', 'create' => 1])) ?>">
            <i class="bi bi-plus-circle"></i>
            <span>Nuevo plato</span>
        </a>
    </div>

    <?php if ($section === 'platos'): ?>
        <section class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Platos</span>
                    <h3>Reporte de carta</h3>
                </div>
            </div>

            <?php if (!$canManageSensitiveProducts): ?>
                <div class="crud-panel__note">
                    Solo <strong>PROPIETARIO</strong> y <strong>ADMINISTRADOR</strong> pueden cambiar la visibilidad de la carta.
                </div>
            <?php endif; ?>

            <div class="table-wrap<?= !$canManageSensitiveProducts ? ' mt-3' : '' ?>">
                <table class="table" data-table-paginate="8">
                    <thead>
                        <tr>
                            <th>Plato</th>
                            <th>Categoria</th>
                            <th>Area preparacion</th>
                            <th>Precio</th>
                            <th>Visible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$products): ?>
                            <tr><td colspan="5" class="muted-cell">No hay platos registrados todavia.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="record-stack">
                                        <strong><?= h($product['nombre']) ?></strong>
                                        <span class="muted-text"><?= h($product['tipo_producto']) ?></span>
                                    </div>
                                </td>
                                <td><?= h($product['categoria']) ?></td>
                                <td><?= h($product['area'] ?: 'Sin area') ?></td>
                                <td><?= h(money($product['precio_venta'])) ?></td>
                                <td>
                                    <form method="post" class="visibility-switch-form">
                                        <?= $csrf->input() ?>
                                        <input type="hidden" name="action" value="toggle_visibility">
                                        <input type="hidden" name="id" value="<?= h((string) $product['id']) ?>">
                                        <input type="hidden" name="visible" value="<?= $product['activo'] ? '1' : '0' ?>" data-visibility-value>

                                        <label class="switch <?= !$canManageSensitiveProducts ? 'switch--disabled' : '' ?>">
                                            <input
                                                class="switch__input"
                                                type="checkbox"
                                                <?= $product['activo'] ? 'checked' : '' ?>
                                                <?= !$canManageSensitiveProducts ? 'disabled' : '' ?>
                                                aria-label="<?= h('Cambiar visibilidad de ' . $product['nombre']) ?>"
                                                data-visibility-toggle
                                            >
                                            <span class="switch__slider"></span>
                                        </label>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($section === 'articulos'): ?>
        <?php if (!$canManageSensitiveProducts): ?>
            <div class="crud-panel__note">
                Solo <strong>PROPIETARIO</strong> y <strong>ADMINISTRADOR</strong> pueden cambiar precios o eliminar platos.
            </div>
        <?php endif; ?>

        <?php if ($showProductForm): ?>
            <section class="panel">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow"><?= $editId ? 'Editar plato' : 'Nuevo plato' ?></span>
                        <h3><?= $editId ? 'Actualizar articulo de carta' : 'Crear articulo de carta' ?></h3>
                    </div>
                    <a class="button button--ghost" href="<?= h(url('products', ['section' => 'articulos'])) ?>">Cerrar formulario</a>
                </div>

                <form method="post" class="form-grid">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="save_product">
                    <input type="hidden" name="id" value="<?= h((string) ($productForm['id'] ?? 0)) ?>">

                    <div class="field">
                        <label for="sku">SKU</label>
                        <input id="sku" name="sku" type="text" value="<?= h($productForm['sku'] ?? '') ?>" placeholder="POL-14">
                    </div>

                    <div class="field">
                        <label for="nombre">Plato</label>
                        <input id="nombre" name="nombre" type="text" required value="<?= h($productForm['nombre'] ?? '') ?>" placeholder="Pollo a la brasa 1/4">
                    </div>

                    <div class="field">
                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <option value="">Selecciona</option>
                            <?php foreach ($categories as $item): ?>
                                <option value="<?= h((string) $item['id']) ?>" <?= (string) ($productForm['id_categoria'] ?? '') === (string) $item['id'] ? 'selected' : '' ?>>
                                    <?= h($item['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="id_area_preparacion">Area preparacion</label>
                        <select id="id_area_preparacion" name="id_area_preparacion">
                            <option value="">Sin area</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= h((string) $area['id']) ?>" <?= (string) ($productForm['id_area_preparacion'] ?? '') === (string) $area['id'] ? 'selected' : '' ?>>
                                    <?= h($area['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="tipo_producto">Tipo</label>
                        <select id="tipo_producto" name="tipo_producto" required>
                            <?php foreach ($productTypes as $productType): ?>
                                <option value="<?= h($productType) ?>" <?= ($productForm['tipo_producto'] ?? ($productTypes[0] ?? '')) === $productType ? 'selected' : '' ?>>
                                    <?= h($productType) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="precio_venta">Precio</label>
                        <input
                            id="precio_venta"
                            name="precio_venta"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            value="<?= h(number_format((float) ($productForm['precio_venta'] ?? 0), 2, '.', '')) ?>"
                            <?= ($editId && !$canManageSensitiveProducts) ? 'readonly' : '' ?>
                        >
                    </div>

                    <div class="field">
                        <label for="costo_unitario">Costo unitario</label>
                        <input id="costo_unitario" name="costo_unitario" type="number" step="0.0001" min="0" value="<?= h(number_format((float) ($productForm['costo_unitario'] ?? 0), 4, '.', '')) ?>">
                    </div>

                    <div class="field field--full">
                        <label for="descripcion">Descripcion</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Incluye papas, ensalada, extras, etc."><?= h($productForm['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="field checkbox-field">
                        <label class="checkbox-row">
                            <input type="checkbox" name="afecto_igv" value="1" <?= !empty($productForm['afecto_igv']) ? 'checked' : '' ?>>
                            <span>Afecto a IGV</span>
                        </label>
                    </div>

                    <div class="actions field--full">
                    <button class="button button--primary" type="submit">
                        <i class="bi bi-floppy"></i>
                        <span><?= $editId ? 'Guardar cambios' : 'Crear plato' ?></span>
                    </button>
                        <a class="button button--ghost" href="<?= h(url('products', ['section' => 'articulos'])) ?>">
                            <i class="bi bi-x-circle"></i>
                            <span>Cancelar</span>
                        </a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <?php if (!$showProductForm): ?>
        <section class="panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Articulos</span>
                    <h3>Listado para crear, modificar y eliminar</h3>
                </div>
            </div>

            <form method="get" class="toolbar toolbar-card">
                <input type="hidden" name="page" value="products">
                <input type="hidden" name="section" value="articulos">

                <div class="field">
                    <label for="search">Buscar</label>
                    <input id="search" name="search" type="text" value="<?= h($search) ?>" placeholder="Plato, SKU...">
                </div>
                <div class="field">
                    <label for="category">Categoria</label>
                    <select id="category" name="category">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= h((string) $item['id']) ?>" <?= (string) $item['id'] === (string) $category ? 'selected' : '' ?>>
                                <?= h($item['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="type">Tipo</label>
                    <select id="type" name="type">
                        <option value="">Todos</option>
                        <?php foreach ($productTypes as $productType): ?>
                            <option value="<?= h($productType) ?>" <?= $productType === $type ? 'selected' : '' ?>><?= h($productType) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="toolbar__actions">
                    <button class="button button--primary" type="submit">
                        <i class="bi bi-funnel"></i>
                        <span>Filtrar</span>
                    </button>
                    <a class="button button--ghost" href="<?= h(url('products', ['section' => 'articulos'])) ?>">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Limpiar</span>
                    </a>
                </div>
            </form>

            <div class="table-wrap">
                <table class="table" data-table-paginate="8">
                    <thead>
                        <tr>
                            <th>Plato</th>
                            <th>Area preparacion</th>
                            <th>Categoria</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$products): ?>
                            <tr><td colspan="5" class="muted-cell">No se encontraron platos con ese filtro.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="record-stack">
                                        <strong><?= h($product['nombre']) ?></strong>
                                        <span class="muted-text"><?= h($product['tipo_producto']) ?> - <?= h($product['activo'] ? 'Visible' : 'Oculto') ?></span>
                                    </div>
                                </td>
                                <td><?= h($product['area'] ?: 'Sin area') ?></td>
                                <td><?= h($product['categoria']) ?></td>
                                <td><?= h(money($product['precio_venta'])) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="button button--ghost button--small" href="<?= h(url('products', ['section' => 'articulos', 'edit' => $product['id']])) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Editar</span>
                                        </a>
                                        <?php if ($canManageSensitiveProducts): ?>
                                            <form method="post" data-confirm="Se desactivara este plato de la carta.">
                                                <?= $csrf->input() ?>
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="id" value="<?= h((string) $product['id']) ?>">
                                                <button class="button button--danger button--small" type="submit">
                                                    <i class="bi bi-trash3"></i>
                                                    <span>Eliminar</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</section>
