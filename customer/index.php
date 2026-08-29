<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\AddressService;
use App\BillingService;
use App\BookingService;
use App\CustomerAuth;

CustomerAuth::requireCustomer();
$customer = CustomerAuth::user();
$bookings = BookingService::allForCustomer((int) CustomerAuth::id());
$addresses = AddressService::allForCustomer((int) CustomerAuth::id());
$documents = BillingService::allForCustomer((int) CustomerAuth::id());
$pageTitle = 'My Easyway Account';
require dirname(__DIR__) . '/app/views/partials/public-header.php';
?>
<section class="account-heading"><div class="container"><div><span class="page-eyebrow">Customer account</span><h1>Hello, <?= e(explode(' ', (string) $customer['name'])[0]) ?>.</h1><p>Everything for your deliveries, from booking to proof of delivery.</p></div><a class="easy-btn orange" href="<?= e(url('customer/book.php')) ?>"><i class="bi bi-plus-lg"></i> Book a shipment</a></div></section>
<section class="account-section"><div class="container"><?php require __DIR__ . '/_nav.php'; ?><div class="row g-4 mb-4"><div class="col-md-4"><div class="account-stat"><span>Bookings</span><strong><?= e(count($bookings)) ?></strong></div></div><div class="col-md-4"><div class="account-stat"><span>Saved addresses</span><strong><?= e(count($addresses)) ?></strong></div></div><div class="col-md-4"><div class="account-stat"><span>Billing documents</span><strong><?= e(count($documents)) ?></strong></div></div></div>
<div class="easy-card"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><h2 class="h4 mb-1">Recent bookings</h2><p class="text-muted mb-0">Payments and fulfilment are shown separately.</p></div><a href="<?= e(url('calculator.php')) ?>">Open calculator</a></div><div class="table-responsive"><table class="table account-table"><thead><tr><th>Booking</th><th>Service</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead><tbody><?php if ($bookings === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No bookings yet. Save two addresses, then create your first shipment.</td></tr><?php endif; ?><?php foreach ($bookings as $booking): ?><tr><td><strong><?= e($booking['booking_number']) ?></strong><br><small><?= e(date('j M Y', strtotime((string) $booking['created_at']))) ?></small></td><td><?= e($booking['service_name']) ?></td><td><?= e($booking['currency']) ?> <?= e(number_format((float) $booking['total_amount'], 2)) ?></td><td><span class="status-pill"><?= e(ucfirst((string) $booking['payment_status'])) ?></span></td><td><?= e(ucwords(str_replace('_', ' ', (string) $booking['status']))) ?></td><td><a href="<?= e(url('customer/booking.php?id=' . $booking['id'])) ?>">View</a></td></tr><?php endforeach; ?></tbody></table></div></div></div></section>
<?php require dirname(__DIR__) . '/app/views/partials/public-footer.php'; ?>
