<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Database;

$customerEmail = mb_strtolower((string) ($argv[1] ?? ''));
$staffEmail = mb_strtolower((string) ($argv[2] ?? ''));
if (!str_starts_with($customerEmail, 'stage2-http-qa-') || !str_ends_with($customerEmail, '@example.test')
    || !str_starts_with($staffEmail, 'stage2-http-qa-staff-') || !str_ends_with($staffEmail, '@example.test')) {
    fwrite(STDERR, "Only Stage 2 HTTP QA accounts can be cleaned.\n");
    exit(1);
}

$pdo = Database::connection();
$customerStatement = $pdo->prepare('SELECT id FROM customer_users WHERE email = :email');
$customerStatement->execute(['email' => $customerEmail]);
$customerId = $customerStatement->fetchColumn();
$staffStatement = $pdo->prepare('SELECT id FROM staff_users WHERE email = :email');
$staffStatement->execute(['email' => $staffEmail]);
$staffId = $staffStatement->fetchColumn();

if ($customerId !== false) {
    $bookings = $pdo->prepare('SELECT id, shipment_id FROM bookings WHERE customer_id = :customer');
    $bookings->execute(['customer' => $customerId]);
    $rows = $bookings->fetchAll();
    foreach ($rows as $row) {
        $pdo->prepare('DELETE FROM notification_outbox WHERE booking_id = :booking OR shipment_id = :shipment')
            ->execute(['booking' => $row['id'], 'shipment' => $row['shipment_id']]);
        $pdo->prepare('DELETE FROM payment_webhook_events WHERE reference IN (SELECT reference FROM payments WHERE booking_id = :booking)')
            ->execute(['booking' => $row['id']]);
        $pdo->prepare('DELETE FROM billing_documents WHERE booking_id = :booking')->execute(['booking' => $row['id']]);
        $pdo->prepare('DELETE FROM payments WHERE booking_id = :booking')->execute(['booking' => $row['id']]);
        $pdo->prepare('DELETE FROM booking_status_history WHERE booking_id = :booking')->execute(['booking' => $row['id']]);
        if ($row['shipment_id'] !== null) {
            $pdo->prepare('DELETE FROM proofs_of_delivery WHERE shipment_id = :shipment')->execute(['shipment' => $row['shipment_id']]);
        }
        $pdo->prepare('DELETE FROM bookings WHERE id = :booking')->execute(['booking' => $row['id']]);
        if ($row['shipment_id'] !== null) {
            $pdo->prepare('DELETE FROM shipment_events WHERE shipment_id = :shipment')->execute(['shipment' => $row['shipment_id']]);
            $pdo->prepare('DELETE FROM shipments WHERE id = :shipment')->execute(['shipment' => $row['shipment_id']]);
        }
        $pdo->prepare('DELETE FROM audit_logs WHERE entity_type = "booking" AND entity_id = :id')->execute(['id' => $row['id']]);
    }
    $addressStatement = $pdo->prepare('SELECT id FROM customer_addresses WHERE customer_id = :customer');
    $addressStatement->execute(['customer' => $customerId]);
    foreach ($addressStatement->fetchAll(PDO::FETCH_COLUMN) as $addressId) {
        $pdo->prepare('DELETE FROM audit_logs WHERE entity_type = "customer_address" AND entity_id = :id')->execute(['id' => $addressId]);
    }
    $pdo->prepare('DELETE FROM notification_outbox WHERE customer_id = :customer')->execute(['customer' => $customerId]);
    $pdo->prepare('DELETE FROM customer_addresses WHERE customer_id = :customer')->execute(['customer' => $customerId]);
    $pdo->prepare('DELETE FROM customer_login_attempts WHERE email = :email')->execute(['email' => $customerEmail]);
    $pdo->prepare('DELETE FROM audit_logs WHERE entity_type = "customer_user" AND entity_id = :customer')->execute(['customer' => $customerId]);
    $pdo->prepare('DELETE FROM customer_users WHERE id = :customer')->execute(['customer' => $customerId]);
}
if ($staffId !== false) {
    $pdo->prepare('DELETE FROM rate_cards WHERE created_by = :staff')->execute(['staff' => $staffId]);
    $pdo->prepare('DELETE FROM login_attempts WHERE email = :email')->execute(['email' => $staffEmail]);
    $pdo->prepare('DELETE FROM audit_logs WHERE staff_user_id = :staff')->execute(['staff' => $staffId]);
    $pdo->prepare('DELETE FROM staff_users WHERE id = :staff')->execute(['staff' => $staffId]);
}
echo "Stage 2 HTTP QA fixtures removed.\n";
