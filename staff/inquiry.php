<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Auth;
use App\InquiryInboxService;
use App\Validator;
Auth::requireRole(['admin', 'dispatcher']);
$type = Validator::choice($_GET['type'] ?? '', InquiryInboxService::TYPES);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($type === '' || $id === false || !($inquiry = InquiryInboxService::find($type, (int) $id))) { http_response_code(404); exit('Inquiry not found.'); }
$installed = InquiryInboxService::installed();
$history = InquiryInboxService::history($type, (int) $id);
$revision = (int) ($history[0]['id'] ?? 0);
$statuses = InquiryInboxService::statuses($type);
$phone = InquiryInboxService::phoneLinks((string) $inquiry['phone'], $inquiry['reference']);
$email = InquiryInboxService::emailReadiness();
$state = pull_form_state('inquiry_' . $type . '_' . $id);
$active = Validator::choice($state['data']['kind'] ?? '', ['reply','quotation','note'], 'reply');
if ($active === 'quotation' && $type !== 'quote') { $active = 'reply'; }
$draft = static fn(string $kind, string $key, string $default = ''): string => $active === $kind ? form_value($state, $key, $default) : e($default);
$formFields = static function () use ($type, $id, $revision): void { ?>
    <?= csrf_field() ?><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="revision" value="<?= $revision ?>"><input type="hidden" name="request_token" value="<?= e(bin2hex(random_bytes(32))) ?>">
<?php };
$lastTerms = '';
foreach ($history as $entry) { if ($entry['kind'] === 'quotation') { $lastTerms = (string) (json_decode((string) $entry['metadata_json'], true)['terms'] ?? ''); break; } }
$staffTitle = $type === 'quote' ? 'Quote request' : 'Contact message';
require __DIR__ . '/_header.php';
?>
<a class="d-inline-flex align-items-center gap-2 mb-3" href="<?= e(url('staff/inquiries.php?type=' . $type)) ?>"><i class="bi bi-arrow-left"></i> Back to <?= $type === 'quote' ? 'quote requests' : 'messages' ?></a>
<div class="inquiry-heading mb-4"><div><span class="small text-muted fw-bold"><?= e($inquiry['reference']) ?></span><h2 class="h3 mb-1"><?= e($inquiry['full_name']) ?></h2><p class="text-muted mb-0">Received <?= e(date('j M Y, g:i A', strtotime($inquiry['created_at']))) ?></p></div><span class="staff-badge <?= in_array($inquiry['status'], ['new','closed','declined'], true) ? 'neutral' : '' ?>"><?= e($statuses[$inquiry['status']] ?? $inquiry['status']) ?></span></div>
<div class="d-xl-none d-flex flex-wrap gap-2 mb-4" aria-label="Quick inquiry actions">
    <?php if ($phone['whatsapp']): ?><a class="staff-btn light" href="<?= e($phone['whatsapp']) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
    <?php if ($phone['call']): ?><a class="staff-btn light" href="<?= e($phone['call']) ?>"><i class="bi bi-telephone"></i> Call</a><?php endif; ?>
    <a class="staff-btn light" href="#inquiry-status-card">Status</a>
</div>
<?php if (!$installed): ?><div class="alert alert-warning">Run <code>php tools/install_inquiry_inbox.php</code> once to enable replies, notes and status updates.</div><?php endif; ?>
<div class="row g-4"><div class="col-xl-8">
    <section class="staff-card mb-4"><h2 class="h5 mb-3"><?= $type === 'quote' ? 'Shipment request' : e($inquiry['subject']) ?></h2>
    <?php if ($type === 'quote'): ?><dl class="row inquiry-facts mb-0"><dt class="col-sm-4">Route</dt><dd class="col-sm-8"><?= e($inquiry['from_location']) ?> → <?= e($inquiry['to_location']) ?></dd><dt class="col-sm-4">Service</dt><dd class="col-sm-8"><?= e($inquiry['delivery_type']) ?></dd><dt class="col-sm-4">Shipment</dt><dd class="col-sm-8"><?= e($inquiry['shipment_type']) ?> · <?= e($inquiry['weight_range']) ?> · <?= e($inquiry['quantity']) ?> piece(s)</dd><?php if ($inquiry['quoted_amount'] !== null): ?><dt class="col-sm-4">Latest quotation</dt><dd class="col-sm-8"><?= e($inquiry['currency']) ?> <?= e(number_format((float) $inquiry['quoted_amount'], 2)) ?></dd><?php endif; ?></dl><?php if ($inquiry['notes']): ?><div class="inquiry-message mt-3"><?= e($inquiry['notes']) ?></div><?php endif; ?>
    <?php else: ?><div class="inquiry-message"><?= e($inquiry['message']) ?></div><?php endif; ?></section>
    <section class="staff-card mb-4">
        <ul class="nav nav-pills inquiry-action-tabs gap-2 mb-4" role="tablist" aria-label="Inquiry actions"><?php foreach (['reply' => 'Reply by email'] + ($type === 'quote' ? ['quotation' => 'Send quotation'] : []) + ['note' => 'Staff note'] as $kind => $label): ?><li class="nav-item" role="presentation"><button class="nav-link <?= $active === $kind ? 'active' : '' ?>" id="<?= e($kind) ?>-tab" data-bs-toggle="pill" data-bs-target="#<?= e($kind) ?>-panel" type="button" role="tab" aria-controls="<?= e($kind) ?>-panel" aria-selected="<?= $active === $kind ? 'true' : 'false' ?>"><?= e($label) ?></button></li><?php endforeach; ?></ul>
        <?php if (!$email['ready']): ?><div class="alert alert-warning"><?= e($email['message']) ?><?php if (Auth::user()['role'] === 'admin'): ?> <a href="<?= e(url('staff/settings.php?channel=email')) ?>">Open email settings</a>.<?php endif; ?></div><?php endif; ?>
        <div class="tab-content">
        <div class="tab-pane fade <?= $active === 'reply' ? 'show active' : '' ?>" id="reply-panel" role="tabpanel" aria-labelledby="reply-tab" tabindex="0">
            <p class="small text-muted">To <strong class="inquiry-email"><?= e($inquiry['email']) ?></strong>. The scheduled worker sends your queued email through the saved SMTP configuration.</p>
            <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.inquiry.reply')) ?>"><?php $formFields(); ?>
                <div class="mb-3"><label for="reply-subject">Subject</label><input class="form-control" id="reply-subject" name="subject" maxlength="140" value="<?= $draft('reply', 'subject', $type === 'contact' ? mb_substr('Re: ' . $inquiry['subject'], 0, 140) : 'Your shipment inquiry') ?>" required></div>
                <div class="mb-3"><label for="reply-body">Your reply</label><textarea class="form-control inquiry-compose" id="reply-body" name="body" rows="7" maxlength="6000" placeholder="Write your response to the customer…" required><?= $draft('reply', 'body') ?></textarea><small class="text-muted">A greeting, inquiry reference and Easyway signature are added automatically.</small></div>
                <button class="staff-btn" type="submit" <?= !$installed || !$email['ready'] ? 'disabled' : '' ?>><i class="bi bi-send"></i> Queue email reply</button>
            </form>
        </div>
        <?php if ($type === 'quote'): ?><div class="tab-pane fade <?= $active === 'quotation' ? 'show active' : '' ?>" id="quotation-panel" role="tabpanel" aria-labelledby="quotation-tab" tabindex="0">
            <p class="small text-muted">Send the confirmed total and terms to <strong class="inquiry-email"><?= e($inquiry['email']) ?></strong>. The email includes the requested route and service. It does not create an invoice or booking.</p>
            <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.inquiry.quotation')) ?>"><?php $formFields(); ?>
                <div class="row g-3 mb-3"><div class="col-sm-8"><label for="quote-amount">Total quoted amount</label><input class="form-control" type="number" inputmode="decimal" id="quote-amount" name="amount" min="0.01" max="999999999999.99" step="0.01" value="<?= $draft('quotation', 'amount', (string) ($inquiry['quoted_amount'] ?? '')) ?>" required></div><div class="col-sm-4"><label for="quote-currency">Currency</label><select class="form-select" id="quote-currency" name="currency"><?php foreach (InquiryInboxService::CURRENCIES as $currency): ?><option value="<?= e($currency) ?>" <?= $draft('quotation', 'currency', $inquiry['currency']) === $currency ? 'selected' : '' ?>><?= e($currency) ?></option><?php endforeach; ?></select></div></div>
                <div class="mb-3"><label for="quote-terms">Terms and conditions</label><textarea class="form-control inquiry-compose" id="quote-terms" name="terms" rows="7" maxlength="6000" placeholder="State what the price includes, taxes or exclusions, delivery estimate, quotation validity and payment terms." required><?= $draft('quotation', 'terms', $lastTerms) ?></textarea><small class="text-muted">Enter the agreed terms explicitly. The exact amount, currency and terms are retained in the history.</small></div>
                <button class="staff-btn" type="submit" <?= !$installed || !$email['ready'] ? 'disabled' : '' ?>><i class="bi bi-envelope-paper"></i> Send quotation</button><p class="small text-muted mt-2 mb-0">Adds one quotation email to the queue; check its status below.</p>
            </form>
        </div><?php endif; ?>
        <div class="tab-pane fade <?= $active === 'note' ? 'show active' : '' ?>" id="note-panel" role="tabpanel" aria-labelledby="note-tab" tabindex="0">
            <p class="small text-muted"><i class="bi bi-lock"></i> Internal only. Notes are never included in customer emails.</p>
            <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.inquiry.note')) ?>"><?php $formFields(); ?>
                <div class="mb-3"><label for="staff-note">Staff note</label><textarea class="form-control inquiry-compose" id="staff-note" name="note" rows="5" maxlength="6000" placeholder="Record a call, WhatsApp follow-up or instructions for your team…" required><?= $draft('note', 'note') ?></textarea></div>
                <button class="staff-btn light" type="submit" <?= !$installed ? 'disabled' : '' ?>>Save internal note</button>
            </form>
        </div></div>
    </section>
    <section class="staff-card"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h2 class="h5 mb-0">Reply and activity history</h2><span class="small text-muted">Latest 100 entries</span></div>
        <?php if ($history === []): ?><p class="text-muted mb-0">No follow-up recorded yet. Your team's replies, quotations, notes and status changes will appear here.</p><?php endif; ?>
        <div class="inquiry-history"><?php foreach ($history as $entry): $emailEntry = in_array($entry['kind'], ['reply','quotation'], true); $metadata = json_decode((string) $entry['metadata_json'], true) ?: []; ?>
            <article class="inquiry-entry <?= $entry['kind'] === 'note' ? 'internal-note' : '' ?>">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2"><strong><?= e(['reply' => 'Email reply','quotation' => 'Quotation','note' => 'Internal note','status' => 'Status update'][$entry['kind']] ?? 'Activity') ?></strong><span class="small text-muted"><?= e($entry['staff_name'] ?: 'Former staff member') ?> · <?= e(date('j M Y, g:i A', strtotime($entry['created_at']))) ?></span></div>
                <?php if ($emailEntry): ?><p class="small mb-2"><span class="staff-badge <?= $entry['delivery_status'] === 'sent' ? '' : 'neutral' ?>"><?= e(match ($entry['delivery_status']) { 'sent' => 'Accepted by mail server', 'failed' => 'Email failed', 'pending' => (int) $entry['attempts'] > 0 ? 'Queued for retry' : 'Queued for email', default => 'Delivery record unavailable' }) ?></span> <span class="text-muted inquiry-email">To <?= e($metadata['recipient'] ?? '') ?></span></p><?php if ($entry['last_error']): ?><p class="small text-danger"><?= e($entry['last_error']) ?></p><?php endif; ?><?php endif; ?>
                <?php if ($entry['subject']): ?><h3 class="h6"><?= e($entry['subject']) ?></h3><?php endif; ?><div class="inquiry-message"><?= e($entry['body']) ?></div>
            </article>
        <?php endforeach; ?></div>
    </section>
</div><aside class="col-xl-4">
    <section class="staff-card mb-4"><h2 class="h5 mb-3">Customer contact</h2><p class="inquiry-email mb-2"><?= e($inquiry['email']) ?></p><?php if ($inquiry['phone']): ?><p class="text-muted mb-3"><?= e($inquiry['phone']) ?></p><?php endif; ?><?php if ($type === 'contact' && $inquiry['company_name']): ?><p class="small text-muted"><?= e($inquiry['company_name']) ?></p><?php endif; ?>
        <div class="d-flex flex-wrap gap-2"><?php if ($phone['whatsapp']): ?><a class="staff-btn light" href="<?= e($phone['whatsapp']) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?><?php if ($phone['call']): ?><a class="staff-btn light" href="<?= e($phone['call']) ?>"><i class="bi bi-telephone"></i> Call customer</a><?php endif; ?></div>
        <p class="small text-muted mt-3 mb-0">These shortcuts open your phone or WhatsApp app; they do not automatically send or log a message. Record the outcome as a staff note.</p>
    </section>
    <section class="staff-card mb-4" id="inquiry-status-card"><h2 class="h5 mb-3">Inquiry status</h2><form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.inquiry.status')) ?>"><?php $formFields(); ?><label for="inquiry-status">Current status</label><select class="form-select mb-3" id="inquiry-status" name="status" required><?php if (!isset($statuses[$inquiry['status']])): ?><option value="" selected>Select a status (current: <?= e($inquiry['status']) ?>)</option><?php endif; ?><?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>" <?= $key === $inquiry['status'] ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="staff-btn light" type="submit" <?= !$installed ? 'disabled' : '' ?>>Update status</button></form><p class="small text-muted mt-3 mb-0">Statuses are staff-managed and separate from email delivery. A first queued reply moves a New inquiry to In progress.</p></section>
    <section class="staff-card"><h2 class="h6">Where customer replies arrive</h2><p class="small text-muted mb-0">Customers reply to the configured sender mailbox<?= $email['sender'] ? ' (' . e($email['sender']) . ')' : '' ?>. Incoming emails are not automatically imported here. Email acceptance by the provider is not a delivery or read receipt.</p></section>
</aside></div>
<?php require __DIR__ . '/_footer.php'; ?>
