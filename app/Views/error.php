<section class="section-shell">
    <article class="empty-state panel error-state">
        <div class="empty-state__icon" aria-hidden="true">!</div>
        <span class="empty-state__eyebrow">Error del sistema</span>
        <h3><?= h($heading ?? 'Algo salio mal') ?></h3>
        <p><?= h($message ?? 'No se pudo continuar con la solicitud.') ?></p>

        <div class="section-hero__actions mt-2">
            <a class="button button--primary" href="<?= h(url('dashboard')) ?>">Volver al panel</a>
            <a class="button button--ghost" href="<?= h(url('orders')) ?>">Ir a Ordenes</a>
        </div>
    </article>
</section>
