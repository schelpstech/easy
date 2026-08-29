<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Auth;
use App\CustomerAuth;
use App\ProofOfDeliveryService;

$proofId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$proof = $proofId === false ? null : ProofOfDeliveryService::find((int) $proofId);
$staff = Auth::user();
$authorized = $proof !== null && (($staff !== null && ProofOfDeliveryService::staffCanAccess((int) $proof['id'], (int) $staff['id'], (string) $staff['role']))
    || (CustomerAuth::check() && ProofOfDeliveryService::customerCanAccess((int) $proof['id'], (int) CustomerAuth::id())));
if (!$authorized || empty($proof['photo_path'])) {
    http_response_code(404);
    exit;
}
$path = EASYWAY_ROOT . '/storage/' . $proof['photo_path'];
if (!is_file($path)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . $proof['photo_mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="delivery-proof-' . (int) $proof['id'] . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
