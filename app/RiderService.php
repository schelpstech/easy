<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class RiderService
{
    private const ACTIVE_SHIPMENT_STATUSES = ['booked', 'received', 'picked_up', 'in_transit', 'at_hub', 'out_for_delivery', 'delivery_failed', 'on_hold'];

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT r.*, u.full_name, u.email, u.status AS user_status,
                    a.shipment_id AS active_shipment_id, s.tracking_number AS active_tracking_number
             FROM rider_profiles r JOIN staff_users u ON u.id = r.staff_user_id
             LEFT JOIN shipment_assignments a ON a.rider_id = r.id AND a.status = "active"
             LEFT JOIN shipments s ON s.id = a.shipment_id
             ORDER BY u.status = "active" DESC, u.full_name'
        )->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data, int $staffId): int
    {
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $exists = $pdo->prepare('SELECT 1 FROM staff_users WHERE email = :email LIMIT 1');
            $exists->execute(['email' => $data['email']]);
            if ($exists->fetchColumn()) {
                throw new RuntimeException('A staff account already uses that email address.');
            }
            $pdo->prepare(
                'INSERT INTO staff_users (full_name, email, password_hash, role, status, created_at, updated_at)
                 VALUES (:name, :email, :password, "rider", "active", NOW(), NOW())'
            )->execute(['name' => $data['full_name'], 'email' => $data['email'], 'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT)]);
            $staffUserId = (int) $pdo->lastInsertId();
            $code = self::riderCode();
            $pdo->prepare(
                'INSERT INTO rider_profiles (staff_user_id, rider_code, phone, vehicle_type, vehicle_registration,
                    licence_number, emergency_contact, availability_status, created_at, updated_at)
                 VALUES (:staff, :code, :phone, :vehicle, :registration, :licence, :emergency, "available", NOW(), NOW())'
            )->execute([
                'staff' => $staffUserId, 'code' => $code, 'phone' => $data['phone'], 'vehicle' => $data['vehicle_type'],
                'registration' => $data['vehicle_registration'] ?: null, 'licence' => $data['licence_number'] ?: null,
                'emergency' => $data['emergency_contact'] ?: null,
            ]);
            $riderId = (int) $pdo->lastInsertId();
            $pdo->commit();
            AuditService::record('rider.created', 'rider_profile', $riderId, ['rider_code' => $code, 'created_by' => $staffId]);
            return $riderId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public static function assign(int $shipmentId, int $riderId, string $note, int $staffId): void
    {
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $shipment = $pdo->prepare('SELECT status FROM shipments WHERE id = :id FOR UPDATE');
            $shipment->execute(['id' => $shipmentId]);
            $status = $shipment->fetchColumn();
            if (!is_string($status) || !in_array($status, self::ACTIVE_SHIPMENT_STATUSES, true)) {
                throw new RuntimeException('Only an active shipment can be assigned.');
            }
            $rider = $pdo->prepare(
                'SELECT r.id FROM rider_profiles r JOIN staff_users u ON u.id = r.staff_user_id
                 WHERE r.id = :id AND u.status = "active" AND r.availability_status <> "offline" FOR UPDATE'
            );
            $rider->execute(['id' => $riderId]);
            if (!$rider->fetchColumn()) { throw new RuntimeException('Choose an active, available rider.'); }
            $active = $pdo->prepare('SELECT shipment_id FROM shipment_assignments WHERE rider_id = :rider AND status = "active" FOR UPDATE');
            $active->execute(['rider' => $riderId]);
            $activeShipment = $active->fetchColumn();
            if ($activeShipment !== false && (int) $activeShipment !== $shipmentId) {
                throw new RuntimeException('This rider already has an active shipment. Complete or unassign it first.');
            }
            $pdo->prepare(
                'UPDATE shipment_assignments SET status = "cancelled", cancelled_at = NOW()
                 WHERE shipment_id = :shipment AND status = "active" AND rider_id <> :rider'
            )->execute(['shipment' => $shipmentId, 'rider' => $riderId]);
            $same = $pdo->prepare('SELECT id FROM shipment_assignments WHERE shipment_id = :shipment AND rider_id = :rider AND status = "active" LIMIT 1');
            $same->execute(['shipment' => $shipmentId, 'rider' => $riderId]);
            if (!$same->fetchColumn()) {
                $pdo->prepare(
                    'INSERT INTO shipment_assignments (shipment_id, rider_id, status, assignment_note, assigned_by, assigned_at)
                     VALUES (:shipment, :rider, "active", :note, :staff, NOW())'
                )->execute(['shipment' => $shipmentId, 'rider' => $riderId, 'note' => $note ?: null, 'staff' => $staffId]);
            }
            $pdo->prepare('UPDATE rider_profiles SET availability_status = "assigned", updated_at = NOW() WHERE id = :id')->execute(['id' => $riderId]);
            $pdo->commit();
            AuditService::record('shipment.rider_assigned', 'shipment', $shipmentId, ['rider_id' => $riderId]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public static function unassign(int $shipmentId, int $staffId): void
    {
        $pdo = Database::connection();
        $current = self::assignmentForShipment($shipmentId);
        if ($current === null) { throw new RuntimeException('This shipment has no active rider assignment.'); }
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE shipment_assignments SET status = "cancelled", cancelled_at = NOW() WHERE id = :id AND status = "active"')->execute(['id' => $current['id']]);
            $pdo->prepare('UPDATE rider_profiles SET availability_status = "available", location_sharing_enabled = 0, updated_at = NOW() WHERE id = :id')->execute(['id' => $current['rider_id']]);
            $pdo->commit();
            AuditService::record('shipment.rider_unassigned', 'shipment', $shipmentId, ['rider_id' => $current['rider_id'], 'staff_id' => $staffId]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public static function setActive(int $riderId, bool $active, int $staffId): void
    {
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $profile = $pdo->prepare('SELECT staff_user_id FROM rider_profiles WHERE id = :id FOR UPDATE');
            $profile->execute(['id' => $riderId]);
            $staffUserId = $profile->fetchColumn();
            if ($staffUserId === false) { throw new RuntimeException('Rider not found.'); }
            if (!$active) {
                $assignment = $pdo->prepare('SELECT 1 FROM shipment_assignments WHERE rider_id = :rider AND status = "active" LIMIT 1 FOR UPDATE');
                $assignment->execute(['rider' => $riderId]);
                if ($assignment->fetchColumn()) { throw new RuntimeException('Unassign the rider\'s active shipment before deactivating the account.'); }
            }
            $pdo->prepare('UPDATE staff_users SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute(['status' => $active ? 'active' : 'inactive', 'id' => $staffUserId]);
            $pdo->prepare('UPDATE rider_profiles SET availability_status = :availability, location_sharing_enabled = 0, updated_at = NOW() WHERE id = :id')
                ->execute(['availability' => $active ? 'available' : 'offline', 'id' => $riderId]);
            $pdo->commit();
            AuditService::record('rider.status_changed', 'rider_profile', $riderId, ['active' => $active, 'staff_id' => $staffId]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public static function assignmentForShipment(int $shipmentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.*, r.rider_code, r.phone, r.vehicle_type, r.vehicle_registration,
                    r.location_sharing_enabled, u.full_name, u.email
             FROM shipment_assignments a JOIN rider_profiles r ON r.id = a.rider_id
             JOIN staff_users u ON u.id = r.staff_user_id
             WHERE a.shipment_id = :shipment AND a.status = "active" ORDER BY a.id DESC LIMIT 1'
        );
        $statement->execute(['shipment' => $shipmentId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function assignmentsForStaff(int $staffId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.id AS assignment_id, a.shipment_id, a.assigned_at, s.*
             FROM rider_profiles r JOIN shipment_assignments a ON a.rider_id = r.id AND a.status = "active"
             JOIN shipments s ON s.id = a.shipment_id
             WHERE r.staff_user_id = :staff ORDER BY a.assigned_at DESC'
        );
        $statement->execute(['staff' => $staffId]);
        return $statement->fetchAll();
    }

    public static function canAccessShipment(int $staffId, int $shipmentId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM rider_profiles r JOIN shipment_assignments a ON a.rider_id = r.id
             WHERE r.staff_user_id = :staff AND a.shipment_id = :shipment AND a.status = "active" LIMIT 1'
        );
        $statement->execute(['staff' => $staffId, 'shipment' => $shipmentId]);
        return (bool) $statement->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    public static function recordLocation(int $staffId, int $shipmentId, array $data): void
    {
        if (!self::canAccessShipment($staffId, $shipmentId)) { throw new RuntimeException('This shipment is not assigned to you.'); }
        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        if (!is_finite($latitude) || !is_finite($longitude) || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new RuntimeException('The device returned an invalid location.');
        }
        $recorded = strtotime((string) ($data['recorded_at'] ?? ''));
        if ($recorded === false || abs(time() - $recorded) > 600) { $recorded = time(); }
        $pdo = Database::connection();
        $profile = $pdo->prepare('SELECT id FROM rider_profiles WHERE staff_user_id = :staff LIMIT 1');
        $profile->execute(['staff' => $staffId]);
        $riderId = (int) $profile->fetchColumn();
        $latest = $pdo->prepare('SELECT received_at FROM rider_location_pings WHERE rider_id = :rider ORDER BY id DESC LIMIT 1');
        $latest->execute(['rider' => $riderId]);
        $lastAt = $latest->fetchColumn();
        if (is_string($lastAt) && strtotime($lastAt) > time() - 8) { return; }
        $share = !empty($data['share_public']);
        $pdo->prepare('UPDATE rider_profiles SET location_sharing_enabled = :share, updated_at = NOW() WHERE id = :id')
            ->execute(['share' => $share ? 1 : 0, 'id' => $riderId]);
        $pdo->prepare(
            'INSERT INTO rider_location_pings (rider_id, shipment_id, latitude, longitude, accuracy_m, speed_mps,
                heading_degrees, share_public, recorded_at, received_at)
             VALUES (:rider, :shipment, :latitude, :longitude, :accuracy, :speed, :heading, :share, :recorded, NOW())'
        )->execute([
            'rider' => $riderId, 'shipment' => $shipmentId, 'latitude' => round($latitude, 7), 'longitude' => round($longitude, 7),
            'accuracy' => self::optionalMetric($data['accuracy_m'] ?? null, 0, 100000),
            'speed' => self::optionalMetric($data['speed_mps'] ?? null, 0, 500),
            'heading' => self::optionalMetric($data['heading_degrees'] ?? null, 0, 360),
            'share' => $share ? 1 : 0, 'recorded' => date('Y-m-d H:i:s', $recorded),
        ]);
    }

    public static function stopSharing(int $staffId): void
    {
        Database::connection()->prepare('UPDATE rider_profiles SET location_sharing_enabled = 0, updated_at = NOW() WHERE staff_user_id = :staff')
            ->execute(['staff' => $staffId]);
    }

    /** @return array<string, mixed>|null */
    public static function publicLocation(string $trackingNumber): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT p.latitude, p.longitude, p.accuracy_m, p.recorded_at, s.status, s.tracking_number
             FROM shipments s JOIN shipment_assignments a ON a.shipment_id = s.id AND a.status = "active"
             JOIN rider_profiles r ON r.id = a.rider_id AND r.location_sharing_enabled = 1
             JOIN rider_location_pings p ON p.id = (
                 SELECT p2.id FROM rider_location_pings p2 WHERE p2.shipment_id = s.id AND p2.rider_id = r.id
                 ORDER BY p2.recorded_at DESC, p2.id DESC LIMIT 1
             )
             WHERE s.tracking_number = :tracking AND p.share_public = 1
               AND p.recorded_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
               AND s.status IN ("picked_up","in_transit","at_hub","out_for_delivery","delivery_failed","on_hold") LIMIT 1'
        );
        $statement->execute(['tracking' => strtoupper(trim($trackingNumber))]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) { return null; }
        return [
            'tracking_number' => $row['tracking_number'], 'status' => $row['status'],
            'latitude' => round((float) $row['latitude'], 5), 'longitude' => round((float) $row['longitude'], 5),
            'accuracy_m' => $row['accuracy_m'] === null ? null : round((float) $row['accuracy_m']),
            'recorded_at' => $row['recorded_at'], 'age_seconds' => max(0, time() - strtotime((string) $row['recorded_at'])),
        ];
    }

    public static function completeAssignment(int $shipmentId): void
    {
        $assignment = self::assignmentForShipment($shipmentId);
        if ($assignment === null) { return; }
        $pdo = Database::connection();
        $pdo->prepare('UPDATE shipment_assignments SET status = "completed", completed_at = NOW() WHERE id = :id')->execute(['id' => $assignment['id']]);
        $pdo->prepare('UPDATE rider_profiles SET availability_status = "available", location_sharing_enabled = 0, updated_at = NOW() WHERE id = :id')->execute(['id' => $assignment['rider_id']]);
    }

    private static function optionalMetric(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') { return null; }
        $metric = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $metric === false || !is_finite((float) $metric) || $metric < $min || $metric > $max ? null : round((float) $metric, 2);
    }

    private static function riderCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = 'RDR-' . date('ym') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $check = Database::connection()->prepare('SELECT 1 FROM rider_profiles WHERE rider_code = :code');
            $check->execute(['code' => $code]);
            if (!$check->fetchColumn()) { return $code; }
        }
        throw new RuntimeException('Unable to create a rider code.');
    }
}
