<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Auth;
Auth::requireStaff();
$staffTitle = 'Change password';
$isRider = (Auth::user()['role'] ?? '') === 'rider';
if (!$isRider) { require __DIR__ . '/_header.php'; }
else { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Change password | Easyway Staff</title><link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/staff.css')) ?>"></head><body class="staff-body"><main class="rider-main"><a class="staff-btn light mb-4" href="<?= e(url('rider/index.php')) ?>">Back to rider workspace</a><?= flash_markup() ?>
<?php } ?>
<section class="staff-card password-card">
    <h2 class="h4 mb-2">Keep your account secure</h2>
    <p class="text-muted">Changing the password for <strong><?= e(Auth::user()['email']) ?></strong> signs out all of your staff sessions, including this one.</p>
    <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.password.change')) ?>"><?= csrf_field() ?>
        <div class="mb-3"><label for="current-password">Current password</label><input class="form-control" type="password" id="current-password" name="current_password" autocomplete="current-password" required></div>
        <div class="mb-3"><label for="new-password">New password</label><input class="form-control" type="password" id="new-password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required><small class="text-muted">Use a unique password of at least 12 characters (maximum 72 bytes).</small></div>
        <div class="mb-4"><label for="confirm-password">Confirm new password</label><input class="form-control" type="password" id="confirm-password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></div>
        <button class="staff-btn" type="submit">Change password and sign out</button>
    </form>
</section>
<?php if (!$isRider) { require __DIR__ . '/_footer.php'; } else { echo '</main></body></html>'; } ?>
