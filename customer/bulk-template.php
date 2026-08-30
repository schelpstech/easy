<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\CustomerAuth;
use App\AddressService;
use App\PricingService;

CustomerAuth::requireCustomer();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="easyway-bulk-shipment-template.csv"');
header('X-Content-Type-Options: nosniff');
$output = fopen('php://output', 'wb');
if ($output === false) { http_response_code(500); exit; }
fputcsv($output, ['pickup_address_id','delivery_address_id','origin_zone_id','destination_zone_id','service_code','package_description','weight_kg','length_cm','width_cm','height_cm','declared_value','packaging_required','is_fragile']);
$addresses = AddressService::allForCustomer((int) CustomerAuth::id());
$zones = PricingService::zones();
$services = PricingService::services();
if (count($addresses) >= 2 && count($zones) >= 2 && $services !== []) {
    $serviceCode = isset($services['standard']) ? 'standard' : array_key_first($services);
    fputcsv($output, [$addresses[0]['id'],$addresses[1]['id'],$zones[0]['id'],$zones[1]['id'],$serviceCode,'Sample parcel','2.5','30','20','15','25000','no','no']);
}
fclose($output);
