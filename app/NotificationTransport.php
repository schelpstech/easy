<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class NotificationTransport
{
    public static function validateHost(string $host): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (!self::publicIp($host)) { throw new RuntimeException('Use a public provider hostname, not a local or private address.'); }
            return;
        }
        if (strlen($host) > 253 || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/iD', $host)
            || preg_match('/\.(?:local|localhost|internal|test|invalid)$/iD', $host)) {
            throw new RuntimeException('Enter a valid public provider hostname.');
        }
    }

    public static function validateWebhookUrl(string $url): array
    {
        $parts = parse_url($url);
        if (strlen($url) > 2000 || !filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || (isset($parts['port']) && (int) $parts['port'] !== 443)) {
            throw new RuntimeException('Use an HTTPS adapter URL on port 443, without credentials, query parameters or a fragment. Put tokens in the credential field.');
        }
        self::validateHost((string) ($parts['host'] ?? ''));
        return $parts;
    }

    private static function publicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            && !str_starts_with($ip, '169.254.') && !preg_match('/^100\.(?:6[4-9]|[7-9][0-9]|1[01][0-9]|12[0-7])\./', $ip);
    }

    private static function resolve(string $host, int $port): array
    {
        self::validateHost($host);
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);
        if (!$addresses) { throw new RuntimeException('Provider DNS could not be resolved. Check the hostname.'); }
        foreach ($addresses as $ip) {
            if (!self::publicIp($ip)) { throw new RuntimeException('Provider DNS resolves to a private or reserved address. Delivery was blocked.'); }
        }
        // Pin the validated address for this request; do not follow redirects or re-resolve it.
        return [$host . ':' . $port . ':' . $addresses[0]];
    }

    public static function send(array $notification, array $settings): void
    {
        $channel = (string) $notification['channel'];
        NotificationSettings::validate($channel, $settings);
        self::assertRuntimeAvailable($channel, $settings);
        if ($channel === 'email') { self::email($notification, $settings); return; }
        $parts = self::validateWebhookUrl((string) $settings['url']);
        $handle = self::handle((string) $settings['url'], (string) $parts['host'], 443);
        $headers = ['Content-Type: application/json'];
        if (($settings['secret'] ?? '') !== '') {
            if (preg_match('/[\r\n\x00]/', $settings['secret'])) { throw new RuntimeException('Invalid provider token.'); }
            $headers[] = 'Authorization: Bearer ' . $settings['secret'];
        }
        curl_setopt_array($handle, [CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_POST => true, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode(['to' => $notification['recipient'], 'message' => $notification['message'], 'reference' => 'EWN-' . $notification['id']], JSON_THROW_ON_ERROR)]);
        try {
            $ok = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($ok === false || $status < 200 || $status >= 300) {
                throw new RuntimeException('Messaging adapter did not accept the request (HTTP ' . $status . ', transport code ' . curl_errno($handle) . '). Check endpoint and credentials.');
            }
        } finally { curl_close($handle); }
    }

    /** Check PHP capabilities only. Does not connect to a provider or send a message. */
    public static function assertRuntimeAvailable(string $channel, array $settings): void
    {
        if ($channel === 'email' && ($settings['transport'] ?? '') === 'mail') {
            if (!function_exists('mail')) {
                throw new RuntimeException('Server mail is selected, but PHP mail() is disabled or unavailable in this runtime. In Delivery Settings > Email, select Authenticated SMTP, save your SMTP settings and send a test. SMTP fields are ignored while Server mail is selected.');
            }
            return;
        }
        if (!function_exists('curl_init') || !function_exists('curl_version')) {
            throw new RuntimeException('PHP cURL is unavailable in this runtime. Enable it for the PHP executable used by cron as well as website PHP.');
        }
        $protocol = $channel === 'email' ? (($settings['encryption'] ?? '') === 'tls' ? 'smtps' : 'smtp') : 'https';
        $version = curl_version();
        if (!in_array($protocol, $version['protocols'] ?? [], true)) {
            throw new RuntimeException('This PHP cURL build does not support ' . strtoupper($protocol) . '. Ask your host to enable it for the PHP executable used by cron.');
        }
        if (((int) ($version['features'] ?? 0) & CURL_VERSION_SSL) === 0) {
            throw new RuntimeException('PHP cURL TLS support is unavailable in this runtime. Ask your host to enable a TLS-capable cURL build.');
        }
    }

    private static function handle(string $url, string $host, int $port): \CurlHandle
    {
        if (!function_exists('curl_init')) { throw new RuntimeException('PHP cURL is required for this delivery transport.'); }
        $resolved = self::resolve($host, $port);
        $handle = curl_init($url);
        if ($handle === false) { throw new RuntimeException('Cannot initialize delivery transport.'); }
        $bytes = 0;
        curl_setopt_array($handle, [CURLOPT_RESOLVE => $resolved, CURLOPT_PROXY => '', CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $data) use (&$bytes): int { $bytes += strlen($data); return $bytes <= 65536 ? strlen($data) : 0; }]);
        return $handle;
    }

    private static function email(array $notification, array $settings): void
    {
        $to = (string) $notification['recipient'];
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n\x00]/', $to)) { throw new RuntimeException('The notification email address is invalid.'); }
        $email = EmailTemplate::compose($notification);
        $subject = $email['subject'];
        $from = EmailTemplate::encodeHeader($settings['from_name']) . "\r\n <" . $settings['from_email'] . '>';
        // Keep replies in the configured sender mailbox, including staff inquiry replies.
        $headers = array_merge($email['headers'], ['From: ' . $from, 'Reply-To: <' . $settings['from_email'] . '>']);
        $body = $email['body'];
        if ($settings['transport'] === 'mail') {
            if (!@mail($to, $subject, $body, implode("\r\n", $headers))) { throw new RuntimeException('Server mail did not accept the notification. Configure SMTP or contact your hosting provider.'); }
            return;
        }
        $scheme = $settings['encryption'] === 'tls' ? 'smtps' : 'smtp';
        $host = (string) $settings['host']; $port = (int) $settings['port'];
        $handle = self::handle($scheme . '://' . $host . ':' . $port, $host, $port);
        $message = 'To: <' . $to . ">\r\nSubject: " . $subject . "\r\nDate: " . date(DATE_RFC2822) . "\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) { curl_close($handle); throw new RuntimeException('Cannot prepare email.'); }
        fwrite($stream, $message); rewind($stream);
        curl_setopt_array($handle, [CURLOPT_PROTOCOLS => CURLPROTO_SMTP | CURLPROTO_SMTPS,
            CURLOPT_USE_SSL => CURLUSESSL_ALL, CURLOPT_USERNAME => $settings['username'], CURLOPT_PASSWORD => $settings['secret'],
            CURLOPT_MAIL_FROM => '<' . $settings['from_email'] . '>', CURLOPT_MAIL_RCPT => ['<' . $to . '>'],
            CURLOPT_UPLOAD => true, CURLOPT_INFILE => $stream, CURLOPT_INFILESIZE => strlen($message)]);
        try {
            if (curl_exec($handle) === false) { throw new RuntimeException('SMTP did not accept the email (transport code ' . curl_errno($handle) . '). Check host, TLS, credentials and sender permissions.'); }
        } finally { fclose($stream); curl_close($handle); }
    }
}
