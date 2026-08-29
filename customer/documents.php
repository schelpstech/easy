<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\BillingService;
use App\CustomerAuth;

CustomerAuth::requireCustomer();
$documents = BillingService::allForCustomer((int) CustomerAuth::id());
$pageTitle = 'Invoices and Receipts';
require dirname(__DIR__) . '/app/views/partials/public-header.php';
?>
<section class="account-heading compact"><div class="container"><div><span class="page-eyebrow">Billing records</span><h1>Invoices and receipts</h1><p>Invoices are issued with bookings. Verified payments create receipts automatically.</p></div></div></section><section class="account-section"><div class="container"><?php require __DIR__ . '/_nav.php'; ?><div class="easy-card"><div class="table-responsive"><table class="table account-table"><thead><tr><th>Document</th><th>Booking</th><th>Issued</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php if ($documents === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No billing documents yet.</td></tr><?php endif; ?><?php foreach ($documents as $document): ?><tr><td><strong><?= e($document['document_number']) ?></strong><br><small><?= e(ucfirst((string) $document['document_type'])) ?></small></td><td><?= e($document['booking_number']) ?></td><td><?= e(date('j M Y', strtotime((string) $document['issued_at']))) ?></td><td><?= e($document['currency']) ?> <?= e(number_format((float) $document['total_amount'], 2)) ?></td><td><span class="status-pill"><?= e(ucfirst((string) $document['status'])) ?></span></td><td><a target="_blank" href="<?= e(url('customer/document.php?id=' . $document['id'])) ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div></div></div></section>
<?php require dirname(__DIR__) . '/app/views/partials/public-footer.php'; ?>
