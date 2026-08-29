<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Config;
$database = (string) Config::get('DB_NAME', 'easyway_logistics');
if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
    fwrite(STDERR, "Invalid DB_NAME.\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;charset=utf8mb4',
    Config::get('DB_HOST', '127.0.0.1'),
    Config::get('DB_PORT', '3306')
);

try {
    $pdo = new PDO($dsn, Config::get('DB_USER', 'root'), Config::get('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        $database
    ));
    $pdo->exec('USE `' . $database . '`');

    $migrationFile = dirname(__DIR__) . '/database/migrations/2026_08_29_stage1_logistics.sql';
    $sql = file_get_contents($migrationFile);
    if ($sql === false) {
        throw new RuntimeException('Unable to read the Stage 1 migration.');
    }

    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }

    echo "Stage 1 database installed successfully: {$database}\n";
    echo "Next: create the first account with php tools/create_staff.php --email=you@example.com --name=\"Your Name\" --role=admin\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Installation failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
