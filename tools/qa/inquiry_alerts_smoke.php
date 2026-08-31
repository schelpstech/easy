<?php
declare(strict_types=1);

namespace App {
    function gethostbynamel(string $host): array { return ['93.184.216.34']; }
    function curl_setopt_array(\CurlHandle $handle, array $options): bool { $GLOBALS['alert_options'] = array_replace($GLOBALS['alert_options'] ?? [], $options); return true; }
    function curl_exec(\CurlHandle $handle): bool {
        $GLOBALS['alert_calls'] = ($GLOBALS['alert_calls'] ?? 0) + 1;
        $GLOBALS['alert_wire'] = stream_get_contents($GLOBALS['alert_options'][CURLOPT_INFILE]);
        return !($GLOBALS['alert_fail'] ?? false);
    }
    function curl_errno(\CurlHandle $handle): int { return 67; }
}

namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
    use App\Database;
    use App\EmailTemplate;
    use App\InquiryService;
    use App\NotificationService;

    function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
    function rejected(callable $call): void {
        try { $call(); } catch (RuntimeException $exception) {
            check($exception->getMessage() === 'We could not save your request. Please try again or contact our team directly.', 'Public error exposed internal configuration.');
            return;
        }
        throw new RuntimeException('Invalid alert configuration was accepted.');
    }
    $pdo = Database::connection();
    $tables = ['quote_requests', 'contact_messages', 'notification_outbox', 'audit_logs'];
    $counts = [];
    foreach ($tables as $table) { $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn(); }
    $beforeOutbox = hash('sha256', json_encode($pdo->query('SELECT * FROM notification_outbox ORDER BY id')->fetchAll()));
    $beforeSettings = $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll();
    $overrides = ['INQUIRY_ALERT_EMAIL' => 'info@easyway.ng', 'APP_URL' => 'https://logistics.example.com/easy',
        'EMAIL_NOTIFICATIONS_ENABLED' => 'false', 'SMS_NOTIFICATIONS_ENABLED' => 'false', 'WHATSAPP_NOTIFICATIONS_ENABLED' => 'false',
        'EMAIL_TRANSPORT' => 'smtp', 'EMAIL_FROM' => 'team@example.com', 'EMAIL_FROM_NAME' => 'Easyway Logistics',
        'SMTP_HOST' => 'smtp.example.com', 'SMTP_PORT' => '587', 'SMTP_ENCRYPTION' => 'starttls', 'SMTP_USERNAME' => 'team@example.com', 'SMTP_PASSWORD' => 'Synthetic-Test-Only'];
    $environment = [];
    foreach ($overrides as $key => $value) { $environment[$key] = getenv($key); putenv($key . '=' . $value); }
    $contact = ['full_name' => 'Ada <b>QA</b>', 'company_name' => 'Example & Partners', 'email' => 'customer@example.test', 'phone' => '09031134210',
        'subject' => "Collection request\r\nBcc: wrong@example.test", 'message' => "Can you collect two cartons tomorrow?\n<script>alert(1)</script>",
        'recipient' => 'wrong@example.test', 'alert_email' => 'wrong@example.test'];
    $quote = ['full_name' => 'Bola QA', 'email' => 'quote@example.test', 'phone' => '08087137894', 'shipment_type' => 'International',
        'from_location' => 'Lagos', 'to_location' => 'London', 'weight_range' => '6kg - 15kg', 'quantity' => 2, 'delivery_type' => 'Express Delivery',
        'notes' => "Fragile clothing samples.\nPlease confirm customs requirements. 🚚"];
    try {
        $pdo->beginTransaction();
        // Uncommitted settings overrides and alerts are invisible to any real cron worker.
        $pdo->exec('DELETE FROM notification_settings');
        $refs = ['contact' => InquiryService::createContact($contact), 'quote' => InquiryService::createQuote($quote)];
        check($pdo->inTransaction(), 'Inquiry creation committed its caller transaction.');
        check(($GLOBALS['alert_calls'] ?? 0) === 0, 'Form submission contacted the mail provider.');
        $alerts = [];
        foreach ($refs as $type => $reference) {
            $title = $type === 'quote' ? 'New quote request' : 'New contact inquiry';
            $query = $pdo->prepare('SELECT * FROM notification_outbox WHERE template_code=:template AND subject=:subject');
            $query->execute(['template' => 'staff_' . $type . '_received', 'subject' => '[' . $reference . '] ' . $title]);
            $rows = $query->fetchAll();
            check(count($rows) === 1, 'Expected exactly one staff alert per request.');
            $alert = $alerts[$type] = $rows[0];
            check($alert['recipient'] === 'info@easyway.ng' && $alert['channel'] === 'email', 'Recipient was changed by customer input or extra channel used.');
            check($alert['status'] === 'pending' && (int) $alert['attempts'] === 0, 'New alert was not queued.');
            check($alert['customer_id'] === null && $alert['booking_id'] === null && $alert['shipment_id'] === null, 'Staff alert linked to a customer account.');
            $query = $pdo->prepare('SELECT * FROM ' . ($type === 'quote' ? 'quote_requests' : 'contact_messages') . ' WHERE reference=:ref');
            $query->execute(['ref' => $reference]); $inquiry = $query->fetch();
            check($inquiry['status'] === 'new', 'An internal alert changed the inquiry status.');
            $query = $pdo->prepare('SELECT context_json FROM audit_logs WHERE action=:action AND entity_id=:id');
            $query->execute(['action' => $type . '.created', 'id' => $inquiry['id']]);
            check((int) json_decode($query->fetchColumn(), true)['notification_id'] === (int) $alert['id'], 'Audit used the wrong inquiry or alert ID.');
            foreach (($type === 'quote' ? $quote : array_intersect_key($contact, array_flip(['full_name', 'company_name', 'email', 'phone', 'subject', 'message']))) as $value) {
                check(str_contains($alert['message'], (string) $value), 'Customer detail omitted from staff alert.');
            }
            $render = EmailTemplate::render($alert);
            check(str_contains($render['html'], 'cid:' . EmailTemplate::LOGO_CID) && str_contains($render['html'], 'Internal notification for the Easyway team'), 'Staff alert branding missing.');
            check(str_contains($render['html'], 'https://logistics.example.com/easy/staff/inquiries.php?type=' . $type), 'Staff inbox link is missing or targets customer account.');
            check(!str_contains($render['html'], '/customer/') && !str_contains($render['html'], '<script>alert(1)</script>'), 'Alert linked to customer account or rendered unsafe HTML.');
            check(str_contains($render['text'], $reference), 'Plain-text alert is incomplete.');
        }
        check((int) $pdo->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn() === $counts['notification_outbox'] + 2, 'Creation queued extra customer/SMS/WhatsApp notifications.');
        echo "PASS one branded internal alert per new inquiry/quote to info@easyway.ng, all request details, safe staff links and unchanged New status\n";

        // A queue/config failure must roll back just this inquiry, preserving the caller's earlier work.
        putenv('INQUIRY_ALERT_EMAIL=invalid');
        rejected(fn() => InquiryService::createContact($contact));
        check($pdo->inTransaction(), 'Save failure rolled back caller transaction.');
        check((int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn() === $counts['contact_messages'] + 1, 'Save failure left an unnotified inquiry.');
        putenv('INQUIRY_ALERT_EMAIL=info@easyway.ng');
        putenv('APP_URL=http://localhost/easy');
        $render = EmailTemplate::render($alerts['quote']);
        check(!str_contains($render['html'], 'Open quote inbox') && !str_contains($render['text'], 'http://localhost'), 'Local staff URL leaked into email.');
        putenv('APP_URL=' . $overrides['APP_URL']);
        check(NotificationService::dispatchPending(2)['sent'] === 0 && ($GLOBALS['alert_calls'] ?? 0) === 0, 'Disabled email sent a staff alert.');
        $ids = array_map(static fn(array $row): int => (int) $row['id'], array_values($alerts));
        foreach ($ids as $id) { $pdo->exec('UPDATE notification_outbox SET created_at="1000-01-01 00:00:00" WHERE id=' . $id); }
        $candidates = array_map('intval', $pdo->query('SELECT id FROM notification_outbox WHERE channel="email" AND status="pending" AND (next_attempt_at IS NULL OR next_attempt_at<=NOW()) ORDER BY created_at,id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN));
        check($candidates === $ids, 'Safety check: worker batch contains non-QA email.');
        putenv('EMAIL_NOTIFICATIONS_ENABLED=true');
        $GLOBALS['alert_fail'] = true;
        check(NotificationService::dispatchPending(2)['failed'] === 2, 'Mock SMTP rejection did not schedule retry.');
        foreach ($ids as $id) {
            $row = $pdo->query('SELECT * FROM notification_outbox WHERE id=' . $id)->fetch();
            check($row['status'] === 'pending' && (int) $row['attempts'] === 1 && strtotime($row['next_attempt_at']) > time(), 'Failed alert was lost or not delayed.');
            $pdo->exec('UPDATE notification_outbox SET next_attempt_at=NULL WHERE id=' . $id);
        }
        $GLOBALS['alert_fail'] = false;
        check(NotificationService::dispatchPending(2)['sent'] === 2, 'Retried staff alerts were not accepted by mock SMTP.');
        check(str_contains($GLOBALS['alert_wire'], 'To: <info@easyway.ng>') && str_contains($GLOBALS['alert_wire'], 'multipart/alternative'), 'Actual transport missed recipient or branded MIME.');
        foreach ($ids as $id) {
            $row = $pdo->query('SELECT status,attempts FROM notification_outbox WHERE id=' . $id)->fetch();
            check($row['status'] === 'sent' && (int) $row['attempts'] === 2, 'Retry inserted a new alert instead of updating the existing one.');
        }
        echo "PASS disabled email waits; mocked SMTP failures retry the same alerts without changing existing outbox rows\n";

        if (in_array('--previews', $argv, true)) {
            foreach ($alerts as $type => $alert) {
                $render = EmailTemplate::render($alert);
                $html = str_replace('cid:' . EmailTemplate::LOGO_CID, 'data:image/jpeg;base64,' . base64_encode($render['logo'] ?? ''), $render['html']);
                $path = EASYWAY_ROOT . '/storage/cache/staff-alert-' . $type . '-' . bin2hex(random_bytes(4)) . '.html';
                check(file_put_contents($path, $html) !== false, 'Could not create synthetic preview.');
                echo 'PREVIEW ' . $path . PHP_EOL;
            }
        }
        $pdo->rollBack();
        // Also exercise the ordinary non-nested transaction's failure path, without committing test data.
        putenv('INQUIRY_ALERT_EMAIL=invalid');
        rejected(fn() => InquiryService::createQuote($quote));
        check(!$pdo->inTransaction(), 'Failed top-level submission left a transaction open.');
        echo "PASS atomic inquiry/alert saves, nested savepoints and safe public error messages\n";
    } finally {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        foreach ($environment as $key => $value) { putenv($value === false ? $key : $key . '=' . $value); }
    }
    foreach ($counts as $table => $count) { check((int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === $count, 'QA data remained in ' . $table); }
    check($pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll() === $beforeSettings, 'Delivery settings changed.');
    check(hash('sha256', json_encode($pdo->query('SELECT * FROM notification_outbox ORDER BY id')->fetchAll())) === $beforeOutbox, 'Existing outbox changed.');
    echo "PASS all test data rolled back; no live email or provider requests\n";
}
