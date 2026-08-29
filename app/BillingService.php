<?php

declare(strict_types=1);

namespace App;

use PDO;

final class BillingService
{
    public static function issueInvoice(int $bookingId): int
    {
        return self::issue($bookingId, 'invoice');
    }

    public static function issueReceipt(int $bookingId): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'UPDATE billing_documents SET status = "paid", paid_at = NOW()
             WHERE booking_id = :booking_id AND document_type = "invoice"'
        )->execute(['booking_id' => $bookingId]);
        return self::issue($bookingId, 'receipt');
    }

    /** @return array<int, array<string, mixed>> */
    public static function allForCustomer(int $customerId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT d.*, b.booking_number FROM billing_documents d
             JOIN bookings b ON b.id = d.booking_id
             WHERE d.customer_id = :customer_id ORDER BY d.issued_at DESC, d.id DESC'
        );
        $statement->execute(['customer_id' => $customerId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function findForCustomer(int $documentId, int $customerId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT d.*, b.booking_number, b.service_name, b.package_description, b.base_amount, b.weight_amount,
                    b.fuel_amount, b.insurance_amount, b.packaging_amount, b.pickup_snapshot_json,
                    b.delivery_snapshot_json, c.full_name, c.email, c.phone
             FROM billing_documents d
             JOIN bookings b ON b.id = d.booking_id
             JOIN customer_users c ON c.id = d.customer_id
             WHERE d.id = :id AND d.customer_id = :customer_id LIMIT 1'
        );
        $statement->execute(['id' => $documentId, 'customer_id' => $customerId]);
        $document = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($document) ? $document : null;
    }

    private static function issue(int $bookingId, string $type): int
    {
        $pdo = Database::connection();
        $existing = $pdo->prepare('SELECT id FROM billing_documents WHERE booking_id = :booking_id AND document_type = :type LIMIT 1');
        $existing->execute(['booking_id' => $bookingId, 'type' => $type]);
        $id = $existing->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $booking = $pdo->prepare('SELECT customer_id, currency, total_amount, tax_amount FROM bookings WHERE id = :id LIMIT 1');
        $booking->execute(['id' => $bookingId]);
        $row = $booking->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            throw new \RuntimeException('Booking not found for billing.');
        }

        $number = self::documentNumber($type);
        $statement = $pdo->prepare(
            'INSERT INTO billing_documents
                (booking_id, customer_id, document_number, document_type, currency, subtotal, tax_amount,
                 total_amount, status, issued_at, paid_at, created_at)
             VALUES
                (:booking_id, :customer_id, :number, :type, :currency, :subtotal, :tax, :total,
                 :status, NOW(), :paid_at, NOW())'
        );
        $tax = (float) $row['tax_amount'];
        $total = (float) $row['total_amount'];
        $statement->execute([
            'booking_id' => $bookingId,
            'customer_id' => $row['customer_id'],
            'number' => $number,
            'type' => $type,
            'currency' => $row['currency'],
            'subtotal' => round($total - $tax, 2),
            'tax' => $tax,
            'total' => $total,
            'status' => $type === 'receipt' ? 'paid' : 'issued',
            'paid_at' => $type === 'receipt' ? date('Y-m-d H:i:s') : null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    private static function documentNumber(string $type): string
    {
        $prefix = $type === 'receipt' ? 'RCPT' : 'INV';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $check = Database::connection()->prepare('SELECT 1 FROM billing_documents WHERE document_number = :number');
            $check->execute(['number' => $number]);
            if (!$check->fetchColumn()) {
                return $number;
            }
        }
        throw new \RuntimeException('Unable to create a billing document number.');
    }
}
