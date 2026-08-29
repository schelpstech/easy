<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\CustomerAuth;

if (CustomerAuth::check()) { redirect('customer/index.php'); }
$pageTitle = 'Customer Sign In';
$pageDescription = 'Sign in to your Easyway Logistics customer account.';
require dirname(__DIR__) . '/app/views/partials/public-header.php';
?>
<section class="content-section soft"><div class="container"><div class="auth-card easy-form-card"><img src="<?= e(url('assets/img/easyway/logo.jpg')) ?>" alt="Easyway Logistics"><span class="section-kicker">Customer account</span><h1 class="h2 mb-2">Welcome back</h1><p class="text-muted mb-4">Manage bookings, payments and delivery records.</p><form method="post" action="<?= e(url('controller/router.php?action=customer.login')) ?>"><?= csrf_field() ?><div class="mb-3"><label for="login-email">Email address</label><input class="form-control" type="email" id="login-email" name="email" autocomplete="email" required></div><div class="mb-4"><label for="login-password">Password</label><input class="form-control" type="password" id="login-password" name="password" autocomplete="current-password" required></div><button class="easy-btn w-100" type="submit">Sign in</button></form><p class="text-center mt-3 mb-0">New to Easyway? <a href="<?= e(url('customer/register.php')) ?>">Create an account</a></p></div></div></section>
<?php require dirname(__DIR__) . '/app/views/partials/public-footer.php'; ?>
