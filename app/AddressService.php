<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class AddressService
{
    /** @return array<int, array<string, mixed>> */
    public static function allForCustomer(int $customerId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM customer_addresses WHERE customer_id = :customer_id ORDER BY is_default DESC, label, id'
        );
        $statement->execute(['customer_id' => $customerId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function findForCustomer(int $addressId, int $customerId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM customer_addresses WHERE id = :id AND customer_id = :customer_id LIMIT 1'
        );
        $statement->execute(['id' => $addressId, 'customer_id' => $customerId]);
        $address = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($address) ? $address : null;
    }

    /** @param array<string, mixed> $data */
    public static function create(int $customerId, array $data): int
    {
        $pdo = Database::connection();
        if (!empty($data['is_default'])) {
            $pdo->prepare('UPDATE customer_addresses SET is_default = 0 WHERE customer_id = :customer_id')->execute(['customer_id' => $customerId]);
        }
        $statement = $pdo->prepare(
            'INSERT INTO customer_addresses
                (customer_id, label, recipient_name, phone, address_line, city, state_name, country_code, directions, is_default, created_at, updated_at)
             VALUES
                (:customer_id, :label, :recipient_name, :phone, :address_line, :city, :state_name, :country_code, :directions, :is_default, NOW(), NOW())'
        );
        $statement->execute([
            'customer_id' => $customerId,
            'label' => $data['label'],
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'address_line' => $data['address_line'],
            'city' => $data['city'],
            'state_name' => $data['state_name'],
            'country_code' => $data['country_code'],
            'directions' => $data['directions'] ?: null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
        ]);
        $id = (int) $pdo->lastInsertId();
        AuditService::record('customer.address_created', 'customer_address', $id, ['customer_id' => $customerId]);
        return $id;
    }

    public static function formatted(array $address): string
    {
        return implode(', ', array_filter([
            (string) ($address['address_line'] ?? ''),
            (string) ($address['city'] ?? ''),
            (string) ($address['state_name'] ?? ''),
            (string) ($address['country_code'] ?? ''),
        ]));
    }
}
