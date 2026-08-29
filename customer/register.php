<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\CustomerAuth;

if (CustomerAuth::check()) { redirect('customer/index.php'); }
$state = pull_form_state('customer_register');
$pageTitle = 'Create Customer Account';
$pageDescription = 'Create an Easyway customer account for online booking, payments and delivery records.';
require dirname(__DIR__) . '/app/views/partials/public-header.php';
?>
<section class="content-section soft"><div class="container"><div class="account-auth-grid"><div><span class="section-kicker">Easyway online</span><h1 class="section-heading">One account for every delivery.</h1><p class="section-lead">Save your addresses, book at configured rates, pay securely and keep invoices, receipts, tracking and proof of delivery together.</p><ul class="check-list mt-4"><li><i class="bi bi-check-lg"></i><span>Server-verified pricing and payment records</span></li><li><i class="bi bi-check-lg"></i><span>Automatic booking documents and notifications</span></li><li><i class="bi bi-check-lg"></i><span>Secure delivery history in your account</span></li></ul></div><div class="easy-form-card"><h2 class="h3 mb-4">Create your account</h2><form method="post" action="<?= e(url('controller/router.php?action=customer.register')) ?>" novalidate><?= csrf_field() ?>
<div class="mb-3"><label for="register-name">Full name</label><input class="form-control" id="register-name" name="full_name" autocomplete="name" value="<?= form_value($state, 'full_name') ?>" required><?= form_error($state, 'full_name') ?></div>
<div class="mb-3"><label for="register-email">Email address</label><input class="form-control" type="email" id="register-email" name="email" autocomplete="email" value="<?= form_value($state, 'email') ?>" required><?= form_error($state, 'email') ?></div>
<div class="mb-3"><label for="register-phone">Phone number</label><input class="form-control" type="tel" id="register-phone" name="phone" autocomplete="tel" value="<?= form_value($state, 'phone') ?>" required><?= form_error($state, 'phone') ?></div>
<div class="mb-3"><label for="register-password">Password</label><input class="form-control" type="password" id="register-password" name="password" autocomplete="new-password" minlength="12" required><small class="text-muted">At least 12 characters.</small><?= form_error($state, 'password') ?></div>
<div class="mb-4"><label for="register-confirm">Confirm password</label><input class="form-control" type="password" id="register-confirm" name="password_confirmation" autocomplete="new-password" minlength="12" required><?= form_error($state, 'password_confirmation') ?></div>
<button class="easy-btn w-100" type="submit">Create account</button><p class="text-center mt-3 mb-0">Already registered? <a href="<?= e(url('customer/login.php')) ?>">Sign in</a></p></form></div></div></div></section>
<?php require dirname(__DIR__) . '/app/views/partials/public-footer.php'; ?>
