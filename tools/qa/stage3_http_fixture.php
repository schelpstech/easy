<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\CargoService;
use App\CorporateService;
use App\Database;
use App\RiderService;
use App\ShipmentService;

$mode = $argv[1] ?? '';
$token = preg_replace('/[^a-z0-9]/i', '', (string) ($argv[2] ?? ''));
if (!in_array($mode, ['create', 'cleanup'], true) || strlen($token) < 6) { fwrite(STDERR, "Usage: php stage3_http_fixture.php create|cleanup TOKEN\n"); exit(1); }
$pdo = Database::connection();
$adminEmail = 'stage3-http-admin-' . $token . '@example.test';
$riderEmail = 'stage3-http-rider-' . $token . '@example.test';
$customerEmail = 'stage3-http-customer-' . $token . '@example.test';

$cleanup = static function () use ($pdo, $adminEmail, $riderEmail, $customerEmail, $token): void {
    $corporates = $pdo->prepare('SELECT id FROM corporate_accounts WHERE company_name = :name');
    $corporates->execute(['name' => 'Stage 3 HTTP ' . $token]);
    foreach ($corporates->fetchAll(PDO::FETCH_COLUMN) as $corporateId) {
        $pdo->prepare('DELETE FROM cargo_shipments WHERE corporate_account_id = :id')->execute(['id' => $corporateId]);
        $pdo->prepare('DELETE FROM corporate_ledger WHERE corporate_account_id = :id')->execute(['id' => $corporateId]);
        $pdo->prepare('DELETE FROM corporate_booking_links WHERE corporate_account_id = :id')->execute(['id' => $corporateId]);
        $pdo->prepare('DELETE FROM bulk_shipment_batches WHERE corporate_account_id = :id')->execute(['id' => $corporateId]);
        $pdo->prepare('DELETE FROM corporate_accounts WHERE id = :id')->execute(['id' => $corporateId]);
    }
    $shipments = $pdo->prepare('SELECT id FROM shipments WHERE package_description = :description');
    $shipments->execute(['description' => 'Stage 3 HTTP ' . $token]);
    foreach ($shipments->fetchAll(PDO::FETCH_COLUMN) as $shipmentId) {
        $pdo->prepare('DELETE FROM notification_outbox WHERE shipment_id = :id')->execute(['id' => $shipmentId]);
        $pdo->prepare('DELETE FROM shipments WHERE id = :id')->execute(['id' => $shipmentId]);
    }
    $customer = $pdo->prepare('SELECT id FROM customer_users WHERE email = :email'); $customer->execute(['email' => $customerEmail]);
    foreach ($customer->fetchAll(PDO::FETCH_COLUMN) as $customerId) {
        $pdo->prepare('DELETE FROM audit_logs WHERE entity_type = "customer_user" AND entity_id = :id')->execute(['id' => $customerId]);
        $pdo->prepare('DELETE FROM notification_outbox WHERE customer_id = :id')->execute(['id' => $customerId]);
        $pdo->prepare('DELETE FROM customer_users WHERE id = :id')->execute(['id' => $customerId]);
    }
    $pdo->prepare('DELETE FROM customer_login_attempts WHERE email = :email')->execute(['email' => $customerEmail]);
    foreach ([$riderEmail, $adminEmail] as $email) {
        $staff = $pdo->prepare('SELECT id FROM staff_users WHERE email = :email'); $staff->execute(['email' => $email]);
        foreach ($staff->fetchAll(PDO::FETCH_COLUMN) as $staffId) {
            $pdo->prepare('DELETE FROM audit_logs WHERE staff_user_id = :id')->execute(['id' => $staffId]);
            $pdo->prepare('DELETE FROM staff_users WHERE id = :id')->execute(['id' => $staffId]);
        }
        $pdo->prepare('DELETE FROM login_attempts WHERE email = :email')->execute(['email' => $email]);
    }
};

if ($mode === 'cleanup') { $cleanup(); echo "Stage 3 HTTP fixtures removed.\n"; exit; }
$cleanup();
$password = 'Stage3-HTTP-' . $token . '!';
$pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status,created_at,updated_at) VALUES ("Stage 3 HTTP Admin",:email,:hash,"admin","active",NOW(),NOW())')
    ->execute(['email' => $adminEmail, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
$adminId = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO customer_users (full_name,email,phone,password_hash,status,created_at,updated_at) VALUES ("Stage 3 HTTP Customer",:email,"+2348000000100",:hash,"active",NOW(),NOW())')
    ->execute(['email' => $customerEmail, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
$customerId = (int) $pdo->lastInsertId();
$riderId = RiderService::create(['full_name' => 'Stage 3 HTTP Rider', 'email' => $riderEmail, 'phone' => '+2348000000101', 'vehicle_type' => 'motorcycle', 'vehicle_registration' => 'HTTP-' . strtoupper($token), 'licence_number' => '', 'emergency_contact' => '', 'password' => $password], $adminId);
$rider = $pdo->prepare('SELECT staff_user_id FROM rider_profiles WHERE id=:id'); $rider->execute(['id' => $riderId]); $riderStaffId = (int) $rider->fetchColumn();
$tracking = ShipmentService::create(['customer_name' => 'Stage 3 HTTP Customer', 'customer_email' => $customerEmail, 'customer_phone' => '+2348000000100', 'origin' => 'Lagos', 'destination' => 'Abeokuta', 'service_type' => 'Standard Delivery', 'package_description' => 'Stage 3 HTTP ' . $token, 'weight_kg' => 2, 'expected_delivery_at' => null], $adminId);
$shipment = $pdo->prepare('SELECT id FROM shipments WHERE tracking_number=:tracking'); $shipment->execute(['tracking' => $tracking]); $shipmentId = (int) $shipment->fetchColumn();
RiderService::assign($shipmentId, $riderId, 'HTTP QA assignment', $adminId);
ShipmentService::addEvent($shipmentId, ['status' => 'picked_up', 'title' => '', 'description' => 'HTTP QA pickup', 'location' => 'Lagos', 'event_time' => date('Y-m-d H:i:s'), 'is_public' => true], $adminId);
$corporateId = CorporateService::create(['company_name' => 'Stage 3 HTTP ' . $token, 'billing_email' => $customerEmail, 'billing_phone' => '+2348000000100', 'billing_address' => 'QA address', 'tax_id' => '', 'credit_limit' => 100000, 'payment_terms_days' => 30, 'currency' => 'NGN', 'account_manager_id' => $adminId], $adminId);
CorporateService::addMemberByEmail($corporateId, $customerEmail, 'owner', $adminId);
$cargoId = CargoService::create(['shipment_id' => 0, 'corporate_account_id' => $corporateId, 'transport_mode' => 'air', 'cargo_type' => 'QA cargo', 'incoterm' => 'CIF', 'origin_terminal' => 'LOS', 'destination_terminal' => 'ABV', 'carrier_name' => 'QA Air', 'vessel_or_flight' => 'QA101', 'airway_or_bill_number' => 'QA-' . $token, 'container_number' => '', 'pieces' => 2, 'gross_weight_kg' => 10, 'volume_cbm' => 0.3, 'estimated_departure_at' => null, 'estimated_arrival_at' => null], $adminId);
echo json_encode(['token' => $token, 'password' => $password, 'admin_email' => $adminEmail, 'rider_email' => $riderEmail, 'customer_email' => $customerEmail, 'shipment_id' => $shipmentId, 'tracking' => $tracking, 'corporate_id' => $corporateId, 'cargo_id' => $cargoId], JSON_UNESCAPED_SLASHES) . PHP_EOL;
