<?php
declare(strict_types=1);

// Transport doubles intercept all network execution in this CLI-only QA process.
namespace App {
    function gethostbynamel(string $host): array { return ['93.184.216.34']; }
    function curl_setopt_array(\CurlHandle $handle, array $options): bool {
        $GLOBALS['qa_transport_options'] = array_replace($GLOBALS['qa_transport_options'] ?? [], $options);
        return true;
    }
    function curl_exec(\CurlHandle $handle): bool {
        $GLOBALS['qa_transport_calls'] = ($GLOBALS['qa_transport_calls'] ?? 0) + 1;
        $stream = $GLOBALS['qa_transport_options'][CURLOPT_INFILE] ?? null;
        if (is_resource($stream)) { $GLOBALS['qa_smtp_message'] = stream_get_contents($stream); }
        return !($GLOBALS['qa_transport_fail'] ?? false);
    }
    function curl_getinfo(\CurlHandle $handle, ?int $option = null): int { return $GLOBALS['qa_transport_fail'] ?? false ? 401 : 202; }
    function curl_errno(\CurlHandle $handle): int { return $GLOBALS['qa_transport_fail'] ?? false ? 67 : 0; }
}

namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
    use App\Auth;
    use App\Database;
    use App\NotificationService;
    use App\NotificationSettings;
    use App\NotificationTransport;
    use App\StaffAccountService;

    function check(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }
    function rejects(callable $call, string $message): void {
        try { $call(); } catch (RuntimeException) { return; }
        throw new RuntimeException($message);
    }

    $pdo = Database::connection();
    check(NotificationSettings::installed(), 'Run tools/install_staff_settings.php first.');
    $snapshot = $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll();
    $staffCount = (int) $pdo->query('SELECT COUNT(*) FROM staff_users')->fetchColumn();
    $outboxCount = (int) $pdo->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn();
    $session = $_SESSION;
    $pdo->beginTransaction();
    try {
        // Roll back settings as well as accounts; other connections cannot see this QA data.
        $pdo->exec('DELETE FROM notification_settings');
        $suffix = bin2hex(random_bytes(6));
        $password = 'Qa-Initial-' . bin2hex(random_bytes(12));
        $newPassword = 'Qa-Changed-' . bin2hex(random_bytes(12));
        $emails = [];
        foreach (['admin', 'dispatcher', 'rider'] as $role) {
            $emails[$role] = 'settings-qa-' . $role . '-' . $suffix . '@example.test';
            $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status) VALUES (:name,:email,:hash,:role,"active")')
                ->execute(['name' => 'Settings QA ' . $role, 'email' => $emails[$role], 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
        }
        check(Auth::attempt($emails['admin'], $password), 'Admin sign-in failed.');
        $adminId = Auth::id();
        $create = ['full_name' => 'New QA Dispatcher', 'email' => 'settings-qa-created-' . $suffix . '@example.test', 'role' => 'dispatcher',
            'password' => $password, 'password_confirmation' => $password, 'current_password' => $password];
        $createdId = StaffAccountService::create($create);
        $created = $pdo->query('SELECT password_hash,role FROM staff_users WHERE id=' . (int) $createdId)->fetch();
        check(password_verify($password, $created['password_hash']) && $created['role'] === 'dispatcher', 'Staff creation hash or role failed.');
        rejects(fn() => StaffAccountService::create($create), 'Duplicate email accepted.');
        rejects(fn() => StaffAccountService::create(array_replace($create, ['role' => 'rider'])), 'Rider bypassed profile creation.');
        rejects(fn() => StaffAccountService::create(array_replace($create, ['role' => 'superadmin'])), 'Invalid role accepted.');
        rejects(fn() => StaffAccountService::validatePassword('short', 'short'), 'Short password accepted.');
        rejects(fn() => StaffAccountService::validatePassword(str_repeat('a', 73), str_repeat('a', 73)), 'Bcrypt truncation allowed.');
        rejects(fn() => StaffAccountService::validatePassword($password, 'mismatch'), 'Password mismatch accepted.');

        foreach (['dispatcher', 'rider'] as $role) {
            check(Auth::attempt($emails[$role], $password), 'Role sign-in failed.');
            rejects(fn() => StaffAccountService::create($create), 'Non-admin created staff.');
            rejects(fn() => NotificationSettings::save('email', []), 'Non-admin saved provider settings.');
            rejects(fn() => NotificationService::sendTest('sms', '+2348000000000', $password), 'Non-admin sent a test.');
        }
        check(($GLOBALS['qa_transport_calls'] ?? 0) === 0, 'Unauthorized test reached transport.');
        check(Auth::attempt($emails['admin'], $password), 'Admin sign-in failed.');
        rejects(fn() => StaffAccountService::changePassword('incorrect', $newPassword, $newPassword), 'Wrong current password accepted.');
        rejects(fn() => StaffAccountService::changePassword($password, $password, $password), 'Reused password accepted.');
        $oldSession = $_SESSION;
        StaffAccountService::changePassword($password, $newPassword, $newPassword);
        check(!Auth::check(), 'Current session survived a password change.');
        $_SESSION = $oldSession;
        check(!Auth::check(), 'Another old session survived a password change.');
        check(!Auth::attempt($emails['admin'], $password), 'Old password still works.');
        check(Auth::attempt($emails['admin'], $newPassword), 'New password does not work.');

        $secret = 'Qa-provider-' . bin2hex(random_bytes(12));
        $emailSettings = ['transport' => 'smtp', 'from_name' => 'Easyway QA', 'from_email' => 'no-reply@example.com',
            'host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'starttls', 'username' => 'qa@example.com',
            'secret' => $secret, 'current_password' => $newPassword, 'version' => 0];
        NotificationSettings::save('email', $emailSettings);
        check(!NotificationService::channelEnabled('email'), 'Disabled email became enabled.');
        $public = NotificationSettings::get('email');
        check($public['secret'] === '' && $public['has_secret'], 'Settings view exposed or lost secret state.');
        check(NotificationSettings::get('email', true)['secret'] === $secret, 'Encrypted credential did not round-trip.');
        $row = $pdo->query('SELECT settings_json,secret_encrypted FROM notification_settings WHERE channel="email"')->fetch();
        check(!str_contains(json_encode($row), $secret), 'Credential persisted in plaintext.');
        rejects(fn() => NotificationSettings::save('email', $emailSettings), 'Stale settings overwrote a newer revision.');
        NotificationSettings::save('email', array_replace($emailSettings, ['secret' => '', 'version' => 1]));
        check(NotificationSettings::get('email', true)['secret'] === $secret, 'Blank credential did not preserve existing value.');
        rejects(fn() => NotificationSettings::save('email', array_replace($emailSettings, ['version' => 2, 'from_name' => "Bad\r\nBcc: x@example.com"])), 'Mail header injection accepted.');

        foreach (['sms', 'whatsapp'] as $channel) {
            NotificationSettings::save($channel, ['url' => 'https://adapter.example.com/send', 'secret' => $secret, 'current_password' => $newPassword, 'version' => 0]);
            check(!NotificationService::channelEnabled($channel), 'Disabled messaging channel became enabled.');
            $GLOBALS['qa_transport_options'] = [];
            NotificationService::sendTest($channel, '+2348000000000', $newPassword);
            $options = $GLOBALS['qa_transport_options'];
            $payload = json_decode($options[CURLOPT_POSTFIELDS], true);
            check($payload['to'] === '+2348000000000' && str_starts_with($payload['reference'], 'EWN-TEST-'), 'Adapter payload or test reference incorrect.');
            check($options[CURLOPT_SSL_VERIFYPEER] && $options[CURLOPT_SSL_VERIFYHOST] === 2 && !$options[CURLOPT_FOLLOWLOCATION] && isset($options[CURLOPT_RESOLVE]), 'HTTPS validation or DNS pinning missing.');
            check(in_array('Authorization: Bearer ' . $secret, $options[CURLOPT_HTTPHEADER], true), 'Bearer credential not passed correctly.');
        }
        $GLOBALS['qa_transport_options'] = [];
        NotificationService::sendTest('email', 'test@example.com', $newPassword);
        $options = $GLOBALS['qa_transport_options'];
        check($options[CURLOPT_USE_SSL] === CURLUSESSL_ALL && $options[CURLOPT_PASSWORD] === $secret && $options[CURLOPT_UPLOAD], 'SMTP TLS/authentication not configured.');
        check(str_contains($GLOBALS['qa_smtp_message'], 'To: <test@example.com>') && str_contains($GLOBALS['qa_smtp_message'], 'Content-Transfer-Encoding: base64'), 'SMTP MIME message incomplete.');
        $GLOBALS['qa_transport_fail'] = true;
        try { NotificationService::sendTest('sms', '+2348000000000', $newPassword); throw new LogicException('Provider rejection reported success.'); }
        catch (RuntimeException $e) { check(!str_contains($e->getMessage(), $secret), 'Provider error leaked credential.'); }
        $GLOBALS['qa_transport_fail'] = false;
        $calls = $GLOBALS['qa_transport_calls'];
        rejects(fn() => NotificationService::sendTest('email', "test@example.com\r\nBcc:x@example.com", $newPassword), 'Invalid test recipient accepted.');
        check($GLOBALS['qa_transport_calls'] === $calls, 'Invalid recipient reached transport.');
        foreach (['http://example.com/send', 'https://127.0.0.1/send', 'https://10.0.0.1/send', 'https://localhost/send', 'https://user:pass@example.com/send', 'https://example.com/send?token=secret', 'https://example.com:8443/send', 'https://169.254.169.254/latest'] as $url) {
            rejects(fn() => NotificationTransport::validateWebhookUrl($url), 'Unsafe adapter URL accepted: ' . $url);
        }
        NotificationSettings::save('email', array_replace($emailSettings, ['secret' => '', 'clear_secret' => 1, 'version' => 2]));
        check(!NotificationSettings::get('email')['has_secret'], 'Explicit credential removal failed.');
        rejects(fn() => NotificationSettings::save('email', array_replace($emailSettings, ['secret' => '', 'enabled' => 1, 'version' => 3])), 'Incomplete SMTP was enabled.');
        $audit = $pdo->prepare('SELECT context_json FROM audit_logs WHERE staff_user_id=:id');
        $audit->execute(['id' => $adminId]);
        check(!str_contains(json_encode($audit->fetchAll()), $secret), 'Audit metadata leaked credentials.');
        check((int) $pdo->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn() === $outboxCount, 'Tests changed the customer outbox.');
        if (in_array('--previews', $argv, true)) {
            // Static, non-submitting layout fixtures: no live credentials, sessions or team data.
            foreach (['accounts', 'password', 'email', 'sms', 'whatsapp'] as $preview) {
                $page = in_array($preview, ['accounts', 'password'], true) ? $preview : 'settings';
                $_GET = ['channel' => $preview];
                $_SERVER['SCRIPT_NAME'] = '/staff/' . $page . '.php';
                $html = (static function (string $page): string {
                    ob_start(); require EASYWAY_ROOT . '/staff/' . $page . '.php'; return (string) ob_get_clean();
                })($page);
                $html = preg_replace('#<tbody>.*?</tbody>#s', '<tbody><tr><td><strong>Sample Administrator</strong><br><small>admin@example.test</small></td><td>Admin</td><td><span class="staff-badge">Active</span></td><td>Not yet</td></tr></tbody>', $html);
                $html = preg_replace('#<input[^>]+name="_token"[^>]*>#', '', $html);
                $html = preg_replace('#<form\b[^>]*>#', '<form class="staff-form" action="#" onsubmit="return false">', $html);
                $html = preg_replace('#href="[^"]*"#', 'href="#"', $html);
                // Restore only local stylesheet links, not application navigation.
                $html = str_replace('<link rel="stylesheet" href="#">', '', $html);
                $html = str_replace('</head>', '<link rel="stylesheet" href="/assets/css/bootstrap.min.css"><link rel="stylesheet" href="/assets/css/bootstrap-icons.css"><link rel="stylesheet" href="/assets/css/staff.css"></head>', $html);
                $path = EASYWAY_ROOT . '/storage/cache/settings-qa-' . $suffix . '-' . $preview . '.html';
                check(file_put_contents($path, $html) !== false, 'Could not generate layout fixture.');
                echo 'PREVIEW ' . $path . PHP_EOL;
            }
        }
        echo "PASS staff roles, duplicate email, password policy and session revocation\n";
        echo "PASS settings encryption, masking, retain/remove, revisions and validation\n";
        echo "PASS mocked SMTP, SMS and WhatsApp transports; no live messages sent\n";
    } finally {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION = $session;
    }
    check($pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll() === $snapshot, 'QA settings were not restored.');
    check((int) $pdo->query('SELECT COUNT(*) FROM staff_users')->fetchColumn() === $staffCount, 'QA staff accounts were not rolled back.');
    echo "PASS all QA database changes rolled back\n";
}
