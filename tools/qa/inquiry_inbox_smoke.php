<?php
declare(strict_types=1);
namespace App {
    function gethostbynamel(string $host): array { return ['93.184.216.34']; }
    function curl_setopt_array(\CurlHandle $handle, array $options): bool { $GLOBALS['qa_options'] = array_replace($GLOBALS['qa_options'] ?? [], $options); return true; }
    function curl_exec(\CurlHandle $handle): bool { $GLOBALS['qa_calls'] = ($GLOBALS['qa_calls'] ?? 0) + 1; return !($GLOBALS['qa_fail'] ?? false); }
    function curl_errno(\CurlHandle $handle): int { return 67; }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
    use App\Auth;
    use App\Database;
    use App\InquiryInboxService as Inbox;
    use App\InquiryService;
    use App\NotificationService;
    use App\NotificationSettings;
    function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
    function rejects(callable $call, string $message): void { try { $call(); } catch (RuntimeException) { return; } throw new RuntimeException($message); }
    function form(string $type, int $id, array $fields = []): array {
        return array_replace(['revision' => Inbox::history($type, $id)[0]['id'] ?? 0, 'request_token' => bin2hex(random_bytes(32))], $fields);
    }
    $pdo = Database::connection();
    check(Inbox::installed(), 'Run tools/install_inquiry_inbox.php first.');
    $before = [];
    foreach (['staff_users','quote_requests','contact_messages','inquiry_activities','notification_outbox','audit_logs'] as $table) { $before[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn(); }
    $settingsBefore = $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll();
    $outboxBefore = hash('sha256', json_encode($pdo->query('SELECT * FROM notification_outbox ORDER BY id')->fetchAll()));
    $sessionBefore = $_SESSION;
    $pdo->beginTransaction();
    try {
        $suffix = bin2hex(random_bytes(6)); $password = 'Inquiry-QA-' . bin2hex(random_bytes(12)); $emails = [];
        foreach (['admin','dispatcher','rider'] as $role) {
            $emails[$role] = 'inquiry-qa-' . $role . '-' . $suffix . '@example.test';
            $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status) VALUES (:name,:email,:hash,:role,"active")')
                ->execute(['name' => 'Inbox QA ' . $role, 'email' => $emails[$role], 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
        }
        check(Auth::attempt($emails['admin'], $password), 'Admin sign-in failed.');
        $contactRef = InquiryService::createContact(['full_name' => 'Maya Inbox QA ' . $suffix, 'company_name' => 'Sample Company', 'email' => 'customer-' . $suffix . '@example.test', 'phone' => '09031134210', 'subject' => 'Collection timing', 'message' => 'Can you collect our packages from Ikeja tomorrow?']);
        $contactId = (int) $pdo->query('SELECT MAX(id) FROM contact_messages')->fetchColumn();
        $quoteRef = InquiryService::createQuote(['full_name' => 'Daniel Inbox QA ' . $suffix, 'email' => 'quote-' . $suffix . '@example.test', 'phone' => '+2348087137894', 'shipment_type' => 'International', 'from_location' => 'Lagos', 'to_location' => 'London', 'weight_range' => '6kg - 15kg', 'quantity' => 2, 'delivery_type' => 'Express Delivery', 'notes' => 'Two cartons of sample clothing. Please confirm estimated transit time.']);
        $quoteId = (int) $pdo->query('SELECT MAX(id) FROM quote_requests')->fetchColumn();
        $outboxAfterSubmissions = (int) $pdo->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn();
        check($outboxAfterSubmissions === $before['notification_outbox'] + 2, 'New inquiries did not queue exactly two staff alerts.');
        $note = 'Private QA note <script>alert("not-executable")</script> — confirm collection with dispatch.';
        $noteForm = form('contact', $contactId, ['note' => $note]);
        $noteId = Inbox::act('contact', $contactId, 'note', $noteForm);
        check(Inbox::act('contact', $contactId, 'note', $noteForm) === $noteId, 'Double submission created another note.');
        rejects(fn() => Inbox::act('contact', $contactId, 'note', array_replace($noteForm, ['note' => 'Different text'])), 'Token reuse with different content accepted.');
        rejects(fn() => Inbox::act('contact', $contactId, 'status', ['revision' => 0, 'request_token' => bin2hex(random_bytes(32)), 'status' => 'closed']), 'Stale form overwrote a newer activity.');
        rejects(fn() => Inbox::act('contact', $contactId, 'status', form('contact', $contactId, ['status' => 'quoted'])), 'Quote-only status accepted on contact.');
        rejects(fn() => Inbox::act('contact', $contactId, 'quotation', form('contact', $contactId)), 'Contact received a quotation action.');
        check((int) $pdo->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn() === $outboxAfterSubmissions, 'A private note sent an email.');
        check(Auth::attempt($emails['rider'], $password), 'Rider sign-in failed.');
        rejects(fn() => Inbox::find('contact', $contactId), 'Rider can read private inquiry.');
        rejects(fn() => Inbox::listing('quote'), 'Rider can read inbox.');
        rejects(fn() => Inbox::act('contact', $contactId, 'note', $noteForm), 'Rider can change inquiry.');
        check(Auth::attempt($emails['admin'], $password), 'Admin sign-in failed.');
        $smtp = ['transport' => 'smtp', 'from_name' => 'Easyway QA', 'from_email' => 'qa@example.com', 'host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'starttls', 'username' => 'qa@example.com', 'secret' => 'Synthetic-QA-Password', 'current_password' => $password, 'version' => NotificationSettings::get('email')['version']];
        NotificationSettings::save('email', $smtp);
        $replyForm = form('contact', $contactId, ['subject' => 'Your collection request', 'body' => 'We can arrange collection. Please confirm your preferred time.', 'recipient' => 'wrong@example.com']);
        rejects(fn() => Inbox::act('contact', $contactId, 'reply', $replyForm), 'Disabled email allowed a new reply.');
        $smtp['version'] = NotificationSettings::get('email')['version']; $smtp['enabled'] = 1;
        NotificationSettings::save('email', $smtp);
        foreach (['sms','whatsapp'] as $channel) {
            NotificationSettings::save($channel, ['url' => '', 'clear_secret' => 1, 'current_password' => $password, 'version' => NotificationSettings::get($channel)['version']]);
        }
        check(Auth::attempt($emails['dispatcher'], $password), 'Dispatcher sign-in failed.');
        $replyId = Inbox::act('contact', $contactId, 'reply', $replyForm);
        check(Inbox::act('contact', $contactId, 'reply', $replyForm) === $replyId, 'Duplicate reply created another email.');
        $reply = Inbox::history('contact', $contactId)[0];
        $queued = $pdo->query('SELECT * FROM notification_outbox WHERE id=' . (int) $reply['notification_id'])->fetch();
        check($queued['recipient'] === 'customer-' . $suffix . '@example.test', 'Posted recipient overrode customer email.');
        check(!str_contains($queued['message'], $note), 'Internal note leaked into customer email.');
        check($queued['status'] === 'pending' && Inbox::find('contact', $contactId)['status'] === 'in_progress', 'Queued reply incorrectly marked delivered/replied.');
        $terms = "Includes collection and air freight.\nDelivery estimate: 5–7 working days after dispatch.\nValid for 7 days. Destination duties excluded.";
        $quoteForm = form('quote', $quoteId, ['amount' => '12500.50', 'currency' => 'NGN', 'terms' => $terms]);
        Inbox::act('quote', $quoteId, 'quotation', $quoteForm);
        $quotation = Inbox::history('quote', $quoteId)[0];
        $quoteRow = Inbox::find('quote', $quoteId);
        check($quoteRow['quoted_amount'] === '12500.50' && $quoteRow['currency'] === 'NGN', 'Quotation amount was rounded or not stored.');
        $oldMetadata = json_decode($quotation['metadata_json'], true);
        check($oldMetadata['terms'] === $terms && $oldMetadata['amount'] === '12500.50', 'Quotation snapshot incomplete.');
        Inbox::act('quote', $quoteId, 'quotation', form('quote', $quoteId, ['amount' => '15000.75', 'currency' => 'NGN', 'terms' => $terms . "\nRevised collection fee included."]));
        $latestQuotation = Inbox::history('quote', $quoteId)[0];
        check(json_decode(Inbox::history('quote', $quoteId)[1]['metadata_json'], true)['amount'] === '12500.50', 'New quotation overwrote original history.');
        Inbox::act('quote', $quoteId, 'status', form('quote', $quoteId, ['status' => 'accepted']));
        check(Inbox::find('quote', $quoteId)['status'] === 'accepted', 'Status update failed.');
        foreach (['0', '-1', '1e3', '1.001', '1,000', '1000000000000', 'NaN'] as $amount) { rejects(fn() => Inbox::amount($amount), 'Invalid amount accepted: ' . $amount); }
        rejects(fn() => Inbox::act('contact', $contactId, 'reply', form('contact', $contactId, ['subject' => "Hello\r\nBcc:wrong@example.com", 'body' => 'test'])), 'Header injection accepted.');
        rejects(fn() => Inbox::act('contact', $contactId, 'note', form('contact', $contactId, ['note' => str_repeat('x', 6001)])), 'Oversized note accepted.');
        check(Inbox::phoneLinks('09031134210', $contactRef)['call'] === 'tel:+2349031134210', 'Nigerian phone normalization failed.');
        check(str_starts_with(Inbox::phoneLinks('002348087137894', $quoteRef)['whatsapp'], 'https://wa.me/2348087137894?text='), 'International WhatsApp shortcut failed.');
        check(Inbox::phoneLinks('javascript:alert(1)', $quoteRef) === ['call' => null, 'whatsapp' => null], 'Invalid phone created actionable URL.');
        check(Inbox::listing('quote', 'accepted', $suffix)['total'] === 1, 'Inbox filters failed.');
        check(Inbox::listing('contact', '', $suffix)['total'] === 1, 'Contact search failed.');

        // Make only our synthetic emails eligible for the small tested batch.
        $notificationIds = array_map('intval', [$reply['notification_id'], $quotation['notification_id'], $latestQuotation['notification_id']]);
        foreach ($notificationIds as $notificationId) { $pdo->exec('UPDATE notification_outbox SET created_at="1001-01-01 00:00:00" WHERE id=' . $notificationId); }
        $blockedInsert = $pdo->prepare('INSERT INTO notification_outbox (channel,recipient,template_code,message,status,created_at) VALUES ("sms","+2348000000000","inquiry_qa",:body,"pending","1000-01-01 00:00:00")');
        for ($i = 0; $i < 60; $i++) { $blockedInsert->execute(['body' => 'Synthetic disabled backlog ' . $suffix]); }
        $candidates = array_map('intval', $pdo->query('SELECT id FROM notification_outbox WHERE channel="email" AND status="pending" AND (next_attempt_at IS NULL OR next_attempt_at<=NOW()) ORDER BY created_at,id LIMIT 3')->fetchAll(PDO::FETCH_COLUMN));
        check($candidates === $notificationIds, 'Safety check: batch contains a non-QA email.');
        $other = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', App\Config::get('DB_HOST', '127.0.0.1'), App\Config::get('DB_PORT', '3306'), App\Config::get('DB_NAME', 'easyway_logistics')),
            App\Config::get('DB_USER', 'root'), App\Config::get('DB_PASSWORD', ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $lockName = 'easyway-notifications-' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 24);
        $otherLock = $other->prepare('SELECT GET_LOCK(:name,0)'); $otherLock->execute(['name' => $lockName]);
        check((int) $otherLock->fetchColumn() === 1, 'Could not set up overlapping worker test.');
        try {
            check(NotificationService::dispatchPending(3)['sent'] === 0 && ($GLOBALS['qa_calls'] ?? 0) === 0, 'Overlapping workers sent the same batch.');
        } finally { $other->prepare('SELECT RELEASE_LOCK(:name)')->execute(['name' => $lockName]); }
        $result = NotificationService::dispatchPending(3);
        check($result['sent'] === 3 && $result['waiting'] >= 60 && $GLOBALS['qa_calls'] === 3, 'Disabled backlog starved emails or dispatch failed.');
        check(Inbox::history('contact', $contactId)[0]['delivery_status'] === 'sent', 'History did not reflect accepted delivery.');
        $GLOBALS['qa_fail'] = true;
        $pdo->exec('UPDATE notification_outbox SET status="pending",attempts=4,next_attempt_at=NULL WHERE id=' . $notificationIds[0]);
        $result = NotificationService::dispatchPending(1);
        check($result['failed'] === 1 && Inbox::history('contact', $contactId)[0]['delivery_status'] === 'failed', 'Terminal email failure missing from history.');
        $GLOBALS['qa_fail'] = false;

        if (in_array('--previews', $argv, true)) {
            foreach (['quote', 'contact', 'inbox'] as $preview) {
                $_GET = $preview === 'inbox' ? ['type' => 'quote', 'q' => $suffix] : ['type' => $preview, 'id' => $preview === 'quote' ? $quoteId : $contactId];
                $pageName = $preview === 'inbox' ? 'inquiries' : 'inquiry';
                $_SERVER['SCRIPT_NAME'] = '/staff/' . $pageName . '.php';
                $html = (static function (string $pageName): string { ob_start(); require EASYWAY_ROOT . '/staff/' . $pageName . '.php'; return (string) ob_get_clean(); })($pageName);
                check(!str_contains($html, '<script>alert('), 'Stored text was not HTML-escaped.');
                $html = preg_replace('#<input[^>]+name="(?:_token|request_token)"[^>]*>#', '', $html);
                $html = preg_replace_callback('#<form\b[^>]*>#', static function (array $match): string {
                    $tag = preg_replace('~\saction="[^"]*"~', ' action="#"', $match[0]);
                    return substr($tag, 0, -1) . ' onsubmit="return false">';
                }, $html);
                $html = preg_replace('#href="[^"]*"#', 'href="#"', $html);
                $html = str_replace('<link rel="stylesheet" href="#">', '', $html);
                $html = str_replace('</head>', '<link rel="stylesheet" href="/assets/css/bootstrap.min.css"><link rel="stylesheet" href="/assets/css/bootstrap-icons.css"><link rel="stylesheet" href="/assets/css/staff.css"></head>', $html);
                $path = EASYWAY_ROOT . '/storage/cache/inbox-qa-' . $suffix . '-' . $preview . '.html';
                check(file_put_contents($path, $html) !== false, 'Could not generate synthetic UI preview.');
                echo 'PREVIEW ' . $path . PHP_EOL;
            }
        }
        echo "PASS roles, internal-note privacy, duplicate protection, stale forms and input validation\n";
        echo "PASS SMTP reply queue, quotation snapshots, statuses, shortcuts and activity history\n";
        echo "PASS mocked delivery/failure history, disabled backlog and overlapping worker protection; no live messages sent\n";
    } finally { if ($pdo->inTransaction()) { $pdo->rollBack(); } $_SESSION = $sessionBefore; }
    foreach ($before as $table => $count) { check((int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === $count, 'QA rows remained in ' . $table); }
    check($settingsBefore === $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll(), 'Provider settings changed.');
    check($outboxBefore === hash('sha256', json_encode($pdo->query('SELECT * FROM notification_outbox ORDER BY id')->fetchAll())), 'Existing outbox changed.');
    echo "PASS all QA database changes rolled back; existing settings and outbox unchanged\n";
}
