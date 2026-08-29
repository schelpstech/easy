<?php

declare(strict_types=1);

namespace App;

final class Flash
{
    private const SESSION_KEY = 'easyway_flash';

    public static function set(string $type, string $message): void
    {
        $_SESSION[self::SESSION_KEY][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /** @return array<int, array{type:string, message:string}> */
    public static function pull(): array
    {
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        return is_array($messages) ? $messages : [];
    }
}

