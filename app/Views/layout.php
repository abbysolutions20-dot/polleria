<?php

$auth = $auth ?? null;
$flashMessages = $flashMessages ?? [];
$user = $auth && $auth->check() ? $auth->user() : null;
$currentPage = (string) ($_GET['page'] ?? 'dashboard');
$isSelectedOrderPage = $currentPage === 'orders' && isset($_GET['selected']) && (int) $_GET['selected'] > 0;
$isOrdersIndexPage = $currentPage === 'orders' && !$isSelectedOrderPage && !isset($_GET['create']);
$isCashPage = $currentPage === 'cash';
$hideTopbar = $isSelectedOrderPage || $isOrdersIndexPage || $isCashPage;
$ordersActive = in_array($currentPage, ['orders', 'order_create', 'order_show'], true) ? 'is-active' : '';
$displayName = $user ? trim((string) ($user['nombres'] ?? '') . ' ' . (string) ($user['apellidos'] ?? '')) : '';
$displayName = $displayName !== '' ? $displayName : 'Usuario';
$nameParts = preg_split('/\s+/', $displayName) ?: [];
$userInitials = strtoupper(substr((string) ($nameParts[0] ?? 'U'), 0, 1) . substr((string) ($nameParts[1] ?? ''), 0, 1));
$roleName = trim((string) ($user['rol_nombre'] ?? 'Sin rol'));
$branchName = trim((string) ($user['sucursal_nombre'] ?? ''));
$flashTypeMap = [
    'success' => 'success',
    'error' => 'danger',
    'info' => 'info',
    'warning' => 'warning',
];
$icons = [
    'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4z"></path><path d="M13 20h7v-9h-7z"></path><path d="M13 11h7V4h-7z"></path><path d="M4 20h7v-5H4z"></path></svg>',
    'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10l3 3v13H7z"></path><path d="M7 7H4v13h13"></path><path d="M10 11h7"></path><path d="M10 15h7"></path></svg>',
    'kitchen' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3v7"></path><path d="M11 3v7"></path><path d="M8 7H5.5A2.5 2.5 0 0 1 3 4.5V3"></path><path d="M11 7h2.5A2.5 2.5 0 0 0 16 4.5V3"></path><path d="M9.5 10v11"></path><path d="M19 3c-1.7 2-2.5 4.2-2.5 6.7V21"></path></svg>',
    'cash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="3"></rect><circle cx="12" cy="12" r="2.5"></circle><path d="M7 12h.01"></path><path d="M17 12h.01"></path></svg>',
    'clients' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 19a4 4 0 0 0-8 0"></path><circle cx="12" cy="11" r="3.5"></circle><path d="M5 19a3 3 0 0 1 3-3"></path><path d="M19 19a3 3 0 0 0-3-3"></path></svg>',
    'products' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14"></path><path d="M6 7V5h12v2"></path><path d="M7 11h10"></path><path d="M8 15h8"></path><path d="M9 19h6"></path></svg>',
    'inventory' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7l8-4l8 4"></path><path d="M4 7v10l8 4l8-4V7"></path><path d="M12 3v18"></path></svg>',
    'receipts' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v18l-2-1l-2 1l-2-1l-2 1l-2-1l-2 1z"></path><path d="M9 8h6"></path><path d="M9 12h6"></path><path d="M9 16h4"></path></svg>',
    'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="10" cy="7" r="4"></circle><path d="M21 21v-2a4 4 0 0 0-3-3.87"></path><path d="M14 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'masters' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34a1.7 1.7 0 0 0-1 1.54V21a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-1.54a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87a1.7 1.7 0 0 0-1.54-1H3a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 1.54-1a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1-1.54V3a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 1.54a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9c0 .67.39 1.28 1 1.54c.26.11.54.17.82.17H21a2 2 0 0 1 0 4h-.09c-.67 0-1.28.39-1.54 1z"></path></svg>',
    'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5l-5-5"></path><path d="M21 12H9"></path></svg>',
    'bell' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path><path d="M10 20a2 2 0 0 0 4 0"></path></svg>',
    'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4"></path><path d="M8 3v4"></path><path d="M3 10h18"></path></svg>',
    'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>',
];
$icon = static function (string $name) use ($icons): string {
    return $icons[$name] ?? '';
};

$navItems = [
    ['label' => 'Dashboard', 'page' => 'dashboard', 'icon' => 'dashboard', 'permission' => 'dashboard.ver', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'COCINA', 'DELIVERY'], 'active' => nav_active('dashboard')],
    ['label' => 'Ordenes', 'page' => 'orders', 'icon' => 'orders', 'permission' => null, 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'CAJERO'], 'active' => $ordersActive],
    ['label' => 'Cocina', 'page' => 'kitchen', 'icon' => 'kitchen', 'permission' => null, 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'COCINA'], 'active' => nav_active('kitchen')],
    ['label' => 'Caja', 'page' => 'cash', 'icon' => 'cash', 'permission' => 'caja.gestionar', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'CAJERO'], 'active' => nav_active('cash')],
    ['label' => 'Clientes', 'page' => 'clients', 'icon' => 'clients', 'permission' => 'ordenes.crear', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'CAJERO', 'MOZO'], 'active' => nav_active('clients')],
    ['label' => 'Carta', 'page' => 'products', 'icon' => 'products', 'permission' => 'ordenes.crear', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO'], 'active' => nav_active('products')],
    ['label' => 'Inventario', 'page' => 'inventory', 'icon' => 'inventory', 'permission' => 'inventario.gestionar', 'active' => nav_active('inventory')],
    ['label' => 'Comprobantes', 'page' => 'receipts', 'icon' => 'receipts', 'permission' => 'comprobantes.emitir', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR'], 'active' => nav_active('receipts')],
    ['label' => 'Usuarios', 'page' => 'users', 'icon' => 'users', 'permission' => null, 'roles' => ['PROPIETARIO', 'ADMINISTRADOR'], 'active' => nav_active('users')],
    ['label' => 'Maestros', 'page' => 'masters', 'icon' => 'masters', 'permission' => null, 'roles' => ['PROPIETARIO'], 'active' => nav_active('masters')],
];
$visibleNavItems = array_values(array_filter($navItems, function (array $item) use ($auth): bool {
    if (($item['permission'] ?? null) && !$auth->can((string) $item['permission'])) {
        return false;
    }

    if (($item['roles'] ?? []) && !$auth->hasRole($item['roles'])) {
        return false;
    }

    return true;
}));
$mobileNavItems = array_slice($visibleNavItems, 0, 4);
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ff6b3d">
    <title><?= h($title ?? app_config('app.name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(asset('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('assets/css/styles.css')) ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="<?= h(asset('assets/js/app.js')) ?>"></script>
</head>
<body class="<?= $user ? 'app-mode has-sidebar' . ($isSelectedOrderPage ? ' selected-order-page' : '') . ($isOrdersIndexPage ? ' orders-index-page' : '') . ($isCashPage ? ' cash-page' : '') : 'guest-mode' ?>">
    <div class="ambient ambient--one"></div>
    <div class="ambient ambient--two"></div>

    <?php if ($user): ?>
        <div class="layout">
            <aside class="sidebar" id="appSidebar">
                <div class="brand-block">
                    <div class="brand-block__head">
                        <span class="brand-mark">PP</span>
                        <div>
                            <span class="brand-block__eyebrow">Panel administrativo</span>
                            <h1><?= h(app_config('app.name')) ?></h1>
                        </div>
                    </div>
                    <p>Ordenes, cocina, caja e inventario en un flujo mas claro y profesional.</p>
                </div>

                <nav class="nav">
                    <?php foreach ($visibleNavItems as $item): ?>
                        <a class="nav__link <?= h($item['active']) ?>" href="<?= h(url($item['page'])) ?>">
                            <span class="nav__icon icon"><?= $icon((string) ($item['icon'] ?? 'dashboard')) ?></span>
                            <span class="nav__text"><?= h($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="sidebar__footer">
                    <a class="logout-link" href="<?= h(url('logout')) ?>">
                        <span class="nav__icon icon"><?= $icon('logout') ?></span>
                        <span class="nav__text">Cerrar sesion</span>
                    </a>
                </div>
            </aside>

            <div class="sidebar-backdrop" data-sidebar-close></div>

            <div class="main-shell container-fluid">
                <?php if (!$hideTopbar): ?>
                    <header class="topbar">
                        <div class="topbar__main">
                            <div class="topbar__heading">
                                <button class="sidebar-toggle btn btn-light d-lg-none" type="button" data-sidebar-toggle aria-label="Abrir menu">
                                    <i class="bi bi-list"></i>
                                </button>

                                <div>
                                    <span class="topbar__eyebrow"><?= h(app_config('app.name')) ?></span>
                                    <h2><?= h($title ?? '') ?></h2>
                                </div>
                            </div>

                            <div class="topbar__meta">
                                <span class="topbar__meta-item">
                                    <span class="icon icon--tiny"><?= $icon('calendar') ?></span>
                                    <span><?= h(date('d/m/Y')) ?></span>
                                </span>
                                <span class="topbar__meta-item">
                                    <span class="icon icon--tiny"><?= $icon('clock') ?></span>
                                    <span><?= h(date('H:i')) ?></span>
                                </span>
                            </div>
                        </div>

                        <div class="topbar__side">
                            <button class="topbar__notify btn btn-light" type="button" aria-label="Notificaciones">
                                <span class="icon"><?= $icon('bell') ?></span>
                            </button>

                            <div class="topbar__user">
                                <span class="topbar__user-avatar"><?= h($userInitials) ?></span>
                                <div class="topbar__user-copy">
                                    <strong><?= h($displayName) ?></strong>
                                    <span><?= h($roleName) ?></span>
                                </div>
                            </div>
                        </div>
                    </header>
                <?php endif; ?>

                <main class="content container-fluid px-0">
                    <?php foreach ($flashMessages as $message): ?>
                        <?php $alertClass = $flashTypeMap[$message['type']] ?? 'secondary'; ?>
                        <div class="flash alert alert-<?= h($alertClass) ?> alert-dismissible fade show" role="alert">
                            <div class="flash__body"><?= h($message['message']) ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endforeach; ?>

                    <?= $content ?>
                </main>
            </div>

            <?php if ($mobileNavItems !== []): ?>
                <nav class="mobile-bottom-nav" aria-label="Navegacion movil">
                    <div class="mobile-bottom-nav__list">
                        <?php foreach ($mobileNavItems as $item): ?>
                            <a class="mobile-bottom-nav__link <?= h($item['active']) ?>" href="<?= h(url($item['page'])) ?>">
                                <span class="mobile-bottom-nav__icon icon"><?= $icon((string) ($item['icon'] ?? 'dashboard')) ?></span>
                                <span class="mobile-bottom-nav__label"><?= h($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>

                        <button class="mobile-bottom-nav__link mobile-bottom-nav__link--menu" type="button" data-sidebar-toggle aria-label="Abrir menu">
                            <span class="mobile-bottom-nav__icon"><i class="bi bi-grid-3x3-gap"></i></span>
                            <span class="mobile-bottom-nav__label">Menu</span>
                        </button>
                    </div>
                </nav>
            <?php endif; ?>
        </div>

        <div class="modal fade confirm-modal" id="appConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <span class="eyebrow mb-2 d-inline-block">Confirmacion</span>
                            <h5 class="modal-title" id="appConfirmModalTitle">Continuar con esta accion</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0 text-secondary" id="appConfirmModalMessage">Confirma si deseas continuar.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="button button--ghost" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="button button--danger" id="appConfirmModalAccept">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <main class="guest-shell container-fluid">
            <?php foreach ($flashMessages as $message): ?>
                <?php $alertClass = $flashTypeMap[$message['type']] ?? 'secondary'; ?>
                <div class="flash alert alert-<?= h($alertClass) ?> alert-dismissible fade show" role="alert">
                    <div class="flash__body"><?= h($message['message']) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </main>
    <?php endif; ?>
</body>
</html>
