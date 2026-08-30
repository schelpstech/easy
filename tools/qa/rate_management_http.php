<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Database;
use App\PricingService;
use App\RateCatalogService;

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8099','/');
if (!preg_match('#^http://(?:127\.0\.0\.1|localhost)(?::[0-9]+)?(?:/[a-zA-Z0-9_-]+)*$#D',$base)) { throw new RuntimeException('Use a loopback development URL only. Never run against production.'); }
function check(bool $ok,string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function client(): CurlHandle { $h = curl_init(); curl_setopt_array($h,[CURLOPT_COOKIEFILE => '',CURLOPT_RETURNTRANSFER => true,CURLOPT_FOLLOWLOCATION => false,CURLOPT_PROXY => '',CURLOPT_TIMEOUT => 15]); return $h; }
function request(CurlHandle $h,string $path,?array $fields = null): array {
    global $base; curl_setopt($h,CURLOPT_URL,$base . '/' . ltrim($path,'/'));
    if ($fields === null) { curl_setopt($h,CURLOPT_HTTPGET,true); } else { curl_setopt_array($h,[CURLOPT_POST => true,CURLOPT_POSTFIELDS => http_build_query($fields)]); }
    $body = curl_exec($h); check(is_string($body),'Local HTTP request failed.');
    return ['status' => curl_getinfo($h,CURLINFO_RESPONSE_CODE),'body' => $body,'redirect' => curl_getinfo($h,CURLINFO_REDIRECT_URL)];
}
function fields(array $page,string $action): array {
    $dom = new DOMDocument(); $previous = libxml_use_internal_errors(true); $dom->loadHTML($page['body']); libxml_clear_errors(); libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($dom); $forms = $xpath->query('//form[contains(@action,"action=' . $action . '")]');
    check($forms->length === 1,'Form missing: ' . $action); $values = [];
    foreach ($xpath->query('.//input[@name]',$forms->item(0)) as $input) { $values[$input->getAttribute('name')] = $input->getAttribute('value'); }
    foreach ($xpath->query('.//select[@name]',$forms->item(0)) as $select) {
        $options = $xpath->query('./option[@selected]',$select); if ($options->length === 0) { $options = $xpath->query('./option',$select); }
        $option = $options->item(0); $values[$select->getAttribute('name')] = $option->hasAttribute('value') ? $option->getAttribute('value') : $option->textContent;
    }
    return $values;
}
function login(CurlHandle $h,string $email,string $password): void {
    $response = request($h,'controller/router.php?action=staff.login',array_replace(fields(request($h,'staff/login.php'),'staff.login'), ['email' => $email,'password' => $password]));
    check($response['status'] === 303 && !str_ends_with($response['redirect'],'/login.php'),'QA login failed.');
}
function savedId(array $response): int {
    check($response['status'] === 303,'Save did not redirect.'); parse_str((string) parse_url($response['redirect'],PHP_URL_QUERY),$params);
    $id = (int) ($params['id'] ?? 0); check($id > 0,'Save did not return an ID: ' . $response['redirect']); return $id;
}
function snapshot(PDO $pdo,string $table): string { return hash('sha256',json_encode($pdo->query('SELECT * FROM ' . $table . ' ORDER BY id')->fetchAll(),JSON_THROW_ON_ERROR)); }

$pdo = Database::connection(); check(RateCatalogService::installed(),'Run the rate management installer first.');
$before = []; foreach (['rate_cards','rate_zones','rate_services','staff_users','bookings','notification_outbox'] as $table) { $before[$table] = snapshot($pdo,$table); }
$suffix = bin2hex(random_bytes(5)); $password = 'Rate-QA-' . bin2hex(random_bytes(12));
$staff = []; $emails = []; $clients = []; $zones = []; $services = [];
try {
    foreach (['admin','dispatcher','rider'] as $role) {
        $email = $emails[$role] = 'rate-http-' . $role . '-' . $suffix . '@example.test';
        $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status) VALUES ("Rate HTTP QA",?,?,?,"active")')->execute([$email,password_hash($password,PASSWORD_DEFAULT),$role]);
        $staff[$role] = (int) $pdo->lastInsertId();
    }
    $anonymous = $clients[] = client(); check(request($anonymous,'staff/rates.php')['status'] === 303,'Anonymous accessed rates.');
    foreach (['staff.rate.save','staff.rate.zone.save','staff.rate.service.save'] as $action) {
        check(request($anonymous,'controller/router.php?action=' . $action)['status'] === 405,'GET mutation allowed.');
        check(request($anonymous,'controller/router.php?action=' . $action,[])['status'] === 419,'Missing CSRF accepted.');
    }
    foreach (['dispatcher','rider'] as $role) {
        $h = $clients[] = client(); login($h,$emails[$role],$password);
        foreach (['staff/rates.php','staff/rate-options.php?kind=zone','staff/rate-options.php?kind=service'] as $path) { check(request($h,$path)['status'] === 403,'Non-admin read pricing admin.'); }
        $token = fields(request($h,'staff/password.php'),'staff.password.change')['_token'];
        foreach (['staff.rate.save','staff.rate.zone.save','staff.rate.service.save'] as $action) { check(request($h,'controller/router.php?action=' . $action,['_token' => $token])['status'] === 403,'Non-admin pricing mutation allowed.'); }
    }
    $admin = $clients[] = client(); login($admin,$emails['admin'],$password);
    $zoneAction = 'staff.rate.zone.save';
    foreach (['Origin','Destination'] as $label) {
        $values = array_replace(fields(request($admin,'staff/rate-options.php?kind=zone'),$zoneAction),['code' => 'HTTP_' . strtoupper($label) . '_' . $suffix,'name' => 'HTTP ' . $label . ' ' . $suffix,'country_code' => 'NG','status' => 'active']);
        $zones[] = savedId(request($admin,'controller/router.php?action=' . $zoneAction,$values));
    }
    $zoneList = request($admin,'staff/rate-options.php?kind=zone');
    check(str_contains($zoneList['body'],'Add location') && str_contains($zoneList['body'],'Location name'), 'Location form labels were overwritten.');
    check(str_contains($zoneList['body'],'rate-options.php?kind=zone&amp;id=' . $zones[0]), 'Location edit link targets the wrong catalogue.');
    $serviceAction = 'staff.rate.service.save'; $code = 'http_air_' . $suffix;
    $values = array_replace(fields(request($admin,'staff/rate-options.php?kind=service'),$serviceAction),['code' => $code,'name' => 'HTTP Air ' . $suffix,'status' => 'active']);
    $services[] = $service = savedId(request($admin,'controller/router.php?action=' . $serviceAction,$values));
    check(str_contains(request($admin,'staff/rate-options.php?kind=service')['body'],'Add service'), 'Service form label incorrect.');
    $calc = request($anonymous,'calculator.php'); check($calc['status'] === 200 && str_contains($calc['body'],$code) && str_contains($calc['body'],'HTTP Origin ' . $suffix),'Customer calculator missing new choices.');
    $action = 'staff.rate.save';
    $values = array_replace(fields(request($admin,'staff/rates.php?new=1'),$action),['origin_zone_id' => $zones[0],'destination_zone_id' => $zones[1],'service_code' => $code,'base_fee' => '2500.25','extra_kg_fee' => '300','estimated_days_min' => '1','estimated_days_max' => '4']);
    $id = savedId(request($admin,'controller/router.php?action=' . $action,$values));
    $editPath = 'staff/rates.php?id=' . $id;
    $page = request($admin,$editPath); $loaded = fields($page,$action);
    check($loaded['base_fee'] === '2500.25' && $loaded['service_code'] === $code && (int) $loaded['destination_zone_id'] === $zones[1],'Rate form not prefilled.');
    check(str_contains($page['body'],'Rate saved.'),'Save feedback missing.');
    request($admin,'controller/router.php?action=' . $action,array_replace($loaded,['base_fee' => '-1']));
    $errorPage = request($admin,$editPath); check(str_contains($errorPage['body'],'Enter a valid base fee') && fields($errorPage,$action)['base_fee'] === '-1','Validation did not retain the form.');
    $loaded = fields(request($admin,$editPath),$action); $stale = $loaded;
    check(savedId(request($admin,'controller/router.php?action=' . $action,array_replace($loaded,['base_fee' => '3250.50']))) === $id,'Edit changed rate identity.');
    check(PricingService::calculate(['origin_zone_id' => $zones[0],'destination_zone_id' => $zones[1],'service_code' => $code,'weight_kg' => 2])['total_amount'] === 3550.5,'HTTP edit did not change calculator.');
    request($admin,'controller/router.php?action=' . $action,array_replace($stale,['base_fee' => '9999']));
    check(str_contains(request($admin,$editPath)['body'],'changed in another session'),'Stale edit was accepted.');
    request($admin,'controller/router.php?action=' . $action,$values);
    check(str_contains(request($admin,'staff/rates.php?new=1')['body'],'A rate already exists'),'Duplicate create silently overwrote rate.');
    check((float) PricingService::findRate($id)['base_fee'] === 3250.5,'Rejected edit changed price.');
    $servicePath = 'staff/rate-options.php?kind=service&id=' . $service;
    $serviceFields = fields(request($admin,$servicePath),$serviceAction);
    $xssName = 'HTTP Air <script>alert(1)</script> ' . $suffix;
    request($admin,'controller/router.php?action=' . $serviceAction,array_replace($serviceFields,['name' => $xssName]));
    $page = request($admin,$servicePath);
    check(str_contains($page['body'],'&lt;script&gt;') && !str_contains($page['body'],'<script>alert(1)</script>'),'Saved label not escaped.');
    request($admin,'controller/router.php?action=' . $serviceAction,array_replace(fields($page,$serviceAction),['status' => 'inactive']));
    check(!str_contains(request($anonymous,'calculator.php')['body'],'value="' . $code . '"'),'Inactive service offered to customers.');
    $zonePath = 'staff/rate-options.php?kind=zone&id=' . $zones[0];
    request($admin,'controller/router.php?action=' . $zoneAction,array_replace(fields(request($admin,$zonePath),$zoneAction),['status' => 'inactive']));
    check(!in_array($zones[0],array_column(PricingService::zones(),'id'),true),'Inactive location offered to customers.');
    foreach (['staff/rates.php?id=-1','staff/rates.php?id=999999999','staff/rate-options.php?kind=wrong','staff/rate-options.php?kind=zone&id=999999999'] as $path) { check(request($admin,$path)['status'] === 404,'Invalid ID/type accepted.'); }
    check(str_contains(request($admin,'staff/rates.php?q=' . $suffix)['body'],'Unavailable'),'Staff list did not explain inactive rate dependencies.');
    echo "PASS HTTP admin permissions, CSRF, catalogue creation, prefilled edits and validation drafts\n";
    echo "PASS duplicate/stale rejection, updated calculator totals, safe labels and inactive options\n";
} finally {
    foreach ($clients as $h) { curl_close($h); }
    if (isset($staff['admin'])) { $pdo->prepare('DELETE FROM rate_cards WHERE created_by = ?')->execute([$staff['admin']]); }
    // All targets are this run's random codes or fixture IDs, never user records.
    foreach ($zones as $zone) { $pdo->prepare('DELETE FROM rate_zones WHERE id = ?')->execute([$zone]); }
    foreach ($services as $service) { $pdo->prepare('DELETE FROM rate_services WHERE id = ?')->execute([$service]); }
    foreach ($staff as $id) { $pdo->prepare('DELETE FROM audit_logs WHERE staff_user_id = ?')->execute([$id]); $pdo->prepare('DELETE FROM staff_users WHERE id = ?')->execute([$id]); }
    foreach ($emails as $email) { $pdo->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]); }
}
foreach ($before as $table => $hash) { check(snapshot($pdo,$table) === $hash,'HTTP QA changed pre-existing ' . $table); }
echo "PASS HTTP fixtures removed; existing rates, bookings and outbox unchanged; no messages sent\n";
