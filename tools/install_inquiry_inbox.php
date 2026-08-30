<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $pdo = App\Database::connection();
    // Verify prerequisite tables without modifying existing inquiry/customer data.
    $pdo->query('SELECT id,status,quoted_amount,currency FROM quote_requests LIMIT 0');
    $pdo->query('SELECT id,status FROM contact_messages LIMIT 0');
    $pdo->query('SELECT id,status FROM notification_outbox LIMIT 0');
    $pdo->exec('CREATE TABLE IF NOT EXISTS inquiry_activities (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        inquiry_type VARCHAR(10) NOT NULL,
        inquiry_id BIGINT UNSIGNED NOT NULL,
        inquiry_reference VARCHAR(30) NOT NULL,
        kind VARCHAR(20) NOT NULL,
        staff_user_id BIGINT UNSIGNED NULL,
        notification_id BIGINT UNSIGNED NULL,
        subject VARCHAR(190) NULL,
        body TEXT NOT NULL,
        metadata_json LONGTEXT NULL,
        request_token CHAR(64) NOT NULL,
        request_hash CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_inquiry_request_token (request_token),
        UNIQUE KEY uq_inquiry_notification (notification_id),
        KEY idx_inquiry_history (inquiry_type,inquiry_id,id),
        CONSTRAINT fk_inquiry_activity_staff FOREIGN KEY (staff_user_id) REFERENCES staff_users(id) ON DELETE SET NULL,
        CONSTRAINT fk_inquiry_activity_notification FOREIGN KEY (notification_id) REFERENCES notification_outbox(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    echo "Inquiry inbox installed. Existing quotes, messages and notification settings were preserved.\n";
} catch (Throwable $e) { fwrite(STDERR, 'Installation failed: ' . $e->getMessage() . PHP_EOL); exit(1); }
