<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Auth;
use App\StaffAccountService;
Auth::requireRole(['admin']);
$accounts = StaffAccountService::all();
$state = pull_form_state('staff_account');
$staffTitle = 'Staff accounts';
require __DIR__ . '/_header.php';
?>
<div class="row g-4">
    <div class="col-xl-7"><section class="staff-card">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h2 class="h4 mb-1">Your team</h2><p class="text-muted mb-0">Staff permissions follow the assigned role.</p></div><a class="staff-btn light" href="<?= e(url('staff/riders.php')) ?>">Manage riders</a></div>
        <div class="table-responsive"><table class="table staff-table"><thead><tr><th>Name / email</th><th>Role</th><th>Status</th><th>Last sign-in</th></tr></thead><tbody>
        <?php foreach ($accounts as $account): ?><tr><td><strong><?= e($account['full_name']) ?></strong><br><small><?= e($account['email']) ?></small></td><td><?= e(ucfirst($account['role'])) ?></td><td><span class="staff-badge"><?= e(ucfirst($account['status'])) ?></span></td><td><?= $account['last_login_at'] ? e(date('j M Y, g:i A', strtotime($account['last_login_at']))) : 'Not yet' ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section></div>
    <div class="col-xl-5"><section class="staff-card">
        <h2 class="h4 mb-2">Create staff account</h2><p class="text-muted">Administrators manage accounts, rates and delivery settings. Dispatchers manage day-to-day operations. Add delivery riders through the Riders module.</p>
        <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.account.create')) ?>"><?= csrf_field() ?>
            <div class="mb-3"><label for="account-name">Full name</label><input class="form-control" id="account-name" name="full_name" maxlength="120" value="<?= form_value($state, 'full_name') ?>" autocomplete="name" required></div>
            <div class="mb-3"><label for="account-email">Work email</label><input class="form-control" type="email" id="account-email" name="email" maxlength="190" value="<?= form_value($state, 'email') ?>" autocomplete="off" required></div>
            <div class="mb-3"><label for="account-role">Role</label><select class="form-select" id="account-role" name="role" required><option value="dispatcher" <?= form_value($state, 'role') !== 'admin' ? 'selected' : '' ?>>Dispatcher</option><option value="admin" <?= form_value($state, 'role') === 'admin' ? 'selected' : '' ?>>Administrator</option></select></div>
            <div class="mb-3"><label for="account-password">Initial password</label><input class="form-control" type="password" id="account-password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required><small class="text-muted">At least 12 characters; maximum 72 bytes. Never sent by email or saved in the form.</small></div>
            <div class="mb-3"><label for="account-confirm">Confirm initial password</label><input class="form-control" type="password" id="account-confirm" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></div>
            <div class="mb-4"><label for="account-current">Your administrator password</label><input class="form-control" type="password" id="account-current" name="current_password" autocomplete="current-password" required></div>
            <button class="staff-btn w-100" type="submit"><i class="bi bi-person-plus"></i> Create staff account</button>
        </form>
    </section></div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
