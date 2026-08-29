<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\NotificationService;

$result = NotificationService::dispatchPending(50);
echo sprintf("Notifications: %d sent, %d failed, %d waiting for provider configuration.\n", $result['sent'], $result['failed'], $result['waiting']);
