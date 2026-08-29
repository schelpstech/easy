<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\RiderService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); throw new RuntimeException('Method not allowed.'); }
    $user = Auth::user();
    if ($user === null || ($user['role'] ?? '') !== 'rider') { http_response_code(401); throw new RuntimeException('Rider sign-in required.'); }
    if (!Csrf::validate($_POST['_token'] ?? null)) { http_response_code(419); throw new RuntimeException('Your session expired. Refresh this page.'); }
    if (($_POST['action'] ?? '') === 'stop') {
        RiderService::stopSharing((int) Auth::id());
        echo json_encode(['ok' => true, 'sharing' => false]); exit;
    }
    $shipmentId = filter_var($_POST['shipment_id'] ?? null, FILTER_VALIDATE_INT);
    if ($shipmentId === false) { throw new RuntimeException('Shipment not found.'); }
    RiderService::recordLocation((int) Auth::id(), (int) $shipmentId, [
        'latitude' => $_POST['latitude'] ?? null, 'longitude' => $_POST['longitude'] ?? null,
        'accuracy_m' => $_POST['accuracy_m'] ?? null, 'speed_mps' => $_POST['speed_mps'] ?? null,
        'heading_degrees' => $_POST['heading_degrees'] ?? null, 'recorded_at' => $_POST['recorded_at'] ?? null,
        'share_public' => ($_POST['share_public'] ?? '') === '1',
    ]);
    echo json_encode(['ok' => true, 'sharing' => true, 'received_at' => date(DATE_ATOM)]);
} catch (Throwable $exception) {
    if (http_response_code() < 400) { http_response_code($exception instanceof RuntimeException ? 422 : 500); }
    echo json_encode(['ok' => false, 'message' => $exception instanceof RuntimeException ? $exception->getMessage() : 'Unable to save location.']);
}
