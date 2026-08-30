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
        // First-time web setup may not have a deployment key yet. Never replace a key
        // or generate one over credentials that need an original key to decrypt.
        if ($secret !== '') { self::initializeKey(); }
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
        if (PHP_SAPI !== 'cli') { StaffAccountService::requireAdmin(); }
        self::assertEncryptionAvailable();
        if ((string) Config::get('NOTIFICATION_SETTINGS_KEY', '') !== '') { self::key(); return; }
        $path = self::keyPath();
        // Healthy existing deployments do not need write access to the key directory.
        if (is_file($path)) { self::key(); return; }
        self::assertNoEncryptedCredentials();
        if (!is_dir(dirname($path)) && !@mkdir(dirname($path), 0700, true) && !is_dir(dirname($path))) {
            throw new RuntimeException('Cannot create the private notification key directory. Allow PHP to write storage/private, or run the installer on this server.');
        }
        $lock = @fopen($path . '.lock', 'c');
        if ($lock === false) { throw new RuntimeException('Cannot initialize the notification key. Allow PHP to write storage/private, or run the installer on this server.'); }
        $temporary = null;
        try {
            if (!flock($lock, LOCK_EX)) { throw new RuntimeException('Cannot lock notification key setup. Please try again.'); }
            clearstatcache(true, $path);
            if (is_file($path)) { self::key(); return; }
            self::assertNoEncryptedCredentials();
            // Publish only a complete key file, so concurrent readers cannot see a partial key.
            $temporary = $path . '.' . bin2hex(random_bytes(8)) . '.php';
            $handle = @fopen($temporary, 'x');
            if ($handle === false) { throw new RuntimeException('Cannot create the notification key. Check write permissions for storage/private.'); }
            $content = "<?php\n// Private encryption key. Back up securely; never commit.\nreturn '" . base64_encode(random_bytes(32)) . "';\n";
            try {
                if (fwrite($handle, $content) !== strlen($content) || !fflush($handle)) { throw new RuntimeException('Could not write the complete notification key.'); }
            } finally { fclose($handle); }
            if (!@chmod($temporary, 0600) || !@rename($temporary, $path)) {
                throw new RuntimeException('Cannot protect or publish the notification key. Check permissions for storage/private.');
            }
            $temporary = null;
        } finally {
            if ($temporary !== null && is_file($temporary)) { @unlink($temporary); }
            flock($lock, LOCK_UN); fclose($lock);
        }
        self::key();
    }

    /** @return array{ready:bool,message:string} */
    public static function encryptionStatus(): array
    {
        try { self::key(); return ['ready' => true, 'message' => 'Notification encryption is ready.']; }
        catch (RuntimeException $e) { return ['ready' => false, 'message' => $e->getMessage()]; }
    }

    private static function assertNoEncryptedCredentials(): void
    {
        if (!self::installed()) { throw new RuntimeException('Run php tools/install_staff_settings.php before initializing notification encryption.'); }
        $count = Database::connection()->query("SELECT COUNT(*) FROM notification_settings WHERE secret_encrypted IS NOT NULL AND secret_encrypted <> ''")->fetchColumn();
        if ((int) $count > 0) {
            throw new RuntimeException('The original notification encryption key is missing. Restore storage/private/notification-key.php or the original NOTIFICATION_SETTINGS_KEY. Existing encrypted credentials prevent creation of a replacement key.');
        }
    }

    private static function assertEncryptionAvailable(): void
    {
        if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt') || !function_exists('openssl_get_cipher_methods')) {
            throw new RuntimeException('PHP OpenSSL encryption is unavailable. Enable the OpenSSL extension for the PHP runtime serving this request; command-line PHP and website PHP may use different configurations.');
        }
        if (!in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            throw new RuntimeException('This PHP OpenSSL installation does not support AES-256-GCM. Ask your host to enable a compatible OpenSSL build.');
        }
    }

    private static function keyPath(): string { return EASYWAY_ROOT . '/storage/private/notification-key.php'; }

    private static function key(): string
    {
        self::assertEncryptionAvailable();
        $encoded = (string) Config::get('NOTIFICATION_SETTINGS_KEY', '');
        $source = 'NOTIFICATION_SETTINGS_KEY';
        if ($encoded === '') {
            if (!is_file(self::keyPath())) {
                throw new RuntimeException('No notification encryption key is configured on this server. First-time setup creates it when you save credentials; otherwise restore the original key or run php tools/install_staff_settings.php.');
            }
            if (!is_readable(self::keyPath())) { throw new RuntimeException('The notification key file exists but PHP cannot read it. Check permissions for storage/private/notification-key.php.'); }
            $source = 'storage/private/notification-key.php';
            $encoded = (string) require self::keyPath();
        }
        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Invalid notification encryption key in ' . $source . '. It must be a base64-encoded 32-byte key. Restore the original value; do not replace a key used by saved credentials.');
        }
        return $key;
    }

    private static function encrypt(string $value, string $channel): string
    {
        $key = self::key();
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $channel, 16);
        if ($cipher === false) { throw new RuntimeException('Cannot protect the notification credential.'); }
        return base64_encode($iv . $tag . $cipher);
    }

    private static function decrypt(string $value, string $channel): string
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false || strlen($decoded) < 29) { throw new RuntimeException('The saved notification credential is invalid.'); }
        $key = self::key();
        $plain = openssl_decrypt(substr($decoded, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($decoded, 0, 12), substr($decoded, 12, 16), $channel);
        if ($plain === false) { throw new RuntimeException('The saved credential cannot be decrypted. Restore the original settings key.'); }
        return $plain;
    }

    private static function assertChannel(string $channel): void
    {
        if (!in_array($channel, self::CHANNELS, true)) { throw new RuntimeException('Unknown notification channel.'); }
    }
}
