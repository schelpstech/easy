<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

function socialCheck(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
$defaults = [
    'TIKTOK_URL' => 'https://www.tiktok.com/@easyway_logistics_?_r=1&_t=ZS-99KHmZfBk4D',
    'INSTAGRAM_URL' => 'https://www.instagram.com/easywaylogistics',
    'FACEBOOK_URL' => 'https://www.facebook.com/share/19PjTGGdLC/',
];
$environment = [];
foreach ($defaults as $key => $url) { $environment[$key] = getenv($key); putenv($key . '=' . $url); }
$session = $_SESSION; $get = $_GET; $server = $_SERVER;
try {
    $_SESSION = []; $_GET = [];
    socialCheck(array_column(social_media_links(), 'url') === array_values($defaults), 'Supplied social URLs were not preserved.');
    $icons = file_get_contents(EASYWAY_ROOT . '/assets/css/bootstrap-icons.css');
    foreach (social_media_links() as $link) { socialCheck(str_contains($icons, '.' . $link['icon'] . '::before'), 'Missing icon for ' . $link['name']); }
    foreach (['index.php','about.php','services.php','destinations.php','cargo-services.php','packaging-materials.php','contact.php','quote.php','tracking.php','calculator.php','customer/login.php'] as $page) {
        $_SERVER['SCRIPT_NAME'] = '/' . $page; $_SERVER['REQUEST_METHOD'] = 'GET';
        $html = (static function (string $page): string { ob_start(); require EASYWAY_ROOT . '/' . $page; return (string) ob_get_clean(); })($page);
        $dom = new DOMDocument(); $prior = libxml_use_internal_errors(true); $dom->loadHTML($html); libxml_clear_errors(); libxml_use_internal_errors($prior);
        $xpath = new DOMXPath($dom);
        foreach (social_media_links() as $link) {
            $found = [];
            foreach ($xpath->query('//a[@href]') as $anchor) {
                if ($anchor->getAttribute('href') === $link['url']) { $found[] = $anchor; }
            }
            socialCheck(count($found) === ($page === 'contact.php' ? 2 : 1), $page . ': missing or duplicate ' . $link['name'] . ' placement.');
            foreach ($found as $anchor) {
                socialCheck($anchor->getAttribute('target') === '_blank', 'Social link did not open in a new tab.');
                $rel = explode(' ', $anchor->getAttribute('rel'));
                socialCheck(in_array('noopener',$rel,true) && in_array('noreferrer',$rel,true), 'Unsafe external link.');
                socialCheck(str_contains($anchor->getAttribute('aria-label'), $link['name']), 'Social link has no accessible name.');
            }
        }
        socialCheck(str_contains($html, 'https://wa.me/'), $page . ': WhatsApp link was removed.');
        echo 'PASS social links, accessible labels and WhatsApp preserved: ' . $page . PHP_EOL;
    }
    putenv('TIKTOK_URL=javascript:alert(1)'); putenv('INSTAGRAM_URL='); putenv('FACEBOOK_URL=http://www.facebook.com/example');
    socialCheck(social_media_links() === [], 'Unsafe or disabled social links were displayed.');
    ob_start(); require EASYWAY_ROOT . '/app/views/partials/social-links.php'; $empty = ob_get_clean();
    socialCheck(trim($empty) === '', 'Empty social navigation was rendered.');
    echo "PASS empty/invalid configuration hidden; no database changes or external requests\n";
} finally {
    foreach ($environment as $key => $value) { putenv($value === false ? $key : $key . '=' . $value); }
    $_SESSION = $session; $_GET = $get; $_SERVER = $server;
}
