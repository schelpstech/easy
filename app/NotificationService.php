<?php

declare(strict_types=1);

namespace App;

use PDO;
use Throwable;

final class NotificationService
{
    public static function queueBooking(int $bookingId, string $template, string $subject, string $message): void
    {
        try {
            $statement = Database::connection()->prepare(
                'SELECT b.customer_id, c.email, c.phone FROM bookings b
                 JOIN customer_users c ON c.id = b.customer_id WHERE b.id = :id LIMIT 1'
            );
            $statement->execute(['id' => $bookingId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                self::queueChannels((int) $row['customer_id'], $bookingId, null, (string) $row['email'], (string) $row['phone'], $template, $subject, $message);
            }
        } catch (Throwable $exception) {
            error_log('Easyway notification queue failed: ' . $exception->getMessage());
        }
    }

    public static function queueShipment(int $shipmentId, string $template, string $subject, string $message): void
    {
        try {
            $statement = Database::connection()->prepare(
                'SELECT b.customer_id, s.customer_email AS email, s.customer_phone AS phone
                 FROM shipments s LEFT JOIN bookings b ON b.shipment_id = s.id WHERE s.id = :id LIMIT 1'
            );
            $statement->execute(['id' => $shipmentId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                self::queueChannels($row['customer_id'] === null ? null : (int) $row['customer_id'], null, $shipmentId, (string) ($row['email'] ?? ''), (string) ($row['phone'] ?? ''), $template, $subject, $message);
            }
        } catch (Throwable $exception) {
            error_log('Easyway shipment notification queue failed: ' . $exception->getMessage());
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function recent(int $limit = 100): array
    {
        $statement = Database::connection()->prepare(
            'SELECT n.*, b.booking_number, s.tracking_number FROM notification_outbox n
             LEFT JOIN bookings b ON b.id = n.booking_id LEFT JOIN shipments s ON s.id = n.shipment_id
             ORDER BY n.created_at DESC LIMIT :limit'
        );
        $statement->bindValue('limit', max(1, min(250, $limit)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array{sent:int,failed:int,waiting:int} */
    public static function dispatchPending(int $limit = 50): array
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'SELECT * FROM notification_outbox
             WHERE status = "pending" AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())
             ORDER BY created_at LIMIT :limit'
        );
        $statement->bindValue('limit', max(1, min(100, $limit)), PDO::PARAM_INT);
        $statement->execute();
        $result = ['sent' => 0, 'failed' => 0, 'waiting' => 0];
        foreach ($statement->fetchAll() as $notification) {
            $enabled = self::channelEnabled((string) $notification['channel']);
            if (!$enabled) {
                $result['waiting']++;
                continue;
            }
            try {
                self::deliver($notification);
                $pdo->prepare('UPDATE notification_outbox SET status = "sent", attempts = attempts + 1, sent_at = NOW(), last_error = NULL WHERE id = :id')
                    ->execute(['id' => $notification['id']]);
                $result['sent']++;
            } catch (Throwable $exception) {
                $attempts = (int) $notification['attempts'] + 1;
                $status = $attempts >= 5 ? 'failed' : 'pending';
                $delay = min(3600, 60 * (2 ** min($attempts, 6)));
                $pdo->prepare(
                    'UPDATE notification_outbox SET status = :status, attempts = :attempts,
                     next_attempt_at = :next_attempt_at, last_error = :error WHERE id = :id'
                )->execute([
                    'status' => $status, 'attempts' => $attempts, 'next_attempt_at' => date('Y-m-d H:i:s', time() + $delay),
                    'error' => mb_substr($exception->getMessage(), 0, 500), 'id' => $notification['id'],
                ]);
                $result['failed']++;
            }
        }
        return $result;
    }

    private static function queueChannels(?int $customerId, ?int $bookingId, ?int $shipmentId, string $email, string $phone, string $template, string $subject, string $message): void
    {
        $recipients = [];
        if ($email !== '') {
            $recipients['email'] = $email;
        }
        if ($phone !== '') {
            $recipients['sms'] = $phone;
            $recipients['whatsapp'] = $phone;
        }
        $statement = Database::connection()->prepare(
            'INSERT INTO notification_outbox
                (customer_id, booking_id, shipment_id, channel, recipient, template_code, subject, message, status, attempts, created_at, updated_at)
             VALUES (:customer_id, :booking_id, :shipment_id, :channel, :recipient, :template, :subject, :message, "pending", 0, NOW(), NOW())'
        );
        foreach ($recipients as $channel => $recipient) {
            $statement->execute([
                'customer_id' => $customerId, 'booking_id' => $bookingId, 'shipment_id' => $shipmentId,
                'channel' => $channel, 'recipient' => $recipient, 'template' => $template,
                'subject' => $subject ?: null, 'message' => $message,
            ]);
        }
    }

    private static function channelEnabled(string $channel): bool
    {
        return match ($channel) {
            'email' => Config::bool('EMAIL_NOTIFICATIONS_ENABLED', false),
            'sms' => Config::bool('SMS_NOTIFICATIONS_ENABLED', false) && trim((string) Config::get('SMS_WEBHOOK_URL', '')) !== '',
            'whatsapp' => Config::bool('WHATSAPP_NOTIFICATIONS_ENABLED', false) && trim((string) Config::get('WHATSAPP_WEBHOOK_URL', '')) !== '',
            default => false,
        };
    }

    /** @param array<string, mixed> $notification */
    private static function deliver(array $notification): void
    {
        if ($notification['channel'] === 'email') {
            $headers = ['Content-Type: text/plain; charset=UTF-8', 'From: Easyway Logistics <' . Config::get('EMAIL_FROM', 'no-reply@easyway.ng') . '>'];
            if (!mail((string) $notification['recipient'], (string) ($notification['subject'] ?: 'Easyway Logistics update'), (string) $notification['message'], implode("\r\n", $headers))) {
                throw new \RuntimeException('The mail transport rejected the notification.');
            }
            return;
        }

        $prefix = strtoupper((string) $notification['channel']);
        $url = (string) Config::get($prefix . '_WEBHOOK_URL', '');
        $token = (string) Config::get($prefix . '_WEBHOOK_TOKEN', '');
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('PHP cURL is required for messaging webhooks.');
        }
        $handle = curl_init($url);
        $headers = ['Content-Type: application/json'];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['to' => $notification['recipient'], 'message' => $notification['message'], 'reference' => 'EWN-' . $notification['id']]),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
        ]);
        curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($error !== '' || $status < 200 || $status >= 300) {
            throw new \RuntimeException($error !== '' ? $error : 'Messaging provider returned HTTP ' . $status . '.');
        }
    }
}
