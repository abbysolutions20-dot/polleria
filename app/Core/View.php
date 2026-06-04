<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $params = []): string
    {
        $file = dirname(__DIR__) . '/Views/' . $template . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('La vista %s no existe.', $template));
        }

        extract($params, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
