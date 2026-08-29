<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$pageTitle = 'Get a Quote';
$pageDescription = 'Request a tailored Easyway delivery, international or cargo quote.';
$quoteState = pull_form_state('quote');
require __DIR__ . '/app/views/partials/public-header.php';
?>
<section class="page-hero">
    <div class="container"><span class="page-eyebrow"><i class="bi bi-calculator"></i> Quote request</span>
        <h1>Give us the details that affect delivery.</h1>
        <p>This Stage 1 form creates a traceable request for our team. Confirmed automated pricing will be introduced with the Stage 2 rate engine.</p>
    </div>
</section>
<section class="content-section soft">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="easy-form-card">
                    <h2 class="mb-4">Shipment details</h2>
                    <form method="post" action="<?= e(url('controller/router.php?action=quote.submit')) ?>" novalidate><?= csrf_field() ?><input type="hidden" name="_return" value="quote.php">
                        <div class="honeypot" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="row g-4">
                            <div class="col-md-6"><label for="shipment-type">Shipment type *</label><select class="form-select" id="shipment-type" name="shipment_type_option" required>
                                    <option value="">Choose one</option>
                                    <option value="Domestic" <?= form_value($quoteState, 'shipment_type') === 'Domestic' ? 'selected' : '' ?>>Domestic</option>
                                    <option value="International" <?= form_value($quoteState, 'shipment_type') === 'International' ? 'selected' : '' ?>>International</option>
                                </select><?= form_error($quoteState, 'shipment_type') ?></div>
                            <div class="col-md-6"><label for="delivery-type">Delivery service *</label><select class="form-select" id="delivery-type" name="delivery_type" required>
                                    <option value="">Choose service</option><?php foreach (['Standard Delivery', 'Express Delivery', 'Same-Day Delivery', 'Cargo / Freight'] as $option): ?><option value="<?= e($option) ?>" <?= form_value($quoteState, 'delivery_type') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?>
                                </select><?= form_error($quoteState, 'delivery_type') ?></div>
                            <div class="col-md-6"><label for="from-location">From *</label><input class="form-control" id="from-location" name="from_location" value="<?= form_value($quoteState, 'from_location') ?>" placeholder="City, state or country" required><?= form_error($quoteState, 'from_location') ?></div>
                            <div class="col-md-6"><label for="to-location">To *</label><input class="form-control" id="to-location" name="to_location" value="<?= form_value($quoteState, 'to_location') ?>" placeholder="City, state or country" required><?= form_error($quoteState, 'to_location') ?></div>
                            <div class="col-md-6"><label for="weight-range">Total weight *</label><select class="form-select" id="weight-range" name="weight_range" required>
                                    <option value="">Choose range</option><?php foreach (['Below 1kg', '1kg - 5kg', '6kg - 15kg', '16kg - 30kg', 'Above 30kg'] as $option): ?><option value="<?= e($option) ?>" <?= form_value($quoteState, 'weight_range') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?>
                                </select><?= form_error($quoteState, 'weight_range') ?></div>
                            <div class="col-md-6"><label for="quantity">Number of pieces *</label><input class="form-control" type="number" min="1" max="10000" id="quantity" name="quantity" value="<?= form_value($quoteState, 'quantity', '1') ?>" required><?= form_error($quoteState, 'quantity') ?></div>
                            <div class="col-md-6"><label for="quote-name">Full name *</label><input class="form-control" id="quote-name" name="fullname" value="<?= form_value($quoteState, 'full_name') ?>" required><?= form_error($quoteState, 'full_name') ?></div>
                            <div class="col-md-6"><label for="quote-phone">Phone number *</label><input class="form-control" type="tel" id="quote-phone" name="phone" value="<?= form_value($quoteState, 'phone') ?>" required><?= form_error($quoteState, 'phone') ?></div>
                            <div class="col-12"><label for="quote-email">Email address *</label><input class="form-control" type="email" id="quote-email" name="email" value="<?= form_value($quoteState, 'email') ?>" required><?= form_error($quoteState, 'email') ?></div>
                            <div class="col-12"><label for="quote-notes">Item description and special instructions</label><textarea class="form-control" id="quote-notes" name="notes" placeholder="What are you sending? Include dimensions or special handling needs if known."><?= form_value($quoteState, 'notes') ?></textarea></div>
                            <div class="col-12"><button class="easy-btn" type="submit">Send quote request <i class="bi bi-arrow-right"></i></button></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <aside class="contact-panel">
                    <h2>What happens next?</h2>
                    <div class="contact-row"><i class="bi bi-1-circle"></i>
                        <div><strong>We review the route</strong><br>Availability, parcel type and handling needs.</div>
                    </div>
                    <div class="contact-row"><i class="bi bi-2-circle"></i>
                        <div><strong>We confirm the price</strong><br>You receive the service option and conditions.</div>
                    </div>
                    <div class="contact-row"><i class="bi bi-3-circle"></i>
                        <div><strong>You approve booking</strong><br>No shipment is created until details are confirmed.</div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>