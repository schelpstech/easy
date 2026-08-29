<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$pageTitle = 'Cargo Services';
$pageDescription = 'Easyway air, sea and road cargo coordination for commercial and heavyweight consignments.';
require __DIR__ . '/app/views/partials/public-header.php';
?>
<section class="page-hero">
    <div class="container"><span class="page-eyebrow"><i class="bi bi-airplane"></i> Cargo services</span>
        <h1>Practical cargo support for heavier and commercial shipments.</h1>
        <p>We assess the consignment, route, urgency and documents before recommending air, sea or road movement.</p>
        <div class="page-hero-actions"><a class="easy-btn orange" href="<?= e(url('quote.php')) ?>">Request cargo pricing</a><a class="easy-btn light" href="<?= e(whatsapp_url('Hello Easyway Logistics, I would like to discuss a cargo shipment.')) ?>" target="_blank" rel="noopener noreferrer">Discuss on WhatsApp</a></div>
    </div>
</section>
<section class="content-section soft">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <article class="easy-card">
                    <div class="card-icon"><i class="bi bi-airplane-engines"></i></div>
                    <h2>Air cargo</h2>
                    <p>For time-sensitive cargo where chargeable weight, space and route availability have been confirmed.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="easy-card">
                    <div class="card-icon"><i class="bi bi-water"></i></div>
                    <h2>Sea cargo</h2>
                    <p>Planned movement for larger consignments where longer transit times are acceptable.</p>
                </article>
            </div>
            <div class="col-md-4">
                <article class="easy-card">
                    <div class="card-icon"><i class="bi bi-truck-front"></i></div>
                    <h2>Road freight</h2>
                    <p>Interstate and regional coordination for palletised, bulk or specialist deliveries.</p>
                </article>
            </div>
        </div>
    </div>
</section>
<section class="content-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6"><img class="feature-image" src="<?= e(url('assets/img/home2/company-banner-bg.jpg')) ?>" alt="Cargo and freight transport"></div>
            <div class="col-lg-6"><span class="section-kicker">Cargo assessment</span>
                <h2 class="section-heading">Accurate details prevent delays and surprise charges.</h2>
                <ul class="check-list mt-4">
                    <li><i class="bi bi-check-lg"></i><span>Commodity description and customs classification where applicable</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Number of pieces, dimensions and gross weight</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Origin, destination and preferred delivery window</span></li>
                    <li><i class="bi bi-check-lg"></i><span>Commercial invoice, packing list or permits where required</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>