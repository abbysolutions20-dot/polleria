<?php

declare(strict_types=1);

namespace App\Core;

final class Flash
{
    private const SESSION_KEY = '_flash_messages';

    public function success(string $message): void
    {
        $this->push('success', $message);
    }

    public function error(string $message): void
    {
        $this->push('error', $message);
    }

    public function info(string $message): void
    {
        $this->push('info', $message);
    }

    public function all(): array
    {
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        return $messages;
    }

    private function push(string $type, string $message): void
    {
        $_SESSION[self::SESSION_KEY] ??= [];
        $_SESSION[self::SESSION_KEY][] = compact('type', 'message');
    }
}
