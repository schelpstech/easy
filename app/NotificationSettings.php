<?php

declare(strict_types=1);

namespace App;

use PDOException;
use RuntimeException;

final class NotificationSettings
{
    public const CHANNELS = ['email', 'sms', 'whatsapp'];

    public static function installed(): bool
    {
        try { Database::connection()->query('SELECT channel FROM notification_settings LIMIT 1'); return true; }
        catch (PDOException $e) { if ($e->getCode() === '42S02') { return false; } throw $e; }
    }

    public static function get(string $channel, bool $withSecret = false): array
    {
        self::assertChannel($channel);
        $prefix = strtoupper($channel);
        $secret = (string) Config::get($channel === 'email' ? 'SMTP_PASSWORD' : $prefix . '_WEBHOOK_TOKEN', '');
        $settings = ['enabled' => Config::bool($prefix . '_NOTIFICATIONS_ENABLED', false)];
        if ($channel === 'email') {
            $settings += ['transport' => Config::get('EMAIL_TRANSPORT', 'mail'), 'from_email' => Config::get('EMAIL_FROM', 'no-reply@easyway.ng'),
                'from_name' => Config::get('EMAIL_FROM_NAME', 'Easyway Logistics'), 'host' => Config::get('SMTP_HOST', ''),
                'port' => (int) Config::get('SMTP_PORT', '587'), 'encryption' => Config::get('SMTP_ENCRYPTION', 'starttls'), 'username' => Config::get('SMTP_USERNAME', '')];
        } else { $settings['url'] = (string) Config::get($prefix . '_WEBHOOK_URL', ''); }
        $version = 0;
        if (self::installed()) {
            $statement = Database::connection()->prepare('SELECT settings_json, secret_encrypted, version FROM notification_settings WHERE channel = :channel');
            $statement->execute(['channel' => $channel]);
            $row = $statement->fetch();
            if ($row) {
                $settings = json_decode((string) $row['settings_json'], true, 512, JSON_THROW_ON_ERROR);
                $version = (int) $row['version'];
                $secret = $row['secret_encrypted'] === null ? '' : ($withSecret ? self::decrypt((string) $row['secret_encrypted'], $channel) : '[stored]');
            }
        }
        $settings['has_secret'] = $secret !== '';
        $settings['secret'] = $withSecret ? $secret : '';
        $settings['version'] = $version;
        return $settings;
    }

    public static function save(string $channel, array $input): void
    {
        StaffAccountService::requireAdmin();
        self::assertChannel($channel);
        if (!Auth::verifyPassword((string) ($input['current_password'] ?? ''))) {
            throw new RuntimeException('Your current password is incorrect or verification is temporarily locked.');
        }
        if (!self::installed()) { throw new RuntimeException('Run php tools/install_staff_settings.php before saving settings.'); }
        $existing = self::get($channel, true);
        $secret = (string) ($input['secret'] ?? '');
        if ($secret === '') { $secret = !empty($input['clear_secret']) ? '' : $existing['secret']; }
        if (strlen($secret) > 4096 || preg_match('/[\r\n\x00]/', $secret)) { throw new RuntimeException('The credential has an invalid format or is too long.'); }
        $settings = ['enabled' => !empty($input['enabled'])];
        if ($channel === 'email') {
            $settings += ['transport' => (string) ($input['transport'] ?? ''), 'from_email' => trim((string) ($input['from_email'] ?? '')),
                'from_name' => trim((string) ($input['from_name'] ?? '')), 'host' => strtolower(trim((string) ($input['host'] ?? ''))),
                'port' => (int) ($input['port'] ?? 587), 'encryption' => (string) ($input['encryption'] ?? ''), 'username' => trim((string) ($input['username'] ?? ''))];
        } else { $settings['url'] = trim((string) ($input['url'] ?? '')); }
        self::validate($channel, $settings + ['secret' => $secret], $settings['enabled']);
        $encrypted = $secret === '' ? null : self::encrypt($secret, $channel);
        $pdo = Database::connection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) { $pdo->beginTransaction(); }
        try {
            $lock = $pdo->prepare('SELECT version FROM notification_settings WHERE channel = :channel FOR UPDATE');
            $lock->execute(['channel' => $channel]);
            $currentVersion = (int) ($lock->fetchColumn() ?: 0);
            if ($currentVersion !== (int) ($input['version'] ?? -1) || $existing['version'] !== $currentVersion) {
                throw new RuntimeException('These settings changed in another session. Reload the page before saving.');
            }
            $params = ['channel' => $channel, 'settings' => json_encode($settings, JSON_THROW_ON_ERROR), 'secret' => $encrypted, 'staff' => Auth::id()];
            if ($currentVersion === 0) {
                $pdo->prepare('INSERT INTO notification_settings (channel,settings_json,secret_encrypted,version,updated_by,updated_at) VALUES (:channel,:settings,:secret,1,:staff,NOW())')->execute($params);
            } else {
                $pdo->prepare('UPDATE notification_settings SET settings_json=:settings,secret_encrypted=:secret,version=version+1,updated_by=:staff,updated_at=NOW() WHERE channel=:channel')->execute($params);
            }
            AuditService::record('notifications.settings_saved', 'notification_settings', null, ['channel' => $channel, 'enabled' => $settings['enabled'], 'credential_changed' => $secret !== $existing['secret']]);
            if ($ownsTransaction) { $pdo->commit(); }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
            if ($e instanceof PDOException) { throw new RuntimeException('Settings could not be saved. Reload the page and try again.'); }
            throw $e;
        }
    }

    public static function validate(string $channel, array $settings, bool $ready = true): void
    {
        self::assertChannel($channel);
        if ($channel === 'email') {
            if (!in_array($settings['transport'], ['mail', 'smtp'], true) || !filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL)
                || preg_match('/[\r\n\x00]/', $settings['from_email'] . $settings['from_name'] . $settings['username'])
                || strlen($settings['from_email']) > 190 || mb_strlen($settings['from_name']) < 2 || mb_strlen($settings['from_name']) > 120
                || strlen($settings['username']) > 190) { throw new RuntimeException('Enter a valid sender name, email and email transport.'); }
            if (!in_array($settings['encryption'], ['starttls', 'tls'], true) || !in_array((int) $settings['port'], [465, 587, 2525], true)) {
                throw new RuntimeException('Choose TLS or STARTTLS and SMTP port 465, 587 or 2525.');
            }
            if ($settings['host'] !== '') { NotificationTransport::validateHost((string) $settings['host']); }
            if ($ready && $settings['transport'] === 'smtp' && ($settings['host'] === '' || $settings['username'] === '' || ($settings['secret'] ?? '') === '')) {
                throw new RuntimeException('SMTP requires a host, username and password before it can send.');
            }
        } else {
            if ($settings['url'] !== '') { NotificationTransport::validateWebhookUrl((string) $settings['url']); }
            if ($ready && $settings['url'] === '') { throw new RuntimeException('Enter the HTTPS adapter endpoint before enabling or testing this channel.'); }
        }
    }

    public static function initializeKey(): void
    {
        if (PHP_SAPI !== 'cli') { throw new RuntimeException('Use the command-line installer.'); }
        if ((string) Config::get('NOTIFICATION_SETTINGS_KEY', '') !== '') { self::key(); return; }
        $path = self::keyPath();
        if (is_file($path)) { self::key(); return; }
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0700, true)) { throw new RuntimeException('Cannot create private key directory.'); }
        $handle = fopen($path, 'x');
        if ($handle === false) { throw new RuntimeException('Cannot create settings encryption key.'); }
        try {
            $content = "<?php\n// Private encryption key. Back up securely; never commit.\nreturn '" . base64_encode(random_bytes(32)) . "';\n";
            if (fwrite($handle, $content) !== strlen($content)) { throw new RuntimeException('Could not write the complete encryption key.'); }
        } finally { fclose($handle); }
        chmod($path, 0600);
        self::key();
    }

    private static function keyPath(): string { return EASYWAY_ROOT . '/storage/private/notification-key.php'; }

    private static function key(): string
    {
        $encoded = (string) Config::get('NOTIFICATION_SETTINGS_KEY', '');
        if ($encoded === '' && is_file(self::keyPath())) { $encoded = (string) require self::keyPath(); }
        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== 32 || !function_exists('openssl_encrypt')) {
            throw new RuntimeException('Notification encryption is unavailable. Run the installer or restore the original settings key.');
        }
        return $key;
    }

    private static function encrypt(string $value, string $channel): string
    {
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($value, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, $channel, 16);
        if ($cipher === false) { throw new RuntimeException('Cannot protect the notification credential.'); }
        return base64_encode($iv . $tag . $cipher);
    }

    private static function decrypt(string $value, string $channel): string
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false || strlen($decoded) < 29) { throw new RuntimeException('The saved notification credential is invalid.'); }
        $plain = openssl_decrypt(substr($decoded, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($decoded, 0, 12), substr($decoded, 12, 16), $channel);
        if ($plain === false) { throw new RuntimeException('The saved credential cannot be decrypted. Restore the original settings key.'); }
        return $plain;
    }

    private static function assertChannel(string $channel): void
    {
        if (!in_array($channel, self::CHANNELS, true)) { throw new RuntimeException('Unknown notification channel.'); }
    }
}
