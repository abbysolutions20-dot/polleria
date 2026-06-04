<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\View;

$routes = [
    'login' => ['file' => 'login.php', 'title' => 'Ingreso', 'auth' => false],
    'logout' => ['file' => 'logout.php', 'title' => 'Salir', 'auth' => true],
    'dashboard' => ['file' => 'dashboard.php', 'title' => 'Dashboard', 'auth' => true, 'permission' => 'dashboard.ver', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'COCINA', 'DELIVERY']],
    'products' => ['file' => 'products.php', 'title' => 'Carta', 'auth' => true, 'permission' => 'ordenes.crear', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO']],
    'clients' => ['file' => 'clients.php', 'title' => 'Clientes', 'auth' => true, 'permission' => 'ordenes.crear', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'CAJERO', 'MOZO']],
    'cash' => ['file' => 'cash.php', 'title' => 'Caja', 'auth' => true, 'permission' => 'caja.gestionar', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'CAJERO']],
    'orders' => ['file' => 'orders.php', 'title' => 'Ordenes', 'auth' => true, 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'CAJERO']],
    'kitchen' => ['file' => 'kitchen.php', 'title' => 'Cocina', 'auth' => true, 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'COCINA']],
    'order_create' => ['file' => 'order_create.php', 'title' => 'Nueva orden', 'auth' => true, 'permission' => 'ordenes.crear', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO']],
    'order_show' => ['file' => 'order_show.php', 'title' => 'Detalle de orden', 'auth' => true, 'roles' => ['PROPIETARIO', 'ADMINISTRADOR', 'MOZO', 'CAJERO']],
    'inventory' => ['file' => 'inventory.php', 'title' => 'Inventario', 'auth' => true, 'permission' => 'inventario.gestionar'],
    'receipts' => ['file' => 'receipts.php', 'title' => 'Comprobantes', 'auth' => true, 'permission' => 'comprobantes.emitir', 'roles' => ['PROPIETARIO', 'ADMINISTRADOR']],
    'users' => ['file' => 'users.php', 'title' => 'Usuarios', 'auth' => true, 'roles' => ['PROPIETARIO', 'ADMINISTRADOR']],
    'masters' => ['file' => 'masters.php', 'title' => 'Maestros', 'auth' => true, 'roles' => ['PROPIETARIO']],
];

$page = (string) ($_GET['page'] ?? ($auth->check() ? 'dashboard' : 'login'));

if ($page === 'order_show' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($orderId > 0) {
        redirect('orders', ['selected' => $orderId]);
    }
}

if (!isset($routes[$page])) {
    http_response_code(404);
    $title = 'Pagina no encontrada';
    $content = View::render('error', [
        'heading' => 'No encontramos esa pantalla',
        'message' => 'La ruta solicitada no existe dentro del sistema.',
    ]);
    echo View::render('layout', compact('title', 'content'));
    return;
}

$route = $routes[$page];

if (($route['auth'] ?? false) && !$auth->check()) {
    $flash->error('Tu sesion expiro. Vuelve a ingresar para continuar.');
    redirect('login');
}

if (!($route['auth'] ?? false) && $auth->check() && $page === 'login') {
    redirect($auth->homePage());
}

$permission = $route['permission'] ?? null;
$roles = $route['roles'] ?? [];

if ($permission && !$auth->can($permission)) {
    $flash->error('Tu perfil no tiene permiso para acceder a esta seccion.');
    redirect($auth->homePage());
}

if ($roles && !$auth->hasRole($roles)) {
    $flash->error('Esta seccion no esta disponible para tu perfil.');
    redirect($auth->homePage());
}

$title = $route['title'];

require __DIR__ . '/app/Pages/' . $route['file'];
