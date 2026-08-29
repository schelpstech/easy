<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\PricingService;

$pageTitle = 'Shipping Calculator';
$pageDescription = 'Calculate an Easyway Logistics delivery estimate from configured service rates.';
$zones = PricingService::zones();
$services = PricingService::services();
$estimate = null;
$calculatorError = '';
$values = $_POST;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    try {
        $estimate = PricingService::calculate([
            'origin_zone_id' => (int) ($_POST['origin_zone_id'] ?? 0),
            'destination_zone_id' => (int) ($_POST['destination_zone_id'] ?? 0),
            'service_code' => (string) ($_POST['service_code'] ?? ''),
            'weight_kg' => (float) ($_POST['weight_kg'] ?? 0),
            'length_cm' => (float) ($_POST['length_cm'] ?? 0),
            'width_cm' => (float) ($_POST['width_cm'] ?? 0),
            'height_cm' => (float) ($_POST['height_cm'] ?? 0),
            'declared_value' => (float) ($_POST['declared_value'] ?? 0),
            'packaging_required' => isset($_POST['packaging_required']),
        ]);
    } catch (Throwable $exception) {
        $calculatorError = $exception->getMessage();
    }
}
require __DIR__ . '/app/views/partials/public-header.php';
?>
<section class="page-hero">
    <div class="container"><span class="page-eyebrow"><i class="bi bi-calculator"></i> Transparent pricing</span>
        <h1>Calculate before you book.</h1>
        <p>Rates come directly from Easyway's active route cards. Package dimensions are considered so the estimate matches the chargeable weight used by operations.</p>
    </div>
</section>
<section class="content-section soft">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <div class="easy-form-card">
                    <h2 class="h3 mb-2">Shipment details</h2>
                    <p class="text-muted mb-4">All figures are recalculated when you create a booking.</p>
                    <form method="post" action="<?= e(url('calculator.php')) ?>"><?= csrf_field() ?><div class="row g-3">
                            <div class="col-md-6"><label for="calc-origin">Origin zone</label><select class="form-select" id="calc-origin" name="origin_zone_id" required>
                                    <option value="">Choose origin</option><?php foreach ($zones as $zone): ?><option value="<?= e($zone['id']) ?>" <?= (string) ($values['origin_zone_id'] ?? '') === (string) $zone['id'] ? 'selected' : '' ?>><?= e($zone['name']) ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-6"><label for="calc-destination">Destination zone</label><select class="form-select" id="calc-destination" name="destination_zone_id" required>
                                    <option value="">Choose destination</option><?php foreach ($zones as $zone): ?><option value="<?= e($zone['id']) ?>" <?= (string) ($values['destination_zone_id'] ?? '') === (string) $zone['id'] ? 'selected' : '' ?>><?= e($zone['name']) ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-7"><label for="calc-service">Service</label><select class="form-select" id="calc-service" name="service_code" required>
                                    <option value="">Choose service</option><?php foreach ($services as $code => $name): ?><option value="<?= e($code) ?>" <?= ($values['service_code'] ?? '') === $code ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-md-5"><label for="calc-weight">Actual weight (kg)</label><input class="form-control" id="calc-weight" type="number" name="weight_kg" step="0.01" min="0.01" value="<?= e($values['weight_kg'] ?? '') ?>" required></div>
                            <div class="col-12"><span class="form-section-label">Package dimensions in centimetres <small>(optional)</small></span></div>
                            <?php foreach (['length_cm' => 'Length', 'width_cm' => 'Width', 'height_cm' => 'Height'] as $field => $label): ?><div class="col-4"><label for="calc-<?= e($field) ?>"><?= e($label) ?></label><input class="form-control" id="calc-<?= e($field) ?>" type="number" name="<?= e($field) ?>" min="0" step="0.01" value="<?= e($values[$field] ?? '') ?>"></div><?php endforeach; ?>
                            <div class="col-md-7"><label for="calc-value">Declared value (NGN)</label><input class="form-control" id="calc-value" type="number" name="declared_value" min="0" step="0.01" value="<?= e($values['declared_value'] ?? '0') ?>"></div>
                            <div class="col-md-5 d-flex align-items-end">
                                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="calc-packaging" name="packaging_required" value="1" <?= isset($values['packaging_required']) ? 'checked' : '' ?>><label class="form-check-label" for="calc-packaging">Add packaging service</label></div>
                            </div>
                            <div class="col-12"><button class="easy-btn" type="submit"><i class="bi bi-calculator"></i> Calculate estimate</button></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5"><?php if ($estimate !== null): ?><div class="price-result"><span class="section-kicker">Estimated total</span><strong><?= e($estimate['currency']) ?> <?= e(number_format((float) $estimate['total_amount'], 2)) ?></strong>
                        <p><?= e($estimate['origin_name']) ?> to <?= e($estimate['destination_name']) ?> · <?= e($estimate['service_name']) ?></p>
                        <dl>
                            <div>
                                <dt>Chargeable weight</dt>
                                <dd><?= e(number_format((float) $estimate['chargeable_weight_kg'], 2)) ?> kg</dd>
                            </div>
                            <div>
                                <dt>Base and weight charge</dt>
                                <dd><?= e(number_format((float) $estimate['base_amount'] + (float) $estimate['weight_amount'], 2)) ?></dd>
                            </div>
                            <div>
                                <dt>Fuel / insurance / packaging</dt>
                                <dd><?= e(number_format((float) $estimate['fuel_amount'] + (float) $estimate['insurance_amount'] + (float) $estimate['packaging_amount'], 2)) ?></dd>
                            </div>
                            <div>
                                <dt>Tax</dt>
                                <dd><?= e(number_format((float) $estimate['tax_amount'], 2)) ?></dd>
                            </div>
                        </dl><a class="easy-btn orange w-100" href="<?= e(url(App\CustomerAuth::check() ? 'customer/book.php' : 'customer/register.php')) ?>">Continue to booking</a><small>Estimate is held for 48 hours once booked.</small>
                    </div><?php elseif ($calculatorError !== ''): ?><div class="easy-card">
                        <div class="card-icon"><i class="bi bi-chat-dots"></i></div>
                        <h2>Manual review needed</h2>
                        <p><?= e($calculatorError) ?></p><a class="easy-btn outline mt-4" href="<?= e(url('quote.php')) ?>">Request a quote</a>
                    </div><?php else: ?><div class="easy-card">
                        <div class="card-icon"><i class="bi bi-shield-check"></i></div>
                        <h2>No hidden calculation</h2>
                        <p>We compare actual and volumetric weight, then apply only the active route's configured charges.</p>
                        <ul>
                            <li>Server-calculated pricing</li>
                            <li>48-hour booking quote</li>
                            <li>Printable invoice before payment</li>
                        </ul>
                    </div><?php endif; ?></div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/app/views/partials/public-footer.php'; ?>