<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\InquiryService;

Auth::requireStaff();
$quotes = InquiryService::recentQuotes();
$contacts = InquiryService::recentContacts();
$staffTitle = 'Quotes and messages';
require __DIR__ . '/_header.php';
?>
<section class="staff-card mb-4">
    <div class="mb-3">
        <h2 class="h4 mb-1">Quote requests</h2>
        <p class="text-muted mb-0">Stage 1 requests awaiting manual service and price confirmation.</p>
    </div>
    <div class="table-responsive">
        <table class="table staff-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th>Shipment</th>
                    <th>Status</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody><?php if ($quotes === []): ?><tr>
                        <td colspan="6" class="text-center text-muted py-4">No quote requests yet.</td>
                    </tr><?php endif; ?><?php foreach ($quotes as $quote): ?><tr>
                        <td><strong><?= e($quote['reference']) ?></strong></td>
                        <td><?= e($quote['full_name']) ?><br><a href="mailto:<?= e($quote['email']) ?>"><?= e($quote['email']) ?></a><br><?= e($quote['phone']) ?></td>
                        <td><?= e($quote['from_location']) ?> → <?= e($quote['to_location']) ?></td>
                        <td><?= e($quote['shipment_type']) ?><br><small><?= e($quote['delivery_type']) ?> · <?= e($quote['weight_range']) ?> · <?= e($quote['quantity']) ?> piece(s)</small><?php if ($quote['notes']): ?><br><small class="text-muted"><?= e($quote['notes']) ?></small><?php endif; ?></td>
                        <td><span class="staff-badge"><?= e(ucfirst((string) $quote['status'])) ?></span></td>
                        <td><?= e(date('j M Y, g:i A', strtotime((string) $quote['created_at']))) ?></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section>
<section class="staff-card">
    <div class="mb-3">
        <h2 class="h4 mb-1">Contact messages</h2>
        <p class="text-muted mb-0">Customer questions received through the website.</p>
    </div>
    <div class="table-responsive">
        <table class="table staff-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Contact</th>
                    <th>Subject and message</th>
                    <th>Status</th>
                    <th>Received</th>
                </tr>
            </thead>
            <tbody><?php if ($contacts === []): ?><tr>
                        <td colspan="5" class="text-center text-muted py-4">No contact messages yet.</td>
                    </tr><?php endif; ?><?php foreach ($contacts as $contact): ?><tr>
                        <td><strong><?= e($contact['reference']) ?></strong></td>
                        <td><?= e($contact['full_name']) ?><?php if ($contact['company_name']): ?><br><small><?= e($contact['company_name']) ?></small><?php endif; ?><br><a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a><?php if ($contact['phone']): ?><br><?= e($contact['phone']) ?><?php endif; ?></td>
                        <td><strong><?= e($contact['subject']) ?></strong><br><small class="text-muted"><?= nl2br(e($contact['message'])) ?></small></td>
                        <td><span class="staff-badge"><?= e(ucfirst((string) $contact['status'])) ?></span></td>
                        <td><?= e(date('j M Y, g:i A', strtotime((string) $contact['created_at']))) ?></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>