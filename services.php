<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$pageTitle = 'Delivery Services';
$pageDescription = 'Explore Easyway local, interstate, express, errand, business and international delivery services.';
require __DIR__ . '/app/views/partials/public-header.php';

$services = [
    ['bi-box-arrow-up-right', 'Pickup and drop-off', 'Scheduled collection and delivery for documents, parcels and packaged goods.'],
    ['bi-house-check', 'Home delivery', 'Convenient doorstep delivery with a traceable Easyway shipment reference.'],
    ['bi-lightning-charge', 'Same-day delivery', 'Time-sensitive delivery on supported local routes, subject to booking time and availability.'],
    ['bi-map', 'Interstate delivery', 'Coordinated parcel movement between supported Nigerian cities and states.'],
    ['bi-basket', 'Personal errands', 'Store pickups, document runs and other clearly defined errands handled by our team.'],
    ['bi-buildings', 'Business distribution', 'Recurring dispatch support for retailers, offices and growing businesses.'],
    ['bi-globe2', 'International delivery', 'Documents and parcels to supported destinations after route and compliance checks.'],
    ['bi-airplane', 'Cargo and freight', 'Air, sea and road cargo coordination for heavier or specialist consignments.'],
];
?>
<section class="page-hero">
    <div class="container">
        <span class="page-eyebrow"><i class="bi bi-truck"></i> Delivery solutions</span>
        <h1>The right delivery option for each parcel and route.</h1>
        <p>From a single local drop-off to business distribution and international enquiries, we help you choose a practical service before you commit.</p>
        <div class="page-hero-actions"><a class="easy-btn orange" href="<?= e(url('quote.php')) ?>">Get a tailored quote</a><a class="easy-btn light" href="<?= e(url('tracking.php')) ?>">Track a shipment</a></div>
    </div>
</section>
<section class="content-section soft">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($services as [$icon, $name, $description]): ?>
                <div class="col-md-6 col-lg-3">
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
        <div class="row g-5 align-items-center">
            <div class="col-lg-6"><img class="feature-image" src="<?= e(url('assets/img/easyway/pickupanddropoff.jpg')) ?>" alt="Parcel pickup and delivery service"></div>
            <div class="col-lg-6"><span class="section-kicker">Before dispatch</span>
                <h2 class="section-heading">A few details help us recommend correctly.</h2>
                <p class="section-lead">Service availability and delivery time depend on the route, parcel, booking time and any special handling needs.</p>
                <ul class="check-list mt-4">
                    <li><i class="bi bi-check-lg"></i><span>Origin and exact destination</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Weight, dimensions and quantity</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Item type, value and required delivery date</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Pickup access or recipient instructions</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>