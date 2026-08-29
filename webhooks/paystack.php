<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\PaymentService;

header('Content-Type: application/json; charset=utf-8');
$payload = file_get_contents('php://input') ?: '';
$signature = (string) ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '');
try {
    $result = PaymentService::processWebhook($payload, $signature);
    http_response_code(200);
    echo json_encode(['ok' => true, 'duplicate' => $result['duplicate']]);
} catch (RuntimeException $exception) {
    error_log('Easyway Paystack webhook rejected: ' . $exception->getMessage());
    http_response_code(str_contains(strtolower($exception->getMessage()), 'signature') ? 401 : 422);
    echo json_encode(['ok' => false]);
} catch (Throwable $exception) {
    error_log('Easyway Paystack webhook failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
