<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Auth;
use App\CustomerAuth;
use App\ProofOfDeliveryService;

$proofId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$proof = $proofId === false ? null : ProofOfDeliveryService::find((int) $proofId);
if ($proof === null) {
    http_response_code(404);
    exit('Proof of delivery not found.');
}
$staff = Auth::user();
$authorized = ($staff !== null && ProofOfDeliveryService::staffCanAccess((int) $proof['id'], (int) $staff['id'], (string) $staff['role']))
    || (CustomerAuth::check() && ProofOfDeliveryService::customerCanAccess((int) $proof['id'], (int) CustomerAuth::id()));
if (!$authorized) {
    http_response_code(403);
    exit('You are not authorized to view this delivery record.');
}
$full = ProofOfDeliveryService::findByShipment((int) $proof['shipment_id']);
$pageTitle = 'Proof of Delivery';
require __DIR__ . '/app/views/partials/public-header.php';
?>
<section class="content-section soft">
    <div class="container">
        <article class="pod-sheet">
            <header>
                <div><span class="section-kicker">Verified delivery record</span>
                    <h1>Proof of delivery</h1>
                    <p>Tracking <?= e($full['tracking_number']) ?></p>
                </div><i class="bi bi-check2-circle"></i>
            </header>
            <div class="row g-4">
                <div class="col-md-6"><small>Received by</small>
                    <h2><?= e($full['recipient_name']) ?></h2>
                    <p><?= e($full['delivery_note'] ?: 'No additional delivery note.') ?></p>
                </div>
                <div class="col-md-6"><small>Delivered at</small>
                    <h2><?= e(date('j F Y, g:i A', strtotime((string) $full['delivered_at']))) ?></h2>
                    <p><?= e($full['destination']) ?></p>
                </div>
            </div><?php if ($full['photo_path']): ?><img class="pod-photo" src="<?= e(url('proof-file.php?id=' . $full['id'])) ?>" alt="Delivery handover evidence"><?php endif; ?><footer><span>Recorded by <?= e($full['staff_name']) ?></span><?php if ($full['latitude'] !== null && $full['longitude'] !== null): ?><span>Coordinates <?= e($full['latitude']) ?>, <?= e($full['longitude']) ?></span><?php endif; ?></footer>
        </article>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>
