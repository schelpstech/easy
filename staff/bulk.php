<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\BulkShipmentService;

Auth::requireRole(['admin', 'dispatcher']);
$batches = BulkShipmentService::allBatches();
$batchId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$record = $batchId === false ? null : BulkShipmentService::find((int) $batchId);
$staffTitle = 'Bulk shipment batches';
require __DIR__ . '/_header.php';
?>
<section class="staff-card mb-4"><div class="d-flex flex-wrap justify-content-between gap-3 mb-3"><div><h2 class="h4 mb-1">Corporate batch queue</h2><p class="text-muted mb-0">Approved CSV rows become ordinary tracked shipments without bypassing booking or credit controls.</p></div><span class="staff-badge"><?= e(count($batches)) ?> batches</span></div><div class="table-responsive"><table class="table staff-table"><thead><tr><th>Batch</th><th>Company</th><th>Rows</th><th>Awaiting shipment</th><th>Shipment records</th><th>Total</th></tr></thead><tbody>
<?php if ($batches === []): ?><tr><td colspan="6" class="text-center text-muted py-4">No corporate bulk batches yet.</td></tr><?php endif; ?>
<?php foreach ($batches as $batch): ?><tr><td><a href="<?= e(url('staff/bulk.php?id=' . $batch['id'])) ?>"><strong><?= e($batch['batch_number']) ?></strong></a><br><small><?= e(date('j M Y, g:i A', strtotime((string) $batch['created_at']))) ?></small></td><td><?= e($batch['company_name']) ?><br><small><?= e($batch['uploaded_by_name']) ?></small></td><td><?= e($batch['successful_count']) ?> created / <?= e($batch['failed_count']) ?> rejected</td><td><?= e($batch['awaiting_fulfilment']) ?></td><td><?= e($batch['shipment_count']) ?></td><td><?= e($batch['currency']) ?> <?= e(number_format((float) $batch['total_amount'], 2)) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php if ($record): $batch = $record['batch']; $pending = count(array_filter($record['items'], static fn (array $item): bool => $item['booking_id'] && !$item['shipment_id'] && $item['payment_status'] === 'corporate_credit' && $item['booking_status'] === 'confirmed')); ?><section class="staff-card"><div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><span class="text-muted"><?= e($batch['company_name']) ?></span><h2 class="h3 mb-1"><?= e($batch['batch_number']) ?></h2><p class="mb-0"><?= e($batch['source_filename']) ?> · uploaded by <?= e($batch['uploaded_by_name']) ?></p></div><?php if ($pending > 0): ?><form method="post" action="<?= e(url('controller/router.php?action=staff.bulk.convert')) ?>"><?= csrf_field() ?><input type="hidden" name="batch_id" value="<?= e($batch['id']) ?>"><button class="staff-btn" type="submit"><i class="bi bi-box-seam"></i> Create <?= e($pending) ?> tracked shipment<?= $pending === 1 ? '' : 's' ?></button></form><?php else: ?><span class="staff-badge">No rows awaiting fulfilment</span><?php endif; ?></div><div class="table-responsive"><table class="table staff-table"><thead><tr><th>CSV row</th><th>Booking</th><th>Result</th><th>Tracking</th><th>Amount</th></tr></thead><tbody><?php foreach ($record['items'] as $item): ?><tr><td><?= e($item['source_line']) ?></td><td><?= e($item['booking_number'] ?: '—') ?></td><td><span class="staff-badge"><?= e(ucwords(str_replace('_', ' ', (string) $item['status']))) ?></span><?= $item['error_message'] ? '<br><small class="text-danger">' . e($item['error_message']) . '</small>' : '' ?></td><td><?php if ($item['shipment_id']): ?><a href="<?= e(url('staff/shipment.php?id=' . $item['shipment_id'])) ?>"><?= e($item['tracking_number']) ?></a><?php else: ?>—<?php endif; ?></td><td><?= e($batch['currency']) ?> <?= e(number_format((float) $item['amount'], 2)) ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
