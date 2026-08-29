<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Database;
use App\InquiryService;
use App\ShipmentService;

$pdo = Database::connection();
$suffix = strtoupper(bin2hex(random_bytes(3)));
$email = 'qa-' . strtolower($suffix) . '@easyway.test';
$staffId = null;
$shipmentId = null;
$contactId = null;
$quoteId = null;

function qa_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $statement = $pdo->prepare(
        'INSERT INTO staff_users (full_name, email, password_hash, role, status, created_at, updated_at)
         VALUES (:full_name, :email, :password_hash, "admin", "active", NOW(), NOW())'
    );
    $statement->execute([
        'full_name' => 'Stage 1 QA ' . $suffix,
        'email' => $email,
        'password_hash' => password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT),
    ]);
    $staffId = (int) $pdo->lastInsertId();

    $contactReference = InquiryService::createContact([
        'full_name' => 'QA Customer',
        'company_name' => 'Easyway QA',
        'email' => 'customer-' . strtolower($suffix) . '@example.test',
        'phone' => '+234 800 000 0000',
        'subject' => 'Stage 1 workflow check',
        'message' => 'This temporary message validates the contact workflow.',
    ]);
    $contactIdStatement = $pdo->prepare('SELECT id FROM contact_messages WHERE reference = :reference');
    $contactIdStatement->execute(['reference' => $contactReference]);
    $contactId = (int) $contactIdStatement->fetchColumn();
    qa_assert(str_starts_with($contactReference, 'MSG-'), 'Contact reference was not generated.');

    $quoteReference = InquiryService::createQuote([
        'shipment_type' => 'Domestic',
        'from_location' => 'Iyana Ilogbo, Ogun State',
        'to_location' => 'Ikeja, Lagos State',
        'weight_range' => '1kg - 5kg',
        'quantity' => 1,
        'delivery_type' => 'Standard Delivery',
        'full_name' => 'QA Customer',
        'email' => 'customer-' . strtolower($suffix) . '@example.test',
        'phone' => '+234 800 000 0000',
        'notes' => 'Temporary QA quote.',
    ]);
    $quoteIdStatement = $pdo->prepare('SELECT id FROM quote_requests WHERE reference = :reference');
    $quoteIdStatement->execute(['reference' => $quoteReference]);
    $quoteId = (int) $quoteIdStatement->fetchColumn();
    qa_assert(str_starts_with($quoteReference, 'QT-'), 'Quote reference was not generated.');

    $trackingNumber = ShipmentService::create([
        'customer_name' => 'QA Customer',
        'customer_email' => 'customer-' . strtolower($suffix) . '@example.test',
        'customer_phone' => '+234 800 000 0000',
        'origin' => 'Iyana Ilogbo, Ogun State',
        'destination' => 'Ikeja, Lagos State',
        'service_type' => 'Standard Delivery',
        'package_description' => 'Temporary QA parcel',
        'weight_kg' => 2.5,
        'expected_delivery_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
    ], $staffId);
    qa_assert((bool) preg_match('/^EWL[0-9]{8}[A-Z0-9]{8}$/', $trackingNumber), 'Tracking number format is invalid.');

    $shipmentIdStatement = $pdo->prepare('SELECT id FROM shipments WHERE tracking_number = :tracking_number');
    $shipmentIdStatement->execute(['tracking_number' => $trackingNumber]);
    $shipmentId = (int) $shipmentIdStatement->fetchColumn();

    $public = ShipmentService::publicTracking($trackingNumber);
    qa_assert($public !== null, 'Public tracking could not find the shipment.');
    qa_assert($public['shipment']['status'] === 'booked', 'Initial shipment status is incorrect.');
    qa_assert(count($public['events']) === 1, 'Initial tracking event is missing.');

    ShipmentService::addEvent($shipmentId, [
        'status' => 'received',
        'title' => 'Parcel received for processing',
        'description' => 'Easyway has received the parcel.',
        'location' => 'Iyana Ilogbo, Ogun State',
        'event_time' => date('Y-m-d H:i:s'),
        'is_public' => true,
    ], $staffId);

    $public = ShipmentService::publicTracking($trackingNumber);
    qa_assert($public !== null && $public['shipment']['status'] === 'received', 'Shipment status did not update.');
    qa_assert(count($public['events']) === 2, 'Public event timeline did not update.');

    $blockedTransition = false;
    try {
        ShipmentService::addEvent($shipmentId, [
            'status' => 'delivered',
            'title' => 'Invalid direct delivery',
            'description' => '',
            'location' => '',
            'event_time' => date('Y-m-d H:i:s'),
            'is_public' => true,
        ], $staffId);
    } catch (RuntimeException) {
        $blockedTransition = true;
    }
    qa_assert($blockedTransition, 'Invalid shipment status transition was not blocked.');

    echo "PASS contact_reference={$contactReference}\n";
    echo "PASS quote_reference={$quoteReference}\n";
    echo "PASS tracking_number={$trackingNumber}\n";
    echo "PASS status_transition_and_public_timeline\n";
} finally {
    if ($shipmentId !== null) {
        $pdo->prepare('DELETE FROM notification_outbox WHERE shipment_id = :id')->execute(['id' => $shipmentId]);
        $pdo->prepare("DELETE FROM audit_logs WHERE entity_type = 'shipment' AND entity_id = :id")->execute(['id' => $shipmentId]);
        $pdo->prepare('DELETE FROM shipments WHERE id = :id')->execute(['id' => $shipmentId]);
    }
    if ($contactId !== null) {
        $pdo->prepare("DELETE FROM audit_logs WHERE entity_type = 'contact_message' AND entity_id = :id")->execute(['id' => $contactId]);
        $pdo->prepare('DELETE FROM contact_messages WHERE id = :id')->execute(['id' => $contactId]);
    }
    if ($quoteId !== null) {
        $pdo->prepare("DELETE FROM audit_logs WHERE entity_type = 'quote_request' AND entity_id = :id")->execute(['id' => $quoteId]);
        $pdo->prepare('DELETE FROM quote_requests WHERE id = :id')->execute(['id' => $quoteId]);
    }
    if ($staffId !== null) {
        $pdo->prepare('DELETE FROM login_attempts WHERE email = :email')->execute(['email' => $email]);
        $pdo->prepare('DELETE FROM staff_users WHERE id = :id')->execute(['id' => $staffId]);
    }
}
