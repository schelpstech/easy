<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\PricingService;
use App\RateCatalogService;

Auth::requireRole(['admin']);
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($id === false) { http_response_code(404); exit('Rate not found.'); }
$rate = $id > 0 ? PricingService::findRate($id) : null;
if ($id > 0 && $rate === null) { http_response_code(404); exit('Rate not found.'); }
$showForm = $id > 0 || isset($_GET['new']);
$values = $rate ?? ['id' => 0, 'base_fee' => '0', 'base_weight_kg' => '1', 'extra_kg_fee' => '0', 'minimum_fee' => '0', 'packaging_fee' => '0', 'fuel_percent' => '0', 'insurance_percent' => '0', 'tax_percent' => '0', 'volumetric_divisor' => '5000', 'currency' => 'NGN', 'status' => 'active'];
$values['version'] = $rate === null ? '' : RateCatalogService::version($rate);
$draft = $_SESSION['rate_management_draft'] ?? null;
if (is_array($draft) && $draft['kind'] === 'rate' && (int) ($draft['values']['id'] ?? 0) === $id && $showForm) {
    $values = array_replace($values, $draft['values']);
    unset($_SESSION['rate_management_draft']);
}
$zones = PricingService::zones(true);
$services = PricingService::services(true);
$activeServices = PricingService::services();
$rates = PricingService::allRates();
$query = is_string($_GET['q'] ?? '') ? mb_substr(trim($_GET['q'] ?? ''), 0, 120) : '';
if ($query !== '') { $rates = array_values(array_filter($rates, static fn (array $row): bool => mb_stripos($row['origin_name'] . ' ' . $row['destination_name'] . ' ' . $row['service_name'], $query) !== false)); }
$staffTitle = 'Rate management'; $rateTab = 'rate';
require __DIR__ . '/_header.php';
require __DIR__ . '/_rate-nav.php';
?>
<?php if ($showForm): ?>
<section class="staff-card mb-4">
    <div class="rate-heading mb-4"><div><h2 class="h4 mb-1"><?= $id > 0 ? 'Edit rate #' . e($id) : 'Add rate card' ?></h2><p class="text-muted mb-0">Prices apply to this direction only. Existing bookings retain their agreed amounts.</p></div><a class="staff-btn light" href="<?= e(url('staff/rates.php')) ?>">Back to rates</a></div>
    <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.rate.save')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="version" value="<?= e($values['version']) ?>">
        <fieldset class="mb-4"><legend class="h6">Route and service</legend><div class="row g-3">
            <?php foreach (['origin_zone_id' => 'Origin','destination_zone_id' => 'Destination'] as $field => $label): ?>
            <div class="col-md-4"><label for="rate-<?= e($field) ?>"><?= e($label) ?></label><select class="form-select" id="rate-<?= e($field) ?>" name="<?= e($field) ?>" required><option value="">Choose location</option><?php foreach ($zones as $zone): ?><option value="<?= e($zone['id']) ?>" <?= (string) ($values[$field] ?? '') === (string) $zone['id'] ? 'selected' : '' ?>><?= e($zone['name'] . ' (' . $zone['country_code'] . ')' . ($zone['status'] === 'active' ? '' : ' — inactive')) ?></option><?php endforeach; ?></select></div>
            <?php endforeach; ?>
            <div class="col-md-4"><label for="rate-service">Service</label><select class="form-select" id="rate-service" name="service_code" required><option value="">Choose service</option><?php foreach ($services as $code => $name): ?><option value="<?= e($code) ?>" <?= ($values['service_code'] ?? '') === $code ? 'selected' : '' ?>><?= e($name . (isset($activeServices[$code]) ? '' : ' — inactive')) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><small class="text-muted">Missing an option? <a href="<?= e(url('staff/rate-options.php?kind=zone')) ?>">Add an origin or destination</a> · <a href="<?= e(url('staff/rate-options.php?kind=service')) ?>">Add a service</a>. Save your current changes before leaving.</small></div>
        </div></fieldset>
        <fieldset class="mb-4"><legend class="h6">Price and weight</legend><div class="row g-3">
            <div class="col-md-4 col-xl-3"><label for="rate-currency">Currency</label><select class="form-select" id="rate-currency" name="currency"><?php foreach (['NGN','USD','GBP','EUR'] as $currency): ?><option <?= $values['currency'] === $currency ? 'selected' : '' ?>><?= e($currency) ?></option><?php endforeach; ?></select></div>
            <?php foreach (['base_fee' => 'Base fee','base_weight_kg' => 'Included weight (kg)','extra_kg_fee' => 'Fee per extra kg','minimum_fee' => 'Minimum fee','packaging_fee' => 'Packaging fee'] as $field => $label): ?>
            <div class="col-md-4 col-xl-3"><label for="rate-<?= e($field) ?>"><?= e($label) ?></label><input class="form-control" type="number" id="rate-<?= e($field) ?>" name="<?= e($field) ?>" min="<?= $field === 'base_weight_kg' ? '0.01' : '0' ?>" max="<?= $field === 'base_weight_kg' ? '100000' : '1000000000' ?>" step="0.01" value="<?= e($values[$field]) ?>" required></div>
            <?php endforeach; ?>
        </div><small class="text-muted">Base fee covers the included weight. Extra weight is charged per kg; the minimum fee is a floor before surcharges. Packaging is charged only when requested.</small></fieldset>
        <fieldset class="mb-4"><legend class="h6">Adjustments</legend><div class="row g-3">
            <?php foreach (['fuel_percent' => 'Fuel %','insurance_percent' => 'Insurance %','tax_percent' => 'Tax %'] as $field => $label): ?><div class="col-md-3"><label for="rate-<?= e($field) ?>"><?= e($label) ?></label><input class="form-control" type="number" id="rate-<?= e($field) ?>" name="<?= e($field) ?>" min="0" max="100" step="0.001" value="<?= e($values[$field]) ?>" required></div><?php endforeach; ?>
            <div class="col-md-3"><label for="rate-divisor">Volumetric divisor</label><input class="form-control" type="number" id="rate-divisor" name="volumetric_divisor" min="1" max="100000" step="0.01" value="<?= e($values['volumetric_divisor']) ?>" required></div>
        </div><small class="text-muted">Fuel applies to transport charges; insurance applies to declared value. Tax applies after surcharges. Volumetric weight = length × width × height (cm) ÷ divisor.</small></fieldset>
        <fieldset class="mb-4"><legend class="h6">Delivery and availability</legend><div class="row g-3">
            <?php foreach (['estimated_days_min' => 'Minimum delivery days','estimated_days_max' => 'Maximum delivery days'] as $field => $label): ?><div class="col-md-4"><label for="rate-<?= e($field) ?>"><?= e($label) ?></label><input class="form-control" type="number" id="rate-<?= e($field) ?>" name="<?= e($field) ?>" min="0" max="365" step="1" value="<?= e($values[$field] ?? '') ?>"></div><?php endforeach; ?>
            <div class="col-md-4"><label for="rate-status">Status</label><select class="form-select" id="rate-status" name="status"><?php foreach (['active' => 'Active','inactive' => 'Inactive'] as $status => $label): ?><option value="<?= e($status) ?>" <?= $values['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        </div><small class="text-muted">Enter both delivery limits or leave both blank. An active rate also needs an active origin, destination and service.</small></fieldset>
        <div class="d-flex flex-wrap gap-2"><button class="staff-btn" type="submit"><i class="bi bi-save"></i> <?= $id > 0 ? 'Save changes' : 'Create rate card' ?></button><a class="staff-btn light" href="<?= e(url('staff/rates.php')) ?>">Cancel</a><?php if ($id > 0): ?><a class="staff-btn light" href="<?= e(url('staff/rates.php?id=' . $id)) ?>">Reload saved values</a><?php endif; ?></div>
    </form>
</section>
<?php endif; ?>
<section class="staff-card">
    <div class="rate-heading mb-3"><div><h2 class="h4 mb-1">Configured rates</h2><p class="text-muted mb-0">Manage the prices used by the calculator and online booking.</p></div><a class="staff-btn" href="<?= e(url('staff/rates.php?new=1')) ?>"><i class="bi bi-plus-lg"></i> Add rate</a></div>
    <form class="staff-form rate-search mb-3" method="get" action="<?= e(url('staff/rates.php')) ?>"><label class="visually-hidden" for="rate-search">Search routes or services</label><input class="form-control" id="rate-search" name="q" maxlength="120" value="<?= e($query) ?>" placeholder="Search routes or services"><button class="staff-btn light" type="submit">Search</button><?php if ($query !== ''): ?><a class="staff-btn light" href="<?= e(url('staff/rates.php')) ?>">Clear</a><?php endif; ?></form>
    <div class="table-responsive"><table class="table staff-table rate-table"><thead><tr><th>Route / Service</th><th>Base / Extra kg</th><th>Adjustments</th><th>Delivery</th><th>Availability</th><th><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
        <?php if ($rates === []): ?><tr><td colspan="6" class="text-center text-muted py-4"><?= $query === '' ? 'No rates configured. Add locations and services, then create your first rate.' : 'No matching rates.' ?></td></tr><?php endif; ?>
        <?php foreach ($rates as $row): ?><tr>
            <td><strong><?= e($row['origin_name']) ?> → <?= e($row['destination_name']) ?></strong><small class="d-block text-muted"><?= e($row['service_name']) ?> · #<?= e($row['id']) ?></small><a class="staff-btn light d-md-none mt-2" href="<?= e(url('staff/rates.php?id=' . $row['id'])) ?>" aria-label="<?= e('Edit rate ' . $row['id']) ?>"><i class="bi bi-pencil"></i> Edit rate</a></td>
            <td><?= e($row['currency']) ?> <?= e(number_format((float) $row['base_fee'], 2)) ?><small class="d-block text-muted">Includes <?= e($row['base_weight_kg']) ?> kg<br>+ <?= e(number_format((float) $row['extra_kg_fee'], 2)) ?> / extra kg</small></td>
            <td><small>Fuel <?= e($row['fuel_percent']) ?>%<br>Insurance <?= e($row['insurance_percent']) ?>%<br>Tax <?= e($row['tax_percent']) ?>%</small></td>
            <td><?= $row['estimated_days_min'] === null ? 'Not set' : e($row['estimated_days_min'] . '–' . $row['estimated_days_max'] . ' days') ?></td>
            <td><span class="staff-badge <?= $row['available'] ? '' : 'neutral' ?>"><?= $row['available'] ? 'Active' : ($row['status'] === 'active' ? 'Unavailable' : 'Inactive') ?></span><?php if ($row['status'] === 'active' && !$row['available']): ?><small class="d-block text-muted mt-1">Location or service inactive</small><?php endif; ?></td>
            <td><a class="staff-btn light" aria-label="<?= e('Edit rate ' . $row['id'] . ': ' . $row['origin_name'] . ' to ' . $row['destination_name']) ?>" href="<?= e(url('staff/rates.php?id=' . $row['id'])) ?>"><i class="bi bi-pencil"></i> Edit</a></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
