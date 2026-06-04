<section class="section-shell crud-shell">
    <article class="panel section-hero">
        <div class="section-hero__header">
            <div class="section-hero__copy">
                <span class="eyebrow">Usuarios</span>
                <h3>Administra cuentas operativas con una interfaz mas clara y empresarial.</h3>
                <p>Gestiona cajeros, mozos, cocina y delivery sin afectar la estructura actual de permisos del sistema.</p>
            </div>

            <div class="section-hero__actions">
                <a class="button button--primary" href="<?= h(url('users')) ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Nuevo usuario</span>
                </a>
            </div>
        </div>
    </article>

    <section class="crud-layout">
        <article class="panel crud-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow"><?= $editId ? 'Editar usuario' : 'Nuevo usuario' ?></span>
                    <h3>Usuarios operativos</h3>
                </div>
            </div>

            <div class="crud-panel__note">
                Solo puedes crear y administrar usuarios con roles <strong>CAJERO</strong>, <strong>MOZO</strong>, <strong>COCINA</strong> y <strong>DELIVERY</strong>.
            </div>

            <form method="post" class="form-grid mt-3">
                <?= $csrf->input() ?>
                <input type="hidden" name="action" value="save_user">
                <input type="hidden" name="id" value="<?= h((string) ($userForm['id'] ?? 0)) ?>">

                <div class="field">
                    <label for="id_sucursal">Sucursal</label>
                    <select id="id_sucursal" name="id_sucursal" required>
                        <option value="">Selecciona</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= h((string) $branch['id']) ?>" <?= (string) ($userForm['id_sucursal'] ?? '') === (string) $branch['id'] ? 'selected' : '' ?>>
                                <?= h($branch['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="id_rol">Rol</label>
                    <select id="id_rol" name="id_rol" required>
                        <option value="">Selecciona</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= h((string) $role['id']) ?>" <?= (string) ($userForm['id_rol'] ?? '') === (string) $role['id'] ? 'selected' : '' ?>>
                                <?= h($role['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="nombres">Nombres</label>
                    <input id="nombres" name="nombres" type="text" required value="<?= h($userForm['nombres'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="apellidos">Apellidos</label>
                    <input id="apellidos" name="apellidos" type="text" value="<?= h($userForm['apellidos'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="usuario">Usuario</label>
                    <input id="usuario" name="usuario" type="text" required value="<?= h($userForm['usuario'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="correo">Correo</label>
                    <input id="correo" name="correo" type="email" value="<?= h($userForm['correo'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="telefono">Telefono</label>
                    <input id="telefono" name="telefono" type="text" value="<?= h($userForm['telefono'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="password"><?= $editId ? 'Nueva contrasena (opcional)' : 'Contrasena' ?></label>
                    <input id="password" name="password" type="password" <?= $editId ? '' : 'required' ?> placeholder="<?= $editId ? 'Solo si deseas cambiarla' : 'Ingresa una contrasena' ?>">
                </div>

                <div class="field checkbox-field field--full">
                    <label class="checkbox-row">
                        <input type="checkbox" name="activo" value="1" <?= !empty($userForm['activo']) ? 'checked' : '' ?>>
                        <span>Usuario activo</span>
                    </label>
                </div>

                <div class="actions field--full">
                    <button class="button button--primary" type="submit">
                        <i class="bi bi-floppy"></i>
                        <span><?= $editId ? 'Guardar cambios' : 'Crear usuario' ?></span>
                    </button>
                    <?php if ($editId): ?>
                        <a class="button button--ghost" href="<?= h(url('users')) ?>">
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
                    <h3>Usuarios registrados</h3>
                </div>
            </div>

            <form method="get" class="toolbar toolbar--compact toolbar-card">
                <input type="hidden" name="page" value="users">
                <div class="field field--grow">
                    <label for="search-user">Buscar</label>
                    <input id="search-user" name="search" type="text" value="<?= h($search) ?>" placeholder="Usuario, nombre o correo">
                </div>
                <div class="toolbar__actions">
                    <button class="button button--primary" type="submit">
                        <i class="bi bi-search"></i>
                        <span>Buscar</span>
                    </button>
                    <a class="button button--ghost" href="<?= h(url('users')) ?>">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Limpiar</span>
                    </a>
                </div>
            </form>

            <div class="table-wrap">
                <table class="table" data-table-paginate="7">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Sucursal</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users): ?>
                            <tr><td colspan="5" class="muted-cell">No hay usuarios operativos registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="record-stack">
                                        <strong><?= h($user['usuario']) ?></strong>
                                        <span class="muted-text"><?= h(trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''))) ?></span>
                                        <span class="muted-text"><?= h($user['correo']) ?: 'Sin correo' ?></span>
                                    </div>
                                </td>
                                <td><?= h($user['rol_nombre']) ?></td>
                                <td><?= h($user['sucursal_nombre'] ?: 'Sin sucursal') ?></td>
                                <td><span class="<?= h(badge_class(!empty($user['activo']) ? 'ABIERTO' : 'INACTIVO')) ?>"><?= !empty($user['activo']) ? 'ACTIVO' : 'INACTIVO' ?></span></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="button button--ghost button--small" href="<?= h(url('users', ['edit' => $user['id']])) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Editar</span>
                                        </a>
                                        <form method="post" data-confirm="<?= !empty($user['activo']) ? 'Se desactivara este usuario.' : 'Se reactivara este usuario.' ?>">
                                            <?= $csrf->input() ?>
                                            <input type="hidden" name="action" value="toggle_user">
                                            <input type="hidden" name="id" value="<?= h((string) $user['id']) ?>">
                                            <input type="hidden" name="activo" value="<?= !empty($user['activo']) ? '0' : '1' ?>">
                                            <button class="button <?= !empty($user['activo']) ? 'button--danger' : 'button--primary' ?> button--small" type="submit">
                                                <?= !empty($user['activo']) ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
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
