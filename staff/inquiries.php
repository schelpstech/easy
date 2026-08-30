<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\InquiryInboxService;
use App\Validator;

Auth::requireRole(['admin', 'dispatcher']);
$type = Validator::choice($_GET['type'] ?? 'quote', InquiryInboxService::TYPES, 'quote');
$statuses = InquiryInboxService::statuses($type);
$status = Validator::choice($_GET['status'] ?? '', array_keys($statuses));
$search = Validator::text($_GET['q'] ?? '', 120);
$inbox = InquiryInboxService::listing($type, $status, $search, max(1, (int) ($_GET['page'] ?? 1)));
$installed = InquiryInboxService::installed();
$staffTitle = 'Quotes and messages';
require __DIR__ . '/_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div><h2 class="h4 mb-1">Your customer inbox</h2><p class="text-muted mb-0">Open an inquiry to reply, prepare a quotation, or record your follow-up.</p></div>
    <?php if (Auth::user()['role'] === 'admin'): ?><a class="staff-btn light" href="<?= e(url('staff/settings.php?channel=email')) ?>">Email settings</a><?php endif; ?>
</div>
<?php if (!$installed): ?><div class="alert alert-warning">Inbox actions need a one-time setup: <code>php tools/install_inquiry_inbox.php</code>. Existing inquiries are still available to read.</div><?php endif; ?>
<nav class="settings-tabs mb-4" aria-label="Inquiry types">
    <a class="<?= $type === 'quote' ? 'active' : '' ?>" <?= $type === 'quote' ? 'aria-current="page"' : '' ?> href="<?= e(url('staff/inquiries.php?type=quote')) ?>">Quote requests</a>
    <a class="<?= $type === 'contact' ? 'active' : '' ?>" <?= $type === 'contact' ? 'aria-current="page"' : '' ?> href="<?= e(url('staff/inquiries.php?type=contact')) ?>">Contact messages</a>
</nav>
<section class="staff-card">
    <form class="staff-form row g-3 align-items-end mb-4" method="get">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <div class="col-md-6"><label for="inbox-search">Find an inquiry</label><input class="form-control" type="search" id="inbox-search" name="q" value="<?= e($search) ?>" maxlength="120" placeholder="Reference, customer name or email"></div>
        <div class="col-md-3"><label for="inbox-status">Status</label><select class="form-select" id="inbox-status" name="status"><option value="">All statuses</option><?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>" <?= $key === $status ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 d-flex gap-2"><button class="staff-btn" type="submit">Filter</button><a class="staff-btn light" href="<?= e(url('staff/inquiries.php?type=' . $type)) ?>">Clear</a></div>
    </form>
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><h2 class="h5 mb-0"><?= $type === 'quote' ? 'Quote requests' : 'Contact messages' ?></h2><span class="small text-muted"><?= e($inbox['total']) ?> result(s)</span></div>
    <div class="table-responsive"><table class="table staff-table"><thead><tr><th>Reference / received</th><th>Customer</th><th><?= $type === 'quote' ? 'Shipment request' : 'Message' ?></th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php if ($inbox['rows'] === []): ?><tr><td colspan="5" class="text-center text-muted py-5">No inquiries match these filters.</td></tr><?php endif; ?>
    <?php foreach ($inbox['rows'] as $inquiry): $detailUrl = url('staff/inquiry.php?type=' . $type . '&id=' . (int) $inquiry['id']); ?>
        <tr><td><a class="fw-bold" href="<?= e($detailUrl) ?>"><?= e($inquiry['reference']) ?></a><br><small class="text-muted"><?= e(date('j M Y, g:i A', strtotime($inquiry['created_at']))) ?></small></td>
        <td><strong><?= e($inquiry['full_name']) ?></strong><br><span class="inquiry-email"><?= e($inquiry['email']) ?></span><?php if ($inquiry['phone']): ?><br><small><?= e($inquiry['phone']) ?></small><?php endif; ?></td>
        <td><?php if ($type === 'quote'): ?><strong><?= e($inquiry['from_location']) ?> → <?= e($inquiry['to_location']) ?></strong><br><small><?= e($inquiry['delivery_type']) ?> · <?= e($inquiry['weight_range']) ?> · <?= e($inquiry['quantity']) ?> piece(s)</small><?php else: ?><strong><?= e($inquiry['subject']) ?></strong><br><small class="text-muted"><?= e(mb_strimwidth($inquiry['message'], 0, 140, '…')) ?></small><?php endif; ?></td>
        <td><span class="staff-badge <?= in_array($inquiry['status'], ['new','closed','declined'], true) ? 'neutral' : '' ?>"><?= e($statuses[$inquiry['status']] ?? ucfirst($inquiry['status'])) ?></span></td>
        <td><a class="staff-btn light text-nowrap" href="<?= e($detailUrl) ?>">Open inquiry <i class="bi bi-arrow-right"></i></a></td></tr>
    <?php endforeach; ?></tbody></table></div>
    <nav class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4" aria-label="Inbox pages"><span class="small text-muted">Page <?= e($inbox['page']) ?> of <?= e($inbox['pages']) ?></span><div class="d-flex gap-2"><?php foreach (['Previous' => $inbox['page'] - 1, 'Next' => $inbox['page'] + 1] as $label => $number): ?><?php if ($number >= 1 && $number <= $inbox['pages']): ?><a class="staff-btn light" href="<?= e(url('staff/inquiries.php?' . http_build_query(['type' => $type, 'status' => $status, 'q' => $search, 'page' => $number]))) ?>"><?= e($label) ?></a><?php endif; ?><?php endforeach; ?></div></nav>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
