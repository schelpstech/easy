<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\NotificationService;

Auth::requireRole(['admin', 'dispatcher']);
$notifications = NotificationService::recent();
$configured = [
    'Email' => NotificationService::channelEnabled('email'),
    'SMS' => NotificationService::channelEnabled('sms'),
    'WhatsApp' => NotificationService::channelEnabled('whatsapp'),
];
$staffTitle = 'Notification outbox';
require __DIR__ . '/_header.php';
?>
<?php if ((Auth::user()['role'] ?? '') === 'admin'): ?><div class="mb-4"><a class="staff-btn" href="<?= e(url('staff/settings.php')) ?>"><i class="bi bi-sliders"></i> Configure email, SMS &amp; WhatsApp</a></div><?php endif; ?>
<div class="row g-4 mb-4"><?php foreach ($configured as $channel => $enabled): ?><div class="col-md-4"><div class="staff-card stat-card"><span><?= e($channel) ?> delivery</span><strong class="fs-3 <?= $enabled ? 'text-success' : 'text-secondary' ?>"><?= $enabled ? 'Enabled' : 'Not configured' ?></strong></div></div><?php endforeach; ?></div><section class="staff-card"><div class="mb-3"><h2 class="h4 mb-1">Queued customer updates</h2><p class="text-muted mb-0">Run <code>php tools/process_notifications.php</code> on a cron schedule. Disabled channels remain pending and can be delivered after configuration.</p></div><div class="table-responsive"><table class="table staff-table"><thead><tr><th>Created</th><th>Channel</th><th>Recipient</th><th>Booking / shipment</th><th>Message</th><th>Status</th></tr></thead><tbody><?php if ($notifications === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No notifications queued yet.</td></tr><?php endif; ?><?php foreach ($notifications as $notification): ?><tr><td><?= e(date('j M, g:i A', strtotime((string) $notification['created_at']))) ?></td><td><?= e(strtoupper((string) $notification['channel'])) ?></td><td><?= e($notification['recipient']) ?></td><td><?= e($notification['booking_number'] ?: $notification['tracking_number'] ?: 'General') ?></td><td><strong><?= e($notification['subject'] ?: $notification['template_code']) ?></strong><br><small><?= e(mb_strimwidth((string) $notification['message'], 0, 95, '…')) ?></small></td><td><span class="staff-badge"><?= e(ucfirst((string) $notification['status'])) ?></span><?php if ($notification['last_error']): ?><br><small class="text-danger"><?= e($notification['last_error']) ?></small><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/_footer.php'; ?>
