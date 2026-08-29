<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Database;
use App\Validator;

$options = getopt('', ['email:', 'name:', 'role::']);
$email = Validator::email($options['email'] ?? '');
$name = Validator::text($options['name'] ?? '', 120);
$role = Validator::choice($options['role'] ?? 'admin', ['admin', 'dispatcher'], 'admin');
$password = getenv('EASYWAY_NEW_STAFF_PASSWORD');

if ($email === '' || mb_strlen($name) < 2 || !is_string($password) || strlen($password) < 12) {
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  Set EASYWAY_NEW_STAFF_PASSWORD to a 12+ character password, then run:\n");
    fwrite(STDERR, "  php tools/create_staff.php --email=oneway@easyway.ng --name=\"Temitope Alofe\" --role=admin\n");
    exit(1);
}

try {
    $statement = Database::connection()->prepare(
        'INSERT INTO staff_users (full_name, email, password_hash, role, status, created_at, updated_at)
         VALUES (:full_name, :email, :password_hash, :role, "active", NOW(), NOW())'
    );
    $statement->execute([
        'full_name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);
    echo "Staff account created for {$email}.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Could not create staff account: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

