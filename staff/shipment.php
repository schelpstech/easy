<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\ShipmentService;
use App\ProofOfDeliveryService;
use App\RiderService;

Auth::requireStaff();
$shipmentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$record = $shipmentId === false ? null : ShipmentService::find((int) $shipmentId);
if ($record === null) {
    http_response_code(404);
    exit('Shipment not found.');
}
$shipment = $record['shipment'];
$statusLabels = ShipmentService::statuses();
$nextStatuses = ShipmentService::allowedNextStatuses((string) $shipment['status']);
unset($nextStatuses['delivered']);
$proof = ProofOfDeliveryService::findByShipment((int) $shipment['id']);
$assignment = RiderService::assignmentForShipment((int) $shipment['id']);
$riders = RiderService::all();
$staffTitle = 'Shipment ' . $shipment['tracking_number'];
require __DIR__ . '/_header.php';
?>
<div class="row g-4">
    <div class="col-xl-7">
        <section class="staff-card mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><span class="text-muted">Tracking number</span><h2 class="h3 mb-0"><?= e($shipment['tracking_number']) ?></h2></div><span class="staff-badge align-self-start"><?= e($statusLabels[$shipment['status']] ?? $shipment['status']) ?></span></div>
            <div class="row g-3"><div class="col-md-6"><small class="text-muted">Customer</small><p class="mb-0"><strong><?= e($shipment['customer_name']) ?></strong><br><?= e($shipment['customer_phone']) ?><?php if ($shipment['customer_email']): ?><br><?= e($shipment['customer_email']) ?><?php endif; ?></p></div><div class="col-md-6"><small class="text-muted">Route</small><p class="mb-0"><strong><?= e($shipment['origin']) ?> → <?= e($shipment['destination']) ?></strong><br><?= e($shipment['service_type']) ?></p></div><div class="col-md-6"><small class="text-muted">Package</small><p class="mb-0"><?= e($shipment['package_description']) ?><?= $shipment['weight_kg'] ? '<br>' . e($shipment['weight_kg']) . ' kg' : '' ?></p></div><div class="col-md-6"><small class="text-muted">Expected delivery</small><p class="mb-0"><?= $shipment['expected_delivery_at'] ? e(date('D, j M Y', strtotime((string) $shipment['expected_delivery_at']))) : 'Not set' ?></p></div></div>
            <a class="staff-btn light mt-4" target="_blank" href="<?= e(url('tracking.php?tracking_id=' . rawurlencode((string) $shipment['tracking_number']))) ?>"><i class="bi bi-box-arrow-up-right"></i> View public tracking</a>
        </section>
        <section class="staff-card"><h2 class="h4 mb-4">Shipment history</h2><ol class="staff-timeline"><?php foreach ($record['events'] as $event): ?><li><h3><?= e($event['title']) ?></h3><p class="mb-1 text-muted"><?= e($event['description'] ?: 'No additional note.') ?></p><small><?= e($event['location'] ?: 'Location not specified') ?> · <?= e(date('j M Y, g:i A', strtotime((string) $event['event_time']))) ?> · <?= $event['is_public'] ? 'Public' : 'Internal' ?></small></li><?php endforeach; ?></ol></section>
    </div>
    <div class="col-xl-5">
        <section class="staff-card mb-4"><h2 class="h4 mb-2">Rider assignment</h2>
        <?php if ($assignment): ?><p class="mb-3"><strong><?= e($assignment['full_name']) ?></strong><br><span class="text-muted"><?= e($assignment['rider_code']) ?> · <?= e(ucfirst((string) $assignment['vehicle_type'])) ?><?= $assignment['vehicle_registration'] ? ' · ' . e($assignment['vehicle_registration']) : '' ?></span></p><div class="d-flex flex-wrap gap-2"><span class="staff-badge"><i class="bi bi-broadcast-pin"></i>&nbsp; <?= $assignment['location_sharing_enabled'] ? 'Live sharing on' : 'Live sharing off' ?></span><form method="post" action="<?= e(url('controller/router.php?action=staff.rider.unassign')) ?>"><?= csrf_field() ?><input type="hidden" name="shipment_id" value="<?= e($shipment['id']) ?>"><button class="staff-btn light" type="submit">Unassign</button></form></div>
        <?php elseif (in_array($shipment['status'], ['booked','received','picked_up','in_transit','at_hub','out_for_delivery','delivery_failed','on_hold'], true)): ?><p class="text-muted">Assign one available rider. A rider can only hold one active shipment.</p><form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.rider.assign')) ?>"><?= csrf_field() ?><input type="hidden" name="shipment_id" value="<?= e($shipment['id']) ?>"><div class="mb-3"><label for="assign-rider">Available rider *</label><select class="form-select" id="assign-rider" name="rider_id" required><option value="">Choose rider</option><?php foreach ($riders as $rider): if ($rider['availability_status'] !== 'available' || $rider['user_status'] !== 'active') { continue; } ?><option value="<?= e($rider['id']) ?>"><?= e($rider['full_name']) ?> · <?= e(ucfirst((string) $rider['vehicle_type'])) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label for="assignment-note">Dispatch note</label><textarea class="form-control" id="assignment-note" name="assignment_note"></textarea></div><button class="staff-btn" type="submit">Assign shipment</button></form>
        <?php else: ?><p class="text-muted mb-0">This shipment is closed and cannot be assigned.</p><?php endif; ?></section>
        <?php if ($shipment['status'] === 'out_for_delivery' && $proof === null): ?><section class="staff-card mb-4"><h2 class="h4 mb-2">Proof of delivery</h2><p class="text-muted">Recipient details are required to close this delivery. A photo is optional and stored privately.</p><form class="staff-form" method="post" enctype="multipart/form-data" action="<?= e(url('controller/router.php?action=staff.pod.create')) ?>"><?= csrf_field() ?><input type="hidden" name="shipment_id" value="<?= e($shipment['id']) ?>"><div class="mb-3"><label for="pod-recipient">Received by *</label><input class="form-control" id="pod-recipient" name="recipient_name" required></div><div class="mb-3"><label for="pod-time">Delivery time *</label><input class="form-control" type="datetime-local" id="pod-time" name="delivered_at" value="<?= e(date('Y-m-d\TH:i')) ?>" required></div><div class="mb-3"><label for="pod-note">Delivery note</label><textarea class="form-control" id="pod-note" name="delivery_note" placeholder="Condition, landmark or handover note"></textarea></div><div class="mb-3"><label for="pod-photo">Delivery photo <small>(JPEG, PNG or WebP; max 5 MB)</small></label><input class="form-control" type="file" id="pod-photo" name="delivery_photo" accept="image/jpeg,image/png,image/webp"></div><div class="row g-3 mb-4"><div class="col-6"><label for="pod-lat">Latitude</label><input class="form-control" type="number" id="pod-lat" name="latitude" min="-90" max="90" step="0.0000001"></div><div class="col-6"><label for="pod-lng">Longitude</label><input class="form-control" type="number" id="pod-lng" name="longitude" min="-180" max="180" step="0.0000001"></div></div><button class="staff-btn" type="submit"><i class="bi bi-check2-circle"></i> Save proof and mark delivered</button></form></section><?php elseif ($proof !== null): ?><section class="staff-card mb-4"><h2 class="h4">Delivery completed</h2><p>Received by <strong><?= e($proof['recipient_name']) ?></strong> on <?= e(date('j M Y, g:i A', strtotime((string) $proof['delivered_at']))) ?>.</p><a class="staff-btn light" href="<?= e(url('proof.php?id=' . $proof['id'])) ?>" target="_blank">View proof of delivery</a></section><?php endif; ?>
        <section class="staff-card"><h2 class="h4 mb-2">Record next milestone</h2><p class="text-muted">Only valid next statuses are available. Delivery completion uses the proof form above.</p>
            <?php if ($nextStatuses === []): ?><div class="alert alert-info mb-0">This shipment is in a final state. No further status transition is available.</div><?php else: ?>
            <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.shipment.event')) ?>"><?= csrf_field() ?><input type="hidden" name="shipment_id" value="<?= e($shipment['id']) ?>">
                <div class="mb-3"><label for="event-status">New status *</label><select class="form-select" id="event-status" name="status" required><option value="">Choose status</option><?php foreach ($nextStatuses as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label for="event-title">Public title</label><input class="form-control" id="event-title" name="title" placeholder="Uses the status label when empty"></div>
                <div class="mb-3"><label for="event-location">Location</label><input class="form-control" id="event-location" name="location"></div>
                <div class="mb-3"><label for="event-time">Event time *</label><input class="form-control" type="datetime-local" id="event-time" name="event_time" value="<?= e(date('Y-m-d\TH:i')) ?>" required></div>
                <div class="mb-3"><label for="event-description">Description</label><textarea class="form-control" id="event-description" name="description"></textarea></div>
                <div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="event-public" name="is_public" value="1" checked><label class="form-check-label" for="event-public">Show this event on public tracking</label></div>
                <button class="staff-btn" type="submit">Save milestone</button>
            </form><?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
