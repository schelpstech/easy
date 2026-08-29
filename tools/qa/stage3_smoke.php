<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\AddressService;
use App\BookingService;
use App\BulkShipmentService;
use App\CargoService;
use App\CorporateService;
use App\Database;
use App\PricingService;
use App\ReportService;
use App\RiderService;
use App\ShipmentService;

$pdo = Database::connection();
$ids = ['admin' => null, 'rider_staff' => null, 'customer' => null, 'customer2' => null, 'addresses' => [], 'shipments' => [], 'bookings' => [], 'corporate' => null, 'batch' => null, 'cargo' => null];
$existingRate = null; $rateKey = [];
$csvPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'easyway-stage3-' . bin2hex(random_bytes(4)) . '.csv';

function stage3Assert(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }

try {
    $stamp = bin2hex(random_bytes(4));
    $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status,created_at,updated_at) VALUES ("Stage 3 QA Admin",:email,:hash,"admin","active",NOW(),NOW())')
        ->execute(['email' => 'stage3-admin-' . $stamp . '@example.test', 'hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $ids['admin'] = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO customer_users (full_name,email,phone,password_hash,status,created_at,updated_at) VALUES ("Stage 3 QA Customer",:email,"+2348000000001",:hash,"active",NOW(),NOW())')
        ->execute(['email' => 'stage3-customer-' . $stamp . '@example.test', 'hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $ids['customer'] = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO customer_users (full_name,email,phone,password_hash,status,created_at,updated_at) VALUES ("Stage 3 QA Member",:email,"+2348000000099",:hash,"active",NOW(),NOW())')
        ->execute(['email' => 'stage3-member-' . $stamp . '@example.test', 'hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $ids['customer2'] = (int) $pdo->lastInsertId();

    foreach ([['Warehouse','QA Sender','1 QA Road','Lagos','Lagos'],['Client','QA Receiver','2 QA Close','Abeokuta','Ogun']] as $index => $address) {
        $ids['addresses'][] = AddressService::create((int) $ids['customer'], [
            'label' => $address[0], 'recipient_name' => $address[1], 'phone' => '+234800000000' . ($index + 2),
            'address_line' => $address[2], 'city' => $address[3], 'state_name' => $address[4], 'country_code' => 'NG',
            'directions' => '', 'is_default' => $index === 0,
        ]);
    }
    $zones = $pdo->query('SELECT code,id FROM rate_zones WHERE code IN ("LAGOS","OGUN")')->fetchAll(PDO::FETCH_KEY_PAIR);
    stage3Assert(isset($zones['LAGOS'], $zones['OGUN']), 'Pricing zones are missing.');
    $rateKey = [(int) $zones['LAGOS'], (int) $zones['OGUN'], 'standard'];
    $existing = $pdo->prepare('SELECT * FROM rate_cards WHERE origin_zone_id=? AND destination_zone_id=? AND service_code=?');
    $existing->execute($rateKey); $existingRate = $existing->fetch() ?: null;
    PricingService::saveRate([
        'origin_zone_id' => $rateKey[0], 'destination_zone_id' => $rateKey[1], 'service_code' => 'standard', 'currency' => 'NGN',
        'base_fee' => 2500, 'base_weight_kg' => 1, 'extra_kg_fee' => 500, 'minimum_fee' => 2500, 'fuel_percent' => 5,
        'insurance_percent' => 1, 'packaging_fee' => 750, 'tax_percent' => 0, 'volumetric_divisor' => 5000,
        'estimated_days_min' => 1, 'estimated_days_max' => 2, 'status' => 'active',
    ], (int) $ids['admin']);

    $riderId = RiderService::create([
        'full_name' => 'Stage 3 QA Rider', 'email' => 'stage3-rider-' . $stamp . '@example.test', 'phone' => '+2348000000004',
        'vehicle_type' => 'motorcycle', 'vehicle_registration' => 'QA-' . strtoupper($stamp), 'licence_number' => 'LIC-' . $stamp,
        'emergency_contact' => '', 'password' => 'Stage3-QA-Password-' . $stamp,
    ], (int) $ids['admin']);
    $riderRow = $pdo->prepare('SELECT staff_user_id FROM rider_profiles WHERE id=:id'); $riderRow->execute(['id' => $riderId]);
    $ids['rider_staff'] = (int) $riderRow->fetchColumn();
    $tracking = ShipmentService::create([
        'customer_name' => 'QA Receiver', 'customer_email' => '', 'customer_phone' => '+2348000000003', 'origin' => 'Lagos',
        'destination' => 'Abeokuta', 'service_type' => 'Standard Delivery', 'package_description' => 'Stage 3 rider test',
        'weight_kg' => 2.5, 'expected_delivery_at' => null,
    ], (int) $ids['admin']);
    $shipment = $pdo->prepare('SELECT id FROM shipments WHERE tracking_number=:tracking'); $shipment->execute(['tracking' => $tracking]);
    $shipmentId = (int) $shipment->fetchColumn(); $ids['shipments'][] = $shipmentId;
    RiderService::assign($shipmentId, $riderId, 'QA assignment', (int) $ids['admin']);
    stage3Assert(RiderService::canAccessShipment((int) $ids['rider_staff'], $shipmentId), 'Assigned rider authorization failed.');
    ShipmentService::addEvent($shipmentId, ['status' => 'picked_up', 'title' => '', 'description' => 'QA pickup', 'location' => 'Lagos', 'event_time' => date('Y-m-d H:i:s'), 'is_public' => true], (int) $ids['rider_staff']);
    RiderService::recordLocation((int) $ids['rider_staff'], $shipmentId, ['latitude' => 6.5244, 'longitude' => 3.3792, 'accuracy_m' => 12, 'speed_mps' => 3, 'heading_degrees' => 40, 'recorded_at' => date(DATE_ATOM), 'share_public' => true]);
    $public = RiderService::publicLocation($tracking);
    stage3Assert($public !== null && abs((float) $public['latitude'] - 6.5244) < 0.001, 'Public live location was not exposed for the opted-in active assignment.');
    RiderService::stopSharing((int) $ids['rider_staff']);
    stage3Assert(RiderService::publicLocation($tracking) === null, 'Stopping rider sharing did not remove the public location.');
    ShipmentService::addEvent($shipmentId, ['status' => 'in_transit', 'title' => '', 'description' => '', 'location' => 'Lagos', 'event_time' => date('Y-m-d H:i:s'), 'is_public' => true], (int) $ids['rider_staff']);
    ShipmentService::addEvent($shipmentId, ['status' => 'returned', 'title' => '', 'description' => '', 'location' => 'Lagos', 'event_time' => date('Y-m-d H:i:s'), 'is_public' => true], (int) $ids['rider_staff']);
    stage3Assert(RiderService::assignmentForShipment($shipmentId) === null, 'A final shipment did not release its rider assignment.');
    RiderService::setActive($riderId, false, (int) $ids['admin']);
    $riderStatus = $pdo->prepare('SELECT status FROM staff_users WHERE id = :id'); $riderStatus->execute(['id' => $ids['rider_staff']]);
    stage3Assert($riderStatus->fetchColumn() === 'inactive', 'Rider deactivation did not close the login account.');
    RiderService::setActive($riderId, true, (int) $ids['admin']);

    $ids['corporate'] = CorporateService::create([
        'company_name' => 'Stage 3 QA Limited', 'billing_email' => 'billing-' . $stamp . '@example.test', 'billing_phone' => '+2348000000005',
        'billing_address' => 'QA Address', 'tax_id' => '', 'credit_limit' => 100000, 'payment_terms_days' => 30,
        'currency' => 'NGN', 'account_manager_id' => $ids['admin'],
    ], (int) $ids['admin']);
    CorporateService::addMemberByEmail((int) $ids['corporate'], 'stage3-customer-' . $stamp . '@example.test', 'owner', (int) $ids['admin']);
    CorporateService::addMemberByEmail((int) $ids['corporate'], 'stage3-member-' . $stamp . '@example.test', 'member', (int) $ids['admin']);
    $bookingData = [
        'pickup_address_id' => $ids['addresses'][0], 'delivery_address_id' => $ids['addresses'][1],
        'origin_zone_id' => $rateKey[0], 'destination_zone_id' => $rateKey[1], 'service_code' => 'standard',
        'package_description' => 'Stage 3 corporate parcel', 'weight_kg' => 2, 'length_cm' => 20, 'width_cm' => 20,
        'height_cm' => 10, 'declared_value' => 10000, 'packaging_required' => false, 'is_fragile' => false,
    ];
    $bookingId = BookingService::create($bookingData, (int) $ids['customer']); $ids['bookings'][] = $bookingId;
    CorporateService::allocateBookingCredit((int) $ids['corporate'], $bookingId, (int) $ids['customer']);
    $approved = BookingService::find($bookingId);
    stage3Assert($approved !== null && $approved['payment_status'] === 'corporate_credit' && $approved['status'] === 'confirmed', 'Corporate credit did not approve the booking.');
    $convertedTracking = BookingService::convertToShipment($bookingId, (int) $ids['admin']);
    $converted = $pdo->prepare('SELECT id FROM shipments WHERE tracking_number=:tracking'); $converted->execute(['tracking' => $convertedTracking]);
    $ids['shipments'][] = (int) $converted->fetchColumn();
    CorporateService::recordPayment((int) $ids['corporate'], 1000, 'QA-PAY-' . $stamp, 'QA part payment', (int) $ids['admin']);
    $account = CorporateService::find((int) $ids['corporate']);
    stage3Assert($account !== null && (float) $account['outstanding'] > 0, 'Corporate statement balance was not calculated.');
    $portfolio = array_values(array_filter(CorporateService::all(), static fn (array $row): bool => (int) $row['id'] === (int) $ids['corporate']));
    stage3Assert(count($portfolio) === 1 && abs((float) $portfolio[0]['outstanding'] - (float) $account['outstanding']) < 0.01 && (int) $portfolio[0]['member_count'] === 2, 'Portfolio balance changed when the account had multiple members.');

    $handle = fopen($csvPath, 'wb');
    stage3Assert($handle !== false, 'Unable to create QA CSV.');
    fputcsv($handle, ['pickup_address_id','delivery_address_id','origin_zone_id','destination_zone_id','service_code','package_description','weight_kg','length_cm','width_cm','height_cm','declared_value','packaging_required','is_fragile']);
    fputcsv($handle, [$ids['addresses'][0],$ids['addresses'][1],$rateKey[0],$rateKey[1],'standard','Bulk valid row','1.5','20','10','10','5000','no','no']);
    fputcsv($handle, [$ids['addresses'][0],$ids['addresses'][1],$rateKey[0],$rateKey[1],'not_a_service','Bulk rejected row','1.5','','','','0','no','no']);
    fclose($handle);
    $ids['batch'] = BulkShipmentService::importFile((int) $ids['corporate'], (int) $ids['customer'], $csvPath, 'qa.csv');
    $batchRecord = BulkShipmentService::findForCustomer((int) $ids['batch'], (int) $ids['customer']);
    stage3Assert($batchRecord !== null && (int) $batchRecord['batch']['successful_count'] === 1 && (int) $batchRecord['batch']['failed_count'] === 1, 'Bulk batch did not preserve row-level success and rejection.');
    foreach ($batchRecord['items'] as $item) { if ($item['booking_id']) { $ids['bookings'][] = (int) $item['booking_id']; } }
    $conversion = BulkShipmentService::convertBatch((int) $ids['batch'], (int) $ids['admin']);
    stage3Assert($conversion['created'] === 1 && $conversion['failed'] === 0, 'Approved bulk rows did not convert to tracked shipments.');
    $staffBatch = BulkShipmentService::find((int) $ids['batch']);
    stage3Assert($staffBatch !== null && count(array_filter($staffBatch['items'], static fn (array $item): bool => $item['status'] === 'shipment_created' && $item['shipment_id'] !== null)) === 1, 'Bulk fulfilment status did not retain its tracking link.');
    foreach ($staffBatch['items'] as $item) { if ($item['shipment_id']) { $ids['shipments'][] = (int) $item['shipment_id']; } }

    $ids['cargo'] = CargoService::create([
        'shipment_id' => 0, 'corporate_account_id' => $ids['corporate'], 'transport_mode' => 'sea', 'cargo_type' => 'General cargo',
        'incoterm' => 'FOB', 'origin_terminal' => 'Apapa Port', 'destination_terminal' => 'Tema Port', 'carrier_name' => 'QA Carrier',
        'vessel_or_flight' => 'QA Vessel', 'airway_or_bill_number' => 'QA-BL-' . $stamp, 'container_number' => 'QA' . strtoupper($stamp),
        'pieces' => 10, 'gross_weight_kg' => 1000, 'volume_cbm' => 4.2, 'estimated_departure_at' => date('Y-m-d H:i:s', time() + 86400),
        'estimated_arrival_at' => date('Y-m-d H:i:s', time() + 604800),
    ], (int) $ids['admin']);
    CargoService::addMilestone((int) $ids['cargo'], ['status' => 'documentation', 'customs_status' => 'documents_review', 'title' => '', 'description' => 'QA documents', 'location' => 'Apapa Port', 'event_time' => date('Y-m-d H:i:s'), 'is_public' => true], (int) $ids['admin']);
    $cargo = CargoService::find((int) $ids['cargo']);
    stage3Assert($cargo !== null && $cargo['cargo']['status'] === 'documentation' && count($cargo['milestones']) === 2, 'Cargo milestone was not recorded.');

    $report = ReportService::dashboard(date('Y-m-d'), date('Y-m-d'), 'NGN');
    stage3Assert($report['currency'] === 'NGN' && $report['metrics']['bookings'] >= 2 && isset($report['generated_at']), 'Stage 3 report did not reconcile QA activity.');
    echo "Stage 3 smoke tests passed: rider assignment/privacy, corporate credit, bulk import, cargo milestones and reports.\n";
} finally {
    @unlink($csvPath);
    if ($ids['cargo']) { $pdo->prepare('DELETE FROM cargo_shipments WHERE id=:id')->execute(['id' => $ids['cargo']]); }
    if ($ids['corporate']) {
        $pdo->prepare('DELETE FROM corporate_ledger WHERE corporate_account_id=:id')->execute(['id' => $ids['corporate']]);
        $pdo->prepare('DELETE FROM corporate_booking_links WHERE corporate_account_id=:id')->execute(['id' => $ids['corporate']]);
        if ($ids['batch']) { $pdo->prepare('DELETE FROM bulk_shipment_batches WHERE id=:id')->execute(['id' => $ids['batch']]); }
    }
    foreach (array_unique(array_filter($ids['bookings'])) as $id) {
        $pdo->prepare('DELETE FROM notification_outbox WHERE booking_id=:id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM billing_documents WHERE booking_id=:id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM bookings WHERE id=:id')->execute(['id' => $id]);
    }
    foreach (array_unique(array_filter($ids['shipments'])) as $id) {
        $pdo->prepare('DELETE FROM notification_outbox WHERE shipment_id=:id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM shipments WHERE id=:id')->execute(['id' => $id]);
    }
    if ($ids['corporate']) { $pdo->prepare('DELETE FROM corporate_accounts WHERE id=:id')->execute(['id' => $ids['corporate']]); }
    foreach ($ids['addresses'] as $id) { $pdo->prepare('DELETE FROM customer_addresses WHERE id=:id')->execute(['id' => $id]); }
    if ($ids['customer']) { $pdo->prepare('DELETE FROM notification_outbox WHERE customer_id=:id')->execute(['id' => $ids['customer']]); $pdo->prepare('DELETE FROM customer_users WHERE id=:id')->execute(['id' => $ids['customer']]); }
    if ($ids['customer2']) { $pdo->prepare('DELETE FROM customer_users WHERE id=:id')->execute(['id' => $ids['customer2']]); }
    if ($ids['rider_staff']) { $pdo->prepare('DELETE FROM staff_users WHERE id=:id')->execute(['id' => $ids['rider_staff']]); }
    if ($existingRate !== null) {
        $pdo->prepare('UPDATE rate_cards SET service_name=:service_name,currency=:currency,base_fee=:base_fee,base_weight_kg=:base_weight_kg,extra_kg_fee=:extra_kg_fee,minimum_fee=:minimum_fee,fuel_percent=:fuel_percent,insurance_percent=:insurance_percent,packaging_fee=:packaging_fee,tax_percent=:tax_percent,volumetric_divisor=:volumetric_divisor,estimated_days_min=:estimated_days_min,estimated_days_max=:estimated_days_max,status=:status,created_by=:created_by,updated_at=:updated_at WHERE id=:id')->execute([
            'service_name'=>$existingRate['service_name'],'currency'=>$existingRate['currency'],'base_fee'=>$existingRate['base_fee'],'base_weight_kg'=>$existingRate['base_weight_kg'],'extra_kg_fee'=>$existingRate['extra_kg_fee'],'minimum_fee'=>$existingRate['minimum_fee'],'fuel_percent'=>$existingRate['fuel_percent'],'insurance_percent'=>$existingRate['insurance_percent'],'packaging_fee'=>$existingRate['packaging_fee'],'tax_percent'=>$existingRate['tax_percent'],'volumetric_divisor'=>$existingRate['volumetric_divisor'],'estimated_days_min'=>$existingRate['estimated_days_min'],'estimated_days_max'=>$existingRate['estimated_days_max'],'status'=>$existingRate['status'],'created_by'=>$existingRate['created_by'],'updated_at'=>$existingRate['updated_at'],'id'=>$existingRate['id'],
        ]);
    } elseif ($rateKey !== []) { $pdo->prepare('DELETE FROM rate_cards WHERE origin_zone_id=? AND destination_zone_id=? AND service_code=?')->execute($rateKey); }
    if ($ids['admin']) { $pdo->prepare('DELETE FROM audit_logs WHERE staff_user_id=:id')->execute(['id' => $ids['admin']]); $pdo->prepare('DELETE FROM staff_users WHERE id=:id')->execute(['id' => $ids['admin']]); }
}
