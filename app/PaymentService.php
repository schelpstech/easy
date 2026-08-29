<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class PaymentService
{
    public static function enabled(): bool
    {
        return PaystackGateway::enabled();
    }

    public static function initialize(int $bookingId, int $customerId): string
    {
        $booking = BookingService::findForCustomer($bookingId, $customerId);
        if ($booking === null) {
            throw new RuntimeException('Booking not found.');
        }
        if ($booking['payment_status'] === 'paid') {
            throw new RuntimeException('This booking has already been paid.');
        }
        if (strtotime((string) $booking['quote_expires_at']) < time()) {
            throw new RuntimeException('This quote has expired. Please create a new booking for the current rate.');
        }
        if (!self::enabled()) {
            throw new RuntimeException('Online payment is not enabled yet. Please contact Easyway support.');
        }
        $appUrl = (string) Config::get('APP_URL', '');
        if (Config::get('APP_ENV', 'production') === 'production'
            && (filter_var($appUrl, FILTER_VALIDATE_URL) === false || !str_starts_with(mb_strtolower($appUrl), 'https://'))) {
            throw new RuntimeException('Online payment requires a secure APP_URL before it can be enabled.');
        }

        $customer = CustomerAuth::user();
        if ($customer === null || (int) $customer['id'] !== $customerId) {
            throw new RuntimeException('Please sign in again before paying.');
        }
        $pdo = Database::connection();
        $pending = $pdo->prepare(
            'SELECT authorization_url FROM payments
             WHERE booking_id = :booking AND customer_id = :customer AND status = "pending"
               AND authorization_url IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
             ORDER BY id DESC LIMIT 1'
        );
        $pending->execute(['booking' => $bookingId, 'customer' => $customerId]);
        $pendingUrl = $pending->fetchColumn();
        if (is_string($pendingUrl) && filter_var($pendingUrl, FILTER_VALIDATE_URL)) {
            return $pendingUrl;
        }
        $reference = 'EWP-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(5)));
        $statement = $pdo->prepare(
            'INSERT INTO payments (booking_id, customer_id, provider, reference, amount, currency, status, created_at, updated_at)
             VALUES (:booking, :customer, "paystack", :reference, :amount, :currency, "pending", NOW(), NOW())'
        );
        $statement->execute([
            'booking' => $bookingId, 'customer' => $customerId, 'reference' => $reference,
            'amount' => $booking['total_amount'], 'currency' => $booking['currency'],
        ]);
        $paymentId = (int) $pdo->lastInsertId();

        try {
            $response = PaystackGateway::initialize([
                'email' => $customer['email'],
                'amount' => (string) self::subunit((float) $booking['total_amount']),
                'currency' => $booking['currency'],
                'reference' => $reference,
                'callback_url' => absolute_url('payment-callback.php'),
                'metadata' => json_encode(['booking_id' => $bookingId, 'booking_number' => $booking['booking_number']], JSON_UNESCAPED_SLASHES),
            ]);
            $data = $response['data'] ?? [];
            $url = filter_var($data['authorization_url'] ?? '', FILTER_VALIDATE_URL);
            if ($url === false) {
                throw new RuntimeException('The payment gateway did not return a checkout address.');
            }
            $pdo->prepare(
                'UPDATE payments SET authorization_url = :url, access_code = :code, gateway_response = :response, updated_at = NOW() WHERE id = :id'
            )->execute([
                'url' => $url, 'code' => mb_substr((string) ($data['access_code'] ?? ''), 0, 120),
                'response' => mb_substr((string) ($response['message'] ?? ''), 0, 500), 'id' => $paymentId,
            ]);
            AuditService::record('payment.initialized', 'payment', $paymentId, ['reference' => $reference]);
            return $url;
        } catch (Throwable $exception) {
            $pdo->prepare('UPDATE payments SET status = "failed", gateway_response = :message, updated_at = NOW() WHERE id = :id')
                ->execute(['message' => mb_substr($exception->getMessage(), 0, 500), 'id' => $paymentId]);
            throw $exception;
        }
    }

    public static function verifyReference(string $reference): ?int
    {
        if (!preg_match('/^EWP-[0-9]{14}-[A-F0-9]{10}$/', $reference)) {
            throw new RuntimeException('Invalid payment reference.');
        }
        $response = PaystackGateway::verify($reference);
        return self::finalize($response['data'] ?? []);
    }

    /** @return array{duplicate:bool,processed:bool} */
    public static function processWebhook(string $payload, string $signature): array
    {
        if (!PaystackGateway::validSignature($payload, $signature)) {
            throw new RuntimeException('Invalid webhook signature.');
        }
        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new RuntimeException('Invalid webhook payload.');
        }
        $hash = hash('sha256', $payload);
        $type = mb_substr((string) ($event['event'] ?? 'unknown'), 0, 80);
        $reference = mb_substr((string) ($event['data']['reference'] ?? ''), 0, 50);
        $pdo = Database::connection();
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO payment_webhook_events
                (provider, payload_hash, event_type, reference, payload_json, status, received_at)
             VALUES ("paystack", :hash, :type, :reference, :payload, "received", NOW())'
        );
        $insert->execute(['hash' => $hash, 'type' => $type, 'reference' => $reference ?: null, 'payload' => $payload]);
        $duplicate = $insert->rowCount() === 0;
        if ($duplicate) {
            $existing = $pdo->prepare('SELECT id, status FROM payment_webhook_events WHERE provider = "paystack" AND payload_hash = :hash LIMIT 1');
            $existing->execute(['hash' => $hash]);
            $existingEvent = $existing->fetch(PDO::FETCH_ASSOC);
            if (!is_array($existingEvent) || in_array($existingEvent['status'], ['processed', 'ignored', 'rejected'], true)) {
                return ['duplicate' => true, 'processed' => is_array($existingEvent) && $existingEvent['status'] === 'processed'];
            }
            $eventId = (int) $existingEvent['id'];
            $pdo->prepare('UPDATE payment_webhook_events SET status = "received", error_message = NULL, processed_at = NULL WHERE id = :id')
                ->execute(['id' => $eventId]);
        } else {
            $eventId = (int) $pdo->lastInsertId();
        }
        if ($type !== 'charge.success') {
            $pdo->prepare('UPDATE payment_webhook_events SET status = "ignored", processed_at = NOW() WHERE id = :id')->execute(['id' => $eventId]);
            return ['duplicate' => $duplicate, 'processed' => false];
        }
        try {
            self::finalize($event['data'] ?? []);
            $pdo->prepare('UPDATE payment_webhook_events SET status = "processed", processed_at = NOW() WHERE id = :id')->execute(['id' => $eventId]);
            return ['duplicate' => $duplicate, 'processed' => true];
        } catch (Throwable $exception) {
            $pdo->prepare('UPDATE payment_webhook_events SET status = :status, error_message = :error, processed_at = NOW() WHERE id = :id')
                ->execute([
                    'status' => $exception instanceof RuntimeException ? 'rejected' : 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 500), 'id' => $eventId,
                ]);
            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public static function latestForBooking(int $bookingId, int $customerId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM payments WHERE booking_id = :booking AND customer_id = :customer ORDER BY id DESC LIMIT 1'
        );
        $statement->execute(['booking' => $bookingId, 'customer' => $customerId]);
        $payment = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($payment) ? $payment : null;
    }

    /** @param mixed $providerData */
    private static function finalize(mixed $providerData): ?int
    {
        if (!is_array($providerData)) {
            throw new RuntimeException('Payment verification returned no transaction data.');
        }
        $reference = (string) ($providerData['reference'] ?? '');
        $statement = Database::connection()->prepare(
            'SELECT p.*, b.booking_number FROM payments p JOIN bookings b ON b.id = p.booking_id
             WHERE p.reference = :reference AND p.provider = "paystack" LIMIT 1'
        );
        $statement->execute(['reference' => $reference]);
        $payment = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($payment)) {
            throw new RuntimeException('Payment reference was not issued by this system.');
        }
        if ($payment['status'] === 'paid') {
            return (int) $payment['booking_id'];
        }
        $alreadyPaid = Database::connection()->prepare(
            'SELECT id FROM payments WHERE booking_id = :booking AND status = "paid" AND id <> :id LIMIT 1'
        );
        $alreadyPaid->execute(['booking' => $payment['booking_id'], 'id' => $payment['id']]);
        if ($alreadyPaid->fetchColumn()) {
            Database::connection()->prepare('UPDATE payments SET status = "review", gateway_response = "Possible duplicate charge", verified_at = NOW() WHERE id = :id')
                ->execute(['id' => $payment['id']]);
            throw new RuntimeException('This booking already has a verified payment. The additional charge requires staff review.');
        }
        $status = (string) ($providerData['status'] ?? '');
        $amount = (int) ($providerData['amount'] ?? -1);
        $currency = strtoupper((string) ($providerData['currency'] ?? ''));
        if ($status !== 'success' || $amount !== self::subunit((float) $payment['amount']) || $currency !== strtoupper((string) $payment['currency'])) {
            Database::connection()->prepare('UPDATE payments SET status = "review", gateway_response = :message, verified_at = NOW() WHERE id = :id')
                ->execute(['message' => 'Verification mismatch: status, amount or currency.', 'id' => $payment['id']]);
            throw new RuntimeException('Payment verification did not match this booking. No shipment was released.');
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                'UPDATE payments SET status = "paid", provider_transaction_id = :provider_id,
                 gateway_response = :response, paid_at = :paid_at, verified_at = NOW(), updated_at = NOW() WHERE id = :id'
            )->execute([
                'provider_id' => mb_substr((string) ($providerData['id'] ?? ''), 0, 40),
                'response' => mb_substr((string) ($providerData['gateway_response'] ?? 'Successful'), 0, 500),
                'paid_at' => self::providerDate((string) ($providerData['paid_at'] ?? '')),
                'id' => $payment['id'],
            ]);
            $pdo->prepare('UPDATE bookings SET payment_status = "paid", status = "confirmed", updated_at = NOW() WHERE id = :id')
                ->execute(['id' => $payment['booking_id']]);
            $pdo->prepare(
                'INSERT INTO booking_status_history (booking_id, status, note, actor_type, actor_id, created_at)
                 VALUES (:booking, "confirmed", "Payment verified by Paystack", "system", NULL, NOW())'
            )->execute(['booking' => $payment['booking_id']]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        BillingService::issueReceipt((int) $payment['booking_id']);
        NotificationService::queueBooking((int) $payment['booking_id'], 'payment_received', 'Payment received for ' . $payment['booking_number'], 'Payment for Easyway booking ' . $payment['booking_number'] . ' has been verified. Our operations team will prepare your shipment.');
        AuditService::record('payment.verified', 'payment', (int) $payment['id'], ['reference' => $reference]);
        return (int) $payment['booking_id'];
    }

    private static function subunit(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private static function providerDate(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp === false ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $timestamp);
    }
}
