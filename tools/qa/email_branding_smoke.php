<?php
declare(strict_types=1);

// Every delivery operation is intercepted. This CLI suite never connects to a provider.
namespace App {
    function gethostbynamel(string $host): array { return ['93.184.216.34']; }
    function curl_version(): array { return ['protocols' => ['smtp', 'smtps', 'https'], 'features' => CURL_VERSION_SSL]; }
    function curl_setopt_array(\CurlHandle $handle, array $options): bool {
        $GLOBALS['brand_options'] = array_replace($GLOBALS['brand_options'] ?? [], $options);
        return true;
    }
    function curl_exec(\CurlHandle $handle): bool {
        $stream = $GLOBALS['brand_options'][CURLOPT_INFILE] ?? null;
        if (is_resource($stream)) { $GLOBALS['brand_smtp'] = stream_get_contents($stream); }
        $GLOBALS['brand_calls'] = ($GLOBALS['brand_calls'] ?? 0) + 1;
        return true;
    }
    function curl_getinfo(\CurlHandle $handle, ?int $option = null): int { return 202; }
    function mail(string $to, string $subject, string $message, string $headers): bool {
        $GLOBALS['brand_native'] = 'To: <' . $to . ">\r\nSubject: " . $subject . "\r\n" . $headers . "\r\n\r\n" . $message;
        return true;
    }
    function file_get_contents(string $filename): string|false {
        return ($GLOBALS['brand_missing_logo'] ?? false) && str_ends_with($filename, '/assets/img/easyway/logo.jpg') ? false : \file_get_contents($filename);
    }
}

namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

    use App\EmailTemplate;
    use App\NotificationTransport;

    function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
    function rejects(callable $call): void {
        try { $call(); } catch (RuntimeException) { return; }
        throw new RuntimeException('Expected invalid recipient or sender to be rejected.');
    }
    /** A small, independent MIME reader for checking the actual wire message, not just the renderer. */
    function mime(string $raw): array {
        [$head, $body] = explode("\r\n\r\n", $raw, 2);
        $headers = [];
        foreach (explode("\r\n", (string) preg_replace('/\r\n[\t ]+/', ' ', $head)) as $line) {
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower($name)] = trim($value);
        }
        $type = strtolower(explode(';', $headers['content-type'])[0]);
        $result = ['type' => $type, 'headers' => $headers, 'parts' => [], 'body' => ''];
        if (str_starts_with($type, 'multipart/')) {
            check(preg_match('/boundary="([^"]+)"/', $headers['content-type'], $match) === 1, 'Missing MIME boundary.');
            $boundary = $match[1];
            check(strlen($boundary) <= 70, 'Boundary too long.');
            check(str_starts_with($body, '--' . $boundary . "\r\n"), 'Missing initial MIME delimiter.');
            check(str_ends_with($body, '--' . $boundary . "--\r\n"), 'Missing closing MIME delimiter.');
            $sections = explode('--' . $boundary, $body);
            check(array_shift($sections) === '', 'Unexpected MIME preamble.');
            check(array_pop($sections) === "--\r\n", 'Incorrect MIME ending.');
            foreach ($sections as $section) { $result['parts'][] = mime(substr($section, 2)); }
        } else {
            check(($headers['content-transfer-encoding'] ?? '') === 'base64', 'Leaf is not base64-encoded.');
            foreach (explode("\r\n", trim($body)) as $line) { check(strlen($line) <= 76, 'Base64 line exceeds 76 characters.'); }
            $decoded = base64_decode($body, true);
            check($decoded !== false, 'Invalid MIME base64.');
            $result['body'] = $decoded;
        }
        return $result;
    }
    function normalized(string $value): string { return str_replace("\r\n", "\n", $value); }
    function assertBranded(string $wire, string $message, string $logo): array {
        check(!preg_match('/(?<!\r)\n/', $wire), 'Wire message contains bare LF.');
        foreach (explode("\r\n", $wire) as $line) { check(strlen($line) <= 998, 'Wire line exceeds SMTP limit.'); }
        $tree = mime($wire);
        check($tree['type'] === 'multipart/alternative' && count($tree['parts']) === 2, 'Expected two alternatives.');
        $plain = $tree['parts'][0]; $related = $tree['parts'][1];
        check($plain['type'] === 'text/plain' && $related['type'] === 'multipart/related', 'Incorrect MIME order.');
        check(str_contains($related['headers']['content-type'], 'type="text/html"'), 'Related root type is unspecified.');
        check(count($related['parts']) === 2, 'HTML or logo is missing.');
        [$html, $image] = $related['parts'];
        check($html['type'] === 'text/html' && $image['type'] === 'image/jpeg', 'Incorrect related part types.');
        check($image['body'] === $logo, 'Embedded logo bytes do not match the company asset.');
        check(($image['headers']['content-id'] ?? '') === '<' . EmailTemplate::LOGO_CID . '>', 'Logo CID mismatch.');
        check(str_contains($image['headers']['content-disposition'], 'inline'), 'Logo is not inline.');
        check(str_contains($html['body'], 'src="cid:' . EmailTemplate::LOGO_CID . '"'), 'HTML does not reference the embedded logo.');
        check(str_contains(normalized($plain['body']), normalized($message)), 'Plain-text message content changed.');
        foreach (['support@easyway.ng', '+234 903 113 4210', '+234 808 713 7894', 'Shop 39, Stakeholder Park', 'Ikeja, Lagos State', 'TikTok', 'Instagram', 'Facebook'] as $contact) {
            check(str_contains($html['body'], $contact) && str_contains($plain['body'], $contact), 'Missing brand contact: ' . $contact);
        }
        check(str_contains($html['body'], '#063f59') && str_contains($html['body'], '#f59b20'), 'Brand colours missing.');
        check(!preg_match('/<script|<link|<iframe|<form|src="https?:/i', $html['body']), 'Active content or remote resources in email.');
        return $tree;
    }

    $environment = [
        'APP_URL' => 'https://logistics.example.com/portal',
        'SUPPORT_EMAIL' => 'support@easyway.ng', 'SUPPORT_PHONE' => '+234 903 113 4210', 'SUPPORT_PHONE_SECONDARY' => '+234 808 713 7894',
        'COMPANY_ADDRESS' => 'Shop 39, Stakeholder Park, International Airport, after International Airport Central Mosque, Ikeja, Lagos State, Nigeria',
        'WHATSAPP_NUMBER' => '2349031134210',
        'TIKTOK_URL' => 'https://www.tiktok.com/@easyway_logistics_?_r=1&_t=ZS-99KHmZfBk4D',
        'INSTAGRAM_URL' => 'https://www.instagram.com/easywaylogistics', 'FACEBOOK_URL' => 'https://www.facebook.com/share/19PjTGGdLC/',
    ];
    $before = [];
    foreach ($environment as $key => $value) { $before[$key] = getenv($key); putenv($key . '=' . $value); }
    $oldHost = $_SERVER['HTTP_HOST'] ?? null;
    try {
        $logo = file_get_contents(EASYWAY_ROOT . '/assets/img/easyway/logo.jpg');
        check(is_string($logo) && $logo !== '', 'Deploy the actual Easyway logo before running QA.');
        $notification = ['id' => 'BRAND-QA', 'channel' => 'email', 'recipient' => 'customer@example.com', 'booking_id' => 42,
            'subject' => 'Your Easyway shipment was delivered', 'template_code' => 'shipment_delivered',
            'message' => 'Shipment EWL20260831DEMO123 was delivered to Ada. Proof of delivery is available in your account.'];
        $settings = ['transport' => 'smtp', 'from_email' => 'team@example.com', 'from_name' => 'Easyway Logistics',
            'host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'starttls', 'username' => 'team@example.com', 'secret' => 'Synthetic-Only-Password'];
        $types = ['booking_created', 'corporate_booking_confirmed', 'payment_received', 'shipment_created', 'shipment_status', 'shipment_delivered',
            'cargo_status', 'inquiry_reply', 'inquiry_quotation', 'notification_test', 'future_template', ''];
        foreach ($types as $type) {
            $sample = array_replace($notification, ['template_code' => $type]);
            NotificationTransport::send($sample, $settings);
            $tree = assertBranded($GLOBALS['brand_smtp'], $sample['message'], $logo);
            check($tree['headers']['reply-to'] === '<team@example.com>', 'Reply-To changed the configured mailbox.');
            check(!str_contains($GLOBALS['brand_smtp'], $settings['secret']), 'Email leaked SMTP credentials.');
        }
        check($GLOBALS['brand_options'][CURLOPT_USE_SSL] === CURLUSESSL_ALL, 'SMTP TLS was weakened.');
        check($GLOBALS['brand_options'][CURLOPT_SSL_VERIFYPEER] === true && $GLOBALS['brand_options'][CURLOPT_SSL_VERIFYHOST] === 2, 'Certificate checks were weakened.');
        $settings['encryption'] = 'tls'; $settings['port'] = 465;
        NotificationTransport::send($notification, $settings);
        assertBranded($GLOBALS['brand_smtp'], $notification['message'], $logo);
        $smtpContent = mime($GLOBALS['brand_smtp']);
        if (function_exists('mail')) {
            NotificationTransport::send($notification, array_replace($settings, ['transport' => 'mail']));
            $nativeContent = assertBranded($GLOBALS['brand_native'], $notification['message'], $logo);
            check($smtpContent['parts'][0]['body'] === $nativeContent['parts'][0]['body'], 'Native/SMTP text differs.');
            check($smtpContent['parts'][1]['parts'][0]['body'] === $nativeContent['parts'][1]['parts'][0]['body'], 'Native/SMTP HTML differs.');
            echo "PASS native mail uses the same HTML, text and inline-logo MIME as SMTP (mocked)\n";
        } else {
            echo "SKIP native-mail send: PHP mail() is disabled; SMTP was tested independently\n";
        }
        echo "PASS all 10 email events plus unknown/legacy templates; SMTP and SMTPS MIME branding\n";

        $hostile = array_replace($notification, ['template_code' => 'inquiry_reply', 'subject' => "Quote <b>₦25,000</b> & shipping\r\nBcc: fake@example.com",
            'message' => "Hello Ada & team,\r\n\r\n<script>alert(1)</script> <img src=x onerror=alert(1)>\n\nTerms: ₦25,000 — café 🚚\n1. Do not change this quotation.\n2. Valid for seven days.", 'staff_note' => 'PRIVATE-NOTE-NEVER-SEND']);
        $rendered = EmailTemplate::render($hostile);
        check(str_contains($rendered['html'], '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Customer HTML was not escaped.');
        check(!str_contains($rendered['html'], '<b>₦') && !str_contains($rendered['html'], '<img src=x'), 'Subject/body HTML injection.');
        check(!str_contains($rendered['html'] . $rendered['text'], 'PRIVATE-NOTE-NEVER-SEND'), 'Internal metadata leaked.');
        check(str_contains($rendered['html'], '₦25,000 — café 🚚') && str_contains($rendered['html'], '1. Do not change this quotation.<br>'), 'Unicode or message line breaks lost.');
        NotificationTransport::send($hostile, $settings);
        assertBranded($GLOBALS['brand_smtp'], $hostile['message'], $logo);
        check(!isset(mime($GLOBALS['brand_smtp'])['headers']['bcc']), 'Injected Bcc header.');
        foreach ([str_repeat('International logistics confirmation ', 12), str_repeat('🚚 café ₦25,000 — ', 25)] as $longSubject) {
            $encoded = EmailTemplate::encodeHeader($longSubject);
            $decoded = '';
            foreach (explode("\r\n ", $encoded) as $word) {
                check(strlen($word) <= 75, 'Encoded word exceeds RFC 2047 limit.');
                $chunk = base64_decode(substr($word, 10, -2), true);
                check($chunk !== false && mb_check_encoding($chunk, 'UTF-8'), 'Split UTF-8 character in header.');
                $decoded .= $chunk;
            }
            check($decoded === trim($longSubject), 'Header content changed during folding.');
        }
        rejects(fn() => NotificationTransport::send(array_replace($notification, ['recipient' => "a@example.com\r\nBcc: b@example.com"]), $settings));
        rejects(fn() => NotificationTransport::send($notification, array_replace($settings, ['from_name' => "Easyway\r\nBcc: b@example.com"])));
        echo "PASS escaped content, preserved terms/Unicode/paragraphs, folded headers and private-note isolation\n";

        foreach ($types as $type) {
            $rendered = EmailTemplate::render(array_replace($notification, ['template_code' => $type]));
            if (in_array($type, ['booking_created', 'corporate_booking_confirmed', 'payment_received', 'shipment_created'], true)) {
                check(str_contains($rendered['html'], 'https://logistics.example.com/portal/customer/booking.php?id=42'), 'Booking CTA lost its safe subdirectory or ID.');
            }
        }
        $_SERVER['HTTP_HOST'] = 'attacker.example.com';
        foreach (['', '/easy', 'http://localhost/easy', 'https://localhost/easy', 'https://127.0.0.1', 'https://10.0.0.1', 'https://intranet.internal',
            'https://user:pass@example.com', 'https://example.com?bad=1', 'https://example.com#fragment', 'javascript:alert(1)'] as $invalidUrl) {
            putenv('APP_URL=' . $invalidUrl);
            $rendered = EmailTemplate::render($notification);
            check(!str_contains($rendered['html'], 'customer/index.php') && !str_contains($rendered['html'], 'Visit our website'), 'Invalid APP_URL emitted a site link.');
            check(!str_contains($rendered['html'], 'attacker.example.com') && str_contains($rendered['html'], 'https://wa.me/2349031134210'), 'Host-header fallback or missing contact action.');
        }
        putenv('APP_URL=' . $environment['APP_URL']);
        putenv('INSTAGRAM_URL=javascript:alert(1)'); putenv('TIKTOK_URL='); putenv('FACEBOOK_URL=');
        check(!str_contains(EmailTemplate::render($notification)['html'], 'Connect with Easyway'), 'Unsafe or disabled social URLs rendered.');
        foreach (['INSTAGRAM_URL', 'TIKTOK_URL', 'FACEBOOK_URL'] as $key) { putenv($key . '=' . $environment[$key]); }
        $GLOBALS['brand_missing_logo'] = true;
        $fallback = EmailTemplate::compose($notification);
        $fallbackTree = mime(implode("\r\n", $fallback['headers']) . "\r\n\r\n" . $fallback['body']);
        check($fallbackTree['parts'][1]['type'] === 'text/html' && !str_contains($fallbackTree['parts'][1]['body'], 'src="cid:'), 'Unreadable logo left a broken inline reference.');
        check(str_contains($fallbackTree['parts'][1]['body'], 'Easyway <span'), 'Missing textual logo fallback.');
        $GLOBALS['brand_missing_logo'] = false;
        echo "PASS safe public HTTPS links, contact fallbacks, optional social links and unreadable-logo fallback\n";

        foreach (['sms', 'whatsapp'] as $channel) {
            $GLOBALS['brand_options'] = [];
            NotificationTransport::send(array_replace($notification, ['channel' => $channel, 'recipient' => '+2349031134210']), ['url' => 'https://adapter.example.com/messages', 'secret' => 'Synthetic-Token']);
            $payload = json_decode($GLOBALS['brand_options'][CURLOPT_POSTFIELDS], true, 512, JSON_THROW_ON_ERROR);
            check($payload === ['to' => '+2349031134210', 'message' => $notification['message'], 'reference' => 'EWN-BRAND-QA'], 'Non-email adapter payload changed.');
        }
        echo "PASS SMS and WhatsApp messages remain unchanged\n";

        if (in_array('--previews', $argv, true)) {
            $quotation = array_replace($notification, ['template_code' => 'inquiry_quotation', 'subject' => 'Your quotation: Lagos to London',
                'message' => "Hello Ada,\n\nThank you for your request. Please find your confirmed quotation below.\n\nReference: EWQ-DEMO-2026\nRoute: Lagos to London\nService: Express Delivery\nShipment: International / 6kg – 15kg / 2 piece(s)\nTotal quoted amount: NGN 185,000.00\n\nTerms and conditions:\n1. Quotation valid for 7 days.\n2. Price is subject to final weight and package inspection.\n3. Customs duties and destination taxes are not included.\n\nPlease reply to this email to discuss or accept the quotation. This quotation is not an invoice or payment confirmation.\n\nEasyway Logistics"]);
            $long = array_replace($quotation, ['subject' => str_repeat('International shipment update ', 4), 'message' => $quotation['message'] . "\n\nReference: " . str_repeat('ABC12345', 50)]);
            $prefix = EASYWAY_ROOT . '/storage/cache/email-branding-' . bin2hex(random_bytes(4));
            foreach (['quotation' => $quotation, 'update' => $notification, 'long' => $long, 'no-logo' => $notification] as $name => $sample) {
                $GLOBALS['brand_missing_logo'] = $name === 'no-logo';
                $preview = EmailTemplate::render($sample);
                $html = str_replace('cid:' . EmailTemplate::LOGO_CID, 'data:image/jpeg;base64,' . base64_encode($preview['logo'] ?? ''), $preview['html']);
                $path = $prefix . '-' . $name . '.html';
                check(file_put_contents($path, $html) !== false, 'Could not save synthetic preview.');
                echo 'PREVIEW ' . $path . PHP_EOL;
            }
            $GLOBALS['brand_missing_logo'] = false;
            NotificationTransport::send($quotation, $settings);
            check(file_put_contents($prefix . '.eml', $GLOBALS['brand_smtp']) !== false, 'Could not save synthetic MIME fixture.');
            echo 'MIME ' . $prefix . '.eml' . PHP_EOL;
        }
        echo "PASS no real mail, network calls, database writes, settings saves or outbox processing\n";
    } finally {
        foreach ($before as $key => $value) { putenv($value === false ? $key : $key . '=' . $value); }
        if ($oldHost === null) { unset($_SERVER['HTTP_HOST']); } else { $_SERVER['HTTP_HOST'] = $oldHost; }
        unset($GLOBALS['brand_missing_logo']);
    }
}
