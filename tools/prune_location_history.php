<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Config;
use App\Database;

$days = filter_var(Config::get('RIDER_LOCATION_RETENTION_DAYS', '30'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 365]]);
if ($days === false) { fwrite(STDERR, "RIDER_LOCATION_RETENTION_DAYS must be between 1 and 365.\n"); exit(1); }
$statement = Database::connection()->prepare('DELETE FROM rider_location_pings WHERE recorded_at < DATE_SUB(NOW(), INTERVAL :days DAY)');
$statement->bindValue('days', (int) $days, PDO::PARAM_INT);
$statement->execute();
echo 'Pruned ' . $statement->rowCount() . ' rider location pings older than ' . $days . " days.\n";
