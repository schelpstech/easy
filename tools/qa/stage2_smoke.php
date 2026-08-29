<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\AddressService;
use App\BillingService;
use App\BookingService;
use App\Database;
use App\PaymentService;
use App\PricingService;
use App\ProofOfDeliveryService;
use App\ShipmentService;

$pdo = Database::connection();
$startedAt = date('Y-m-d H:i:s');
$ids = ['staff' => null, 'customer' => null, 'addresses' => [], 'booking' => null, 'payment' => null, 'shipment' => null, 'proof' => null];
$rateKey = [];
$existingRate = null;

function qaAssert(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

try {
    $email = 'stage2-qa-' . bin2hex(random_bytes(4)) . '@example.test';
    $pdo->prepare(
        'INSERT INTO staff_users (full_name, email, password_hash, role, status, created_at, updated_at)
         VALUES ("Stage 2 QA", :email, :hash, "admin", "active", NOW(), NOW())'
    )->execute(['email' => $email, 'hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $ids['staff'] = (int) $pdo->lastInsertId();
    $pdo->prepare(
        'INSERT INTO customer_users (full_name, email, phone, password_hash, status, created_at, updated_at)
         VALUES ("Stage 2 Customer", :email, "+2348000000000", :hash, "active", NOW(), NOW())'
    )->execute(['email' => 'customer-' . $email, 'hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $ids['customer'] = (int) $pdo->lastInsertId();

    $zones = $pdo->query('SELECT code, id FROM rate_zones WHERE code IN ("LAGOS","OGUN")')->fetchAll(PDO::FETCH_KEY_PAIR);
    qaAssert(isset($zones['LAGOS'], $zones['OGUN']), 'Seed pricing zones were not installed.');
    $rateKey = [(int) $zones['LAGOS'], (int) $zones['OGUN'], 'standard'];
    $existingRateStatement = $pdo->prepare('SELECT * FROM rate_cards WHERE origin_zone_id = ? AND destination_zone_id = ? AND service_code = ?');
    $existingRateStatement->execute($rateKey);
    $existingRate = $existingRateStatement->fetch() ?: null;
    PricingService::saveRate([
        'origin_zone_id' => $rateKey[0], 'destination_zone_id' => $rateKey[1], 'service_code' => 'standard',
        'currency' => 'NGN', 'base_fee' => 2500, 'base_weight_kg' => 1, 'extra_kg_fee' => 500,
        'minimum_fee' => 2500, 'fuel_percent' => 5, 'insurance_percent' => 1, 'packaging_fee' => 750,
        'tax_percent' => 0, 'volumetric_divisor' => 5000, 'estimated_days_min' => 1,
        'estimated_days_max' => 2, 'status' => 'active',
    ], (int) $ids['staff']);

    foreach ([['Pickup HQ','Sender'],['Delivery Office','Receiver']] as $index => $details) {
        $ids['addresses'][] = AddressService::create((int) $ids['customer'], [
            'label' => $details[0], 'recipient_name' => $details[1], 'phone' => '+2348000000000',
            'address_line' => $index === 0 ? '1 Marina Road' : '22 Easyway Avenue',
            'city' => $index === 0 ? 'Lagos' : 'Ifo', 'state_name' => $index === 0 ? 'Lagos' : 'Ogun',
            'country_code' => 'NG', 'directions' => '', 'is_default' => $index === 0,
        ]);
    }
    $bookingData = [
        'pickup_address_id' => $ids['addresses'][0], 'delivery_address_id' => $ids['addresses'][1],
        'origin_zone_id' => $rateKey[0], 'destination_zone_id' => $rateKey[1], 'service_code' => 'standard',
        'package_description' => 'QA documentation parcel', 'weight_kg' => 2.5,
        'length_cm' => 50, 'width_cm' => 40, 'height_cm' => 30, 'declared_value' => 10000,
        'is_fragile' => true, 'packaging_required' => true,
    ];
    $estimate = PricingService::calculate($bookingData);
    qaAssert((float) $estimate['volumetric_weight_kg'] === 12.0, 'Volumetric weight was not calculated correctly.');
    qaAssert((float) $estimate['total_amount'] === 9250.0, 'Pricing breakdown total is incorrect.');
    $ids['booking'] = BookingService::create($bookingData, (int) $ids['customer']);
    $booking = BookingService::find((int) $ids['booking']);
    qaAssert($booking !== null && $booking['payment_status'] === 'unpaid', 'Booking was not created as unpaid.');
    qaAssert(count(BillingService::allForCustomer((int) $ids['customer'])) === 1, 'Invoice was not issued automatically.');

    $reference = 'EWP-QA-' . strtoupper(bin2hex(random_bytes(5)));
    $pdo->prepare(
        'INSERT INTO payments (booking_id, customer_id, provider, reference, amount, currency, status, created_at, updated_at)
         VALUES (:booking, :customer, "paystack", :reference, :amount, "NGN", "pending", NOW(), NOW())'
    )->execute(['booking' => $ids['booking'], 'customer' => $ids['customer'], 'reference' => $reference, 'amount' => $estimate['total_amount']]);
    $ids['payment'] = (int) $pdo->lastInsertId();
    putenv('PAYSTACK_SECRET_KEY=stage2_qa_secret');
    $payload = json_encode(['event' => 'charge.success', 'data' => [
        'id' => 987654321, 'status' => 'success', 'reference' => $reference,
        'amount' => (int) round((float) $estimate['total_amount'] * 100), 'currency' => 'NGN',
        'paid_at' => date(DATE_ATOM), 'gateway_response' => 'Successful',
    ]], JSON_UNESCAPED_SLASHES);
    $signature = hash_hmac('sha512', (string) $payload, 'stage2_qa_secret');
    $invalidSignatureBlocked = false;
    try {
        PaymentService::processWebhook((string) $payload, str_repeat('0', 128));
    } catch (RuntimeException) {
        $invalidSignatureBlocked = true;
    }
    qaAssert($invalidSignatureBlocked, 'An invalid payment webhook signature was accepted.');
    $mismatchPayload = json_encode(['event' => 'charge.success', 'data' => [
        'id' => 987654320, 'status' => 'success', 'reference' => $reference,
        'amount' => ((int) round((float) $estimate['total_amount'] * 100)) - 1, 'currency' => 'NGN',
        'paid_at' => date(DATE_ATOM), 'gateway_response' => 'Successful',
    ]], JSON_UNESCAPED_SLASHES);
    $mismatchBlocked = false;
    try {
        PaymentService::processWebhook((string) $mismatchPayload, hash_hmac('sha512', (string) $mismatchPayload, 'stage2_qa_secret'));
    } catch (RuntimeException) {
        $mismatchBlocked = true;
    }
    $unpaidAfterMismatch = BookingService::find((int) $ids['booking']);
    qaAssert($mismatchBlocked && $unpaidAfterMismatch['payment_status'] === 'unpaid', 'A payment amount mismatch released the booking.');
    $first = PaymentService::processWebhook((string) $payload, $signature);
    $second = PaymentService::processWebhook((string) $payload, $signature);
    qaAssert($first['processed'] && !$first['duplicate'] && $second['duplicate'], 'Webhook processing was not idempotent.');
    $booking = BookingService::find((int) $ids['booking']);
    qaAssert($booking['payment_status'] === 'paid' && $booking['status'] === 'confirmed', 'Verified payment did not confirm the booking.');
    qaAssert(count(BillingService::allForCustomer((int) $ids['customer'])) === 2, 'Receipt was not issued after payment.');

    $tracking = BookingService::convertToShipment((int) $ids['booking'], (int) $ids['staff']);
    $shipmentStatement = $pdo->prepare('SELECT id FROM shipments WHERE tracking_number = :tracking');
    $shipmentStatement->execute(['tracking' => $tracking]);
    $ids['shipment'] = (int) $shipmentStatement->fetchColumn();
    foreach ([
        ['picked_up','Picked up'], ['in_transit','In transit'], ['out_for_delivery','Out for delivery'],
    ] as $event) {
        ShipmentService::addEvent((int) $ids['shipment'], [
            'status' => $event[0], 'title' => $event[1], 'description' => 'Stage 2 QA milestone',
            'location' => 'QA facility', 'event_time' => date('Y-m-d H:i:s'), 'is_public' => true,
        ], (int) $ids['staff']);
    }
    $deliveryBlocked = false;
    try {
        ShipmentService::addEvent((int) $ids['shipment'], [
            'status' => 'delivered', 'title' => 'Delivered', 'description' => '', 'location' => '',
            'event_time' => date('Y-m-d H:i:s'), 'is_public' => true,
        ], (int) $ids['staff']);
    } catch (RuntimeException) {
        $deliveryBlocked = true;
    }
    qaAssert($deliveryBlocked, 'Delivery was allowed without proof of delivery.');
    $ids['proof'] = ProofOfDeliveryService::capture((int) $ids['shipment'], [
        'recipient_name' => 'QA Receiver', 'delivery_note' => 'Parcel received in good condition.',
        'latitude' => 6.6018, 'longitude' => 3.3515, 'delivered_at' => date('Y-m-d H:i:s'),
    ], null, (int) $ids['staff']);
    $final = ShipmentService::find((int) $ids['shipment']);
    qaAssert($final !== null && $final['shipment']['status'] === 'delivered', 'Proof of delivery did not close the shipment.');
    qaAssert(ProofOfDeliveryService::customerCanAccess((int) $ids['proof'], (int) $ids['customer']), 'Customer cannot access their proof of delivery.');

    echo "Stage 2 smoke test passed.\n";
    echo "Booking {$booking['booking_number']}, payment webhook, receipt, shipment {$tracking}, notifications and proof of delivery verified.\n";
} finally {
    putenv('PAYSTACK_SECRET_KEY');
    if ($ids['customer'] !== null) {
        $pdo->prepare('DELETE FROM notification_outbox WHERE customer_id = :customer')->execute(['customer' => $ids['customer']]);
        $pdo->prepare('DELETE FROM billing_documents WHERE customer_id = :customer')->execute(['customer' => $ids['customer']]);
        $pdo->prepare('DELETE FROM payments WHERE customer_id = :customer')->execute(['customer' => $ids['customer']]);
    }
    if ($ids['payment'] !== null) {
        $pdo->prepare('DELETE FROM payment_webhook_events WHERE reference LIKE "EWP-QA-%"')->execute();
    }
    if ($ids['proof'] !== null) {
        $pdo->prepare('DELETE FROM proofs_of_delivery WHERE id = :id')->execute(['id' => $ids['proof']]);
    }
    if ($ids['booking'] !== null) {
        $pdo->prepare('DELETE FROM booking_status_history WHERE booking_id = :booking')->execute(['booking' => $ids['booking']]);
        $pdo->prepare('DELETE FROM bookings WHERE id = :booking')->execute(['booking' => $ids['booking']]);
    }
    if ($ids['shipment'] !== null) {
        $pdo->prepare('DELETE FROM shipment_events WHERE shipment_id = :shipment')->execute(['shipment' => $ids['shipment']]);
        $pdo->prepare('DELETE FROM shipments WHERE id = :shipment')->execute(['shipment' => $ids['shipment']]);
    }
    if ($ids['addresses'] !== []) {
        $placeholders = implode(',', array_fill(0, count($ids['addresses']), '?'));
        $pdo->prepare('DELETE FROM customer_addresses WHERE id IN (' . $placeholders . ')')->execute($ids['addresses']);
    }
    if ($ids['customer'] !== null) {
        $pdo->prepare('DELETE FROM customer_users WHERE id = :customer')->execute(['customer' => $ids['customer']]);
    }
    if ($rateKey !== []) {
        if (is_array($existingRate)) {
            PricingService::saveRate($existingRate, (int) $existingRate['created_by']);
        } else {
            $pdo->prepare('DELETE FROM rate_cards WHERE origin_zone_id = ? AND destination_zone_id = ? AND service_code = ?')->execute($rateKey);
        }
    }
    $pdo->prepare('DELETE FROM audit_logs WHERE ip_address = "cli" AND created_at >= :started')->execute(['started' => $startedAt]);
    if ($ids['staff'] !== null) {
        $pdo->prepare('DELETE FROM staff_users WHERE id = :staff')->execute(['staff' => $ids['staff']]);
    }
}
