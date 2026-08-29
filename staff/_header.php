<?php

declare(strict_types=1);

use App\Auth;

Auth::requireRole(['admin', 'dispatcher']);
$staffUser = Auth::user();
$staffTitle = $staffTitle ?? 'Operations';
$staffCurrent = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($staffTitle) ?> | Easyway Staff</title>
    <link rel="icon" href="<?= e(url('assets/img/easyway/logo.jpg')) ?>" type="image/jpeg">
    <link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/bootstrap-icons.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/staff.css')) ?>">
</head>
<body class="staff-body">
<div class="staff-layout">
    <aside class="staff-sidebar" data-staff-sidebar>
        <a href="<?= e(url('staff/index.php')) ?>"><img class="staff-logo" src="<?= e(url('assets/img/easyway/logo.jpg')) ?>" alt="Easyway Logistics"></a>
        <nav class="staff-nav" aria-label="Staff navigation">
            <a class="<?= $staffCurrent === 'index.php' ? 'active' : '' ?>" href="<?= e(url('staff/index.php')) ?>"><i class="bi bi-grid"></i> Dashboard</a>
            <a class="<?= in_array($staffCurrent, ['shipments.php','shipment.php'], true) ? 'active' : '' ?>" href="<?= e(url('staff/shipments.php')) ?>"><i class="bi bi-box-seam"></i> Shipments</a>
            <a class="<?= $staffCurrent === 'bookings.php' ? 'active' : '' ?>" href="<?= e(url('staff/bookings.php')) ?>"><i class="bi bi-calendar-check"></i> Online Bookings</a>
            <a class="<?= $staffCurrent === 'riders.php' ? 'active' : '' ?>" href="<?= e(url('staff/riders.php')) ?>"><i class="bi bi-bicycle"></i> Riders</a>
            <a class="<?= $staffCurrent === 'corporate.php' ? 'active' : '' ?>" href="<?= e(url('staff/corporate.php')) ?>"><i class="bi bi-buildings"></i> Corporate</a>
            <a class="<?= $staffCurrent === 'bulk.php' ? 'active' : '' ?>" href="<?= e(url('staff/bulk.php')) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Bulk Batches</a>
            <a class="<?= $staffCurrent === 'cargo.php' ? 'active' : '' ?>" href="<?= e(url('staff/cargo.php')) ?>"><i class="bi bi-globe2"></i> Cargo</a>
            <a class="<?= $staffCurrent === 'reports.php' ? 'active' : '' ?>" href="<?= e(url('staff/reports.php')) ?>"><i class="bi bi-bar-chart"></i> Reports</a>
            <?php if (($staffUser['role'] ?? '') === 'admin'): ?><a class="<?= $staffCurrent === 'rates.php' ? 'active' : '' ?>" href="<?= e(url('staff/rates.php')) ?>"><i class="bi bi-calculator"></i> Rates</a><?php endif; ?>
            <a class="<?= $staffCurrent === 'notifications.php' ? 'active' : '' ?>" href="<?= e(url('staff/notifications.php')) ?>"><i class="bi bi-bell"></i> Notifications</a>
            <a class="<?= $staffCurrent === 'inquiries.php' ? 'active' : '' ?>" href="<?= e(url('staff/inquiries.php')) ?>"><i class="bi bi-inbox"></i> Quotes & Messages</a>
            <a href="<?= e(url('tracking.php')) ?>" target="_blank"><i class="bi bi-search"></i> Public Tracking</a>
            <a href="<?= e(url('index.php')) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Public Website</a>
        </nav>
        <div class="staff-user"><strong><?= e($staffUser['name'] ?? '') ?></strong><span><?= e(ucfirst((string) ($staffUser['role'] ?? 'staff'))) ?></span></div>
    </aside>
    <div class="staff-content">
        <header class="staff-topbar">
            <div class="d-flex align-items-center gap-3"><button class="staff-menu-toggle" type="button" data-staff-menu aria-label="Open staff navigation"><i class="bi bi-list"></i></button><h1><?= e($staffTitle) ?></h1></div>
            <form method="post" action="<?= e(url('controller/router.php?action=staff.logout')) ?>"><?= csrf_field() ?><button class="staff-btn light" type="submit"><i class="bi bi-box-arrow-right"></i> Sign out</button></form>
        </header>
        <?= flash_markup() ?>
        <main class="staff-main">
