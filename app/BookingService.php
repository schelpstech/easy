<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class BookingService
{
    /** @param array<string, mixed> $data */
    public static function create(array $data, int $customerId): int
    {
        $pickup = AddressService::findForCustomer((int) $data['pickup_address_id'], $customerId);
        $delivery = AddressService::findForCustomer((int) $data['delivery_address_id'], $customerId);
        if ($pickup === null || $delivery === null) {
            throw new RuntimeException('Choose pickup and delivery addresses from your account.');
        }
        $pricing = PricingService::calculate($data);
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $number = self::bookingNumber();
            $statement = $pdo->prepare(
                'INSERT INTO bookings
                    (booking_number, customer_id, origin_zone_id, destination_zone_id, pickup_address_id, delivery_address_id,
                     pickup_snapshot_json, delivery_snapshot_json, service_code, service_name, package_description,
                     weight_kg, length_cm, width_cm, height_cm, volumetric_weight_kg, chargeable_weight_kg,
                     declared_value, is_fragile, packaging_required, currency, base_amount, weight_amount, fuel_amount,
                     insurance_amount, packaging_amount, tax_amount, total_amount, status, payment_status,
                     quote_expires_at, created_at, updated_at)
                 VALUES
                    (:number, :customer, :origin, :destination, :pickup_id, :delivery_id, :pickup_json, :delivery_json,
                     :service_code, :service_name, :description, :weight, :length, :width, :height, :volumetric,
                     :chargeable, :declared, :fragile, :packaging_required, :currency, :base, :weight_amount, :fuel,
                     :insurance, :packaging, :tax, :total, "awaiting_payment", "unpaid", DATE_ADD(NOW(), INTERVAL 48 HOUR), NOW(), NOW())'
            );
            $statement->execute([
                'number' => $number, 'customer' => $customerId, 'origin' => $pricing['origin_zone_id'],
                'destination' => $pricing['destination_zone_id'], 'pickup_id' => $pickup['id'], 'delivery_id' => $delivery['id'],
                'pickup_json' => json_encode($pickup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'delivery_json' => json_encode($delivery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'service_code' => $pricing['service_code'], 'service_name' => $pricing['service_name'],
                'description' => $data['package_description'], 'weight' => $pricing['weight_kg'],
                'length' => ($data['length_cm'] ?? 0) > 0 ? $data['length_cm'] : null,
                'width' => ($data['width_cm'] ?? 0) > 0 ? $data['width_cm'] : null,
                'height' => ($data['height_cm'] ?? 0) > 0 ? $data['height_cm'] : null,
                'volumetric' => $pricing['volumetric_weight_kg'], 'chargeable' => $pricing['chargeable_weight_kg'],
                'declared' => $pricing['declared_value'], 'fragile' => !empty($data['is_fragile']) ? 1 : 0,
                'packaging_required' => !empty($data['packaging_required']) ? 1 : 0, 'currency' => $pricing['currency'],
                'base' => $pricing['base_amount'], 'weight_amount' => $pricing['weight_amount'], 'fuel' => $pricing['fuel_amount'],
                'insurance' => $pricing['insurance_amount'], 'packaging' => $pricing['packaging_amount'],
                'tax' => $pricing['tax_amount'], 'total' => $pricing['total_amount'],
            ]);
            $bookingId = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO booking_status_history (booking_id, status, note, actor_type, actor_id, created_at)
                 VALUES (:booking, "awaiting_payment", "Online booking created", "customer", :customer, NOW())'
            )->execute(['booking' => $bookingId, 'customer' => $customerId]);
            $pdo->commit();

            BillingService::issueInvoice($bookingId);
            NotificationService::queueBooking($bookingId, 'booking_created', 'Booking ' . $number . ' created', 'Your Easyway booking ' . $number . ' has been created. Total due: ' . $pricing['currency'] . ' ' . number_format((float) $pricing['total_amount'], 2) . '.');
            AuditService::record('booking.created', 'booking', $bookingId, ['booking_number' => $number, 'customer_id' => $customerId]);
            return $bookingId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function allForCustomer(int $customerId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.*, s.tracking_number FROM bookings b LEFT JOIN shipments s ON s.id = b.shipment_id
             WHERE b.customer_id = :customer ORDER BY b.created_at DESC'
        );
        $statement->execute(['customer' => $customerId]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(int $limit = 200): array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.*, c.full_name, c.email, c.phone, s.tracking_number
             FROM bookings b JOIN customer_users c ON c.id = b.customer_id
             LEFT JOIN shipments s ON s.id = b.shipment_id ORDER BY b.created_at DESC LIMIT :limit'
        );
        $statement->bindValue('limit', max(1, min(500, $limit)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function findForCustomer(int $bookingId, int $customerId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.*, s.tracking_number FROM bookings b LEFT JOIN shipments s ON s.id = b.shipment_id
             WHERE b.id = :id AND b.customer_id = :customer LIMIT 1'
        );
        $statement->execute(['id' => $bookingId, 'customer' => $customerId]);
        $booking = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($booking) ? $booking : null;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $bookingId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.*, c.full_name, c.email, c.phone, s.tracking_number
             FROM bookings b JOIN customer_users c ON c.id = b.customer_id
             LEFT JOIN shipments s ON s.id = b.shipment_id WHERE b.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $bookingId]);
        $booking = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($booking) ? $booking : null;
    }

    public static function convertToShipment(int $bookingId, int $staffId): string
    {
        $booking = self::find($bookingId);
        if ($booking === null) {
            throw new RuntimeException('Booking not found.');
        }
        if (!in_array($booking['payment_status'], ['paid', 'corporate_credit'], true) || $booking['status'] !== 'confirmed') {
            throw new RuntimeException('Only a paid or approved corporate booking can become a shipment.');
        }
        if ($booking['shipment_id'] !== null) {
            return (string) $booking['tracking_number'];
        }
        $pickup = json_decode((string) $booking['pickup_snapshot_json'], true) ?: [];
        $delivery = json_decode((string) $booking['delivery_snapshot_json'], true) ?: [];
        $tracking = ShipmentService::create([
            'customer_name' => $booking['full_name'], 'customer_email' => $booking['email'], 'customer_phone' => $booking['phone'],
            'origin' => AddressService::formatted($pickup), 'destination' => AddressService::formatted($delivery),
            'service_type' => $booking['service_name'], 'package_description' => $booking['package_description'],
            'weight_kg' => $booking['chargeable_weight_kg'], 'expected_delivery_at' => null,
        ], $staffId);
        $pdo = Database::connection();
        $shipment = $pdo->prepare('SELECT id FROM shipments WHERE tracking_number = :tracking LIMIT 1');
        $shipment->execute(['tracking' => $tracking]);
        $shipmentId = (int) $shipment->fetchColumn();
        $pdo->prepare('UPDATE bookings SET shipment_id = :shipment, status = "processing", updated_at = NOW() WHERE id = :id AND shipment_id IS NULL')
            ->execute(['shipment' => $shipmentId, 'id' => $bookingId]);
        $pdo->prepare(
            'INSERT INTO booking_status_history (booking_id, status, note, actor_type, actor_id, created_at)
             VALUES (:booking, "processing", :note, "staff", :staff, NOW())'
        )->execute(['booking' => $bookingId, 'note' => 'Shipment created: ' . $tracking, 'staff' => $staffId]);
        NotificationService::queueBooking($bookingId, 'shipment_created', 'Shipment ' . $tracking . ' is ready', 'Your booking ' . $booking['booking_number'] . ' is now shipment ' . $tracking . '. You can track it on the Easyway website.');
        return $tracking;
    }

    private static function bookingNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = 'EWB' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
            $check = Database::connection()->prepare('SELECT 1 FROM bookings WHERE booking_number = :number');
            $check->execute(['number' => $number]);
            if (!$check->fetchColumn()) {
                return $number;
            }
        }
        throw new RuntimeException('Unable to create a booking number.');
    }
}
