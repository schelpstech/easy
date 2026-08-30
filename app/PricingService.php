<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class PricingService
{
    /** @var array<string, string> */
    public const DEFAULT_SERVICES = [
        'standard' => 'Standard Delivery',
        'express' => 'Express Delivery',
        'same_day' => 'Same-Day Delivery',
        'cargo' => 'Cargo / Freight',
        'international' => 'International Delivery',
    ];

    /** @return array<string, string> */
    public static function services(bool $includeInactive = false): array
    {
        // Compatible with deployments awaiting the additive catalogue installer.
        if (!RateCatalogService::installed()) { return self::DEFAULT_SERVICES; }
        return Database::connection()->query('SELECT code, name FROM rate_services'
            . ($includeInactive ? '' : ' WHERE status = "active"') . ' ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** @return array<int, array<string, mixed>> */
    public static function zones(bool $includeInactive = false): array
    {
        return Database::connection()->query(
            'SELECT * FROM rate_zones' . ($includeInactive ? '' : ' WHERE status = "active"') . ' ORDER BY country_code = "ZZ", name'
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function allRates(): array
    {
        $rates = Database::connection()->query(
            'SELECT r.*, oz.name AS origin_name, dz.name AS destination_name, oz.status AS origin_status,
                    dz.status AS destination_status, u.full_name AS staff_name
             FROM rate_cards r
             JOIN rate_zones oz ON oz.id = r.origin_zone_id
             JOIN rate_zones dz ON dz.id = r.destination_zone_id
             JOIN staff_users u ON u.id = r.created_by
             ORDER BY r.status = "active" DESC, oz.name, dz.name, r.service_name'
        )->fetchAll();
        $services = self::services(true);
        $activeServices = self::services();
        foreach ($rates as &$rate) {
            $rate['service_name'] = $services[$rate['service_code']] ?? $rate['service_name'];
            $rate['available'] = $rate['status'] === 'active' && $rate['origin_status'] === 'active'
                && $rate['destination_status'] === 'active' && isset($activeServices[$rate['service_code']]);
        }
        unset($rate);
        return $rates;
    }

    public static function findRate(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM rate_cards WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public static function calculate(array $input): array
    {
        $originId = (int) ($input['origin_zone_id'] ?? 0);
        $destinationId = (int) ($input['destination_zone_id'] ?? 0);
        $serviceCode = (string) ($input['service_code'] ?? '');
        $services = self::services();
        $weight = round((float) ($input['weight_kg'] ?? 0), 2);
        if ($originId < 1 || $destinationId < 1 || !isset($services[$serviceCode]) || !is_finite($weight) || $weight <= 0 || $weight > 100000) {
            throw new RuntimeException('Choose a valid route, service and package weight.');
        }

        $statement = Database::connection()->prepare(
            'SELECT r.*, oz.name AS origin_name, dz.name AS destination_name
             FROM rate_cards r
             JOIN rate_zones oz ON oz.id = r.origin_zone_id
             JOIN rate_zones dz ON dz.id = r.destination_zone_id
             WHERE r.origin_zone_id = :origin AND r.destination_zone_id = :destination
               AND r.service_code = :service AND r.status = "active"
               AND oz.status = "active" AND dz.status = "active" LIMIT 1'
        );
        $statement->execute(['origin' => $originId, 'destination' => $destinationId, 'service' => $serviceCode]);
        $rate = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($rate)) {
            throw new RuntimeException('This route does not yet have an online rate. Please request a manual quote.');
        }

        $length = max(0, (float) ($input['length_cm'] ?? 0));
        $width = max(0, (float) ($input['width_cm'] ?? 0));
        $height = max(0, (float) ($input['height_cm'] ?? 0));
        foreach ([$length, $width, $height] as $dimension) {
            if (!is_finite($dimension) || $dimension > 100000) {
                throw new RuntimeException('Package dimensions are outside the supported range.');
            }
        }
        $volumetric = ($length > 0 && $width > 0 && $height > 0)
            ? round(($length * $width * $height) / max(1, (float) $rate['volumetric_divisor']), 2)
            : 0.0;
        $chargeable = round(max($weight, $volumetric), 2);
        $extraKg = max(0, $chargeable - (float) $rate['base_weight_kg']);
        $rawWeightAmount = round($extraKg * (float) $rate['extra_kg_fee'], 2);
        $linehaul = max((float) $rate['minimum_fee'], (float) $rate['base_fee'] + $rawWeightAmount);
        $baseAmount = min((float) $rate['base_fee'], $linehaul);
        $weightAmount = round($linehaul - $baseAmount, 2);
        $fuelAmount = round($linehaul * ((float) $rate['fuel_percent'] / 100), 2);
        $declaredValue = max(0, (float) ($input['declared_value'] ?? 0));
        if (!is_finite($declaredValue) || $declaredValue > 1000000000) {
            throw new RuntimeException('Declared value is outside the supported range.');
        }
        $insuranceAmount = round($declaredValue * ((float) $rate['insurance_percent'] / 100), 2);
        $packagingAmount = !empty($input['packaging_required']) ? (float) $rate['packaging_fee'] : 0.0;
        $subtotal = round($linehaul + $fuelAmount + $insuranceAmount + $packagingAmount, 2);
        $taxAmount = round($subtotal * ((float) $rate['tax_percent'] / 100), 2);

        return [
            'rate_id' => (int) $rate['id'],
            'origin_zone_id' => $originId,
            'destination_zone_id' => $destinationId,
            'origin_name' => (string) $rate['origin_name'],
            'destination_name' => (string) $rate['destination_name'],
            'service_code' => $serviceCode,
            'service_name' => $services[$serviceCode],
            'currency' => (string) $rate['currency'],
            'weight_kg' => $weight,
            'volumetric_weight_kg' => $volumetric,
            'chargeable_weight_kg' => $chargeable,
            'declared_value' => round($declaredValue, 2),
            'base_amount' => round($baseAmount, 2),
            'weight_amount' => $weightAmount,
            'fuel_amount' => $fuelAmount,
            'insurance_amount' => $insuranceAmount,
            'packaging_amount' => round($packagingAmount, 2),
            'tax_amount' => $taxAmount,
            'total_amount' => round($subtotal + $taxAmount, 2),
            'estimated_days_min' => $rate['estimated_days_min'] === null ? null : (int) $rate['estimated_days_min'],
            'estimated_days_max' => $rate['estimated_days_max'] === null ? null : (int) $rate['estimated_days_max'],
        ];
    }

    /** @param array<string, mixed> $data */
    public static function saveRate(array $data, int $staffId, bool $fromForm = false): int
    {
        RateCatalogService::assertAdmin($staffId);
        $values = self::validateRate($data);
        $id = RateCatalogService::id($data['id'] ?? 0, true);
        $pdo = Database::connection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) { $pdo->beginTransaction(); }
        try {
            // Keep the old internal route-upsert API for existing integrations. Staff
            // forms always pass an explicit ID and may never overwrite another route.
            if (!$fromForm && $id === 0) {
                $lookup = $pdo->prepare('SELECT id FROM rate_cards WHERE origin_zone_id = ? AND destination_zone_id = ? AND service_code = ?');
                $lookup->execute([$values['origin_zone_id'],$values['destination_zone_id'],$values['service_code']]);
                $id = (int) ($lookup->fetchColumn() ?: 0);
            }
            if ($id > 0) {
                $lookup = $pdo->prepare('SELECT * FROM rate_cards WHERE id = ? FOR UPDATE');
                $lookup->execute([$id]); $existing = $lookup->fetch();
                if (!is_array($existing)) { throw new RuntimeException('That rate no longer exists.'); }
                if ($fromForm) { RateCatalogService::assertVersion($existing, $data['version'] ?? null); }
            }
            $duplicate = $pdo->prepare('SELECT id FROM rate_cards WHERE origin_zone_id = ? AND destination_zone_id = ? AND service_code = ? AND id <> ?');
            $duplicate->execute([$values['origin_zone_id'],$values['destination_zone_id'],$values['service_code'],$id]);
            if ($duplicate->fetchColumn()) { throw new RuntimeException('A rate already exists for this origin, destination and service. Use its Edit button instead.'); }
            $zoneLookup = $pdo->prepare('SELECT id,status FROM rate_zones WHERE id IN (?,?) ORDER BY id FOR UPDATE');
            $zoneLookup->execute([$values['origin_zone_id'],$values['destination_zone_id']]);
            $zones = $zoneLookup->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach (['origin_zone_id','destination_zone_id'] as $field) {
                if (!isset($zones[$values[$field]])) { throw new RuntimeException('Choose an existing origin and destination.'); }
                if ($values['status'] === 'active' && $zones[$values[$field]] !== 'active') { throw new RuntimeException('Activate the origin and destination before activating this rate.'); }
            }
            if (RateCatalogService::installed()) {
                $serviceLookup = $pdo->prepare('SELECT name,status FROM rate_services WHERE code = ? FOR UPDATE');
                $serviceLookup->execute([$values['service_code']]); $service = $serviceLookup->fetch();
                if (!is_array($service)) { throw new RuntimeException('Choose an existing service.'); }
                if ($values['status'] === 'active' && $service['status'] !== 'active') { throw new RuntimeException('Activate the service before activating this rate.'); }
                $values['service_name'] = $service['name'];
            } else {
                $values['service_name'] = self::DEFAULT_SERVICES[$values['service_code']] ?? throw new RuntimeException('Choose a valid service.');
            }
            $assignments = implode(', ', array_map(static fn (string $key): string => $key . ' = :' . $key, array_keys($values)));
            if ($id > 0) {
                $values['id'] = $id;
                $pdo->prepare('UPDATE rate_cards SET ' . $assignments . ', updated_at = NOW() WHERE id = :id')->execute($values);
            } else {
                $values['staff'] = $staffId;
                $pdo->prepare('INSERT INTO rate_cards SET ' . $assignments . ', created_by = :staff, created_at = NOW(), updated_at = NOW()')->execute($values);
                $id = (int) $pdo->lastInsertId();
            }
            AuditService::record('pricing.rate_saved', 'rate_card', $id, ['route' => $values['origin_zone_id'] . '-' . $values['destination_zone_id'], 'service' => $values['service_code'], 'actor_id' => $staffId]);
            if ($ownsTransaction) { $pdo->commit(); }
            return $id;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
            if ($exception instanceof PDOException && (int) ($exception->errorInfo[1] ?? 0) === 1062) { throw new RuntimeException('A rate already exists for this origin, destination and service. Use its Edit button instead.'); }
            throw $exception;
        }
    }

    private static function validateRate(array $data): array
    {
        $values = [
            'origin_zone_id' => RateCatalogService::id($data['origin_zone_id'] ?? null),
            'destination_zone_id' => RateCatalogService::id($data['destination_zone_id'] ?? null),
            'service_code' => RateCatalogService::text($data['service_code'] ?? '', 40, 'service'),
            'currency' => strtoupper(RateCatalogService::text($data['currency'] ?? '', 3, 'currency')),
            'status' => RateCatalogService::text($data['status'] ?? 'active', 20, 'status'),
        ];
        if (!in_array($values['currency'], ['NGN','USD','GBP','EUR'], true) || !in_array($values['status'], ['active','inactive'], true)) {
            throw new RuntimeException('Choose a valid currency and status.');
        }
        foreach (['base_fee','base_weight_kg','extra_kg_fee','minimum_fee','packaging_fee','fuel_percent','insurance_percent','tax_percent','volumetric_divisor'] as $field) {
            $percentage = str_ends_with($field, '_percent');
            $min = $field === 'base_weight_kg' ? 0.01 : ($field === 'volumetric_divisor' ? 1 : 0);
            $max = $percentage ? 100 : (in_array($field, ['base_weight_kg','volumetric_divisor'], true) ? 100000 : 1000000000);
            $input = $data[$field] ?? null;
            $value = is_scalar($input) ? filter_var($input, FILTER_VALIDATE_FLOAT) : false;
            if ($value === false || !is_finite((float) $value) || $value < $min || $value > $max) {
                throw new RuntimeException('Enter a valid ' . str_replace('_', ' ', $field) . ' between ' . $min . ' and ' . $max . '.');
            }
            $values[$field] = round((float) $value, $percentage ? 3 : 2);
        }
        foreach (['estimated_days_min','estimated_days_max'] as $field) {
            $input = $data[$field] ?? null;
            $value = ($input === null || $input === '') ? null : filter_var($input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 365]]);
            if ($value === false) { throw new RuntimeException('Delivery days must be whole numbers between 0 and 365.'); }
            $values[$field] = $value;
        }
        if (($values['estimated_days_min'] === null) !== ($values['estimated_days_max'] === null)
            || ($values['estimated_days_min'] !== null && $values['estimated_days_min'] > $values['estimated_days_max'])) {
            throw new RuntimeException('Enter both delivery-day limits in order, or leave both blank.');
        }
        return $values;
    }
}
