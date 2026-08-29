<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class ProofOfDeliveryService
{
    /** @param array<string, mixed> $data @param array<string, mixed>|null $photo */
    public static function capture(int $shipmentId, array $data, ?array $photo, int $staffId): int
    {
        $record = ShipmentService::find($shipmentId);
        if ($record === null || $record['shipment']['status'] !== 'out_for_delivery') {
            throw new RuntimeException('Proof of delivery can only close a shipment that is out for delivery.');
        }
        $deliveredTimestamp = strtotime((string) ($data['delivered_at'] ?? ''));
        if ($deliveredTimestamp === false || $deliveredTimestamp > time() + 300
            || $deliveredTimestamp < strtotime((string) $record['shipment']['created_at'])) {
            throw new RuntimeException('Delivery time must fall between shipment creation and the current time.');
        }
        $stored = self::storePhoto($photo);
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare(
                'INSERT INTO proofs_of_delivery
                    (shipment_id, recipient_name, delivery_note, photo_path, photo_mime, latitude, longitude, delivered_at, captured_by, created_at)
                 VALUES (:shipment, :recipient, :note, :path, :mime, :latitude, :longitude, :delivered_at, :staff, NOW())'
            );
            $statement->execute([
                'shipment' => $shipmentId, 'recipient' => $data['recipient_name'], 'note' => $data['delivery_note'] ?: null,
                'path' => $stored['path'], 'mime' => $stored['mime'], 'latitude' => $data['latitude'],
                'longitude' => $data['longitude'], 'delivered_at' => $data['delivered_at'], 'staff' => $staffId,
            ]);
            $proofId = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO shipment_events
                    (shipment_id, status, title, description, location, event_time, is_public, created_by, created_at)
                 VALUES (:shipment, "delivered", "Delivered", :description, :location, :event_time, 1, :staff, NOW())'
            )->execute([
                'shipment' => $shipmentId,
                'description' => 'Received by ' . $data['recipient_name'] . ($data['delivery_note'] ? '. ' . $data['delivery_note'] : ''),
                'location' => $record['shipment']['destination'], 'event_time' => $data['delivered_at'], 'staff' => $staffId,
            ]);
            $pdo->prepare('UPDATE shipments SET status = "delivered", updated_at = NOW() WHERE id = :id')->execute(['id' => $shipmentId]);
            $pdo->prepare('UPDATE bookings SET status = "delivered", updated_at = NOW() WHERE shipment_id = :shipment')->execute(['shipment' => $shipmentId]);
            RiderService::completeAssignment($shipmentId);
            $pdo->commit();
            NotificationService::queueShipment($shipmentId, 'shipment_delivered', 'Your Easyway shipment was delivered', 'Shipment ' . $record['shipment']['tracking_number'] . ' was delivered to ' . $data['recipient_name'] . '. Proof of delivery is available in your account.');
            AuditService::record('shipment.proof_of_delivery', 'shipment', $shipmentId, ['proof_id' => $proofId]);
            return $proofId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($stored['path'] !== null) {
                @unlink(EASYWAY_ROOT . '/storage/' . $stored['path']);
            }
            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public static function findByShipment(int $shipmentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT p.*, s.tracking_number, s.customer_name, s.origin, s.destination, u.full_name AS staff_name
             FROM proofs_of_delivery p JOIN shipments s ON s.id = p.shipment_id
             JOIN staff_users u ON u.id = p.captured_by WHERE p.shipment_id = :shipment LIMIT 1'
        );
        $statement->execute(['shipment' => $shipmentId]);
        $proof = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($proof) ? $proof : null;
    }

    public static function customerCanAccess(int $proofId, int $customerId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM proofs_of_delivery p JOIN bookings b ON b.shipment_id = p.shipment_id
             WHERE p.id = :proof AND b.customer_id = :customer LIMIT 1'
        );
        $statement->execute(['proof' => $proofId, 'customer' => $customerId]);
        return (bool) $statement->fetchColumn();
    }

    public static function staffCanAccess(int $proofId, int $staffId, string $role): bool
    {
        if (in_array($role, ['admin', 'dispatcher'], true)) { return true; }
        $statement = Database::connection()->prepare('SELECT 1 FROM proofs_of_delivery WHERE id = :proof AND captured_by = :staff LIMIT 1');
        $statement->execute(['proof' => $proofId, 'staff' => $staffId]);
        return (bool) $statement->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public static function find(int $proofId): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM proofs_of_delivery WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $proofId]);
        $proof = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($proof) ? $proof : null;
    }

    /** @param array<string, mixed>|null $photo @return array{path:?string,mime:?string} */
    private static function storePhoto(?array $photo): array
    {
        if ($photo === null || (int) ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'mime' => null];
        }
        if ((int) ($photo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) ($photo['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('The delivery photo could not be uploaded or is larger than 5 MB.');
        }
        $temporary = (string) ($photo['tmp_name'] ?? '');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            throw new RuntimeException('Delivery photos must be JPEG, PNG or WebP images.');
        }
        $relative = 'proof-of-delivery/' . date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $absolute = EASYWAY_ROOT . '/storage/' . $relative;
        $directory = dirname($absolute);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to prepare proof-of-delivery storage.');
        }
        if (!move_uploaded_file($temporary, $absolute)) {
            throw new RuntimeException('Unable to store the delivery photo.');
        }
        return ['path' => $relative, 'mime' => $mime];
    }
}
