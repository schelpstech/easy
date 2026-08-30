<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    App\Database::connection()->exec('CREATE TABLE IF NOT EXISTS notification_settings (
        channel VARCHAR(20) NOT NULL PRIMARY KEY,
        settings_json LONGTEXT NOT NULL,
        secret_encrypted TEXT NULL,
        version BIGINT UNSIGNED NOT NULL DEFAULT 1,
        updated_by BIGINT UNSIGNED NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    App\NotificationSettings::initializeKey();
    echo "Staff settings installed. Existing staff and notification configuration were preserved.\n";
    echo "Keep storage/private/notification-key.php in a secure backup alongside the database.\n";
} catch (Throwable $e) { fwrite(STDERR, "Installation failed: " . $e->getMessage() . "\n"); exit(1); }
