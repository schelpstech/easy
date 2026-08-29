<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Config;

$database = (string) Config::get('DB_NAME', 'easyway_logistics');
if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) { fwrite(STDERR, "Invalid DB_NAME.\n"); exit(1); }
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', Config::get('DB_HOST', '127.0.0.1'), Config::get('DB_PORT', '3306')),
        Config::get('DB_USER', 'root'), Config::get('DB_PASSWORD', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $database));
    $pdo->exec('USE `' . $database . '`');
    foreach (['2026_08_29_stage1_logistics.sql', '2026_08_29_stage2_online_logistics.sql', '2026_08_29_stage3_logistics_management.sql'] as $migration) {
        $sql = file_get_contents(dirname(__DIR__) . '/database/migrations/' . $migration);
        if ($sql === false) { throw new RuntimeException('Unable to read migration ' . $migration . '.'); }
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) { if (trim($statement) !== '') { $pdo->exec($statement); } }
    }
    echo "Stage 3 database installed successfully: {$database}\n";
    echo "Create riders and corporate memberships in the staff dashboard before assigning live work.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Installation failed: ' . $exception->getMessage() . PHP_EOL); exit(1);
}
