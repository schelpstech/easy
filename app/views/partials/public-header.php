<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Easyway Logistics';
$pageDescription = $pageDescription ?? 'Reliable delivery, courier and cargo support within Nigeria and internationally.';
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?> | Easyway Logistics</title>
    <link rel="icon" href="<?= e(url('assets/img/easyway/logo.jpg')) ?>" type="image/jpeg">
    <link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/bootstrap-icons.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/boxicons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/stage1.css')) ?>">
</head>
<body class="easyway-public">
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="stage1-topbar">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <p class="mb-0"><i class="bi bi-geo-alt"></i> Iyana Ilogbo, Ogun State, Nigeria</p>
        <div class="d-flex flex-wrap gap-3">
            <a href="tel:<?= e(preg_replace('/\s+/', '', support_phone())) ?>"><i class="bi bi-telephone"></i> <?= e(support_phone()) ?></a>
            <a href="mailto:<?= e(support_email()) ?>"><i class="bi bi-envelope"></i> <?= e(support_email()) ?></a>
        </div>
    </div>
</div>
<header class="stage1-header">
    <div class="container stage1-nav-wrap">
        <a class="stage1-brand" href="<?= e(url('index.php')) ?>" aria-label="Easyway Logistics home">
            <img src="<?= e(url('assets/img/easyway/logo.jpg')) ?>" alt="Easyway Logistics">
        </a>
        <button class="stage1-menu-toggle" type="button" aria-expanded="false" aria-controls="stage1-menu" data-site-menu-toggle>
            <span class="visually-hidden">Open navigation</span>
            <i class="bi bi-list"></i>
        </button>
        <nav id="stage1-menu" class="stage1-menu" aria-label="Primary navigation" data-site-menu>
            <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= e(url('index.php')) ?>">Home</a>
            <a class="<?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= e(url('about.php')) ?>">About Us</a>
            <div class="stage1-menu-group">
                <button type="button" data-menu-group-toggle aria-expanded="false">Solutions <i class="bi bi-chevron-down"></i></button>
                <div class="stage1-submenu">
                    <a href="<?= e(url('services.php')) ?>">Delivery Services</a>
                    <a href="<?= e(url('destinations.php')) ?>">International Destinations</a>
                    <a href="<?= e(url('cargo-services.php')) ?>">Cargo Services</a>
                    <a href="<?= e(url('packaging-materials.php')) ?>">Packaging Materials</a>
                    <a href="<?= e(url('calculator.php')) ?>">Shipping Calculator</a>
                </div>
            </div>
            <a class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= e(url('contact.php')) ?>">Contact</a>
            <a href="<?= e(url(App\CustomerAuth::check() ? 'customer/index.php' : 'customer/login.php')) ?>"><?= App\CustomerAuth::check() ? 'My Account' : 'Sign In' ?></a>
            <a class="stage1-track-link <?= $currentPage === 'tracking.php' ? 'active' : '' ?>" href="<?= e(url('tracking.php')) ?>">Track Shipment</a>
            <a class="stage1-quote-link" href="<?= e(url('quote.php')) ?>">Get a Quote</a>
        </nav>
    </div>
</header>
<?= flash_markup() ?>
<main id="main-content">
