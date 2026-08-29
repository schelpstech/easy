<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;

if (Auth::check()) {
    redirect(staff_home_path());
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Staff Sign In | Easyway Logistics</title>
    <link rel="icon" href="<?= e(url('assets/img/easyway/logo.jpg')) ?>" type="image/jpeg">
    <link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/staff.css')) ?>">
</head>

<body>
    <main class="staff-login">
        <section class="staff-login-card" aria-labelledby="login-title">
            <img src="<?= e(url('assets/img/easyway/logo.jpg')) ?>" alt="Easyway Logistics">
            <?= flash_markup() ?>
            <div class="text-center mb-4">
                <h1 id="login-title" class="h3">Staff operations</h1>
                <p class="text-muted mb-0">Sign in to create shipments and update tracking milestones.</p>
            </div>
            <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.login')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label for="staff-email">Email address</label><input class="form-control" type="email" id="staff-email" name="email" autocomplete="username" required></div>
                <div class="mb-4"><label for="staff-password">Password</label><input class="form-control" type="password" id="staff-password" name="password" autocomplete="current-password" required></div>
                <button class="staff-btn w-100" type="submit">Sign in</button>
            </form>
            <p class="text-center mt-4 mb-0"><a href="<?= e(url('index.php')) ?>">Return to website</a></p>
        </section>
    </main>
</body>

</html>
