<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$pageTitle = 'International Destinations';
$pageDescription = 'International document, parcel and cargo delivery enquiries from Nigeria to supported global destinations.';
require __DIR__ . '/app/views/partials/public-header.php';

$regions = [
    ['bi-globe-europe-africa', 'West and East Africa', 'Selected regional routes for documents, parcels and commercial consignments.'],
    ['bi-buildings', 'United Kingdom and Europe', 'International parcel and document enquiries to supported European destinations.'],
    ['bi-globe-americas', 'United States and Canada', 'Tracked document and parcel services subject to destination and item checks.'],
    ['bi-globe-central-south-asia', 'Middle East and Asia', 'Supported air-courier and cargo routes confirmed individually by our team.'],
];
?>
<section class="page-hero">
    <div class="container"><span class="page-eyebrow"><i class="bi bi-globe2"></i> International destinations</span>
        <h1>Send beyond Nigeria with the right route and documents.</h1>
        <p>International availability changes by destination, parcel type and carrier. We confirm the route, documentation and estimated transit window before booking.</p>
    </div>
</section>
<section class="content-section soft">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($regions as [$icon, $name, $description]): ?>
                <div class="col-md-6">
                    <article class="easy-card">
                        <div class="card-icon"><i class="bi <?= e($icon) ?>"></i></div>
                        <h2><?= e($name) ?></h2>
                        <p><?= e($description) ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="content-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7"><span class="section-kicker">Prepare your enquiry</span>
                <h2 class="section-heading">What we need to check an international route.</h2>
                <ul class="check-list mt-4">
                    <li><i class="bi bi-check-lg"></i><span>Destination country, city and postal code</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Accurate item description, quantity and declared value</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Actual weight and parcel dimensions</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Sender and recipient identification/contact details where required</span></li>
                </ul>
            </div>
            <div class="col-lg-5">
                <div class="info-band h-100">
                    <h2>Restricted and prohibited items</h2>
                    <p>Liquids, food, medicine, batteries, chemicals, cash, precious items and other controlled goods may need special documents or may not be accepted on a route.</p>
                    <p class="mb-4">Do not conceal an item description. Our team will help check eligibility before payment.</p><a class="easy-btn orange" href="<?= e(url('quote.php')) ?>">Check my destination</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>