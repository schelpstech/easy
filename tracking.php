<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\ShipmentService;
use App\Validator;

$pageTitle = 'Shipment Tracking';
$pageDescription = 'Track an Easyway Logistics shipment using its secure tracking number.';
$trackingNumber = strtoupper(Validator::text($_GET['tracking_id'] ?? '', 24));
$trackingResult = null;
$trackingError = '';
if ($trackingNumber !== '') {
    try {
        $trackingResult = ShipmentService::publicTracking($trackingNumber);
        if ($trackingResult === null) {
            $trackingError = 'We could not find a public shipment record for that tracking number. Check the characters and try again.';
        }
    } catch (Throwable $exception) {
        error_log('Public tracking failed: ' . $exception->getMessage());
        $trackingError = 'Tracking is temporarily unavailable. Please contact support with your tracking number.';
    }
}
$statusLabels = ShipmentService::statuses();
require __DIR__ . '/app/views/partials/public-header.php';
?>
<section class="page-hero">
    <div class="container text-center"><span class="page-eyebrow"><i class="bi bi-geo-alt"></i> Shipment tracking</span>
        <h1 class="mx-auto">See the latest verified shipment milestone.</h1>
        <p class="mx-auto">Enter the complete Easyway tracking number supplied at booking. Public tracking never displays the recipient's private contact details.</p>
    </div>
</section>
<section class="content-section soft pt-0">
    <div class="container">
        <div class="tracking-shell">
            <form class="tracking-search" method="get" action="<?= e(url('tracking.php')) ?>"><input class="form-control" type="text" name="tracking_id" value="<?= e($trackingNumber) ?>" placeholder="Example: EWL20260829ABCD2345" maxlength="19" autocomplete="off" data-tracking-input required aria-label="Easyway tracking number"><button class="easy-btn" type="submit">Track shipment <i class="bi bi-search"></i></button></form>
            <?php if ($trackingError !== ''): ?><div class="alert alert-warning mt-4" role="alert"><?= e($trackingError) ?></div><?php endif; ?>
            <?php if ($trackingResult !== null): $shipment = $trackingResult['shipment']; ?>
                <article class="tracking-summary">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div><span class="section-kicker">Tracking number</span>
                            <h2 class="mb-0"><?= e($shipment['tracking_number']) ?></h2>
                        </div><span class="status-pill"><?= e($statusLabels[$shipment['status']] ?? ucwords(str_replace('_', ' ', (string) $shipment['status']))) ?></span>
                    </div>
                    <div class="tracking-route">
                        <div><small class="text-muted">From</small>
                            <h3><?= e($shipment['origin']) ?></h3>
                        </div><i class="bi bi-arrow-right"></i>
                        <div><small class="text-muted">To</small>
                            <h3><?= e($shipment['destination']) ?></h3>
                        </div>
                    </div>
                    <?php if (!empty($shipment['expected_delivery_at'])): ?><p class="mt-4 mb-0"><strong>Expected delivery:</strong> <?= e(date('D, j M Y', strtotime((string) $shipment['expected_delivery_at']))) ?></p><?php endif; ?>
                    <section class="public-live mt-4" data-live-tracking data-endpoint="<?= e(url('api/tracking/live.php?tracking_id=' . rawurlencode((string) $shipment['tracking_number']))) ?>"><div class="d-flex flex-wrap justify-content-between align-items-start gap-3"><div><span class="section-kicker"><i class="bi bi-broadcast-pin"></i> Live rider location</span><h3 class="h5 mb-1" data-live-title>Checking for an active shared trip…</h3><p class="text-muted mb-0" data-live-message>Live location only appears while an assigned rider explicitly shares this shipment.</p></div><span class="status-pill" data-live-age>Private by default</span></div><div class="live-map mt-3" data-live-map hidden><iframe title="Approximate rider location map" loading="lazy" scrolling="no" referrerpolicy="no-referrer-when-downgrade"></iframe><a class="easy-btn outline" target="_blank" rel="noopener" data-live-link>Open map</a></div></section>
                    <ol class="tracking-timeline">
                        <?php foreach ($trackingResult['events'] as $event): ?><li>
                                <h3><?= e($event['title']) ?></h3><?php if (!empty($event['description'])): ?><p><?= e($event['description']) ?></p><?php endif; ?><?php if (!empty($event['location'])): ?><p><i class="bi bi-geo-alt"></i> <?= e($event['location']) ?></p><?php endif; ?><time datetime="<?= e(date(DATE_ATOM, strtotime((string) $event['event_time']))) ?>"><?= e(date('D, j M Y · g:i A', strtotime((string) $event['event_time']))) ?></time>
                            </li><?php endforeach; ?>
                    </ol>
                </article>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php if ($trackingResult !== null): ?><script src="<?= e(url('assets/js/live-tracking.js')) ?>" defer></script><?php endif; ?>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>
