<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
use App\Database;
use App\InquiryService;
use App\NotificationSettings;

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8099', '/');
if (!preg_match('#^http://(?:127\.0\.0\.1|localhost)(?::[0-9]+)?(?:/[a-zA-Z0-9_-]+)*$#D', $base)) { throw new RuntimeException('Use a loopback development URL only.'); }
function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function client(): CurlHandle {
    $handle = curl_init(); curl_setopt_array($handle, [CURLOPT_COOKIEFILE => '', CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROXY => '', CURLOPT_TIMEOUT => 15]); return $handle;
}
function request(CurlHandle $handle, string $path, ?array $fields = null): array {
    global $base;
    curl_setopt($handle, CURLOPT_URL, $base . '/' . ltrim($path, '/'));
    if ($fields === null) { curl_setopt($handle, CURLOPT_HTTPGET, true); }
    else { curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields)]); }
    $body = curl_exec($handle); check(is_string($body), 'Local HTTP request failed.');
    return ['status' => curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'redirect' => curl_getinfo($handle, CURLINFO_REDIRECT_URL)];
}
function fields(array $page, string $action): array {
    $document = new DOMDocument(); $prior = libxml_use_internal_errors(true); $document->loadHTML($page['body']); libxml_clear_errors(); libxml_use_internal_errors($prior);
    $xpath = new DOMXPath($document);
    $forms = $xpath->query('//form[contains(@action,"action=' . $action . '")]');
    check($forms->length === 1, 'Could not find form: ' . $action);
    $fields = [];
    foreach ($xpath->query('.//input[@type="hidden"]', $forms->item(0)) as $input) { $fields[$input->getAttribute('name')] = $input->getAttribute('value'); }
    return $fields;
}
function login(CurlHandle $handle, string $email, string $password): void {
    $fields = fields(request($handle, 'staff/login.php'), 'staff.login');
    $response = request($handle, 'controller/router.php?action=staff.login', $fields + ['email' => $email, 'password' => $password]);
    check($response['status'] === 303 && !str_ends_with($response['redirect'], '/login.php'), 'QA login failed.');
}

// The dedicated HTTP server can use synthetic SMTP process-environment settings.
// Real local cron configuration must stay disabled throughout this integration test.
check(!NotificationSettings::get('email')['enabled'], 'For safe HTTP QA, disable local worker email first; do not run this test against production.');
$pdo = Database::connection();
$before = [];
foreach (['staff_users','contact_messages','quote_requests','inquiry_activities','notification_outbox','bookings','billing_documents'] as $table) { $before[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn(); }
$settingsBefore = $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll();
$suffix = bin2hex(random_bytes(6)); $password = 'Inbox-HTTP-QA-' . bin2hex(random_bytes(12));
$oldAlertEmail = getenv('INQUIRY_ALERT_EMAIL');
$alertEmail = 'inbox-alert-' . $suffix . '@example.test';
putenv('INQUIRY_ALERT_EMAIL=' . $alertEmail);
$emails = []; $inquiries = []; $clients = [];
try {
    foreach (['admin','dispatcher','rider'] as $role) {
        $emails[$role] = 'inbox-http-' . $role . '-' . $suffix . '@example.test';
        $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status) VALUES (:name,:email,:hash,:role,"active")')
            ->execute(['name' => 'Inbox HTTP QA', 'email' => $emails[$role], 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
    }
    $reference = InquiryService::createContact(['full_name' => 'Inbox HTTP ' . $suffix, 'company_name' => '', 'email' => 'inbox-customer-' . $suffix . '@example.test', 'phone' => '09031134210', 'subject' => 'Collection request', 'message' => 'Please confirm a pickup time for tomorrow.']);
    $query = $pdo->prepare('SELECT id FROM contact_messages WHERE reference=:ref'); $query->execute(['ref' => $reference]); $inquiries['contact'] = (int) $query->fetchColumn();
    $reference = InquiryService::createQuote(['full_name' => 'Inbox HTTP ' . $suffix, 'email' => 'inbox-quote-' . $suffix . '@example.test', 'phone' => '+2348087137894', 'shipment_type' => 'Domestic', 'from_location' => 'Ikeja', 'to_location' => 'Abuja', 'weight_range' => '1kg - 5kg', 'quantity' => 1, 'delivery_type' => 'Express Delivery', 'notes' => 'HTTP QA only.']);
    $query = $pdo->prepare('SELECT id FROM quote_requests WHERE reference=:ref'); $query->execute(['ref' => $reference]); $inquiries['quote'] = (int) $query->fetchColumn();
    $anonymous = $clients[] = client();
    foreach (['staff/inquiries.php', 'staff/inquiry.php?type=contact&id=' . $inquiries['contact']] as $path) { check(request($anonymous, $path)['status'] === 303, 'Anonymous inquiry access allowed.'); }
    foreach (['reply','quotation','note','status'] as $kind) {
        $path = 'controller/router.php?action=staff.inquiry.' . $kind;
        check(request($anonymous, $path)['status'] === 405, 'GET mutation allowed.');
        $rejected = request($anonymous, $path, []);
        check(in_array($rejected['status'], [419,500], true) && $rejected['body'] === 'Your session expired. Please return to the form and try again.', 'CSRF guard missing.');
    }
    $rider = $clients[] = client(); login($rider, $emails['rider'], $password);
    check(request($rider, 'staff/inquiries.php')['status'] === 403, 'Rider read inquiry inbox.');
    $csrf = fields(request($rider, 'staff/password.php'), 'staff.password.change')['_token'];
    foreach (['reply','quotation','note','status'] as $kind) { check(request($rider, 'controller/router.php?action=staff.inquiry.' . $kind, ['_token' => $csrf])['status'] === 403, 'Rider inquiry mutation allowed.'); }
    $staff = $clients[] = client(); login($staff, $emails['dispatcher'], $password);
    $contactPath = 'staff/inquiry.php?type=contact&id=' . $inquiries['contact'];
    $page = request($staff, $contactPath);
    check($page['status'] === 200 && str_contains($page['body'], 'https://wa.me/2349031134210?text='), 'Contact page or shortcuts failed.');
    check(!str_contains($page['body'], 'Email delivery is disabled.'), 'Start the dedicated HTTP server with synthetic SMTP enabled in its process environment.');
    $noteFields = fields($page, 'staff.inquiry.note') + ['note' => 'Internal HTTP note <script>alert("escaped")</script>'];
    $staleFields = fields($page, 'staff.inquiry.status') + ['status' => 'closed'];
    request($staff, 'controller/router.php?action=staff.inquiry.note', $noteFields);
    $page = request($staff, $contactPath);
    check(str_contains($page['body'], 'Internal note saved.') && str_contains($page['body'], '&lt;script&gt;alert(') && !str_contains($page['body'], '<script>alert('), 'Note save or HTML escaping failed.');
    request($staff, 'controller/router.php?action=staff.inquiry.status', $staleFields);
    check(str_contains(request($staff, $contactPath)['body'], 'changed in another session'), 'Stale status form accepted.');
    $replyFields = fields(request($staff, $contactPath), 'staff.inquiry.reply') + ['subject' => 'Your pickup time', 'body' => 'Please confirm whether 10 AM works for collection.', 'recipient' => 'wrong@example.com'];
    request($staff, 'controller/router.php?action=staff.inquiry.reply', $replyFields);
    request($staff, 'controller/router.php?action=staff.inquiry.reply', $replyFields);
    $query = $pdo->prepare('SELECT a.*,n.recipient,n.message,n.status AS delivery_status FROM inquiry_activities a JOIN notification_outbox n ON n.id=a.notification_id WHERE a.inquiry_type="contact" AND a.inquiry_id=:id');
    $query->execute(['id' => $inquiries['contact']]); $replies = $query->fetchAll();
    check(count($replies) === 1 && $replies[0]['delivery_status'] === 'pending', 'Reply not queued once.');
    check($replies[0]['recipient'] === 'inbox-customer-' . $suffix . '@example.test' && !str_contains($replies[0]['message'], 'Internal HTTP note'), 'Email recipient or note privacy failed.');
    $page = request($staff, $contactPath);
    check(str_contains($page['body'], 'Queued for email'), 'Reply delivery state missing.');
    request($staff, 'controller/router.php?action=staff.inquiry.status', fields($page, 'staff.inquiry.status') + ['status' => 'closed']);
    check(str_contains(request($staff, $contactPath)['body'], 'Inquiry status updated.'), 'Status change failed.');
    $quotePath = 'staff/inquiry.php?type=quote&id=' . $inquiries['quote'];
    $quoteFields = fields(request($staff, $quotePath), 'staff.inquiry.quotation') + ['amount' => '12500.55', 'currency' => 'NGN', 'terms' => 'Collection included. Delivery estimate 2 working days. Valid for 7 days.'];
    request($staff, 'controller/router.php?action=staff.inquiry.quotation', $quoteFields);
    $page = request($staff, $quotePath);
    check(str_contains($page['body'], 'Quotation recorded.') && str_contains($page['body'], '12500.55') && str_contains($page['body'], $quoteFields['terms']), 'Quotation amount/terms missing from history.');
    $admin = $clients[] = client(); login($admin, $emails['admin'], $password);
    check(request($admin, $quotePath)['status'] === 200, 'Admin cannot read inquiry.');
    check(request($staff, 'staff/inquiry.php?type=invalid&id=1')['status'] === 404, 'Invalid type accepted.');
    check(request($staff, 'staff/inquiry.php?type=contact&id=0')['status'] === 404, 'Invalid ID accepted.');
    $page = request($staff, 'staff/inquiries.php?type=contact&status=closed&q=' . $suffix);
    check(str_contains($page['body'], '1 result(s)'), 'Filtered inbox did not find updated record.');
    echo "PASS HTTP permissions/CSRF, note privacy, stale forms and duplicate submission\n";
    echo "PASS real reply/quotation POSTs queue once, preserve terms, update history/status and render safely\n";
} finally {
    foreach ($clients as $handle) { curl_close($handle); }
    foreach ($inquiries as $type => $id) {
        $source = $pdo->prepare('SELECT reference FROM ' . ($type === 'quote' ? 'quote_requests' : 'contact_messages') . ' WHERE id=:id');
        $source->execute(['id' => $id]); $reference = $source->fetchColumn();
        $pdo->prepare('DELETE FROM notification_outbox WHERE template_code=:template AND subject=:subject AND recipient=:recipient')->execute([
            'template' => 'staff_' . $type . '_received', 'subject' => '[' . $reference . '] ' . ($type === 'quote' ? 'New quote request' : 'New contact inquiry'), 'recipient' => $alertEmail]);
        $query = $pdo->prepare('SELECT notification_id FROM inquiry_activities WHERE inquiry_type=:type AND inquiry_id=:id AND notification_id IS NOT NULL'); $query->execute(['type' => $type, 'id' => $id]); $notifications = $query->fetchAll(PDO::FETCH_COLUMN);
        $pdo->prepare('DELETE FROM inquiry_activities WHERE inquiry_type=:type AND inquiry_id=:id')->execute(['type' => $type, 'id' => $id]);
        foreach ($notifications as $notificationId) { $pdo->prepare('DELETE FROM notification_outbox WHERE id=:id')->execute(['id' => $notificationId]); }
        $pdo->prepare('DELETE FROM audit_logs WHERE entity_type=:type AND entity_id=:id')->execute(['type' => $type === 'quote' ? 'quote_request' : 'contact_message', 'id' => $id]);
        $pdo->prepare('DELETE FROM ' . ($type === 'quote' ? 'quote_requests' : 'contact_messages') . ' WHERE id=:id')->execute(['id' => $id]);
    }
    foreach ($emails as $email) {
        $query = $pdo->prepare('SELECT id FROM staff_users WHERE email=:email'); $query->execute(['email' => $email]);
        foreach ($query->fetchAll(PDO::FETCH_COLUMN) as $id) { $pdo->prepare('DELETE FROM audit_logs WHERE staff_user_id=:id')->execute(['id' => $id]); $pdo->prepare('DELETE FROM staff_users WHERE id=:id')->execute(['id' => $id]); }
        $pdo->prepare('DELETE FROM login_attempts WHERE email=:email')->execute(['email' => $email]);
    }
    putenv($oldAlertEmail === false ? 'INQUIRY_ALERT_EMAIL' : 'INQUIRY_ALERT_EMAIL=' . $oldAlertEmail);
}
foreach ($before as $table => $count) { check((int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === $count, 'QA data remained in ' . $table); }
check($settingsBefore === $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll(), 'Provider settings changed.');
echo "PASS QA fixtures removed; no messages sent; existing settings preserved\n";
