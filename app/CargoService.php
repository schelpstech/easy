<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class CargoService
{
    private const STATUSES = [
        'booked' => 'Booked', 'documentation' => 'Documentation', 'customs_clearance' => 'Customs clearance',
        'departed' => 'Departed', 'in_transit' => 'In transit', 'arrived' => 'Arrived',
        'released' => 'Released', 'delivered' => 'Delivered', 'on_hold' => 'On hold', 'cancelled' => 'Cancelled',
    ];
    private const CUSTOMS = ['not_started' => 'Not started', 'documents_review' => 'Documents review', 'submitted' => 'Submitted', 'inspection' => 'Inspection', 'cleared' => 'Cleared', 'held' => 'Held'];

    /** @return array<string, string> */ public static function statuses(): array { return self::STATUSES; }
    /** @return array<string, string> */ public static function customsStatuses(): array { return self::CUSTOMS; }
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT c.*, s.tracking_number, a.company_name FROM cargo_shipments c
             LEFT JOIN shipments s ON s.id = c.shipment_id LEFT JOIN corporate_accounts a ON a.id = c.corporate_account_id
             ORDER BY c.created_at DESC LIMIT 500'
        )->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data, int $staffId): int
    {
        if (!in_array($data['transport_mode'], ['air', 'sea', 'road'], true)) { throw new RuntimeException('Choose air, sea or road cargo.'); }
        $reference = 'CGO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                'INSERT INTO cargo_shipments (cargo_reference, shipment_id, corporate_account_id, transport_mode, cargo_type,
                    incoterm, origin_terminal, destination_terminal, carrier_name, vessel_or_flight, airway_or_bill_number,
                    container_number, pieces, gross_weight_kg, volume_cbm, status, customs_status,
                    estimated_departure_at, estimated_arrival_at, created_by, created_at, updated_at)
                 VALUES (:reference, :shipment, :corporate, :mode, :cargo_type, :incoterm, :origin, :destination,
                    :carrier, :vessel, :bill, :container, :pieces, :weight, :volume, "booked", "not_started",
                    :etd, :eta, :staff, NOW(), NOW())'
            )->execute([
                'reference' => $reference, 'shipment' => $data['shipment_id'] ?: null, 'corporate' => $data['corporate_account_id'] ?: null,
                'mode' => $data['transport_mode'], 'cargo_type' => $data['cargo_type'], 'incoterm' => $data['incoterm'] ?: null,
                'origin' => $data['origin_terminal'], 'destination' => $data['destination_terminal'], 'carrier' => $data['carrier_name'] ?: null,
                'vessel' => $data['vessel_or_flight'] ?: null, 'bill' => $data['airway_or_bill_number'] ?: null,
                'container' => $data['container_number'] ?: null, 'pieces' => $data['pieces'], 'weight' => $data['gross_weight_kg'] ?: null,
                'volume' => $data['volume_cbm'] ?: null, 'etd' => $data['estimated_departure_at'] ?: null,
                'eta' => $data['estimated_arrival_at'] ?: null, 'staff' => $staffId,
            ]);
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO cargo_milestones (cargo_id, status, title, description, location, event_time, is_public, created_by, created_at)
                 VALUES (:cargo, "booked", "Cargo booking created", "Cargo record received by Easyway Logistics.", :location, NOW(), 1, :staff, NOW())'
            )->execute(['cargo' => $id, 'location' => $data['origin_terminal'], 'staff' => $staffId]);
            $pdo->commit();
            AuditService::record('cargo.created', 'cargo_shipment', $id, ['reference' => $reference]);
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public static function addMilestone(int $cargoId, array $data, int $staffId): void
    {
        if (!isset(self::STATUSES[$data['status']]) || !isset(self::CUSTOMS[$data['customs_status']])) { throw new RuntimeException('Choose valid cargo and customs statuses.'); }
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $record = $pdo->prepare('SELECT * FROM cargo_shipments WHERE id = :id FOR UPDATE');
            $record->execute(['id' => $cargoId]);
            $cargo = $record->fetch(PDO::FETCH_ASSOC);
            if (!is_array($cargo)) { throw new RuntimeException('Cargo record not found.'); }
            if (in_array($cargo['status'], ['delivered', 'cancelled'], true)) { throw new RuntimeException('A final cargo record cannot receive another milestone.'); }
            $title = $data['title'] !== '' ? $data['title'] : self::STATUSES[$data['status']];
            $pdo->prepare(
                'INSERT INTO cargo_milestones (cargo_id, status, title, description, location, event_time, is_public, created_by, created_at)
                 VALUES (:cargo, :status, :title, :description, :location, :event_time, :public, :staff, NOW())'
            )->execute([
                'cargo' => $cargoId, 'status' => $data['status'], 'title' => $title, 'description' => $data['description'] ?: null,
                'location' => $data['location'] ?: null, 'event_time' => $data['event_time'], 'public' => !empty($data['is_public']) ? 1 : 0, 'staff' => $staffId,
            ]);
            $pdo->prepare(
                'UPDATE cargo_shipments SET status = :status, customs_status = :customs,
                    actual_arrival_at = IF(:status_check = "arrived" AND actual_arrival_at IS NULL, :event_time, actual_arrival_at), updated_at = NOW()
                 WHERE id = :id'
            )->execute(['status' => $data['status'], 'customs' => $data['customs_status'], 'status_check' => $data['status'], 'event_time' => $data['event_time'], 'id' => $cargoId]);
            $pdo->commit();
            AuditService::record('cargo.milestone_added', 'cargo_shipment', $cargoId, ['status' => $data['status']]);
            if (!empty($cargo['shipment_id'])) {
                NotificationService::queueShipment(
                    (int) $cargo['shipment_id'], 'cargo_status', 'Cargo update: ' . $title,
                    'Cargo ' . $cargo['cargo_reference'] . ' is now ' . self::STATUSES[$data['status']] . ($data['location'] ? ' at ' . $data['location'] : '') . '.'
                );
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    /** @return array{cargo:array<string,mixed>,milestones:array<int,array<string,mixed>>}|null */
    public static function find(int $cargoId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT c.*, s.tracking_number, a.company_name FROM cargo_shipments c
             LEFT JOIN shipments s ON s.id = c.shipment_id LEFT JOIN corporate_accounts a ON a.id = c.corporate_account_id
             WHERE c.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $cargoId]);
        $cargo = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($cargo)) { return null; }
        $milestones = Database::connection()->prepare('SELECT * FROM cargo_milestones WHERE cargo_id = :cargo ORDER BY event_time DESC, id DESC');
        $milestones->execute(['cargo' => $cargoId]);
        return ['cargo' => $cargo, 'milestones' => $milestones->fetchAll()];
    }
}
