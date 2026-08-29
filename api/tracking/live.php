<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\RiderService;
use App\ShipmentService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
$tracking = strtoupper(trim((string) ($_GET['tracking_id'] ?? '')));
if (!preg_match('/^EWL[0-9]{8}[A-Z0-9]{8}$/', $tracking) || ShipmentService::publicTracking($tracking) === null) {
    http_response_code(404); echo json_encode(['ok' => false, 'message' => 'Shipment not found.']); exit;
}
$location = RiderService::publicLocation($tracking);
echo json_encode(['ok' => true, 'live' => $location !== null, 'location' => $location]);
