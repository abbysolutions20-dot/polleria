<?php

declare(strict_types=1);

if (!function_exists('app_config')) {
    function app_config(?string $key = null, mixed $default = null): mixed
    {
        $config = $GLOBALS['app_config'] ?? [];

        if ($key === null) {
            return $config;
        }

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $configured = trim((string) app_config('app.base_url', ''), '/');

        if ($configured !== '') {
            return '/' . $configured;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(str_replace('/index.php', '', $scriptName), '/');

        return $base === '' ? '' : $base;
    }
}

if (!function_exists('url')) {
    function url(string $page = 'dashboard', array $params = []): string
    {
        $query = http_build_query(array_merge(['page' => $page], $params));

        return sprintf('%s/index.php?%s', base_url(), $query);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return sprintf('%s/%s', base_url(), ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $page, array $params = []): never
    {
        header('Location: ' . url($page, $params));
        exit;
    }
}

if (!function_exists('h')) {
    function h(string|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money(float|int|string|null $value): string
    {
        $symbol = (string) app_config('app.currency', 'S/');

        return $symbol . ' ' . number_format((float) $value, 2);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(string|null $value, string $format = 'd/m/Y H:i'): string
    {
        if (!$value) {
            return '-';
        }

        return date($format, strtotime($value));
    }
}

if (!function_exists('format_date')) {
    function format_date(string|null $value, string $format = 'd/m/Y'): string
    {
        if (!$value) {
            return '-';
        }

        return date($format, strtotime($value));
    }
}

if (!function_exists('badge_class')) {
    function badge_class(string $status): string
    {
        $normalized = strtoupper($status);

        return match ($normalized) {
            'ABIERTO', 'ABIERTA', 'VALIDADO', 'EMITIDO', 'LISTA', 'DISPONIBLE' => 'badge badge--success',
            'PENDIENTE', 'BORRADOR', 'EN_PREPARACION', 'EN_RUTA', 'REGISTRADO' => 'badge badge--warning',
            'ANULADO', 'CERRADO', 'NO_ENTREGADO', 'INACTIVO' => 'badge badge--danger',
            default => 'badge badge--neutral',
        };
    }
}

if (!function_exists('nav_active')) {
    function nav_active(string $page): string
    {
        return (($_GET['page'] ?? 'dashboard') === $page) ? 'is-active' : '';
    }
}

if (!function_exists('old_value')) {
    function old_value(string $key, mixed $default = ''): mixed
    {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('render_page')) {
    function render_page(string $title, string $template, array $params = []): void
    {
        global $auth, $flash;

        $content = App\Core\View::render($template, $params);

        echo App\Core\View::render('layout', [
            'title' => $title,
            'content' => $content,
            'auth' => $auth,
            'flashMessages' => $flash->all(),
        ]);
    }
}
