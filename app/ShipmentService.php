<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class ShipmentService
{
    /** @var array<string, string> */
    private const STATUSES = [
        'booked' => 'Booked',
        'received' => 'Received at Easyway',
        'picked_up' => 'Picked Up',
        'in_transit' => 'In Transit',
        'at_hub' => 'At Distribution Hub',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'delivery_failed' => 'Delivery Attempt Unsuccessful',
        'on_hold' => 'On Hold',
        'returned' => 'Returned',
        'cancelled' => 'Cancelled',
    ];

    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'booked' => ['received', 'picked_up', 'cancelled', 'on_hold'],
        'received' => ['picked_up', 'in_transit', 'cancelled', 'on_hold'],
        'picked_up' => ['in_transit', 'cancelled', 'on_hold'],
        'in_transit' => ['at_hub', 'out_for_delivery', 'returned', 'on_hold'],
        'at_hub' => ['in_transit', 'out_for_delivery', 'on_hold'],
        'out_for_delivery' => ['delivered', 'delivery_failed', 'on_hold'],
        'delivery_failed' => ['out_for_delivery', 'returned', 'on_hold'],
        'on_hold' => ['received', 'in_transit', 'out_for_delivery', 'returned', 'cancelled'],
        'delivered' => [],
        'returned' => [],
        'cancelled' => [],
    ];

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return self::STATUSES;
    }

    /** @return array<string, string> */
    public static function allowedNextStatuses(string $currentStatus): array
    {
        $allowed = self::TRANSITIONS[$currentStatus] ?? [];
        return array_intersect_key(self::STATUSES, array_flip($allowed));
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data, int $staffUserId): string
    {
        $pdo = Database::connection();
        $trackingNumber = self::trackingNumber();

        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare(
                'INSERT INTO shipments
                    (tracking_number, customer_name, customer_email, customer_phone, origin, destination,
                     service_type, package_description, weight_kg, status, expected_delivery_at,
                     created_by, created_at, updated_at)
                 VALUES
                    (:tracking_number, :customer_name, :customer_email, :customer_phone, :origin, :destination,
                     :service_type, :package_description, :weight_kg, "booked", :expected_delivery_at,
                     :created_by, NOW(), NOW())'
            );
            $statement->execute([
                'tracking_number' => $trackingNumber,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?: null,
                'customer_phone' => $data['customer_phone'],
                'origin' => $data['origin'],
                'destination' => $data['destination'],
                'service_type' => $data['service_type'],
                'package_description' => $data['package_description'],
                'weight_kg' => $data['weight_kg'] ?: null,
                'expected_delivery_at' => $data['expected_delivery_at'] ?: null,
                'created_by' => $staffUserId,
            ]);
            $shipmentId = (int) $pdo->lastInsertId();

            $event = $pdo->prepare(
                'INSERT INTO shipment_events
                    (shipment_id, status, title, description, location, event_time, is_public, created_by, created_at)
                 VALUES
                    (:shipment_id, "booked", "Shipment booked", :description, :location, NOW(), 1, :created_by, NOW())'
            );
            $event->execute([
                'shipment_id' => $shipmentId,
                'description' => 'Shipment information has been received by Easyway Logistics.',
                'location' => $data['origin'],
                'created_by' => $staffUserId,
            ]);
            $pdo->commit();

            AuditService::record('shipment.created', 'shipment', $shipmentId, ['tracking_number' => $trackingNumber]);
            return $trackingNumber;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array{shipment:array<string,mixed>,events:array<int,array<string,mixed>>}|null */
    public static function publicTracking(string $trackingNumber): ?array
    {
        $trackingNumber = strtoupper(trim($trackingNumber));
        if (!preg_match('/^EWL[0-9]{8}[A-Z0-9]{8}$/', $trackingNumber)) {
            return null;
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'SELECT tracking_number, origin, destination, service_type, status, expected_delivery_at, created_at, updated_at
             FROM shipments WHERE tracking_number = :tracking_number LIMIT 1'
        );
        $statement->execute(['tracking_number' => $trackingNumber]);
        $shipment = $statement->fetch();
        if (!is_array($shipment)) {
            return null;
        }

        $events = $pdo->prepare(
            'SELECT status, title, description, location, event_time
             FROM shipment_events WHERE shipment_id = (
                SELECT id FROM shipments WHERE tracking_number = :tracking_number LIMIT 1
             ) AND is_public = 1 ORDER BY event_time DESC, id DESC'
        );
        $events->execute(['tracking_number' => $trackingNumber]);

        return ['shipment' => $shipment, 'events' => $events->fetchAll()];
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(int $limit = 100): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.*, u.full_name AS created_by_name
             FROM shipments s LEFT JOIN staff_users u ON u.id = s.created_by
             ORDER BY s.created_at DESC LIMIT :limit'
        );
        $statement->bindValue('limit', max(1, min($limit, 250)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array{shipment:array<string,mixed>,events:array<int,array<string,mixed>>}|null */
    public static function find(int $shipmentId): ?array
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare('SELECT * FROM shipments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $shipmentId]);
        $shipment = $statement->fetch();
        if (!is_array($shipment)) {
            return null;
        }

        $events = $pdo->prepare(
            'SELECT e.*, u.full_name AS created_by_name
             FROM shipment_events e LEFT JOIN staff_users u ON u.id = e.created_by
             WHERE e.shipment_id = :shipment_id ORDER BY e.event_time DESC, e.id DESC'
        );
        $events->execute(['shipment_id' => $shipmentId]);
        return ['shipment' => $shipment, 'events' => $events->fetchAll()];
    }

    /** @param array<string, mixed> $data */
    public static function addEvent(int $shipmentId, array $data, int $staffUserId): void
    {
        $pdo = Database::connection();
        $shipment = self::find($shipmentId);
        if ($shipment === null) {
            throw new RuntimeException('Shipment not found.');
        }

        $currentStatus = (string) $shipment['shipment']['status'];
        $status = (string) $data['status'];
        if ($status === 'delivered') {
            throw new RuntimeException('Use the proof-of-delivery form to mark this shipment delivered.');
        }
        if (!isset(self::STATUSES[$status]) || !in_array($status, self::TRANSITIONS[$currentStatus] ?? [], true)) {
            throw new RuntimeException('That shipment status change is not allowed.');
        }

        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare(
                'INSERT INTO shipment_events
                    (shipment_id, status, title, description, location, event_time, is_public, created_by, created_at)
                 VALUES
                    (:shipment_id, :status, :title, :description, :location, :event_time, :is_public, :created_by, NOW())'
            );
            $statement->execute([
                'shipment_id' => $shipmentId,
                'status' => $status,
                'title' => $data['title'] ?: self::STATUSES[$status],
                'description' => $data['description'] ?: null,
                'location' => $data['location'] ?: null,
                'event_time' => $data['event_time'],
                'is_public' => !empty($data['is_public']) ? 1 : 0,
                'created_by' => $staffUserId,
            ]);
            $pdo->prepare(
                'UPDATE shipments SET status = :status, updated_at = NOW() WHERE id = :id'
            )->execute(['status' => $status, 'id' => $shipmentId]);
            if (in_array($status, ['returned', 'cancelled'], true)) {
                RiderService::completeAssignment($shipmentId);
            }
            $pdo->commit();
            AuditService::record('shipment.event_added', 'shipment', $shipmentId, ['status' => $status]);
            NotificationService::queueShipment(
                $shipmentId,
                'shipment_status',
                'Shipment ' . $shipment['shipment']['tracking_number'] . ': ' . self::STATUSES[$status],
                'Your Easyway shipment ' . $shipment['shipment']['tracking_number'] . ' is now ' . self::STATUSES[$status] . '.'
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function trackingNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $suffix = '';
            for ($index = 0; $index < 8; $index++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $trackingNumber = 'EWL' . date('Ymd') . $suffix;
            $statement = Database::connection()->prepare('SELECT 1 FROM shipments WHERE tracking_number = :tracking_number');
            $statement->execute(['tracking_number' => $trackingNumber]);
            if (!$statement->fetchColumn()) {
                return $trackingNumber;
            }
        }

        throw new RuntimeException('Unable to generate a unique tracking number.');
    }
}
