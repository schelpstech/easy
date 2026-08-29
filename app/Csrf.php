<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    private const SESSION_KEY = 'easyway_csrf_token';

    public static function token(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION[self::SESSION_KEY])
            && is_string($_SESSION[self::SESSION_KEY])
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function rotate(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::token();
    }
}

