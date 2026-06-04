<?php

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/config.php';
$config = require $configPath;

date_default_timezone_set($config['app']['timezone'] ?? 'America/Lima');

$sessionPath = dirname(__DIR__) . '/storage/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_save_path($sessionPath);
    session_name('POLLERIA_POS_SESSION');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $relative = substr($class, 4);
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/Core/Helpers.php';

$GLOBALS['app_config'] = $config;

$flash = new App\Core\Flash();
$csrf = new App\Core\Csrf();

try {
    $db = App\Core\Database::connect($config['database']);
    $auth = new App\Core\Auth($db, $config['auth']);
    $orderService = new App\Services\OrderService($db);
    $masterDataService = new App\Services\MasterDataService($db);
} catch (Throwable $exception) {
    http_response_code(500);
    $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Error de conexión</title>
        <style>
            body{font-family:"Trebuchet MS","Segoe UI",sans-serif;background:#f6f1e8;color:#2a1b12;margin:0;padding:32px}
            .card{max-width:780px;margin:40px auto;background:#fffaf3;border:1px solid rgba(109,71,41,.12);border-radius:24px;padding:28px;box-shadow:0 24px 64px rgba(74,46,24,.12)}
            h1{font-family:Cambria,Georgia,serif;margin-top:0}
            code{background:rgba(64,80,41,.1);padding:2px 6px;border-radius:8px}
        </style>
    </head>
    <body>
        <div class="card">
            <h1>No se pudo conectar con MySQL</h1>
            <p>Verifica que <code>Apache</code> y <code>MySQL</code> estén encendidos en XAMPP y que la base <code>polleria_pos_pro</code> ya haya sido importada.</p>
            <p>También revisa <code>config/config.php</code> si tu usuario, puerto o contraseña son distintos.</p>
            <p><strong>Detalle técnico:</strong> <?= $message ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
