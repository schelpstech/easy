<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\RiderService;

Auth::requireRole(['admin', 'dispatcher']);
$riders = RiderService::all();
$staffTitle = 'Rider management';
require __DIR__ . '/_header.php';
?>
<div class="row g-4">
    <div class="col-xl-8"><section class="staff-card"><div class="d-flex justify-content-between align-items-start gap-3 mb-4"><div><h2 class="h4 mb-1">Delivery team</h2><p class="text-muted mb-0">Availability and current assignment are shown from live operations data.</p></div><span class="staff-badge"><?= e(count($riders)) ?> riders</span></div>
        <div class="table-responsive"><table class="table staff-table"><thead><tr><th>Rider</th><th>Vehicle</th><th>Availability</th><th>Current shipment</th><?php if ((Auth::user()['role'] ?? '') === 'admin'): ?><th></th><?php endif; ?></tr></thead><tbody>
        <?php if ($riders === []): ?><tr><td colspan="5" class="text-center text-muted py-4">No riders have been added.</td></tr><?php endif; ?>
        <?php foreach ($riders as $rider): ?><tr><td><strong><?= e($rider['full_name']) ?></strong><br><small><?= e($rider['rider_code']) ?> · <?= e($rider['phone']) ?></small></td><td><?= e(ucfirst((string) $rider['vehicle_type'])) ?><?= $rider['vehicle_registration'] ? '<br><small>' . e($rider['vehicle_registration']) . '</small>' : '' ?></td><td><span class="staff-badge"><?= e(ucwords(str_replace('_', ' ', (string) $rider['availability_status']))) ?></span></td><td><?php if ($rider['active_shipment_id']): ?><a href="<?= e(url('staff/shipment.php?id=' . $rider['active_shipment_id'])) ?>"><?= e($rider['active_tracking_number']) ?></a><?php else: ?><span class="text-muted">Unassigned</span><?php endif; ?></td><?php if ((Auth::user()['role'] ?? '') === 'admin'): ?><td><form method="post" action="<?= e(url('controller/router.php?action=staff.rider.status')) ?>"><?= csrf_field() ?><input type="hidden" name="rider_id" value="<?= e($rider['id']) ?>"><input type="hidden" name="active" value="<?= $rider['user_status'] === 'active' ? '0' : '1' ?>"><button class="staff-btn light" type="submit"><?= $rider['user_status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form></td><?php endif; ?></tr><?php endforeach; ?>
        </tbody></table></div>
    </section></div>
    <div class="col-xl-4"><?php if ((Auth::user()['role'] ?? '') === 'admin'): ?><section class="staff-card"><h2 class="h4 mb-2">Add rider</h2><p class="text-muted">This creates a restricted rider login. Share the temporary password securely.</p><form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.rider.create')) ?>"><?= csrf_field() ?>
        <div class="mb-3"><label for="rider-name">Full name *</label><input class="form-control" id="rider-name" name="full_name" required></div>
        <div class="mb-3"><label for="rider-email">Email *</label><input class="form-control" type="email" id="rider-email" name="email" required></div>
        <div class="mb-3"><label for="rider-phone">Phone *</label><input class="form-control" id="rider-phone" name="phone" required></div>
        <div class="mb-3"><label for="rider-vehicle">Vehicle *</label><select class="form-select" id="rider-vehicle" name="vehicle_type" required><option value="">Choose vehicle</option><?php foreach (['motorcycle','car','van','truck','bicycle'] as $vehicle): ?><option value="<?= e($vehicle) ?>"><?= e(ucfirst($vehicle)) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label for="rider-reg">Vehicle registration</label><input class="form-control" id="rider-reg" name="vehicle_registration"></div>
        <div class="mb-3"><label for="rider-licence">Licence number</label><input class="form-control" id="rider-licence" name="licence_number"></div>
        <div class="mb-3"><label for="rider-emergency">Emergency contact</label><input class="form-control" id="rider-emergency" name="emergency_contact"></div>
        <div class="mb-4"><label for="rider-password">Temporary password *</label><input class="form-control" type="password" id="rider-password" name="password" minlength="12" autocomplete="new-password" required></div>
        <button class="staff-btn w-100" type="submit"><i class="bi bi-person-plus"></i> Create rider</button>
    </form></section><?php endif; ?></div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
