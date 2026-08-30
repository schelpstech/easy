<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\RateCatalogService;

Auth::requireRole(['admin']);
$kind = $_GET['kind'] ?? 'zone';
if (!in_array($kind, ['zone','service'], true)) { http_response_code(404); exit('Catalogue not found.'); }
$installed = $kind === 'zone' || RateCatalogService::installed();
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($id === false) { http_response_code(404); exit('Record not found.'); }
$record = $id > 0 && $installed ? RateCatalogService::find($kind, $id) : null;
if ($id > 0 && $record === null) { http_response_code(404); exit('Record not found.'); }
$values = $record ?? ['id' => 0, 'name' => '', 'code' => '', 'country_code' => 'NG', 'status' => 'active'];
$values['version'] = $record === null ? '' : RateCatalogService::version($record);
$draft = $_SESSION['rate_management_draft'] ?? null;
if (is_array($draft) && $draft['kind'] === $kind && (int) ($draft['values']['id'] ?? 0) === $id) {
    $values = array_replace($values, $draft['values']); unset($_SESSION['rate_management_draft']);
}
$entries = RateCatalogService::all($kind);
$label = $kind === 'zone' ? 'location' : 'service';
$path = 'staff/rate-options.php?kind=' . $kind;
$staffTitle = 'Rate management'; $rateTab = $kind;
require __DIR__ . '/_header.php';
require __DIR__ . '/_rate-nav.php';
?>
<div class="mb-4"><h2 class="h4 mb-1"><?= $kind === 'zone' ? 'Origins & destinations' : 'Delivery services' ?></h2><p class="text-muted mb-0"><?= $kind === 'zone' ? 'One shared location list for both ends of a route. Add a city, state, country or service zone.' : 'Services appear in the calculator, customer booking and bulk shipment validation.' ?> Inactive entries are hidden from new customer selections; existing bookings are kept.</p></div>
<?php if (!$installed): ?><div class="alert alert-warning">Run <code>php tools/install_rate_management.php</code> once to enable service management. Existing standard services continue working.</div><?php endif; ?>
<div class="row g-4">
    <div class="col-xl-5"><section class="staff-card"><h3 class="h5 mb-4"><?= $id > 0 ? 'Edit ' : 'Add ' ?><?= e($label) ?></h3>
        <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.rate.' . $kind . '.save')) ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="version" value="<?= e($values['version']) ?>">
            <fieldset <?= $installed ? '' : 'disabled' ?>>
                <div class="mb-3"><label for="option-name"><?= ucfirst($label) ?> name</label><input class="form-control" id="option-name" name="name" maxlength="<?= $kind === 'zone' ? '120' : '80' ?>" value="<?= e($values['name']) ?>" placeholder="<?= $kind === 'zone' ? 'e.g. Port Harcourt' : 'e.g. Air Freight' ?>" required></div>
                <div class="mb-3"><label for="option-code">Code</label><input class="form-control" id="option-code" name="code" minlength="2" maxlength="40" pattern="[A-Za-z][A-Za-z0-9_\-]{1,39}" value="<?= e($values['code']) ?>" placeholder="<?= $kind === 'zone' ? 'e.g. PORT_HARCOURT' : 'e.g. air_freight' ?>" <?= $id > 0 ? 'readonly' : '' ?> required><small class="text-muted">A unique, permanent code. Use letters, numbers, hyphens or underscores; start with a letter.</small></div>
                <?php if ($kind === 'zone'): ?><div class="mb-3"><label for="option-country">Country code</label><input class="form-control" id="option-country" name="country_code" minlength="2" maxlength="2" pattern="[A-Za-z]{2}" value="<?= e($values['country_code']) ?>" required><small class="text-muted">Two letters: NG for Nigeria, GB for the UK, US for the USA. Use ZZ for a mixed-country zone.</small></div><?php endif; ?>
                <div class="mb-4"><label for="option-status">Status</label><select class="form-select" id="option-status" name="status"><?php foreach (['active' => 'Active','inactive' => 'Inactive'] as $status => $statusLabel): ?><option value="<?= e($status) ?>" <?= $values['status'] === $status ? 'selected' : '' ?>><?= e($statusLabel) ?></option><?php endforeach; ?></select><small class="text-muted">Deactivating this <?= e($label) ?> also pauses online pricing for every rate that uses it.</small></div>
                <div class="d-flex flex-wrap gap-2"><button class="staff-btn" type="submit"><i class="bi bi-save"></i> <?= $id > 0 ? 'Save changes' : 'Add ' . e($label) ?></button><?php if ($id > 0): ?><a class="staff-btn light" href="<?= e(url($path)) ?>">Add another</a><a class="staff-btn light" href="<?= e(url($path . '&id=' . $id)) ?>">Reload saved values</a><?php endif; ?></div>
            </fieldset>
        </form>
    </section></div>
    <div class="col-xl-7"><section class="staff-card"><div class="rate-heading mb-3"><h3 class="h5 mb-0"><?= $kind === 'zone' ? 'Saved locations' : 'Saved services' ?></h3><span class="text-muted"><?= count($entries) ?> total</span></div><div class="table-responsive"><table class="table staff-table rate-options-table"><thead><tr><th>Name / Code</th><?php if ($kind === 'zone'): ?><th>Country</th><?php endif; ?><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
        <?php if ($entries === []): ?><tr><td colspan="<?= $kind === 'zone' ? 4 : 3 ?>" class="text-center text-muted py-4">No entries yet.</td></tr><?php endif; ?>
        <?php foreach ($entries as $entry): ?><tr><td><strong><?= e($entry['name']) ?></strong><small class="d-block text-muted"><?= e($entry['code']) ?> · #<?= e($entry['id']) ?></small></td><?php if ($kind === 'zone'): ?><td><?= e($entry['country_code']) ?></td><?php endif; ?><td><span class="staff-badge <?= $entry['status'] === 'active' ? '' : 'neutral' ?>"><?= e(ucfirst($entry['status'])) ?></span></td><td><a class="staff-btn light" href="<?= e(url($path . '&id=' . $entry['id'])) ?>" aria-label="<?= e('Edit ' . $entry['name']) ?>"><i class="bi bi-pencil"></i> Edit</a></td></tr><?php endforeach; ?>
    </tbody></table></div><p class="small text-muted mt-3 mb-0">After adding entries, <a href="<?= e(url('staff/rates.php?new=1')) ?>">create a rate card</a> for each route and service you offer. Reverse routes need their own rates.</p></section></div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
