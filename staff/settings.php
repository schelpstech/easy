<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Auth;
use App\Database;
use App\NotificationSettings;
use App\Validator;
Auth::requireRole(['admin']);
$channel = Validator::choice($_GET['channel'] ?? 'email', NotificationSettings::CHANNELS, 'email');
$settings = NotificationSettings::get($channel);
$installed = NotificationSettings::installed();
$encryption = NotificationSettings::encryptionStatus();
$pending = Database::connection()->prepare('SELECT COUNT(*) FROM notification_outbox WHERE channel=:channel AND status="pending"');
$pending->execute(['channel' => $channel]); $pendingCount = (int) $pending->fetchColumn();
$staffTitle = 'Delivery settings';
require __DIR__ . '/_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><h2 class="h4 mb-1">Customer communication</h2><p class="text-muted mb-0">Configure a channel, save it, then send a test to a number or inbox you control.</p></div><a class="staff-btn light" href="<?= e(url('staff/notifications.php')) ?>">View notification outbox</a></div>
<?php if (!$installed): ?><div class="alert alert-warning">Run <code>php tools/install_staff_settings.php</code> once before saving provider settings. Current environment configuration continues to work.</div><?php endif; ?>
<?php if (!$encryption['ready']): ?><div class="alert alert-warning" role="alert"><strong>Credential encryption needs attention.</strong> <?= e($encryption['message']) ?> No existing key will be replaced automatically.</div><?php endif; ?>
<nav class="settings-tabs mb-4" aria-label="Notification channels"><?php foreach (['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $key => $label): ?><a class="<?= $channel === $key ? 'active' : '' ?>" <?= $channel === $key ? 'aria-current="page"' : '' ?> href="<?= e(url('staff/settings.php?channel=' . $key)) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
<div class="row g-4"><div class="col-xl-7"><section class="staff-card">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><h2 class="h4 mb-0"><?= e($channel === 'email' ? 'Email transport' : ($channel === 'sms' ? 'SMS adapter' : 'WhatsApp adapter')) ?></h2><span class="staff-badge <?= $settings['enabled'] ? '' : 'neutral' ?>"><?= $settings['enabled'] ? 'Enabled' : 'Disabled' ?></span></div>
    <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.notification_settings.save')) ?>"><?= csrf_field() ?><input type="hidden" name="channel" value="<?= e($channel) ?>"><input type="hidden" name="version" value="<?= e($settings['version']) ?>">
    <fieldset <?= !$installed ? 'disabled' : '' ?>>
    <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" role="switch" id="channel-enabled" name="enabled" value="1" <?= $settings['enabled'] ? 'checked' : '' ?>><label class="form-check-label" for="channel-enabled">Enable automated <?= e($channel === 'email' ? 'email' : strtoupper($channel)) ?> delivery</label></div>
    <?php if ($channel === 'email'): ?>
    <div class="mb-3"><label for="transport">Transport</label><select class="form-select" id="transport" name="transport"><option value="smtp" <?= $settings['transport'] === 'smtp' ? 'selected' : '' ?>>Authenticated SMTP</option><option value="mail" <?= $settings['transport'] === 'mail' ? 'selected' : '' ?>>Server mail (hosting configured)</option></select><small class="text-muted">SMTP fields apply only to authenticated SMTP.</small></div>
    <div class="row g-3 mb-3"><div class="col-md-6"><label for="from-name">Sender name</label><input class="form-control" id="from-name" name="from_name" value="<?= e($settings['from_name']) ?>" maxlength="120" required></div><div class="col-md-6"><label for="from-email">Sender email</label><input class="form-control" type="email" id="from-email" name="from_email" value="<?= e($settings['from_email']) ?>" maxlength="190" required></div></div>
    <div class="mb-3"><label for="smtp-host">SMTP hostname</label><input class="form-control" id="smtp-host" name="host" value="<?= e($settings['host']) ?>" placeholder="smtp.your-provider.com" maxlength="253"></div>
    <div class="row g-3 mb-3"><div class="col-md-6"><label for="smtp-port">Port</label><select class="form-select" id="smtp-port" name="port"><?php foreach ([587,465,2525] as $port): ?><option value="<?= $port ?>" <?= (int) $settings['port'] === $port ? 'selected' : '' ?>><?= $port ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label for="smtp-encryption">Encryption</label><select class="form-select" id="smtp-encryption" name="encryption"><option value="starttls" <?= $settings['encryption'] === 'starttls' ? 'selected' : '' ?>>STARTTLS (usually 587)</option><option value="tls" <?= $settings['encryption'] === 'tls' ? 'selected' : '' ?>>Implicit TLS (usually 465)</option></select></div></div>
    <div class="mb-3"><label for="smtp-user">SMTP username</label><input class="form-control" id="smtp-user" name="username" value="<?= e($settings['username']) ?>" maxlength="190" autocomplete="off"></div>
    <?php else: ?>
    <p class="text-muted">Connect your provider through an HTTPS adapter that accepts the JSON contract shown here. This is not a direct provider-specific API integration.</p>
    <div class="mb-3"><label for="adapter-url">HTTPS adapter endpoint</label><input class="form-control" type="url" id="adapter-url" name="url" value="<?= e($settings['url']) ?>" placeholder="https://your-provider.com/easyway/send" maxlength="2000"><small class="text-muted">Public host, port 443. No redirects, URL credentials or query-string tokens.</small></div>
    <?php endif; ?>
    <div class="mb-3"><label for="provider-secret"><?= $channel === 'email' ? 'SMTP password / app password' : 'Bearer token (if required by adapter)' ?></label><input class="form-control" type="password" id="provider-secret" name="secret" autocomplete="new-password" maxlength="4096" placeholder="<?= $settings['has_secret'] ? 'Credential saved — leave blank to keep it' : 'No credential saved' ?>"><small class="text-muted">Saved credentials are encrypted and never shown again. Blank keeps the current value.</small></div>
    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="clear-secret" name="clear_secret" value="1"><label class="form-check-label" for="clear-secret">Remove saved credential (leave the field blank)</label></div>
    <div class="alert alert-info"><?= e($pendingCount) ?> pending <?= e($channel) ?> notification(s). Enabling this channel lets the scheduled worker send existing queued messages as well as new ones.</div>
    <div class="mb-4"><label for="settings-password">Your administrator password</label><input class="form-control" type="password" id="settings-password" name="current_password" autocomplete="current-password" required></div>
    <button class="staff-btn" type="submit">Save <?= e($channel) ?> settings</button>
    </fieldset></form>
</section></div>
<div class="col-xl-5"><section class="staff-card mb-4"><h2 class="h4 mb-2">Send a test</h2><p class="text-muted">Uses the <strong>saved</strong> settings, even while automatic delivery is disabled. Sends one fixed test only; it does not process the outbox.</p>
    <form class="staff-form" method="post" action="<?= e(url('controller/router.php?action=staff.notification_settings.test')) ?>"><?= csrf_field() ?><input type="hidden" name="channel" value="<?= e($channel) ?>">
        <div class="mb-3"><label for="test-recipient"><?= $channel === 'email' ? 'Your test inbox' : 'Your test phone (international format)' ?></label><input class="form-control" type="<?= $channel === 'email' ? 'email' : 'tel' ?>" id="test-recipient" name="recipient" placeholder="<?= $channel === 'email' ? 'you@example.com' : '+2349031134210' ?>" required></div>
        <div class="mb-4"><label for="test-password">Your administrator password</label><input class="form-control" type="password" id="test-password" name="current_password" autocomplete="current-password" required></div>
        <button class="staff-btn light" type="submit">Send <?= e($channel) ?> test now</button>
    </form>
</section><section class="staff-card"><h2 class="h5">Delivery notes</h2><p class="text-muted">Schedule <code>php tools/process_notifications.php</code> on your server. Saving configuration does not send messages or create a schedule.</p>
<?php if ($channel !== 'email'): ?><p class="text-muted">Adapter requests are JSON POSTs with optional <code>Authorization: Bearer …</code>:</p><pre class="settings-code">{"to":"+234…","message":"…","reference":"EWN-123"}</pre><p class="text-muted">The adapter must map this payload to your provider, handle sender IDs or approved WhatsApp templates, and return a non-2xx status if the provider rejects it. Deduplicate retries using <code>reference</code>.</p><?php else: ?><p class="text-muted">Use the SMTP hostname and app password supplied by your email provider. TLS certificate verification remains on. Server mail requires your hosting mail system to be configured.</p><?php endif; ?>
<p class="small text-muted mb-0">Settings saved here override the channel's environment defaults. Back up the private encryption key with your deployment; do not commit or share it.</p></section></div></div>
<?php require __DIR__ . '/_footer.php'; ?>
