<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class PricingService
{
    /** @var array<string, string> */
    private const SERVICES = [
        'standard' => 'Standard Delivery',
        'express' => 'Express Delivery',
        'same_day' => 'Same-Day Delivery',
        'cargo' => 'Cargo / Freight',
        'international' => 'International Delivery',
    ];

    /** @return array<string, string> */
    public static function services(): array
    {
        return self::SERVICES;
    }

    /** @return array<int, array<string, mixed>> */
    public static function zones(): array
    {
        return Database::connection()->query(
            'SELECT id, code, name, country_code FROM rate_zones WHERE status = "active" ORDER BY country_code = "ZZ", name'
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function allRates(): array
    {
        return Database::connection()->query(
            'SELECT r.*, oz.name AS origin_name, dz.name AS destination_name, u.full_name AS staff_name
             FROM rate_cards r
             JOIN rate_zones oz ON oz.id = r.origin_zone_id
             JOIN rate_zones dz ON dz.id = r.destination_zone_id
             JOIN staff_users u ON u.id = r.created_by
             ORDER BY r.status = "active" DESC, oz.name, dz.name, r.service_name'
        )->fetchAll();
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public static function calculate(array $input): array
    {
        $originId = (int) ($input['origin_zone_id'] ?? 0);
        $destinationId = (int) ($input['destination_zone_id'] ?? 0);
        $serviceCode = (string) ($input['service_code'] ?? '');
        $weight = round((float) ($input['weight_kg'] ?? 0), 2);
        if ($originId < 1 || $destinationId < 1 || !isset(self::SERVICES[$serviceCode]) || !is_finite($weight) || $weight <= 0 || $weight > 100000) {
            throw new RuntimeException('Choose a valid route, service and package weight.');
        }

        $statement = Database::connection()->prepare(
            'SELECT r.*, oz.name AS origin_name, dz.name AS destination_name
             FROM rate_cards r
             JOIN rate_zones oz ON oz.id = r.origin_zone_id
             JOIN rate_zones dz ON dz.id = r.destination_zone_id
             WHERE r.origin_zone_id = :origin AND r.destination_zone_id = :destination
               AND r.service_code = :service AND r.status = "active" LIMIT 1'
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
            'service_name' => (string) $rate['service_name'],
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
    public static function saveRate(array $data, int $staffId): void
    {
        if (!isset(self::SERVICES[$data['service_code']])) {
            throw new RuntimeException('Choose a valid service.');
        }
        $statement = Database::connection()->prepare(
            'INSERT INTO rate_cards
                (origin_zone_id, destination_zone_id, service_code, service_name, currency, base_fee, base_weight_kg,
                 extra_kg_fee, minimum_fee, fuel_percent, insurance_percent, packaging_fee, tax_percent,
                 volumetric_divisor, estimated_days_min, estimated_days_max, status, created_by, created_at, updated_at)
             VALUES
                (:origin, :destination, :service_code, :service_name, :currency, :base_fee, :base_weight,
                 :extra_fee, :minimum_fee, :fuel, :insurance, :packaging, :tax, :divisor, :days_min, :days_max,
                 :status, :staff, NOW(), NOW())
             ON DUPLICATE KEY UPDATE service_name = VALUES(service_name), currency = VALUES(currency),
                 base_fee = VALUES(base_fee), base_weight_kg = VALUES(base_weight_kg), extra_kg_fee = VALUES(extra_kg_fee),
                 minimum_fee = VALUES(minimum_fee), fuel_percent = VALUES(fuel_percent), insurance_percent = VALUES(insurance_percent),
                 packaging_fee = VALUES(packaging_fee), tax_percent = VALUES(tax_percent), volumetric_divisor = VALUES(volumetric_divisor),
                 estimated_days_min = VALUES(estimated_days_min), estimated_days_max = VALUES(estimated_days_max),
                 status = VALUES(status), created_by = VALUES(created_by), updated_at = NOW()'
        );
        $statement->execute([
            'origin' => $data['origin_zone_id'], 'destination' => $data['destination_zone_id'],
            'service_code' => $data['service_code'], 'service_name' => self::SERVICES[$data['service_code']],
            'currency' => $data['currency'], 'base_fee' => $data['base_fee'], 'base_weight' => $data['base_weight_kg'],
            'extra_fee' => $data['extra_kg_fee'], 'minimum_fee' => $data['minimum_fee'], 'fuel' => $data['fuel_percent'],
            'insurance' => $data['insurance_percent'], 'packaging' => $data['packaging_fee'], 'tax' => $data['tax_percent'],
            'divisor' => $data['volumetric_divisor'], 'days_min' => $data['estimated_days_min'],
            'days_max' => $data['estimated_days_max'], 'status' => $data['status'], 'staff' => $staffId,
        ]);
        AuditService::record('pricing.rate_saved', 'rate_card', null, ['route' => $data['origin_zone_id'] . '-' . $data['destination_zone_id'], 'service' => $data['service_code']]);
    }
}
