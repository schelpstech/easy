<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\NotificationService;
use App\NotificationSettings;
use App\NotificationTransport;

$arguments = array_slice($argv, 1);
if ($arguments === ['--help']) {
    echo "Usage: php tools/process_notifications.php [--check|--help]\nWithout options: process the pending queue.\n--check: inspect saved settings and this PHP runtime; no messages sent or queue changes.\n";
    exit;
}
if ($arguments !== [] && $arguments !== ['--check']) {
    fwrite(STDERR, "Unknown option. Use --check for non-sending diagnostics, or --help. Nothing was sent.\n");
    exit(64);
}
if ($arguments === ['--check']) {
    $report = ['php_version' => PHP_VERSION, 'php_binary' => PHP_BINARY, 'php_ini' => php_ini_loaded_file() ?: '(none)',
        'mail_available' => function_exists('mail'), 'sends_messages' => false, 'channels' => []];
    $blocked = false;
    foreach (NotificationSettings::CHANNELS as $channel) {
        $details = ['enabled' => null, 'ready' => false];
        try {
            $settings = NotificationSettings::get($channel);
            $details['enabled'] = (bool) $settings['enabled'];
            $details['source'] = $settings['version'] > 0 ? 'saved database settings' : 'environment defaults';
            if ($channel === 'email') {
                $details['transport'] = $settings['transport'];
                if ($settings['transport'] === 'mail') { $details['note'] = 'Server mail uses PHP mail(); any SMTP fields are ignored.'; }
            }
            NotificationSettings::validate($channel, NotificationSettings::get($channel, true));
            NotificationTransport::assertRuntimeAvailable($channel, $settings);
            $details['ready'] = true;
            $details['message'] = 'Local prerequisites pass. Provider connection, credentials and delivery are not tested.';
        } catch (\PDOException) {
            $details['message'] = 'Could not read settings. Check the database configuration used by this PHP runtime.';
        } catch (\RuntimeException $exception) {
            $details['message'] = $exception->getMessage();
        } catch (\Throwable) {
            $details['message'] = 'Could not inspect notification settings. Check the installation and PHP runtime.';
        }
        if ($details['enabled'] !== false && !$details['ready']) { $blocked = true; }
        $report['channels'][$channel] = $details;
    }
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit($blocked ? 1 : 0);
}

$result = NotificationService::dispatchPending(50);
echo sprintf("Notifications: %d sent, %d failed, %d waiting for provider configuration.\n", $result['sent'], $result['failed'], $result['waiting']);
