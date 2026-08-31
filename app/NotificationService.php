<?php

declare(strict_types=1);

namespace App;

use PDO;
use Throwable;

final class NotificationService
{
    /** Called once inside the new inquiry's transaction. The recipient is never taken from a public form. */
    public static function queueInquiry(string $type, array $inquiry): int
    {
        $title = match ($type) {
            'quote' => 'New quote request',
            'contact' => 'New contact inquiry',
            default => throw new \RuntimeException('Unknown inquiry alert type.'),
        };
        $recipient = trim((string) Config::get('INQUIRY_ALERT_EMAIL', 'info@easyway.ng'));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || strlen($recipient) > 190 || preg_match('/[\r\n\x00]/', $recipient)) {
            throw new \RuntimeException('INQUIRY_ALERT_EMAIL must contain one valid staff email address.');
        }
        $subject = '[' . $inquiry['reference'] . '] ' . $title;
        $body = ($type === 'quote' ? 'A new quotation request has been submitted on the Easyway website.' : 'A new contact inquiry has been submitted on the Easyway website.')
            . "\n\nReference: " . $inquiry['reference'] . "\nReceived: " . date('j M Y, g:i A T')
            . "\n\nCustomer: " . $inquiry['full_name'] . "\nEmail: " . $inquiry['email']
            . "\nPhone: " . ($inquiry['phone'] ?: 'Not provided');
        if ($type === 'quote') {
            $body .= "\n\nShipment type: " . $inquiry['shipment_type'] . "\nOrigin: " . $inquiry['from_location']
                . "\nDestination: " . $inquiry['to_location'] . "\nWeight range: " . $inquiry['weight_range']
                . "\nQuantity: " . $inquiry['quantity'] . "\nService: " . $inquiry['delivery_type']
                . "\n\nCustomer notes:\n" . ($inquiry['notes'] ?: 'No additional notes provided.');
        } else {
            $body .= "\nCompany: " . ($inquiry['company_name'] ?: 'Not provided')
                . "\n\nSubject: " . $inquiry['subject'] . "\n\nCustomer message:\n" . $inquiry['message'];
        }
        $body .= "\n\nOpen Quotes & Messages in the staff portal and find the reference above to reply, send a quotation or record follow-up."
            . "\nThis is an internal alert, not a reply to the customer. Customer-provided details have not been verified.";
        $statement = Database::connection()->prepare('INSERT INTO notification_outbox'
            . ' (channel,recipient,template_code,subject,message,status,attempts) VALUES ("email",:recipient,:template,:subject,:message,"pending",0)');
        $statement->execute(['recipient' => $recipient, 'template' => 'staff_' . $type . '_received', 'subject' => $subject, 'message' => $body]);
        return (int) Database::connection()->lastInsertId();
    }

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
        // Serialize cron workers for this database so overlapping schedules do not
        // select and send the same pending emails at the same time.
        $lockName = 'easyway-notifications-' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 24);
        $lock = $pdo->prepare('SELECT GET_LOCK(:name,0)'); $lock->execute(['name' => $lockName]);
        $acquired = $lock->fetchColumn();
        if ($acquired === null || $acquired === false) { throw new \RuntimeException('The notification worker lock is unavailable. No messages were sent.'); }
        if ((int) $acquired !== 1) { return ['sent' => 0, 'failed' => 0, 'waiting' => 0]; }
        try { return self::dispatchReady($limit); }
        finally { $pdo->prepare('SELECT RELEASE_LOCK(:name)')->execute(['name' => $lockName]); }
    }

    private static function dispatchReady(int $limit): array
    {
        $pdo = Database::connection();
        $enabledChannels = array_values(array_filter(NotificationSettings::CHANNELS, static fn(string $channel): bool => self::channelEnabled($channel)));
        $due = 'status = "pending" AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())';
        if ($enabledChannels === []) {
            return ['sent' => 0, 'failed' => 0, 'waiting' => (int) $pdo->query('SELECT COUNT(*) FROM notification_outbox WHERE ' . $due)->fetchColumn()];
        }
        $placeholders = implode(',', array_fill(0, count($enabledChannels), '?'));
        $waiting = $pdo->prepare('SELECT COUNT(*) FROM notification_outbox WHERE ' . $due . ' AND channel NOT IN (' . $placeholders . ')');
        $waiting->execute($enabledChannels);
        // Apply the enabled-channel filter before the limit: disabled SMS/WhatsApp
        // backlog must not starve newer email replies.
        $statement = $pdo->prepare(
            'SELECT * FROM notification_outbox WHERE ' . $due . ' AND channel IN (' . $placeholders . ')
             ORDER BY created_at,id LIMIT ?'
        );
        foreach ($enabledChannels as $index => $channel) { $statement->bindValue($index + 1, $channel, PDO::PARAM_STR); }
        $statement->bindValue(count($enabledChannels) + 1, max(1, min(100, $limit)), PDO::PARAM_INT);
        $statement->execute();
        $result = ['sent' => 0, 'failed' => 0, 'waiting' => (int) $waiting->fetchColumn()];
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

    public static function channelEnabled(string $channel): bool
    {
        $settings = NotificationSettings::get($channel);
        if (empty($settings['enabled'])) { return false; }
        try {
            $settings['secret'] = $settings['has_secret'] ? '[configured]' : '';
            NotificationSettings::validate($channel, $settings);
            return true;
        } catch (\RuntimeException) { return false; }
    }

    public static function sendTest(string $channel, string $recipient, string $password): void
    {
        StaffAccountService::requireAdmin();
        if (!Auth::verifyPassword($password)) { throw new \RuntimeException('Your current password is incorrect or verification is temporarily locked.'); }
        $recipient = trim($recipient);
        if (($channel === 'email' && (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n\x00]/', $recipient)))
            || ($channel !== 'email' && !preg_match('/^\+[1-9][0-9]{7,14}$/D', $recipient))) {
            throw new \RuntimeException('Enter a valid test email or international phone number, for example +2349031134210.');
        }
        // Only this fixed test is sent; pending customer notifications are never processed here.
        NotificationTransport::send(['id' => 'TEST-' . bin2hex(random_bytes(8)), 'channel' => $channel,
            'recipient' => $recipient, 'subject' => 'Easyway notification test', 'template_code' => 'notification_test',
            'message' => 'This is a test message from Easyway Logistics. No shipment or payment action is required.'], NotificationSettings::get($channel, true));
        AuditService::record('notifications.test_accepted', 'notification_settings', null, ['channel' => $channel]);
    }

    /** @param array<string, mixed> $notification */
    private static function deliver(array $notification): void
    {
        $settings = NotificationSettings::get((string) $notification['channel'], true);
        if (in_array($notification['template_code'], ['inquiry_reply','inquiry_quotation'], true) && ($settings['transport'] ?? '') !== 'smtp') {
            throw new \RuntimeException('Inquiry email requires Authenticated SMTP. Restore the SMTP configuration in Delivery Settings.');
        }
        NotificationTransport::send($notification, $settings);
    }
}
