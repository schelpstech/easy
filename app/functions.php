<?php

declare(strict_types=1);

use App\Config;
use App\Csrf;
use App\Flash;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url_path(): string
{
    $configured = trim((string) Config::get('APP_URL', ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    foreach (['/staff/', '/customer/', '/rider/', '/api/', '/controller/', '/webhooks/', '/tools/'] as $marker) {
        $position = strpos($script, $marker);
        if ($position !== false) {
            return rtrim(substr($script, 0, $position), '/');
        }
    }

    $directory = str_replace('\\', '/', dirname($script));
    return $directory === '/' ? '' : rtrim($directory, '/');
}

function staff_home_path(): string
{
    $user = \App\Auth::user();
    return ($user['role'] ?? '') === 'rider' ? 'rider/index.php' : 'staff/index.php';
}

function url(string $path = ''): string
{
    $base = base_url_path();
    $path = ltrim($path, '/');
    return $base . ($path === '' ? '/' : '/' . $path);
}

function absolute_url(string $path = ''): string
{
    $configured = rtrim((string) Config::get('APP_URL', ''), '/');
    if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
        return $configured . '/' . ltrim($path, '/');
    }
    $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return $scheme . '://' . ($host ?: 'localhost') . url($path);
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function request_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
    return mb_substr($ip, 0, 45);
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed.');
    }
}

function require_csrf(): void
{
    if (!Csrf::validate($_POST['_token'] ?? null)) {
        http_response_code(419);
        exit('Your session expired. Please return to the form and try again.');
    }
}

function submission_allowed(string $key, int $seconds = 5): bool
{
    $sessionKey = 'easyway_submit_' . preg_replace('/[^a-z0-9_]/i', '', $key);
    $lastSubmitted = (int) ($_SESSION[$sessionKey] ?? 0);
    if ($lastSubmitted > 0 && (time() - $lastSubmitted) < $seconds) {
        return false;
    }
    $_SESSION[$sessionKey] = time();
    return true;
}

function flash_messages(): array
{
    return Flash::pull();
}

/** @param array<string, mixed> $data @param array<string, string> $errors */
function store_form_state(string $key, array $data, array $errors): void
{
    $_SESSION['easyway_form_state'][$key] = ['data' => $data, 'errors' => $errors];
}

/** @return array{data:array<string,mixed>,errors:array<string,string>} */
function pull_form_state(string $key): array
{
    $state = $_SESSION['easyway_form_state'][$key] ?? ['data' => [], 'errors' => []];
    unset($_SESSION['easyway_form_state'][$key]);
    return is_array($state) ? $state : ['data' => [], 'errors' => []];
}

function form_value(array $state, string $key, string $default = ''): string
{
    return e($state['data'][$key] ?? $default);
}

function form_error(array $state, string $key): string
{
    $message = $state['errors'][$key] ?? '';
    return $message === '' ? '' : '<div class="invalid-feedback d-block">' . e($message) . '</div>';
}

function flash_markup(): string
{
    $output = '';
    foreach (flash_messages() as $message) {
        $type = in_array($message['type'], ['success', 'danger', 'warning', 'info'], true) ? $message['type'] : 'info';
        $output .= '<div class="container mt-3"><div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">'
            . e($message['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div></div>';
    }
    return $output;
}

function support_phone(): string
{
    return (string) Config::get('SUPPORT_PHONE', '+234 903 113 4210');
}

function support_phone_secondary(): string
{
    return (string) Config::get('SUPPORT_PHONE_SECONDARY', '+234 808 713 7894');
}

/** @return list<string> */
function support_phones(): array
{
    return array_values(array_unique(array_filter(
        [support_phone(), support_phone_secondary()],
        static fn (string $phone): bool => trim($phone) !== ''
    )));
}

function phone_href(string $phone): string
{
    return (string) preg_replace('/[^\d+]+/', '', $phone);
}

function company_address(): string
{
    return (string) Config::get(
        'COMPANY_ADDRESS',
        'Shop 39, Stakeholder Park, International Airport, after International Airport Central Mosque, Ikeja, Lagos State, Nigeria'
    );
}

function support_email(): string
{
    return (string) Config::get('SUPPORT_EMAIL', 'support@easyway.ng');
}

function whatsapp_url(string $message = 'Hello Easyway Logistics, I would like to make an enquiry.'): string
{
    $number = preg_replace('/\D+/', '', (string) Config::get('WHATSAPP_NUMBER', '2349031134210'));
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
}
