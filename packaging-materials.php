<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$pageTitle = 'Packaging Materials';
$pageDescription = 'Packaging boxes and parcel protection materials for local and international shipments.';
require __DIR__ . '/app/views/partials/public-header.php';

$products = [
    ['EasyWay Packaging Box 1.jpg', 'Compact parcel box', 'For smaller accessories, documents and protected lightweight items.'],
    ['EasyWay Packaging Box 2.jpg', 'Everyday courier box', 'A practical carton format for common retail and personal shipments.'],
    ['EasyWay Packaging Box 4.jpg', 'Medium shipping box', 'Additional room for grouped items with protective fill.'],
    ['EasyWay Packaging Box 7.jpg', 'Large parcel box', 'For larger packaged goods after weight and dimension checks.'],
    ['EasyWay Packaging Box 8.jpg', 'Heavy-duty carton', 'Stronger outer packaging for suitable heavier contents.'],
    ['EasyWay Packaging Box 10.jpg', 'Custom packaging support', 'Packaging recommendations for unusual sizes or fragile consignments.'],
];
?>
<section class="page-hero">
    <div class="container"><span class="page-eyebrow"><i class="bi bi-box-seam"></i> Packaging materials</span>
        <h1>Protect the item before the journey begins.</h1>
        <p>Choose suitable outer packaging and protective material for the item, weight and transport method. Our team can advise before pickup.</p>
    </div>
</section>
<section class="content-section soft">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($products as [$image, $name, $description]): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="product-card"><img src="<?= e(url('assets/img/easyway/' . $image)) ?>" alt="<?= e($name) ?>">
                        <div class="product-card-content">
                            <h2><?= e($name) ?></h2>
                            <p><?= e($description) ?></p><a class="easy-btn outline mt-3" href="<?= e(url('contact.php?subject=Packaging%20enquiry&product=' . rawurlencode($name))) ?>">Ask about availability</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="content-section">
    <div class="container">
        <div class="info-band d-lg-flex justify-content-between align-items-center gap-4">
            <div>
                <h2>Not sure which packaging is suitable?</h2>
                <p class="mb-lg-0">Share the item type, quantity, approximate dimensions and route. We will advise before you seal it.</p>
            </div><a class="easy-btn orange flex-shrink-0" href="<?= e(whatsapp_url('Hello Easyway Logistics, I need help choosing packaging for a shipment.')) ?>" target="_blank" rel="noopener noreferrer">Ask on WhatsApp</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>