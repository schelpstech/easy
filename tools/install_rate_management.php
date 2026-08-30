<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $pdo = App\Database::connection();
    $pdo->query('SELECT id,code,name,status FROM rate_zones LIMIT 0');
    $pdo->query('SELECT id,service_code,service_name FROM rate_cards LIMIT 0');
    $pdo->exec('CREATE TABLE IF NOT EXISTS rate_services (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(40) NOT NULL,
        name VARCHAR(80) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT "active",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_rate_services_code (code),
        KEY idx_rate_services_status_name (status,name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO rate_services (code,name) VALUES (?,?) ON DUPLICATE KEY UPDATE code = VALUES(code)');
    foreach (App\PricingService::DEFAULT_SERVICES as $code => $name) { $insert->execute([$code,$name]); }
    // Preserve any older custom service codes without rewriting current labels or status.
    foreach ($pdo->query('SELECT service_code, MIN(service_name) AS service_name FROM rate_cards GROUP BY service_code')->fetchAll() as $rate) {
        $insert->execute([$rate['service_code'],$rate['service_name']]);
    }
    $pdo->commit();
    echo "Rate management installed. Existing zones, prices and bookings were preserved.\n";
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, 'Installation failed: ' . $exception->getMessage() . PHP_EOL); exit(1);
}
