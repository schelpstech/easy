<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\AddressService;
use App\BillingService;
use App\CustomerAuth;

CustomerAuth::requireCustomer();
$documentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$document = $documentId === false ? null : BillingService::findForCustomer((int) $documentId, (int) CustomerAuth::id());
if ($document === null) { http_response_code(404); exit('Document not found.'); }
$pickup = json_decode((string) $document['pickup_snapshot_json'], true) ?: [];
$delivery = json_decode((string) $document['delivery_snapshot_json'], true) ?: [];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($document['document_number']) ?> | Easyway Logistics</title><link rel="stylesheet" href="<?= e(url('assets/css/bootstrap.min.css')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/stage1.css')) ?>"></head><body class="billing-body"><main class="billing-sheet"><header><img src="<?= e(url('assets/img/easyway/logo.jpg')) ?>" alt="Easyway Logistics"><div><span><?= e(strtoupper((string) $document['document_type'])) ?></span><h1><?= e($document['document_number']) ?></h1></div></header><div class="billing-meta"><div><small>Billed to</small><strong><?= e($document['full_name']) ?></strong><span><?= e($document['email']) ?><br><?= e($document['phone']) ?></span></div><div><small>Booking</small><strong><?= e($document['booking_number']) ?></strong><span>Issued <?= e(date('j F Y', strtotime((string) $document['issued_at']))) ?><br>Status: <?= e(ucfirst((string) $document['status'])) ?></span></div></div><section class="billing-route"><div><small>Pickup</small><p><?= e(AddressService::formatted($pickup)) ?></p></div><i class="bi bi-arrow-right"></i><div><small>Delivery</small><p><?= e(AddressService::formatted($delivery)) ?></p></div></section><table><thead><tr><th>Description</th><th class="text-end">Amount (<?= e($document['currency']) ?>)</th></tr></thead><tbody><tr><td><?= e($document['service_name']) ?> — <?= e($document['package_description']) ?></td><td class="text-end"><?= e(number_format((float) $document['base_amount'] + (float) $document['weight_amount'], 2)) ?></td></tr><?php foreach (['fuel_amount' => 'Fuel surcharge','insurance_amount' => 'Insurance','packaging_amount' => 'Packaging'] as $field => $label): ?><?php if ((float) $document[$field] > 0): ?><tr><td><?= e($label) ?></td><td class="text-end"><?= e(number_format((float) $document[$field], 2)) ?></td></tr><?php endif; ?><?php endforeach; ?><tr><td>Tax</td><td class="text-end"><?= e(number_format((float) $document['tax_amount'], 2)) ?></td></tr></tbody><tfoot><tr><th>Total</th><th class="text-end"><?= e($document['currency']) ?> <?= e(number_format((float) $document['total_amount'], 2)) ?></th></tr></tfoot></table><footer><p>Easyway Logistics · <?= e(support_email()) ?> · <?= e(support_phone()) ?></p><button class="easy-btn" type="button" onclick="window.print()">Print / Save PDF</button></footer></main></body></html>
