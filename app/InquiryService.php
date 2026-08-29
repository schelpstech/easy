<?php

declare(strict_types=1);

namespace App;

use PDO;

final class InquiryService
{
    /** @param array<string, string> $data */
    public static function createContact(array $data): string
    {
        $reference = self::reference('MSG');
        $statement = Database::connection()->prepare(
            'INSERT INTO contact_messages
                (reference, full_name, company_name, email, phone, subject, message, status, created_at)
             VALUES
                (:reference, :full_name, :company_name, :email, :phone, :subject, :message, "new", NOW())'
        );
        $statement->execute([
            'reference' => $reference,
            'full_name' => $data['full_name'],
            'company_name' => $data['company_name'] ?: null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        AuditService::record('contact.created', 'contact_message', (int) Database::connection()->lastInsertId(), ['reference' => $reference]);
        return $reference;
    }

    /** @param array<string, string|int> $data */
    public static function createQuote(array $data): string
    {
        $reference = self::reference('QT');
        $statement = Database::connection()->prepare(
            'INSERT INTO quote_requests
                (reference, shipment_type, from_location, to_location, weight_range, quantity,
                 delivery_type, full_name, email, phone, notes, status, created_at)
             VALUES
                (:reference, :shipment_type, :from_location, :to_location, :weight_range, :quantity,
                 :delivery_type, :full_name, :email, :phone, :notes, "new", NOW())'
        );
        $statement->execute([
            'reference' => $reference,
            'shipment_type' => $data['shipment_type'],
            'from_location' => $data['from_location'],
            'to_location' => $data['to_location'],
            'weight_range' => $data['weight_range'],
            'quantity' => $data['quantity'],
            'delivery_type' => $data['delivery_type'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'notes' => $data['notes'] ?: null,
        ]);

        AuditService::record('quote.created', 'quote_request', (int) Database::connection()->lastInsertId(), ['reference' => $reference]);
        return $reference;
    }

    /** @return array<int, array<string, mixed>> */
    public static function recentQuotes(int $limit = 50): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM quote_requests ORDER BY created_at DESC LIMIT :limit'
        );
        $statement->bindValue('limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function recentContacts(int $limit = 50): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT :limit'
        );
        $statement->bindValue('limit', max(1, min($limit, 100)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    private static function reference(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, date('Ym'), strtoupper(bin2hex(random_bytes(3))));
    }
}

