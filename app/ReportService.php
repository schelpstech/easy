<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

final class ReportService
{
    /** @return array{from:string,to:string} */
    public static function dateRange(?string $from, ?string $to): array
    {
        $today = new DateTimeImmutable('today');
        $toDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $to) ?: $today;
        if ($toDate > $today) { $toDate = $today; }
        $fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $from) ?: $toDate->modify('-29 days');
        if ($fromDate > $toDate) { $fromDate = $toDate; }
        if ($fromDate < $toDate->modify('-365 days')) { $fromDate = $toDate->modify('-365 days'); }
        return ['from' => $fromDate->format('Y-m-d'), 'to' => $toDate->format('Y-m-d')];
    }

    /** @return array<string, mixed> */
    public static function dashboard(?string $from = null, ?string $to = null, string $currency = 'NGN'): array
    {
        $range = self::dateRange($from, $to);
        $params = ['from' => $range['from'] . ' 00:00:00', 'to' => $range['to'] . ' 23:59:59'];
        $currency = in_array(strtoupper($currency), ['NGN', 'USD', 'GBP', 'EUR'], true) ? strtoupper($currency) : 'NGN';
        $financialParams = $params + ['currency' => $currency];
        $pdo = Database::connection();
        $one = static function (string $sql, bool $financial = false) use ($pdo, $params, $financialParams): float {
            $statement = $pdo->prepare($sql); $statement->execute($financial ? $financialParams : $params); return (float) $statement->fetchColumn();
        };
        $bookings = $one('SELECT COUNT(*) FROM bookings WHERE created_at BETWEEN :from AND :to AND currency = :currency', true);
        $bookingValue = $one('SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE created_at BETWEEN :from AND :to AND status <> "cancelled" AND currency = :currency', true);
        $onlineCollections = $one('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid" AND paid_at BETWEEN :from AND :to AND currency = :currency', true);
        $corporateCollections = $one('SELECT COALESCE(SUM(credit_amount),0) FROM corporate_ledger WHERE entry_type = "payment" AND created_at BETWEEN :from AND :to AND currency = :currency', true);
        $shipments = $one('SELECT COUNT(*) FROM shipments WHERE created_at BETWEEN :from AND :to');
        $delivered = $one('SELECT COUNT(*) FROM shipments WHERE status = "delivered" AND updated_at BETWEEN :from AND :to');
        $bulkBatches = $one('SELECT COUNT(*) FROM bulk_shipment_batches WHERE created_at BETWEEN :from AND :to');
        $cargoCreated = $one('SELECT COUNT(*) FROM cargo_shipments WHERE created_at BETWEEN :from AND :to');
        $active = (int) $pdo->query('SELECT COUNT(*) FROM shipments WHERE status NOT IN ("delivered","returned","cancelled")')->fetchColumn();
        $unassigned = (int) $pdo->query('SELECT COUNT(*) FROM shipments s WHERE s.status NOT IN ("delivered","returned","cancelled") AND NOT EXISTS (SELECT 1 FROM shipment_assignments a WHERE a.shipment_id = s.id AND a.status = "active")')->fetchColumn();
        $ridersAvailable = (int) $pdo->query('SELECT COUNT(*) FROM rider_profiles r JOIN staff_users u ON u.id = r.staff_user_id WHERE u.status = "active" AND r.availability_status = "available"')->fetchColumn();
        $outstandingStatement = $pdo->prepare('SELECT COALESCE(SUM(debit_amount-credit_amount),0) FROM corporate_ledger WHERE currency = :currency');
        $outstandingStatement->execute(['currency' => $currency]);
        $corporateOutstanding = (float) $outstandingStatement->fetchColumn();
        $activeCargo = (int) $pdo->query('SELECT COUNT(*) FROM cargo_shipments WHERE status NOT IN ("delivered","cancelled")')->fetchColumn();

        $dailyStatement = $pdo->prepare(
            'SELECT DATE(created_at) AS day, COUNT(*) AS booking_count, COALESCE(SUM(CASE WHEN status <> "cancelled" THEN total_amount ELSE 0 END),0) AS booking_value
             FROM bookings WHERE created_at BETWEEN :from AND :to AND currency = :currency GROUP BY DATE(created_at) ORDER BY day'
        );
        $dailyStatement->execute($financialParams);
        $dailyRaw = [];
        foreach ($dailyStatement->fetchAll() as $row) { $dailyRaw[(string) $row['day']] = $row; }
        $daily = [];
        for ($day = new DateTimeImmutable($range['from']); $day <= new DateTimeImmutable($range['to']); $day = $day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            $daily[] = ['day' => $key, 'booking_count' => (int) ($dailyRaw[$key]['booking_count'] ?? 0), 'booking_value' => (float) ($dailyRaw[$key]['booking_value'] ?? 0)];
        }
        $statusStatement = $pdo->query('SELECT status, COUNT(*) AS total FROM shipments GROUP BY status ORDER BY total DESC');
        $cargoStatement = $pdo->prepare('SELECT transport_mode, status, COUNT(*) AS total FROM cargo_shipments WHERE created_at BETWEEN :from AND :to GROUP BY transport_mode, status ORDER BY transport_mode, total DESC');
        $cargoStatement->execute($params);
        return [
            'range' => $range, 'currency' => $currency,
            'metrics' => [
                'bookings' => (int) $bookings, 'booking_value' => $bookingValue,
                'collections' => $onlineCollections + $corporateCollections, 'shipments' => (int) $shipments,
                'delivered' => (int) $delivered, 'active_shipments' => $active, 'unassigned_shipments' => $unassigned,
                'available_riders' => $ridersAvailable, 'corporate_outstanding' => $corporateOutstanding,
                'bulk_batches' => (int) $bulkBatches, 'cargo_created' => (int) $cargoCreated, 'active_cargo' => $activeCargo,
            ],
            'daily' => $daily, 'shipment_statuses' => $statusStatement->fetchAll(), 'cargo_mix' => $cargoStatement->fetchAll(),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
