<section class="login-card login-card--split">
    <div class="login-card__intro login-welcome-panel">
        <div class="login-welcome-panel__brand">
            <span class="login-logo-mark">PP</span>
            <div>
                <span class="login-logo-kicker">Polleria</span>
                <strong>POS Pro</strong>
            </div>
        </div>

        <div class="login-welcome-panel__copy">
            <span class="eyebrow">Gestion comercial</span>
            <h1>Hola,<br>bienvenido.</h1>
            <p>Controla ordenes, cocina y caja desde una interfaz rapida, moderna y lista para XAMPP.</p>
        </div>

        <div class="login-welcome-panel__actions">
            <span class="login-status-pill"><i class="bi bi-lightning-charge"></i> Ventas rapidas</span>
            <span class="login-status-pill"><i class="bi bi-shield-check"></i> Acceso seguro</span>
        </div>
    </div>

    <div class="login-card__panel">
        <div class="login-form-card login-form-card--modern">
            <div class="login-form-card__header">
                <span class="eyebrow">Acceso al sistema</span>
                <h2>Iniciar sesion</h2>
                <p class="muted-text mb-0">Ingresa tus credenciales para continuar.</p>
            </div>

            <?php if (!empty($loginError)): ?>
                <div class="flash alert alert-danger" role="alert">
                    <div class="flash__body"><?= h($loginError) ?></div>
                </div>
            <?php endif; ?>

            <form method="post" class="stack login-modern-form">
                <?= $csrf->input() ?>

                <div class="field field--with-icon">
                    <label for="login">Usuario o correo</label>
                    <div class="input-shell">
                        <span class="input-shell__icon"><i class="bi bi-person"></i></span>
                        <input id="login" name="login" type="text" required value="<?= h(old_value('login')) ?>" placeholder="admin o propietario">
                    </div>
                </div>

                <div class="field field--with-icon">
                    <label for="password">Contrasena</label>
                    <div class="input-shell">
                        <span class="input-shell__icon"><i class="bi bi-lock"></i></span>
                        <input id="password" name="password" type="password" required placeholder="Ingresa tu contrasena">
                    </div>
                </div>

                <div class="login-form-options">
                    <label class="login-remember">
                        <input type="checkbox" name="remember" value="1">
                        <span>Recordarme</span>
                    </label>
                    <a href="<?= h(url('login')) ?>">Olvidaste tu contrasena?</a>
                </div>

                <div class="login-form-actions">
                    <button class="button button--primary button--full login-primary-button" type="submit">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Ingresar</span>
                    </button>

                    <a class="button button--ghost button--full login-secondary-button" href="<?= h(app_config('app.base_url')) ?>">
                        <i class="bi bi-arrow-left"></i>
                        <span>Regresar al inicio</span>
                    </a>
                </div>
            </form>

            <div class="seed-box seed-box--compact">
                <strong>Usuarios de prueba</strong>
                <p>propietario, admin, cajera, mozo1, cocina1, delivery1</p>
                <p>Contrasena inicial: <code>123456</code></p>
            </div>
        </div>
    </div>
</section>
