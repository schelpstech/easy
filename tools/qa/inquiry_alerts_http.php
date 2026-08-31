<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Database;
use App\NotificationSettings;

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8099', '/');
if (!preg_match('#^http://(?:127\.0\.0\.1|localhost)(?::[0-9]+)?(?:/[a-zA-Z0-9_-]+)*$#D', $base)) { throw new RuntimeException('Use a dedicated loopback development server only.'); }
function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function request(CurlHandle $handle, string $path, ?array $fields = null): array {
    global $base;
    curl_setopt($handle, CURLOPT_URL, $base . '/' . ltrim($path, '/'));
    if ($fields === null) { curl_setopt($handle, CURLOPT_HTTPGET, true); }
    else { curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields)]); }
    $body = curl_exec($handle);
    check(is_string($body), 'Local HTTP request failed.');
    return ['status' => curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'redirect' => curl_getinfo($handle, CURLINFO_REDIRECT_URL)];
}
function newClient(): CurlHandle {
    $handle = curl_init();
    curl_setopt_array($handle, [CURLOPT_COOKIEFILE => '', CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROXY => '', CURLOPT_TIMEOUT => 15]);
    return $handle;
}
function fields(string $html, string $type): array {
    $dom = new DOMDocument(); $before = libxml_use_internal_errors(true); $dom->loadHTML($html); libxml_clear_errors(); libxml_use_internal_errors($before);
    $xpath = new DOMXPath($dom);
    $forms = $xpath->query('//form[contains(@action,"action=' . $type . '.submit")]');
    check($forms->length === 1, 'Public form was not found.');
    $result = [];
    foreach ($xpath->query('.//input[@type="hidden"]', $forms->item(0)) as $input) { $result[$input->getAttribute('name')] = $input->getAttribute('value'); }
    return $result;
}

// This test creates committed, synthetic requests, so require the ordinary local cron to remain disabled.
check(!NotificationSettings::get('email')['enabled'], 'Local automatic email must be disabled before HTTP QA. Do not run against production.');
$pdo = Database::connection();
$beforeSettings = $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll();
$beforeOutbox = hash('sha256', json_encode($pdo->query('SELECT * FROM notification_outbox ORDER BY id')->fetchAll()));
$before = [];
foreach (['contact_messages', 'quote_requests', 'notification_outbox', 'audit_logs'] as $table) { $before[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn(); }
$suffix = bin2hex(random_bytes(8));
$alertRecipient = 'inquiry-alerts-http@example.test';
$clients = []; $customerEmails = [];
try {
    foreach ([['contact', 'contact.php'], ['quote', 'quote.php'], ['contact', 'index.php'], ['quote', 'index.php']] as $index => [$type, $page]) {
        $customerEmail = $customerEmails[] = 'public-alert-' . $suffix . '-' . $index . '@example.test';
        $client = $clients[] = newClient();
        $form = request($client, $page); check($form['status'] === 200, 'Public form page failed.');
        $data = fields($form['body'], $type) + ['website' => '', 'recipient' => 'wrong@example.test', 'alert_email' => 'wrong@example.test'];
        if ($type === 'contact') {
            $data += ['full_name' => 'Public Alert QA', 'company_name' => 'Example QA', 'email' => $customerEmail, 'phone' => '09031134210',
                'subject' => 'Public contact alert ' . $suffix, 'message' => 'Please arrange a collection of sample cartons tomorrow.'];
        } else {
            $data += ['fullname' => 'Public Alert QA', 'email' => $customerEmail, 'phone' => '08087137894', 'shipment_type_option' => 'International',
                'from_location' => 'Lagos', 'to_location' => 'London', 'weight_range' => '6kg - 15kg', 'quantity' => 2, 'delivery_type' => 'Express Delivery', 'notes' => 'Public quote alert QA ' . $suffix];
        }
        $path = 'controller/router.php?action=' . $type . '.submit';
        $response = request($client, $path, $data);
        check($response['status'] === 303, 'Valid public submission did not redirect.');
        $table = $type === 'quote' ? 'quote_requests' : 'contact_messages';
        $query = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE email=:email'); $query->execute(['email' => $customerEmail]);
        $rows = $query->fetchAll(); check(count($rows) === 1, 'Valid submission was not saved once.');
        $inquiry = $rows[0]; $title = $type === 'quote' ? 'New quote request' : 'New contact inquiry';
        $query = $pdo->prepare('SELECT * FROM notification_outbox WHERE template_code=:template AND subject=:subject');
        $query->execute(['template' => 'staff_' . $type . '_received', 'subject' => '[' . $inquiry['reference'] . '] ' . $title]);
        $alerts = $query->fetchAll(); check(count($alerts) === 1, 'New public request did not queue exactly one alert.');
        check($alerts[0]['recipient'] === $alertRecipient, 'Start the dedicated server with INQUIRY_ALERT_EMAIL=inquiry-alerts-http@example.test.');
        check($alerts[0]['status'] === 'pending' && (int) $alerts[0]['attempts'] === 0, 'Public submission invoked delivery.');
        check(str_contains($alerts[0]['message'], $customerEmail), 'Alert is missing the customer email.');
        $queueCount = (int) $pdo->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn();
        // A duplicate immediate POST, missing CSRF, invalid details and honeypot must not enqueue.
        request($client, $path, $data);
        $csrf = $data; unset($csrf['_token']);
        $response = request($client, $path, $csrf); check($response['status'] === 419, 'CSRF rejection is missing.');
        foreach (['invalid', 'honeypot'] as $case) {
            $other = $clients[] = newClient();
            $bad = array_replace($data, fields(request($other, $page)['body'], $type));
            if ($case === 'invalid') { $bad['email'] = 'not-an-email'; } else { $bad['website'] = 'spam.example.test'; }
            request($other, $path, $bad);
        }
        check((int) $pdo->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn() === $queueCount, 'Rejected or duplicate form queued another alert.');
        $query = $pdo->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE email=:email'); $query->execute(['email' => $customerEmail]);
        check((int) $query->fetchColumn() === 1, 'Duplicate or spam inquiry was saved.');
        check(request($client, $path)['status'] === 405, 'GET submission accepted.');
        echo 'PASS ' . $page . ' ' . $type . ': one queued alert; validation, CSRF, honeypot and immediate duplicate protections' . PHP_EOL;
    }
    $anonymous = $clients[] = newClient();
    check(request($anonymous, 'staff/inquiries.php?type=quote')['status'] === 303, 'Staff inbox did not require sign-in.');
} finally {
    foreach ($clients as $client) { curl_close($client); }
    foreach ($customerEmails as $email) {
        foreach (['contact' => 'contact_messages', 'quote' => 'quote_requests'] as $type => $table) {
            $query = $pdo->prepare('SELECT id,reference FROM ' . $table . ' WHERE email=:email'); $query->execute(['email' => $email]);
            foreach ($query->fetchAll() as $inquiry) {
                $pdo->prepare('DELETE FROM notification_outbox WHERE template_code=:template AND subject=:subject')->execute([
                    'template' => 'staff_' . $type . '_received', 'subject' => '[' . $inquiry['reference'] . '] ' . ($type === 'quote' ? 'New quote request' : 'New contact inquiry')]);
                $pdo->prepare('DELETE FROM audit_logs WHERE entity_type=:type AND entity_id=:id')->execute(['type' => $type === 'quote' ? 'quote_request' : 'contact_message', 'id' => $inquiry['id']]);
                $pdo->prepare('DELETE FROM ' . $table . ' WHERE id=:id')->execute(['id' => $inquiry['id']]);
            }
        }
    }
}
foreach ($before as $table => $count) { check((int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === $count, 'QA data remained in ' . $table); }
check($pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll() === $beforeSettings, 'Delivery settings changed.');
check(hash('sha256', json_encode($pdo->query('SELECT * FROM notification_outbox ORDER BY id')->fetchAll())) === $beforeOutbox, 'Existing outbox changed.');
echo "PASS staff sign-in enforced; synthetic requests/alerts removed; no real email, settings changes or existing outbox changes\n";
