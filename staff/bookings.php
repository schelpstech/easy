<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\BookingService;

Auth::requireStaff();
$bookings = BookingService::all();
$staffTitle = 'Online bookings';
require __DIR__ . '/_header.php';
?>
<section class="staff-card"><div class="mb-4"><h2 class="h4 mb-1">Customer bookings</h2><p class="text-muted mb-0">Verified gateway payments and approved corporate-credit bookings can become tracked shipments.</p></div><div class="table-responsive"><table class="table staff-table"><thead><tr><th>Booking</th><th>Customer</th><th>Service</th><th>Total</th><th>Payment</th><th>Fulfilment</th><th></th></tr></thead><tbody><?php if ($bookings === []): ?><tr><td colspan="7" class="text-center text-muted py-4">No online bookings yet.</td></tr><?php endif; ?><?php foreach ($bookings as $booking): ?><tr><td><strong><?= e($booking['booking_number']) ?></strong><br><small><?= e(date('j M Y, g:i A', strtotime((string) $booking['created_at']))) ?></small></td><td><?= e($booking['full_name']) ?><br><small><?= e($booking['phone']) ?></small></td><td><?= e($booking['service_name']) ?><br><small><?= e($booking['chargeable_weight_kg']) ?> kg</small></td><td><?= e($booking['currency']) ?> <?= e(number_format((float) $booking['total_amount'], 2)) ?></td><td><span class="staff-badge"><?= e(ucwords(str_replace('_', ' ', (string) $booking['payment_status']))) ?></span></td><td><?= e(ucwords(str_replace('_', ' ', (string) $booking['status']))) ?><?php if ($booking['tracking_number']): ?><br><a href="<?= e(url('staff/shipment.php?id=' . $booking['shipment_id'])) ?>"><?= e($booking['tracking_number']) ?></a><?php endif; ?></td><td><?php if (in_array($booking['payment_status'], ['paid','corporate_credit'], true) && $booking['shipment_id'] === null): ?><form method="post" action="<?= e(url('controller/router.php?action=staff.booking.convert')) ?>"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>"><button class="staff-btn" type="submit">Create shipment</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/_footer.php'; ?>
