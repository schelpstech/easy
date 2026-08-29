<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\ReportService;

Auth::requireRole(['admin', 'dispatcher']);
$report = ReportService::dashboard($_GET['from'] ?? null, $_GET['to'] ?? null, (string) ($_GET['currency'] ?? 'NGN'));
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="easyway-operations-' . $report['range']['from'] . '-to-' . $report['range']['to'] . '.csv"');
header('X-Content-Type-Options: nosniff');
$output = fopen('php://output', 'wb');
if ($output === false) { http_response_code(500); exit; }
fputcsv($output, ['Easyway operations report', $report['range']['from'], $report['range']['to'], $report['currency']]);
fputcsv($output, []); fputcsv($output, ['Metric', 'Value']);
foreach ($report['metrics'] as $metric => $value) { fputcsv($output, [ucwords(str_replace('_', ' ', $metric)), $value]); }
fputcsv($output, []); fputcsv($output, ['Date', 'Booking count', 'Booking value']);
foreach ($report['daily'] as $day) { fputcsv($output, [$day['day'], $day['booking_count'], $day['booking_value']]); }
fclose($output);
