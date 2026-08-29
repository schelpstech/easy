<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\ReportService;
use App\ShipmentService;

Auth::requireRole(['admin', 'dispatcher']);
$report = ReportService::dashboard();
$metrics = $report['metrics'];
$recentShipments = ShipmentService::all(8);
$statusLabels = ShipmentService::statuses();
$maxBookings = max(1, ...array_column($report['daily'], 'booking_count'));
$staffTitle = 'Operations dashboard';
require __DIR__ . '/_header.php';
?>
<section class="dashboard-intro mb-4">
    <div><span>Live operating view</span>
        <h2>What needs attention today?</h2>
        <p>Volume and collections cover the last 30 days in <?= e($report['currency']) ?>. Operational queues are current.</p>
    </div>
    <div><small>Fresh at</small><strong><?= e(date('g:i A', strtotime((string) $report['generated_at']))) ?></strong><a href="<?= e(url('staff/reports.php')) ?>">Open full reports <i class="bi bi-arrow-right"></i></a></div>
</section>
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="staff-card stat-card"><span>Bookings · 30 days</span><strong><?= e($metrics['bookings']) ?></strong><small><?= e($report['currency']) ?> <?= e(number_format((float) $metrics['booking_value'], 2)) ?> booked</small></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="staff-card stat-card"><span>Collected · 30 days</span><strong class="money-value"><?= e($report['currency']) ?> <?= e(number_format((float) $metrics['collections'], 0)) ?></strong><small>Verified online + posted corporate payments</small></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="staff-card stat-card <?= $metrics['unassigned_shipments'] > 0 ? 'needs-attention' : '' ?>"><span>Unassigned active</span><strong><?= e($metrics['unassigned_shipments']) ?></strong><small>of <?= e($metrics['active_shipments']) ?> active shipments</small></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="staff-card stat-card"><span>Available riders</span><strong><?= e($metrics['available_riders']) ?></strong><small>Ready for dispatch</small></div>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <section class="staff-card h-100">
            <div class="d-flex justify-content-between gap-3 mb-4">
                <div>
                    <h2 class="h4 mb-1">Daily booking demand</h2>
                    <p class="text-muted mb-0">Booking count by creation date; cancelled rows remain in volume.</p>
                </div><span class="staff-badge">30 days</span>
            </div>
            <div class="mini-bars" aria-label="Daily booking count chart"><?php foreach ($report['daily'] as $day): ?><div class="mini-bar" title="<?= e(date('j M', strtotime((string) $day['day']))) ?>: <?= e($day['booking_count']) ?> bookings"><span style="height:<?= e(max(4, round(((int) $day['booking_count'] / $maxBookings) * 100))) ?>%"></span></div><?php endforeach; ?></div>
            <div class="chart-axis"><span><?= e(date('j M', strtotime((string) $report['range']['from']))) ?></span><span><?= e(date('j M', strtotime((string) $report['range']['to']))) ?></span></div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="staff-card h-100">
            <h2 class="h4 mb-3">Financial watch</h2>
            <div class="watch-value"><span>Corporate outstanding</span><strong><?= e($report['currency']) ?> <?= e(number_format((float) $metrics['corporate_outstanding'], 2)) ?></strong></div>
            <div class="watch-value"><span>Delivered in period</span><strong><?= e($metrics['delivered']) ?></strong></div>
            <p class="small text-muted mb-0">Outstanding is debits less posted payments in <?= e($report['currency']) ?>. It is not mixed with other currencies.</p>
        </section>
    </div>
</div>
<div class="staff-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="h4 mb-1">Recent shipments</h2>
            <p class="text-muted mb-0">Latest bookings and delivery activity.</p>
        </div><a class="staff-btn" href="<?= e(url('staff/shipments.php#new-shipment')) ?>"><i class="bi bi-plus-lg"></i> New shipment</a>
    </div>
    <div class="table-responsive">
        <table class="table staff-table">
            <thead>
                <tr>
                    <th>Tracking</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th>Status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody><?php if ($recentShipments === []): ?><tr>
                        <td colspan="5" class="text-center text-muted py-4">No shipments have been created yet.</td>
                    </tr><?php endif; ?><?php foreach ($recentShipments as $shipment): ?><tr>
                        <td><a href="<?= e(url('staff/shipment.php?id=' . $shipment['id'])) ?>"><strong><?= e($shipment['tracking_number']) ?></strong></a></td>
                        <td><?= e($shipment['customer_name']) ?></td>
                        <td><?= e($shipment['origin']) ?> → <?= e($shipment['destination']) ?></td>
                        <td><span class="staff-badge"><?= e($statusLabels[$shipment['status']] ?? $shipment['status']) ?></span></td>
                        <td><?= e(date('j M Y, g:i A', strtotime((string) $shipment['updated_at']))) ?></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>