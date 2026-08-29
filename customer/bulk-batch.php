<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\BulkShipmentService;
use App\CustomerAuth;

CustomerAuth::requireCustomer();
$batchId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$record = $batchId === false ? null : BulkShipmentService::findForCustomer((int) $batchId, (int) CustomerAuth::id());
if ($record === null) { http_response_code(404); exit('Bulk batch not found.'); }
$batch = $record['batch']; $pageTitle = 'Bulk Batch ' . $batch['batch_number'];
require dirname(__DIR__) . '/app/views/partials/public-header.php';
?>
<section class="account-heading compact"><div class="container"><div><span class="page-eyebrow">Bulk shipment batch</span><h1><?= e($batch['batch_number']) ?></h1><p><?= e($batch['source_filename']) ?> · <?= e(date('j M Y, g:i A', strtotime((string) $batch['created_at']))) ?></p></div><span class="account-status"><?= e(ucwords(str_replace('_', ' ', (string) $batch['status']))) ?></span></div></section>
<section class="account-section"><div class="container"><?php require __DIR__ . '/_nav.php'; ?><div class="row g-4 mb-4"><div class="col-md-4"><div class="account-stat"><span>Rows</span><strong><?= e($batch['row_count']) ?></strong></div></div><div class="col-md-4"><div class="account-stat"><span>Created</span><strong><?= e($batch['successful_count']) ?></strong></div></div><div class="col-md-4"><div class="account-stat"><span>Rejected</span><strong><?= e($batch['failed_count']) ?></strong></div></div></div><section class="easy-card"><div class="d-flex justify-content-between gap-3 mb-3"><div><h2 class="h4 mb-1">Row results</h2><p class="text-muted mb-0">Rejected rows have no corporate charge.</p></div><strong><?= e($batch['currency']) ?> <?= e(number_format((float) $batch['total_amount'], 2)) ?></strong></div><div class="table-responsive"><table class="table account-table"><thead><tr><th>CSV row</th><th>Result</th><th>Booking</th><th>Amount</th></tr></thead><tbody><?php foreach ($record['items'] as $item): ?><tr><td><?= e($item['source_line']) ?></td><td><span class="status-pill"><?= e(ucfirst((string) $item['status'])) ?></span><?= $item['error_message'] ? '<br><small class="text-danger">' . e($item['error_message']) . '</small>' : '' ?></td><td><?php if ($item['booking_id']): ?><a href="<?= e(url('customer/booking.php?id=' . $item['booking_id'])) ?>"><?= e($item['booking_number']) ?></a><?php else: ?>—<?php endif; ?></td><td><?= e(number_format((float) $item['amount'], 2)) ?></td></tr><?php endforeach; ?></tbody></table></div></section></div></section>
<?php require dirname(__DIR__) . '/app/views/partials/public-footer.php'; ?>
