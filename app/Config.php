<?php

declare(strict_types=1);

namespace App;

final class Config
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $values = parse_ini_file($file, false, INI_SCANNER_RAW);
        if (!is_array($values)) {
            return;
        }

        foreach ($values as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                self::$values[$key] = (string) $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $environmentValue = getenv($key);
        if ($environmentValue !== false) {
            return $environmentValue;
        }

        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}

