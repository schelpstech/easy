<?php

declare(strict_types=1);

use App\Config;

define('EASYWAY_ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

Config::load(EASYWAY_ROOT . DIRECTORY_SEPARATOR . '.env');

$timezone = Config::get('APP_TIMEZONE', 'Africa/Lagos');
date_default_timezone_set(is_string($timezone) && $timezone !== '' ? $timezone : 'Africa/Lagos');

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $sessionPath = EASYWAY_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0770, true);
    }
    session_save_path($sessionPath);
    session_name('easyway_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (PHP_SAPI === 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $_SESSION = [];
}

ini_set('display_errors', Config::bool('APP_DEBUG', false) ? '1' : '0');
error_reporting(E_ALL);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
