<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
use App\Database;

// Run against a local development server only. No provider settings or outbox writes.
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8099', '/');
if (!preg_match('#^http://(?:127\.0\.0\.1|localhost)(?::[0-9]+)?(?:/[a-zA-Z0-9_-]+)*$#D', $base)) {
    throw new RuntimeException('Use a loopback development URL only.');
}
function ensure(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
function client(): CurlHandle {
    $handle = curl_init();
    curl_setopt_array($handle, [CURLOPT_COOKIEFILE => '', CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROXY => '', CURLOPT_TIMEOUT => 15]);
    return $handle;
}
function request(CurlHandle $handle, string $path, ?array $fields = null): array {
    global $base;
    curl_setopt($handle, CURLOPT_URL, $base . '/' . ltrim($path, '/'));
    if ($fields === null) { curl_setopt($handle, CURLOPT_HTTPGET, true); }
    else { curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields)]); }
    $body = curl_exec($handle);
    ensure(is_string($body), 'Local HTTP request failed.');
    return ['status' => curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'redirect' => curl_getinfo($handle, CURLINFO_REDIRECT_URL)];
}
function token(array $response): string {
    ensure((bool) preg_match('/name="_token" value="([^"]+)"/', $response['body'], $match), 'Form has no CSRF token.');
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}
function login(CurlHandle $handle, string $email, string $password): void {
    $csrf = token(request($handle, 'staff/login.php'));
    $response = request($handle, 'controller/router.php?action=staff.login', ['_token' => $csrf, 'email' => $email, 'password' => $password]);
    ensure($response['status'] === 303 && !str_ends_with($response['redirect'], '/login.php'), 'QA staff sign-in failed.');
}

$pdo = Database::connection();
$settings = $pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll();
$staffCount = (int) $pdo->query('SELECT COUNT(*) FROM staff_users')->fetchColumn();
$suffix = bin2hex(random_bytes(8));
$password = 'Http-QA-' . bin2hex(random_bytes(15));
$newPassword = 'Http-Changed-' . bin2hex(random_bytes(15));
$emails = [];
foreach (['admin', 'dispatcher', 'rider', 'created'] as $role) { $emails[$role] = 'settings-http-' . $role . '-' . $suffix . '@example.test'; }
$clients = [];
try {
    foreach (['admin', 'dispatcher', 'rider'] as $role) {
        $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status) VALUES (:name,:email,:hash,:role,"active")')
            ->execute(['name' => 'Settings HTTP QA', 'email' => $emails[$role], 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
    }
    $anonymous = $clients[] = client();
    foreach (['staff/accounts.php', 'staff/password.php', 'staff/settings.php'] as $page) {
        $response = request($anonymous, $page);
        ensure($response['status'] === 303 && str_ends_with($response['redirect'], '/staff/login.php'), 'Anonymous access was allowed.');
    }
    $actions = ['staff.account.create', 'staff.password.change', 'staff.notification_settings.save', 'staff.notification_settings.test'];
    foreach ($actions as $action) {
        ensure(request($anonymous, 'controller/router.php?action=' . $action)['status'] === 405, 'GET mutation was allowed.');
        ensure(request($anonymous, 'controller/router.php?action=' . $action, [])['status'] === 419, 'Missing CSRF token was allowed.');
    }
    foreach (['dispatcher', 'rider'] as $role) {
        $handle = $clients[] = client(); login($handle, $emails[$role], $password);
        $page = request($handle, 'staff/password.php');
        ensure($page['status'] === 200 && str_contains($page['body'], 'Keep your account secure'), 'Staff cannot access own password form.');
        foreach (['staff/accounts.php', 'staff/settings.php'] as $path) { ensure(request($handle, $path)['status'] === 403, 'Non-admin page access allowed.'); }
        foreach (['staff.account.create', 'staff.notification_settings.save', 'staff.notification_settings.test'] as $action) {
            ensure(request($handle, 'controller/router.php?action=' . $action, ['_token' => token($page)])['status'] === 403, 'Non-admin POST access allowed.');
        }
    }
    $admin = $clients[] = client(); login($admin, $emails['admin'], $password);
    foreach (['accounts.php', 'password.php', 'settings.php?channel=email', 'settings.php?channel=sms', 'settings.php?channel=whatsapp'] as $path) {
        $page = request($admin, 'staff/' . $path);
        ensure($page['status'] === 200 && !preg_match('/(?:Fatal error|Warning:|Notice:)/', $page['body']), 'Admin form failed to render.');
        ensure(!str_contains($page['body'], $password), 'A form exposed the account password.');
    }
    $csrf = token(request($admin, 'staff/accounts.php'));
    $fields = ['_token' => $csrf, 'full_name' => 'HTTP Created QA', 'email' => $emails['created'], 'role' => 'dispatcher',
        'password' => $password, 'password_confirmation' => $password, 'current_password' => $password];
    ensure(request($admin, 'controller/router.php?action=staff.account.create', $fields)['status'] === 303, 'Staff creation did not redirect.');
    ensure(str_contains(request($admin, 'staff/accounts.php')['body'], 'Staff account created.'), 'Staff creation failed.');
    $created = $clients[] = client(); login($created, $emails['created'], $password);
    request($admin, 'controller/router.php?action=staff.account.create', $fields);
    ensure(str_contains(request($admin, 'staff/accounts.php')['body'], 'already uses that email'), 'Duplicate account error missing.');
    // Deliberately invalid settings/test requests must not mutate config or call a provider.
    $csrf = token(request($admin, 'staff/settings.php'));
    request($admin, 'controller/router.php?action=staff.notification_settings.save', ['_token' => $csrf, 'channel' => 'invalid', 'current_password' => $password]);
    ensure(str_contains(request($admin, 'staff/settings.php')['body'], 'Unknown notification channel'), 'Invalid channel was accepted.');
    request($admin, 'controller/router.php?action=staff.notification_settings.test', ['_token' => $csrf, 'channel' => 'sms', 'recipient' => 'invalid', 'current_password' => $password]);
    ensure(str_contains(request($admin, 'staff/settings.php')['body'], 'valid test email or international phone number'), 'Invalid test recipient was accepted.');
    $oldSession = $clients[] = client(); login($oldSession, $emails['admin'], $password);
    $csrf = token(request($admin, 'staff/password.php'));
    $response = request($admin, 'controller/router.php?action=staff.password.change', ['_token' => $csrf,
        'current_password' => $password, 'password' => $newPassword, 'password_confirmation' => $newPassword]);
    ensure($response['status'] === 303 && str_ends_with($response['redirect'], '/staff/login.php'), 'Password change did not sign out.');
    ensure(request($oldSession, 'staff/accounts.php')['status'] === 303, 'Other HTTP session survived password change.');
    login($admin, $emails['admin'], $newPassword);
    echo "PASS HTTP login/create/change-password flows; concurrent session revoked\n";
    echo "PASS anonymous, role, method and CSRF guards; all forms render\n";
} finally {
    foreach ($clients as $handle) { curl_close($handle); }
    foreach ($emails as $email) {
        $lookup = $pdo->prepare('SELECT id FROM staff_users WHERE email=:email'); $lookup->execute(['email' => $email]);
        foreach ($lookup->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $pdo->prepare('DELETE FROM audit_logs WHERE staff_user_id=:id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM staff_users WHERE id=:id')->execute(['id' => $id]);
        }
        $pdo->prepare('DELETE FROM login_attempts WHERE email=:email')->execute(['email' => $email]);
    }
}
ensure($pdo->query('SELECT * FROM notification_settings ORDER BY channel')->fetchAll() === $settings, 'Provider settings changed.');
ensure((int) $pdo->query('SELECT COUNT(*) FROM staff_users')->fetchColumn() === $staffCount, 'QA accounts not cleaned.');
echo "PASS QA accounts removed; provider settings unchanged; no messages sent\n";
