<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public function input(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public function validate(?string $token): bool
    {
        if (!$token || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals((string) $_SESSION[self::SESSION_KEY], $token);
    }

    public function guard(?string $token): void
    {
        if (!$this->validate($token)) {
            throw new RuntimeException('La solicitud no pasó la validación de seguridad. Recarga la página e inténtalo otra vez.');
        }
    }
}
