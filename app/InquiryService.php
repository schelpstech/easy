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
        self::save('contact', [
            'reference' => $reference,
            'full_name' => $data['full_name'],
            'company_name' => $data['company_name'] ?: null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        return $reference;
    }

    /** @param array<string, string|int> $data */
    public static function createQuote(array $data): string
    {
        $reference = self::reference('QT');
        self::save('quote', [
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

        return $reference;
    }

    /** Persist the inquiry and its staff alert together; no provider calls during form submission. */
    private static function save(string $type, array $data): void
    {
        [$table, $entity] = match ($type) {
            'contact' => ['contact_messages', 'contact_message'],
            'quote' => ['quote_requests', 'quote_request'],
        };
        $pdo = Database::connection();
        $ownsTransaction = !$pdo->inTransaction();
        $savepoint = 'easyway_inquiry_' . bin2hex(random_bytes(6));
        if ($ownsTransaction) { $pdo->beginTransaction(); }
        else { $pdo->exec('SAVEPOINT ' . $savepoint); }
        try {
            // Column names come only from the explicit field lists in createContact/createQuote.
            $columns = array_keys($data);
            $statement = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(',', $columns) . ',status,created_at)'
                . ' VALUES (:' . implode(',:', $columns) . ',"new",NOW())');
            $statement->execute($data);
            $id = (int) $pdo->lastInsertId();
            $notificationId = NotificationService::queueInquiry($type, $data);
            AuditService::record($type . '.created', $entity, $id, ['reference' => $data['reference'], 'notification_id' => $notificationId]);
            if ($ownsTransaction) { $pdo->commit(); }
            else { $pdo->exec('RELEASE SAVEPOINT ' . $savepoint); }
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                if ($ownsTransaction) { $pdo->rollBack(); }
                else { $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint); $pdo->exec('RELEASE SAVEPOINT ' . $savepoint); }
            }
            error_log('Easyway inquiry save failed: ' . $exception->getMessage());
            throw new \RuntimeException('We could not save your request. Please try again or contact our team directly.', 0, $exception);
        }
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
