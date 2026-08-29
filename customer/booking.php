<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\AddressService;
use App\BillingService;
use App\BookingService;
use App\CustomerAuth;
use App\PaymentService;
use App\ProofOfDeliveryService;

CustomerAuth::requireCustomer();
$bookingId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$booking = $bookingId === false ? null : BookingService::findForCustomer((int) $bookingId, (int) CustomerAuth::id());
if ($booking === null) { http_response_code(404); exit('Booking not found.'); }
$pickup = json_decode((string) $booking['pickup_snapshot_json'], true) ?: [];
$delivery = json_decode((string) $booking['delivery_snapshot_json'], true) ?: [];
$documents = array_values(array_filter(BillingService::allForCustomer((int) CustomerAuth::id()), static fn(array $document): bool => (int) $document['booking_id'] === (int) $booking['id']));
$payment = PaymentService::latestForBooking((int) $booking['id'], (int) CustomerAuth::id());
$proof = $booking['shipment_id'] === null ? null : ProofOfDeliveryService::findByShipment((int) $booking['shipment_id']);
$pageTitle = 'Booking ' . $booking['booking_number'];
require dirname(__DIR__) . '/app/views/partials/public-header.php';
?>
<section class="account-heading compact"><div class="container"><div><span class="page-eyebrow">Booking <?= e($booking['booking_number']) ?></span><h1><?= e($booking['service_name']) ?></h1><p><?= e(AddressService::formatted($pickup)) ?> to <?= e(AddressService::formatted($delivery)) ?></p></div><span class="account-status"><?= e(ucwords(str_replace('_', ' ', (string) $booking['status']))) ?></span></div></section>
<section class="account-section"><div class="container"><?php require __DIR__ . '/_nav.php'; ?><div class="row g-4"><div class="col-lg-7"><div class="easy-card mb-4"><div class="d-flex justify-content-between gap-3 mb-4"><div><span class="section-kicker">Amount due</span><h2 class="booking-total"><?= e($booking['currency']) ?> <?= e(number_format((float) $booking['total_amount'], 2)) ?></h2></div><span class="status-pill align-self-start"><?= e(ucfirst((string) $booking['payment_status'])) ?></span></div><dl class="price-lines"><div><dt>Base charge</dt><dd><?= e(number_format((float) $booking['base_amount'], 2)) ?></dd></div><div><dt>Weight charge</dt><dd><?= e(number_format((float) $booking['weight_amount'], 2)) ?></dd></div><div><dt>Fuel surcharge</dt><dd><?= e(number_format((float) $booking['fuel_amount'], 2)) ?></dd></div><div><dt>Insurance</dt><dd><?= e(number_format((float) $booking['insurance_amount'], 2)) ?></dd></div><div><dt>Packaging</dt><dd><?= e(number_format((float) $booking['packaging_amount'], 2)) ?></dd></div><div><dt>Tax</dt><dd><?= e(number_format((float) $booking['tax_amount'], 2)) ?></dd></div></dl>
<?php if ($booking['payment_status'] === 'unpaid'): ?><?php if (PaymentService::enabled()): ?><form method="post" action="<?= e(url('controller/router.php?action=payment.initialize')) ?>"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>"><button class="easy-btn w-100" type="submit"><i class="bi bi-lock"></i> Pay securely with Paystack</button></form><?php else: ?><div class="alert alert-info mt-4 mb-0"><strong>Online payment is not enabled yet.</strong><br>Contact <?= e(support_phone()) ?> and quote booking <?= e($booking['booking_number']) ?>, or use an available corporate account.</div><?php endif; ?><?php elseif ($booking['payment_status'] === 'corporate_credit'): ?><div class="alert alert-success mt-4 mb-0"><i class="bi bi-buildings"></i> Approved against corporate credit. Operations will prepare the shipment.</div><?php elseif ($booking['payment_status'] === 'paid'): ?><div class="alert alert-success mt-4 mb-0"><i class="bi bi-check-circle"></i> Payment has been verified. Operations will prepare the shipment.</div><?php else: ?><div class="alert alert-warning mt-4 mb-0">Payment is under review. Please contact support before retrying.</div><?php endif; ?></div>
<div class="easy-card"><h2 class="h4 mb-3">Package</h2><p><strong><?= e($booking['package_description']) ?></strong></p><div class="row g-3"><div class="col-6 col-md-3"><small>Actual</small><strong class="d-block"><?= e($booking['weight_kg']) ?> kg</strong></div><div class="col-6 col-md-3"><small>Volumetric</small><strong class="d-block"><?= e($booking['volumetric_weight_kg']) ?> kg</strong></div><div class="col-6 col-md-3"><small>Chargeable</small><strong class="d-block"><?= e($booking['chargeable_weight_kg']) ?> kg</strong></div><div class="col-6 col-md-3"><small>Quote valid to</small><strong class="d-block"><?= e(date('j M, g:i A', strtotime((string) $booking['quote_expires_at']))) ?></strong></div></div></div></div>
<div class="col-lg-5"><div class="easy-card mb-4"><h2 class="h4 mb-3">Documents</h2><?php foreach ($documents as $document): ?><a class="document-link" href="<?= e(url('customer/document.php?id=' . $document['id'])) ?>" target="_blank"><i class="bi bi-file-earmark-text"></i><span><strong><?= e(ucfirst((string) $document['document_type'])) ?></strong><small><?= e($document['document_number']) ?></small></span><i class="bi bi-box-arrow-up-right"></i></a><?php endforeach; ?></div>
<?php if ($booking['tracking_number']): ?><div class="easy-card mb-4"><div class="card-icon"><i class="bi bi-box-seam"></i></div><h2>Shipment created</h2><p>Tracking number <strong><?= e($booking['tracking_number']) ?></strong></p><a class="easy-btn outline" href="<?= e(url('tracking.php?tracking_id=' . rawurlencode((string) $booking['tracking_number']))) ?>">Track shipment</a></div><?php endif; ?>
<?php if ($proof !== null): ?><div class="easy-card"><div class="card-icon"><i class="bi bi-check2-circle"></i></div><h2>Proof of delivery</h2><p>Received by <strong><?= e($proof['recipient_name']) ?></strong> on <?= e(date('j M Y, g:i A', strtotime((string) $proof['delivered_at']))) ?>.</p><a class="easy-btn outline" href="<?= e(url('proof.php?id=' . $proof['id'])) ?>">View proof</a></div><?php endif; ?>
</div></div></div></section>
<?php require dirname(__DIR__) . '/app/views/partials/public-footer.php'; ?>
