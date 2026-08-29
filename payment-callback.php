<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\CustomerAuth;
use App\Flash;
use App\PaymentService;

try {
    $reference = (string) ($_GET['reference'] ?? '');
    $bookingId = PaymentService::verifyReference($reference);
    Flash::set('success', 'Payment verified successfully. Your receipt is ready.');
    if (CustomerAuth::check() && $bookingId !== null) {
        redirect('customer/booking.php?id=' . $bookingId);
    }
    redirect('customer/login.php');
} catch (Throwable $exception) {
    error_log('Easyway payment callback failed: ' . $exception->getMessage());
    Flash::set('danger', $exception->getMessage());
    redirect(CustomerAuth::check() ? 'customer/index.php' : 'customer/login.php');
}
