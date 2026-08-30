<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\AddressService;
use App\Database;
use App\PricingService as Pricing;
use App\RateCatalogService as Catalog;

function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function rejects(callable $call, string $message): void { try { $call(); } catch (RuntimeException $e) { if ($e instanceof PDOException) { throw $e; } return; } throw new RuntimeException($message); }
function rateForm(int $id, array $changes = []): array {
    $row = Pricing::findRate($id); check($row !== null, 'Rate missing.');
    return array_replace($row, ['version' => Catalog::version($row)], $changes);
}
function optionForm(string $kind, int $id, array $changes = []): array {
    $row = Catalog::find($kind, $id); check($row !== null, 'Option missing.');
    return array_replace($row, ['version' => Catalog::version($row)], $changes);
}
function snapshot(PDO $pdo, string $table): string { return hash('sha256', json_encode($pdo->query('SELECT * FROM ' . $table . ' ORDER BY id')->fetchAll(), JSON_THROW_ON_ERROR)); }

$pdo = Database::connection();
check(Catalog::installed(), 'Run php tools/install_rate_management.php first.');
$before = [];
foreach (['rate_zones','rate_services','rate_cards','bookings','customer_users','customer_addresses','staff_users','audit_logs','notification_outbox'] as $table) { $before[$table] = snapshot($pdo,$table); }
$sessionBefore = $_SESSION;
$suffix = bin2hex(random_bytes(5));
$pdo->beginTransaction();
try {
    $staff = [];
    foreach (['admin','dispatcher','rider'] as $role) {
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status) VALUES ("Rate QA",?,?,?,"active")')->execute(['rate-' . $role . '-' . $suffix . '@example.test',$hash,$role]);
        $staff[$role] = (int) $pdo->lastInsertId();
        if ($role === 'admin') { $_SESSION['easyway_staff'] = ['id' => $staff[$role], 'credential_stamp' => hash('sha256',$hash)]; }
    }
    $zone = ['code' => 'QA_ORIGIN_' . $suffix, 'name' => 'QA Origin ' . $suffix, 'country_code' => 'ng','status' => 'active'];
    foreach (['dispatcher','rider'] as $role) { rejects(fn () => Catalog::save('zone',$zone,$staff[$role]), 'Non-admin created a location.'); }
    $origin = Catalog::save('zone',$zone,$staff['admin']);
    $destination = Catalog::save('zone',array_replace($zone,['code' => 'QA_DEST_' . $suffix,'name' => 'QA Destination ' . $suffix,'country_code' => 'GB']),$staff['admin']);
    $other = Catalog::save('zone',array_replace($zone,['code' => 'QA_OTHER_' . $suffix,'name' => 'QA Other ' . $suffix]),$staff['admin']);
    rejects(fn () => Catalog::save('zone',$zone,$staff['admin']), 'Duplicate code accepted.');
    rejects(fn () => Catalog::save('zone',array_replace($zone,['code' => 'QA_DIFFERENT_' . $suffix]),$staff['admin']), 'Duplicate location name accepted.');
    foreach ([['code' => '../bad'],['country_code' => 'Nigeria'],['name' => str_repeat('x',121)],['status' => 'unknown'],['id' => '2wrong']] as $invalid) {
        rejects(fn () => Catalog::save('zone',array_replace($zone,$invalid),$staff['admin']), 'Invalid location accepted.');
    }
    $serviceCode = 'qa_air_' . $suffix;
    $serviceId = Catalog::save('service',['code' => strtoupper($serviceCode),'name' => 'QA Air Freight ' . $suffix,'status' => 'active'],$staff['admin']);
    check(isset(Pricing::services()[$serviceCode]), 'New service missing from customer choices.');
    check(in_array($origin, array_column(Pricing::zones(),'id'),true) && in_array($destination, array_column(Pricing::zones(),'id'),true), 'New route locations missing.');
    $data = ['id' => 0, 'origin_zone_id' => $origin, 'destination_zone_id' => $destination, 'service_code' => $serviceCode,
        'currency' => 'NGN','base_fee' => 1000,'base_weight_kg' => 1,'extra_kg_fee' => 200,'minimum_fee' => 0,'packaging_fee' => 100,
        'fuel_percent' => 10,'insurance_percent' => 1,'tax_percent' => 7.5,'volumetric_divisor' => 5000,'estimated_days_min' => 1,'estimated_days_max' => 3,'status' => 'active'];
    rejects(fn () => Pricing::saveRate($data,$staff['dispatcher'],true), 'Non-admin saved rate.');
    $rateId = Pricing::saveRate($data,$staff['admin'],true);
    rejects(fn () => Pricing::saveRate($data,$staff['admin'],true), 'Duplicate new rate overwrote existing.');
    $package = ['origin_zone_id' => $origin,'destination_zone_id' => $destination,'service_code' => $serviceCode,'weight_kg' => 3,'declared_value' => 10000,'packaging_required' => 1];
    check(Pricing::calculate($package)['total_amount'] === 1870.5, 'Initial custom-service estimate wrong.');

    // Persist a representative booking snapshot within the rollback-only transaction.
    $pdo->prepare('INSERT INTO customer_users (full_name,email,phone,password_hash,status) VALUES ("Rate QA customer",?,"+2348000000000",?,"active")')->execute(['rate-customer-' . $suffix . '@example.test',password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT)]);
    $customer = (int) $pdo->lastInsertId();
    $address = AddressService::create($customer,['label' => 'QA pickup','recipient_name' => 'QA only','phone' => '+2348000000000','address_line' => 'QA address','city' => 'Lagos','state_name' => 'Lagos','country_code' => 'NG','directions' => '','is_default' => true]);
    $pdo->prepare('INSERT INTO bookings (booking_number,customer_id,origin_zone_id,destination_zone_id,pickup_address_id,delivery_address_id,pickup_snapshot_json,delivery_snapshot_json,service_code,service_name,package_description,weight_kg,chargeable_weight_kg,base_amount,total_amount,quote_expires_at) VALUES (?,?,?,?,?,?,"{}","{}",?,"Original service label","QA only",3,3,1000,1870.50,DATE_ADD(NOW(),INTERVAL 2 DAY))')->execute(['QA-RATE-' . $suffix,$customer,$origin,$destination,$address,$address,$serviceCode]);
    $bookingsBefore = snapshot($pdo,'bookings');
    $stale = rateForm($rateId,['base_fee' => 4000]);
    Pricing::saveRate(rateForm($rateId,['base_fee' => 2000]),$staff['admin'],true);
    check(Pricing::calculate($package)['total_amount'] === 3053.0, 'Edited price not used by calculator.');
    rejects(fn () => Pricing::saveRate($stale,$staff['admin'],true), 'Stale rate overwrote new prices.');
    $secondId = Pricing::saveRate(array_replace($data,['destination_zone_id' => $other]),$staff['admin'],true);
    rejects(fn () => Pricing::saveRate(rateForm($rateId,['destination_zone_id' => $other]),$staff['admin'],true), 'Route collision overwrote a different rate.');
    Pricing::saveRate(rateForm($secondId,['origin_zone_id' => $other,'destination_zone_id' => $origin]),$staff['admin'],true);
    check((int) Pricing::findRate($secondId)['origin_zone_id'] === $other, 'Route editing created a new record instead of updating.');
    foreach ([['base_fee' => '-1'],['base_fee' => 'INF'],['base_weight_kg' => 0],['tax_percent' => 101],['estimated_days_min' => 'bad'],['estimated_days_max' => ''],['estimated_days_min' => 5,'estimated_days_max' => 2],['currency' => 'ABC'],['status' => 'bad'],['origin_zone_id' => 999999999],['service_code' => 'not-a-service']] as $invalid) {
        rejects(fn () => Pricing::saveRate(rateForm($rateId,$invalid),$staff['admin'],true), 'Invalid rate data accepted: ' . json_encode($invalid));
    }
    $serviceStale = optionForm('service',$serviceId,['name' => 'Stale label']);
    Catalog::save('service',optionForm('service',$serviceId,['name' => 'QA Air Express ' . $suffix]),$staff['admin']);
    rejects(fn () => Catalog::save('service',$serviceStale,$staff['admin']), 'Stale catalogue edit accepted.');
    rejects(fn () => Catalog::save('service',optionForm('service',$serviceId,['code' => 'new_code']),$staff['admin']), 'Stable service code changed.');
    check(Pricing::calculate($package)['service_name'] === 'QA Air Express ' . $suffix, 'New estimate used obsolete service label.');
    foreach (['zone' => $origin,'service' => $serviceId] as $kind => $optionId) {
        Catalog::save($kind,optionForm($kind,$optionId,['status' => 'inactive']),$staff['admin']);
        rejects(fn () => Pricing::calculate($package), 'Inactive catalogue entry still priced.');
        rejects(fn () => Pricing::saveRate(rateForm($rateId),$staff['admin'],true), 'Active rate with inactive entry accepted.');
        $listed = array_column(Pricing::allRates(),null,'id'); check(!$listed[$rateId]['available'], 'Paused route not identified in staff list.');
        Catalog::save($kind,optionForm($kind,$optionId,['status' => 'active']),$staff['admin']);
    }
    Pricing::saveRate(rateForm($rateId,['status' => 'inactive']),$staff['admin'],true);
    rejects(fn () => Pricing::calculate($package), 'Inactive rate still priced.');
    Pricing::saveRate(rateForm($rateId,['status' => 'active']),$staff['admin'],true);
    check(snapshot($pdo,'bookings') === $bookingsBefore, 'Rate or catalogue edits changed saved booking snapshots.');
    if (in_array('--previews',$argv,true)) {
        foreach (['list','edit','zone','service'] as $preview) {
            $page = in_array($preview,['list','edit'],true) ? 'rates' : 'rate-options';
            $_GET = match ($preview) { 'list' => ['q' => $suffix], 'edit' => ['id' => $rateId,'q' => $suffix], default => ['kind' => $preview] };
            $_SERVER['SCRIPT_NAME'] = '/staff/' . $page . '.php';
            $html = (static function (string $page): string { ob_start(); require EASYWAY_ROOT . '/staff/' . $page . '.php'; return (string) ob_get_clean(); })($page);
            $html = preg_replace('#<input[^>]+name="(?:_token|version)"[^>]*>#','',$html);
            $html = preg_replace_callback('#<form\b[^>]*>#',static fn (array $match): string => substr(preg_replace('~\saction="[^"]*"~',' action="#"',$match[0]),0,-1) . ' onsubmit="return false">',$html);
            $html = preg_replace('#href="[^"]*"#','href="#"',$html);
            $html = str_replace('<link rel="stylesheet" href="#">','',$html);
            $html = str_replace('</head>','<link rel="stylesheet" href="/assets/css/bootstrap.min.css"><link rel="stylesheet" href="/assets/css/bootstrap-icons.css"><link rel="stylesheet" href="/assets/css/staff.css"></head>',$html);
            $path = EASYWAY_ROOT . '/storage/cache/rates-qa-' . $suffix . '-' . $preview . '.html';
            check(file_put_contents($path,$html) !== false,'Preview failed.'); echo 'PREVIEW ' . $path . PHP_EOL;
        }
    }
    echo "PASS admin permissions, locations/services, duplicates, stable codes and stale-form protection\n";
    echo "PASS rate edits, route collisions, custom-service calculations, inactive entries and booking preservation\n";
} finally { if ($pdo->inTransaction()) { $pdo->rollBack(); } $_SESSION = $sessionBefore; }
foreach ($before as $table => $hash) { check(snapshot($pdo,$table) === $hash, 'QA changed existing data in ' . $table); }
echo "PASS all QA data rolled back; existing rates, bookings and outbox unchanged; no messages sent\n";
