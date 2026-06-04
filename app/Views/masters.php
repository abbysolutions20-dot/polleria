<section class="section-shell master-shell">
    <article class="panel section-hero">
        <div class="section-hero__header">
            <div class="section-hero__copy">
                <span class="eyebrow">Control exclusivo</span>
                <h3>Maestros del sistema</h3>
                <p>Administra categorias, areas, tipos de producto y mesas desde una consola mas clara, unificada y alineada con el resto del panel institucional.</p>
            </div>
        </div>

        <div class="section-tabs">
            <a class="section-tab <?= $section === 'categories' ? 'is-active' : '' ?>" href="<?= h(url('masters', ['section' => 'categories'])) ?>">Categorias</a>
            <a class="section-tab <?= $section === 'areas' ? 'is-active' : '' ?>" href="<?= h(url('masters', ['section' => 'areas'])) ?>">Areas</a>
            <a class="section-tab <?= $section === 'types' ? 'is-active' : '' ?>" href="<?= h(url('masters', ['section' => 'types'])) ?>">Tipos</a>
            <a class="section-tab <?= $section === 'tables' ? 'is-active' : '' ?>" href="<?= h(url('masters', ['section' => 'tables'])) ?>">Mesas</a>
        </div>
    </article>

    <section class="stat-grid stat-grid--compact">
        <article class="stat-card metric-card--accent">
            <span class="stat-card__label">Categorias</span>
            <strong><?= h((string) count($categories)) ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Areas</span>
            <strong><?= h((string) count($areas)) ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Tipos</span>
            <strong><?= h((string) count($productTypes)) ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">Mesas</span>
            <strong><?= h((string) count($tables)) ?></strong>
        </article>
    </section>

    <article class="toolbar-card">
        <strong>Modulo activo:</strong> <?= h(ucfirst($section)) ?>.
        <span class="muted-text">El propietario puede mantener la estructura operativa del negocio sin alterar la logica del sistema.</span>
    </article>

    <section class="crud-layout">
    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow"><?= $editCategoryId ? 'Editar categoria' : 'Nueva categoria' ?></span>
                <h3>Categorias</h3>
            </div>
        </div>

        <div class="crud-panel__note">
            Define la estructura comercial que organiza la carta y los articulos operativos del negocio.
        </div>

        <form method="post" class="form-grid">
            <?= $csrf->input() ?>
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?= h((string) ($categoryForm['id'] ?? 0)) ?>">

            <div class="field">
                <label for="category_nombre">Nombre</label>
                <input id="category_nombre" name="nombre" type="text" required value="<?= h($categoryForm['nombre'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="category_tipo">Tipo de categoria</label>
                <input id="category_tipo" name="tipo" type="text" value="<?= h($categoryForm['tipo'] ?? '') ?>" placeholder="PLATOS, BEBIDAS, EXTRAS...">
            </div>

            <div class="field field--full">
                <label for="category_descripcion">Descripcion</label>
                <input id="category_descripcion" name="descripcion" type="text" value="<?= h($categoryForm['descripcion'] ?? '') ?>">
            </div>

            <div class="field checkbox-field field--full">
                <label class="checkbox-row">
                    <input type="checkbox" name="activo" value="1" <?= !empty($categoryForm['activo']) ? 'checked' : '' ?>>
                    <span>Categoria activa</span>
                </label>
            </div>

            <div class="actions field--full">
                <button class="button button--primary" type="submit">
                    <i class="bi bi-floppy"></i>
                    <span><?= $editCategoryId ? 'Guardar cambios' : 'Crear categoria' ?></span>
                </button>
                <?php if ($editCategoryId): ?>
                    <a class="button button--ghost" href="<?= h(url('masters', ['section' => 'categories'])) ?>">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancelar</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Listado</span>
                <h3>Categorias registradas</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" data-table-paginate="7">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $categoryRow): ?>
                        <tr>
                            <td>
                                <strong><?= h($categoryRow['nombre']) ?></strong>
                                <div class="muted-text"><?= h($categoryRow['descripcion']) ?></div>
                            </td>
                            <td><?= h($categoryRow['tipo']) ?></td>
                            <td><span class="<?= h(badge_class(!empty($categoryRow['activo']) ? 'ABIERTO' : 'INACTIVO')) ?>"><?= !empty($categoryRow['activo']) ? 'ACTIVA' : 'INACTIVA' ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button button--ghost button--small" href="<?= h(url('masters', ['section' => 'categories', 'edit_category' => $categoryRow['id']])) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Editar</span>
                                    </a>
                                    <?php if (!empty($categoryRow['activo'])): ?>
                                        <form method="post" data-confirm="La categoria quedara desactivada.">
                                            <?= $csrf->input() ?>
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?= h((string) $categoryRow['id']) ?>">
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
    </article>
</section>

    <section class="crud-layout">
    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow"><?= $editAreaId ? 'Editar area' : 'Nueva area' ?></span>
                <h3>Areas de preparacion</h3>
            </div>
        </div>

        <div class="crud-panel__note">
            Mantiene las estaciones de trabajo que luego usaran cocina, comandas y reportes operativos.
        </div>

        <form method="post" class="form-grid">
            <?= $csrf->input() ?>
            <input type="hidden" name="action" value="save_area">
            <input type="hidden" name="id" value="<?= h((string) ($areaForm['id'] ?? 0)) ?>">

            <div class="field field--full">
                <label for="area_nombre">Nombre</label>
                <input id="area_nombre" name="nombre" type="text" required value="<?= h($areaForm['nombre'] ?? '') ?>">
            </div>

            <div class="field field--full">
                <label for="area_descripcion">Descripcion</label>
                <input id="area_descripcion" name="descripcion" type="text" value="<?= h($areaForm['descripcion'] ?? '') ?>">
            </div>

            <div class="field checkbox-field field--full">
                <label class="checkbox-row">
                    <input type="checkbox" name="activo" value="1" <?= !empty($areaForm['activo']) ? 'checked' : '' ?>>
                    <span>Area activa</span>
                </label>
            </div>

            <div class="actions field--full">
                <button class="button button--primary" type="submit">
                    <i class="bi bi-floppy"></i>
                    <span><?= $editAreaId ? 'Guardar cambios' : 'Crear area' ?></span>
                </button>
                <?php if ($editAreaId): ?>
                    <a class="button button--ghost" href="<?= h(url('masters', ['section' => 'areas'])) ?>">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancelar</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Listado</span>
                <h3>Areas registradas</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" data-table-paginate="7">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $areaRow): ?>
                        <tr>
                            <td>
                                <strong><?= h($areaRow['nombre']) ?></strong>
                                <div class="muted-text"><?= h($areaRow['descripcion']) ?></div>
                            </td>
                            <td><span class="<?= h(badge_class(!empty($areaRow['activo']) ? 'ABIERTO' : 'INACTIVO')) ?>"><?= !empty($areaRow['activo']) ? 'ACTIVA' : 'INACTIVA' ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button button--ghost button--small" href="<?= h(url('masters', ['section' => 'areas', 'edit_area' => $areaRow['id']])) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Editar</span>
                                    </a>
                                    <?php if (!empty($areaRow['activo'])): ?>
                                        <form method="post" data-confirm="El area quedara desactivada.">
                                            <?= $csrf->input() ?>
                                            <input type="hidden" name="action" value="delete_area">
                                            <input type="hidden" name="id" value="<?= h((string) $areaRow['id']) ?>">
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
    </article>
</section>

    <section class="crud-layout">
    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Nuevo tipo</span>
                <h3>Tipos de producto</h3>
            </div>
        </div>

        <div class="crud-panel__note">
            Este catalogo alimenta el tipo interno de los productos disponibles en la carta y en inventario.
        </div>

        <form method="post" class="form-grid">
            <?= $csrf->input() ?>
            <input type="hidden" name="action" value="add_product_type">

            <div class="field field--full">
                <label for="nuevo_tipo">Agregar tipo</label>
                <input id="nuevo_tipo" name="nuevo_tipo" type="text" required placeholder="PARRILLA, POSTRE, DESAYUNO...">
            </div>

            <div class="actions field--full">
                <button class="button button--primary" type="submit">
                    <i class="bi bi-plus-circle"></i>
                    <span>Agregar tipo</span>
                </button>
            </div>
        </form>
    </article>

    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Enum actual</span>
                <h3>Modificar o eliminar tipos</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" data-table-paginate="7">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Uso</th>
                        <th>Renombrar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productTypes as $productType): ?>
                        <tr>
                            <td><strong><?= h($productType) ?></strong></td>
                            <td><?= h((string) ($productTypeUsage[$productType] ?? 0)) ?> productos</td>
                            <td>
                                <form method="post" class="inline-master-form">
                                    <?= $csrf->input() ?>
                                    <input type="hidden" name="action" value="rename_product_type">
                                    <input type="hidden" name="tipo_actual" value="<?= h($productType) ?>">
                                    <input name="nuevo_nombre" type="text" value="<?= h($productType) ?>" required>
                                    <button class="button button--ghost button--small" type="submit">
                                        <i class="bi bi-floppy"></i>
                                        <span>Guardar</span>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="post" class="stacked-mini-form" data-confirm="El tipo se eliminara del catalogo.">
                                    <?= $csrf->input() ?>
                                    <input type="hidden" name="action" value="delete_product_type">
                                    <input type="hidden" name="tipo_actual" value="<?= h($productType) ?>">
                                    <select name="tipo_reemplazo">
                                        <option value="">Sin reemplazo</option>
                                        <?php foreach ($productTypes as $replacementType): ?>
                                            <?php if ($replacementType === $productType) {
                                                continue;
                                            } ?>
                                            <option value="<?= h($replacementType) ?>"><?= h($replacementType) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button button--danger button--small" type="submit">
                                        <i class="bi bi-trash3"></i>
                                        <span>Eliminar</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

    <section class="crud-layout">
    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow"><?= $editTableId ? 'Editar mesa' : 'Nueva mesa' ?></span>
                <h3>Mesas</h3>
            </div>
        </div>

        <div class="crud-panel__note">
            Configura el mapa operativo del salon con sucursal, zona, capacidad y disponibilidad visible.
        </div>

        <form method="post" class="form-grid">
            <?= $csrf->input() ?>
            <input type="hidden" name="action" value="save_table">
            <input type="hidden" name="id" value="<?= h((string) ($tableForm['id'] ?? 0)) ?>">

            <div class="field">
                <label for="id_sucursal">Sucursal</label>
                <select id="id_sucursal" name="id_sucursal" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= h((string) $branch['id']) ?>" <?= (string) ($tableForm['id_sucursal'] ?? '') === (string) $branch['id'] ? 'selected' : '' ?>>
                            <?= h($branch['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="id_zona">Zona</label>
                <select id="id_zona" name="id_zona">
                    <option value="">Sin zona</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= h((string) $zone['id']) ?>" <?= (string) ($tableForm['id_zona'] ?? '') === (string) $zone['id'] ? 'selected' : '' ?>>
                            <?= h($zone['sucursal']) ?> - <?= h($zone['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="numero">Numero</label>
                <input id="numero" name="numero" type="text" required value="<?= h($tableForm['numero'] ?? '') ?>" placeholder="01">
            </div>

            <div class="field">
                <label for="nombre">Nombre visible</label>
                <input id="nombre" name="nombre" type="text" value="<?= h($tableForm['nombre'] ?? '') ?>" placeholder="Mesa 01">
            </div>

            <div class="field">
                <label for="capacidad">Capacidad</label>
                <input id="capacidad" name="capacidad" type="number" min="1" step="1" value="<?= h((string) ($tableForm['capacidad'] ?? 4)) ?>">
            </div>

            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <?php foreach (['LIBRE', 'OCUPADA', 'RESERVADA', 'INACTIVA'] as $tableState): ?>
                        <option value="<?= h($tableState) ?>" <?= ($tableForm['estado'] ?? 'LIBRE') === $tableState ? 'selected' : '' ?>>
                            <?= h($tableState) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field checkbox-field field--full">
                <label class="checkbox-row">
                    <input type="checkbox" name="activo" value="1" <?= !empty($tableForm['activo']) ? 'checked' : '' ?>>
                    <span>Mesa activa</span>
                </label>
            </div>

            <div class="actions field--full">
                <button class="button button--primary" type="submit">
                    <i class="bi bi-floppy"></i>
                    <span><?= $editTableId ? 'Guardar cambios' : 'Crear mesa' ?></span>
                </button>
                <?php if ($editTableId): ?>
                    <a class="button button--ghost" href="<?= h(url('masters', ['section' => 'tables'])) ?>">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancelar</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="panel crud-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Plano operativo</span>
                <h3>Mesas registradas</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" data-table-paginate="7">
                <thead>
                    <tr>
                        <th>Mesa</th>
                        <th>Sucursal</th>
                        <th>Capacidad</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $tableRow): ?>
                        <tr>
                            <td>
                                <strong><?= h($tableRow['nombre'] ?: ('Mesa ' . $tableRow['numero'])) ?></strong>
                                <div class="muted-text">Numero <?= h($tableRow['numero']) ?><?= $tableRow['zona'] ? ' - ' . h($tableRow['zona']) : '' ?></div>
                            </td>
                            <td><?= h($tableRow['sucursal']) ?></td>
                            <td><?= h((string) $tableRow['capacidad']) ?> pax</td>
                            <td><span class="<?= h(badge_class(!empty($tableRow['activo']) ? $tableRow['estado'] : 'INACTIVO')) ?>"><?= !empty($tableRow['activo']) ? h($tableRow['estado']) : 'INACTIVA' ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button button--ghost button--small" href="<?= h(url('masters', ['section' => 'tables', 'edit_table' => $tableRow['id']])) ?>">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Editar</span>
                                    </a>
                                    <?php if (!empty($tableRow['activo'])): ?>
                                        <form method="post" data-confirm="La mesa quedara inactiva y dejara de mostrarse como disponible.">
                                            <?= $csrf->input() ?>
                                            <input type="hidden" name="action" value="delete_table">
                                            <input type="hidden" name="id" value="<?= h((string) $tableRow['id']) ?>">
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
    </article>
    </section>
</section>
