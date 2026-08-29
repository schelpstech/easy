<?php

declare(strict_types=1);

$customerCurrent = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
?>
<nav class="customer-nav" aria-label="Customer account">
    <a class="<?= $customerCurrent === 'index.php' ? 'active' : '' ?>" href="<?= e(url('customer/index.php')) ?>"><i class="bi bi-grid"></i> Overview</a>
    <a class="<?= in_array($customerCurrent, ['book.php','booking.php'], true) ? 'active' : '' ?>" href="<?= e(url('customer/book.php')) ?>"><i class="bi bi-plus-circle"></i> Book shipment</a>
    <a class="<?= $customerCurrent === 'addresses.php' ? 'active' : '' ?>" href="<?= e(url('customer/addresses.php')) ?>"><i class="bi bi-geo-alt"></i> Addresses</a>
    <a class="<?= in_array($customerCurrent, ['documents.php','document.php'], true) ? 'active' : '' ?>" href="<?= e(url('customer/documents.php')) ?>"><i class="bi bi-receipt"></i> Invoices & receipts</a>
    <a class="<?= in_array($customerCurrent, ['corporate.php','bulk-batch.php'], true) ? 'active' : '' ?>" href="<?= e(url('customer/corporate.php')) ?>"><i class="bi bi-buildings"></i> Corporate</a>
    <form method="post" action="<?= e(url('controller/router.php?action=customer.logout')) ?>"><?= csrf_field() ?><button type="submit"><i class="bi bi-box-arrow-right"></i> Sign out</button></form>
</nav>
