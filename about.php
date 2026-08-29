<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'About Us';
$pageDescription = 'Meet Easyway Logistics and learn how we support dependable local, interstate and international delivery.';
require __DIR__ . '/app/views/partials/public-header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="page-eyebrow"><i class="bi bi-compass"></i> About Easyway</span>
        <h1>Logistics made clearer, friendlier and easier to follow.</h1>
        <p>We help individuals and businesses move parcels with careful handling, practical support and visible shipment progress from booking to delivery.</p>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-kicker">Who we are</span>
                <h2 class="section-heading">A delivery partner built around real customer needs.</h2>
                <p class="section-lead">Easyway Logistics supports everyday deliveries, business distribution and international shipping enquiries from our base in Ogun State.</p>
                <p>Our work is guided by three simple ideas: communicate honestly, handle every parcel responsibly and give customers useful information at every stage. Where a route requires a specialist courier or cargo partner, our team confirms availability and the correct documentation before accepting the shipment.</p>
                <ul class="check-list mt-4">
                    <li><i class="bi bi-check-lg"></i><span>Clear shipment references and status updates</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Support for personal, retail and corporate deliveries</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Careful packaging guidance for different item types</span></li>
                </ul>
            </div>
            <div class="col-lg-6">
                <img class="feature-image" src="<?= e(url('assets/img/easyway/efficient.jpg')) ?>" alt="Easyway Logistics team preparing a delivery">
            </div>
        </div>
    </div>
</section>

<section class="content-section soft">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:760px">
            <span class="section-kicker">How we work</span>
            <h2 class="section-heading mx-auto">Responsible service at every handoff.</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <article class="easy-card">
                    <div class="card-icon"><i class="bi bi-chat-square-text"></i></div>
                    <h2>Clear communication</h2>
                    <p>We confirm shipment details, delivery expectations and any restrictions before dispatch.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="easy-card">
                    <div class="card-icon"><i class="bi bi-box-seam"></i></div>
                    <h2>Careful handling</h2>
                    <p>Packaging and handling guidance is matched to the parcel, route and transport method.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="easy-card">
                    <div class="card-icon"><i class="bi bi-geo-alt"></i></div>
                    <h2>Visible progress</h2>
                    <p>Customers can use their Easyway tracking number to view public shipment milestones.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="info-band d-lg-flex justify-content-between align-items-center gap-4">
            <div>
                <h2>Ready to plan a delivery?</h2>
                <p class="mb-lg-0">Tell us the origin, destination and package details. Our team will confirm the suitable service.</p>
            </div>
            <a class="easy-btn orange flex-shrink-0" href="<?= e(url('quote.php')) ?>">Request a quote <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>