<?php

declare(strict_types=1);

namespace App;

/** Shared, delivery-time branding. Stored messages and non-email channels stay plain text. */
final class EmailTemplate
{
    public const LOGO_CID = 'easyway-logo@easyway';

    /** @return array{subject:string,html:string,text:string,logo:?string} */
    public static function render(array $notification): array
    {
        $subject = trim((string) preg_replace('/[\x00-\x1f\x7f]+/u', ' ', (string) ($notification['subject'] ?? '')));
        $subject = $subject !== '' ? $subject : 'Easyway Logistics update';
        $message = str_replace(["\r\n", "\r"], "\n", (string) ($notification['message'] ?? ''));
        $template = (string) ($notification['template_code'] ?? '');
        $staffAlert = in_array($template, ['staff_quote_received', 'staff_contact_received'], true);
        $label = match ($template) {
            'booking_created' => 'Booking confirmation',
            'corporate_booking_confirmed' => 'Corporate booking',
            'payment_received' => 'Payment confirmation',
            'shipment_created' => 'Shipment confirmation',
            'shipment_status' => 'Shipment update',
            'shipment_delivered' => 'Delivery confirmation',
            'cargo_status' => 'Cargo update',
            'inquiry_reply' => 'A message from our team',
            'inquiry_quotation' => 'Your quotation',
            'notification_test' => 'Email delivery test',
            'staff_quote_received' => 'Staff alert · New quote request',
            'staff_contact_received' => 'Staff alert · New inquiry',
            default => 'Customer update',
        };
        $reason = match ($template) {
            'notification_test' => 'This is a delivery test requested by the Easyway team. No action is required.',
            'staff_quote_received', 'staff_contact_received' => 'Internal notification for the Easyway team. Customer details are confidential; share only with authorized staff.',
            'inquiry_reply', 'inquiry_quotation' => 'You are receiving this email about your inquiry with Easyway Logistics.',
            'booking_created', 'corporate_booking_confirmed', 'payment_received', 'shipment_created' => 'You are receiving this email about your booking with Easyway Logistics.',
            'shipment_status', 'shipment_delivered', 'cargo_status' => 'You are receiving this email about your shipment with Easyway Logistics.',
            default => 'A service update from Easyway Logistics.',
        };
        $action = self::action($notification, $template);
        $website = self::siteUrl();
        $address = company_address();
        $phones = support_phones();
        $email = support_email();
        $emailLink = filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/[\r\n\x00]/', $email) ? 'mailto:' . $email : null;
        $whatsapp = whatsapp_url();
        $socials = social_media_links();
        $year = date('Y');
        // Embed only this trusted local asset; never fetch an image from a URL in an email.
        $logoPath = EASYWAY_ROOT . '/assets/img/easyway/logo.jpg';
        $logo = is_file($logoPath) && is_readable($logoPath) ? @file_get_contents($logoPath) : false;
        $logo = is_string($logo) && $logo !== '' ? $logo : null;
        $paragraphs = preg_split('/\n[\t ]*\n+/', trim($message)) ?: [];
        $preheader = $label . ' — ' . mb_substr($subject, 0, 150, 'UTF-8');

        ob_start();
        try {
            require EASYWAY_ROOT . '/app/views/emails/notification.php';
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $text = "EASYWAY LOGISTICS\n" . $label . "\n\n" . $subject . "\n\n" . $message;
        if ($action !== null) { $text .= "\n\n" . $action['label'] . ': ' . $action['url']; }
        $text .= ($staffAlert ? "\n\nEasyway contact details" : "\n\nNeed a hand? Contact our team.") . "\nEmail: " . $email;
        foreach ($phones as $phone) { $text .= "\nCall: " . $phone; }
        $text .= "\nWhatsApp: " . $whatsapp . "\nVisit: " . $address;
        if ($website !== null) { $text .= "\nWebsite: " . $website; }
        if ($socials !== []) {
            $text .= "\n\nConnect with Easyway";
            foreach ($socials as $social) { $text .= "\n" . $social['name'] . ': ' . $social['url']; }
        }
        $text .= "\n\n" . $reason
            . "\n© " . $year . " Easyway Logistics.\n";

        return ['subject' => $subject, 'html' => $html, 'text' => $text, 'logo' => $logo];
    }

    /** @return array{subject:string,headers:list<string>,body:string} */
    public static function compose(array $notification): array
    {
        $content = self::render($notification);
        $nonce = bin2hex(random_bytes(16));
        $alternative = 'easyway_alt_' . $nonce;
        $related = 'easyway_rel_' . $nonce;
        $body = '--' . $alternative . "\r\n" . self::part('text/plain; charset=UTF-8', self::crlf($content['text']));
        $body .= '--' . $alternative . "\r\n";
        if ($content['logo'] !== null) {
            $body .= 'Content-Type: multipart/related; type="text/html";' . "\r\n boundary=\"" . $related . "\"\r\n\r\n"
                . '--' . $related . "\r\n" . self::part('text/html; charset=UTF-8', self::crlf($content['html']))
                . '--' . $related . "\r\n" . self::part('image/jpeg; name="easyway-logo.jpg"', $content['logo'], [
                    'Content-ID: <' . self::LOGO_CID . '>',
                    'Content-Disposition: inline; filename="easyway-logo.jpg"',
                ]) . '--' . $related . "--\r\n";
        } else {
            $body .= self::part('text/html; charset=UTF-8', self::crlf($content['html']));
        }
        $body .= '--' . $alternative . "--\r\n";
        return [
            'subject' => self::encodeHeader($content['subject']),
            'headers' => ['MIME-Version: 1.0', 'Content-Type: multipart/alternative;' . "\r\n boundary=\"" . $alternative . '"'],
            'body' => $body,
        ];
    }

    /** Fold encoded words without splitting a UTF-8 character (RFC 2047). */
    public static function encodeHeader(string $value): string
    {
        $value = trim((string) preg_replace('/[\x00-\x1f\x7f]+/u', ' ', $value));
        $words = [];
        while ($value !== '') {
            $chunk = mb_strcut($value, 0, 42, 'UTF-8');
            $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
            $value = substr($value, strlen($chunk));
        }
        return implode("\r\n ", $words);
    }

    private static function crlf(string $value): string
    {
        return str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $value));
    }

    private static function part(string $type, string $body, array $headers = []): string
    {
        return implode("\r\n", array_merge(['Content-Type: ' . $type, 'Content-Transfer-Encoding: base64'], $headers))
            . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n") . "\r\n";
    }

    /** Never derive customer-facing links from a web request's Host header or cron's localhost. */
    private static function siteUrl(string $path = ''): ?string
    {
        $base = rtrim(trim((string) Config::get('APP_URL', '')), '/');
        $parts = parse_url($base);
        if (!filter_var($base, FILTER_VALIDATE_URL) || !is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            return null;
        }
        try {
            // This shared check validates public host syntax only; it makes no DNS/network request.
            NotificationTransport::validateHost((string) ($parts['host'] ?? ''));
        } catch (\RuntimeException) { return null; }
        return $base . '/' . ltrim($path, '/');
    }

    /** @return array{label:string,url:string}|null */
    private static function action(array $notification, string $template): ?array
    {
        if (in_array($template, ['staff_quote_received', 'staff_contact_received'], true)) {
            $type = $template === 'staff_quote_received' ? 'quote' : 'contact';
            $target = self::siteUrl('staff/inquiries.php?type=' . $type);
            return $target === null ? null : ['label' => $type === 'quote' ? 'Open quote inbox' : 'Open message inbox', 'url' => $target];
        }
        $action = ['label' => 'Chat with our team', 'url' => whatsapp_url()];
        if (in_array($template, ['booking_created', 'corporate_booking_confirmed', 'payment_received', 'shipment_created'], true)) {
            $bookingId = (int) ($notification['booking_id'] ?? 0);
            $target = self::siteUrl($bookingId > 0 ? 'customer/booking.php?id=' . $bookingId : 'customer/index.php');
            if ($target !== null) { $action = ['label' => 'View your booking', 'url' => $target]; }
        } elseif ($template === 'shipment_delivered') {
            $target = self::siteUrl('customer/index.php');
            if ($target !== null) { $action = ['label' => 'Open your account', 'url' => $target]; }
        } elseif (in_array($template, ['shipment_status', 'cargo_status'], true)) {
            $target = self::siteUrl('tracking.php');
            if ($target !== null) { $action = ['label' => 'Track your shipment', 'url' => $target]; }
        }
        return $action;
    }
}
