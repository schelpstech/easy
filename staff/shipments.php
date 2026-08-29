<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\ShipmentService;

Auth::requireStaff();
$shipmentState = pull_form_state('shipment');
$shipments = ShipmentService::all();
$statusLabels = ShipmentService::statuses();
$staffTitle = 'Shipments';
require __DIR__ . '/_header.php';
?>
<section id="new-shipment" class="staff-card mb-4">
    <div class="mb-4"><h2 class="h4 mb-1">Create shipment</h2><p class="text-muted mb-0">A secure Easyway tracking number and first public event will be generated automatically.</p></div>
    <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.shipment.create')) ?>" novalidate><?= csrf_field() ?><div class="row g-3">
        <div class="col-md-6 col-xl-4"><label for="customer-name">Customer name *</label><input class="form-control" id="customer-name" name="customer_name" value="<?= form_value($shipmentState, 'customer_name') ?>" required><?= form_error($shipmentState, 'customer_name') ?></div>
        <div class="col-md-6 col-xl-4"><label for="customer-phone">Customer phone *</label><input class="form-control" type="tel" id="customer-phone" name="customer_phone" value="<?= form_value($shipmentState, 'customer_phone') ?>" required><?= form_error($shipmentState, 'customer_phone') ?></div>
        <div class="col-md-6 col-xl-4"><label for="customer-email">Customer email</label><input class="form-control" type="email" id="customer-email" name="customer_email" value="<?= form_value($shipmentState, 'customer_email') ?>"><?= form_error($shipmentState, 'customer_email') ?></div>
        <div class="col-md-6"><label for="origin">Origin *</label><input class="form-control" id="origin" name="origin" value="<?= form_value($shipmentState, 'origin') ?>" required><?= form_error($shipmentState, 'origin') ?></div>
        <div class="col-md-6"><label for="destination">Destination *</label><input class="form-control" id="destination" name="destination" value="<?= form_value($shipmentState, 'destination') ?>" required><?= form_error($shipmentState, 'destination') ?></div>
        <div class="col-md-6 col-xl-4"><label for="service-type">Service *</label><select class="form-select" id="service-type" name="service_type" required><option value="">Choose service</option><?php foreach (['Standard Delivery','Express Delivery','Same-Day Delivery','International Delivery','Cargo / Freight'] as $option): ?><option value="<?= e($option) ?>" <?= form_value($shipmentState, 'service_type') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select><?= form_error($shipmentState, 'service_type') ?></div>
        <div class="col-md-3 col-xl-2"><label for="weight-kg">Weight (kg)</label><input class="form-control" type="number" step="0.01" min="0.01" id="weight-kg" name="weight_kg" value="<?= form_value($shipmentState, 'weight_kg') ?>"></div>
        <div class="col-md-3 col-xl-3"><label for="expected-date">Expected delivery</label><input class="form-control" type="date" id="expected-date" name="expected_delivery_at" value="<?= form_value($shipmentState, 'expected_delivery_at') ?>"></div>
        <div class="col-12"><label for="package-description">Package description *</label><textarea class="form-control" id="package-description" name="package_description" required><?= form_value($shipmentState, 'package_description') ?></textarea><?= form_error($shipmentState, 'package_description') ?></div>
        <div class="col-12"><button class="staff-btn" type="submit"><i class="bi bi-plus-lg"></i> Create shipment</button></div>
    </div></form>
</section>
<section class="staff-card">
    <div class="mb-3"><h2 class="h4 mb-1">Shipment register</h2><p class="text-muted mb-0">Open a shipment to record its next verified milestone.</p></div>
    <div class="table-responsive"><table class="table staff-table"><thead><tr><th>Tracking</th><th>Customer</th><th>Route</th><th>Service</th><th>Status</th><th>Created</th></tr></thead><tbody>
        <?php if ($shipments === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No shipments have been created yet.</td></tr><?php endif; ?>
        <?php foreach ($shipments as $shipment): ?><tr><td><a href="<?= e(url('staff/shipment.php?id=' . $shipment['id'])) ?>"><strong><?= e($shipment['tracking_number']) ?></strong></a></td><td><?= e($shipment['customer_name']) ?><br><small class="text-muted"><?= e($shipment['customer_phone']) ?></small></td><td><?= e($shipment['origin']) ?> → <?= e($shipment['destination']) ?></td><td><?= e($shipment['service_type']) ?></td><td><span class="staff-badge"><?= e($statusLabels[$shipment['status']] ?? $shipment['status']) ?></span></td><td><?= e(date('j M Y', strtotime((string) $shipment['created_at']))) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>

