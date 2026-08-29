<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class CustomerAuth
{
    private const SESSION_KEY = 'easyway_customer';
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    /** @return array{id:int,name:string,email:string,phone:string}|null */
    public static function user(): ?array
    {
        $user = $_SESSION[self::SESSION_KEY] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::SESSION_KEY]['id']) ? (int) $_SESSION[self::SESSION_KEY]['id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function register(string $name, string $email, string $phone, string $password): int
    {
        $pdo = Database::connection();
        $exists = $pdo->prepare('SELECT 1 FROM customer_users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetchColumn()) {
            throw new RuntimeException('An account already exists for that email address. Please sign in instead.');
        }

        $statement = $pdo->prepare(
            'INSERT INTO customer_users (full_name, email, phone, password_hash, status, created_at, updated_at)
             VALUES (:full_name, :email, :phone, :password_hash, "active", NOW(), NOW())'
        );
        $statement->execute([
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $customerId = (int) $pdo->lastInsertId();
        self::startSession($customerId, $name, $email, $phone);
        AuditService::record('customer.registered', 'customer_user', $customerId);
        return $customerId;
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = mb_strtolower(trim($email));
        $pdo = Database::connection();
        $attempts = $pdo->prepare(
            'SELECT COUNT(*) FROM customer_login_attempts
             WHERE email = :email AND ip_address = :ip AND was_successful = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)'
        );
        $attempts->execute(['email' => $email, 'ip' => request_ip()]);
        if ((int) $attempts->fetchColumn() >= self::MAX_ATTEMPTS) {
            return false;
        }

        $statement = $pdo->prepare(
            'SELECT id, full_name, email, phone, password_hash FROM customer_users
             WHERE email = :email AND status = "active" LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $customer = $statement->fetch(PDO::FETCH_ASSOC);
        $success = is_array($customer) && password_verify($password, (string) $customer['password_hash']);

        $log = $pdo->prepare(
            'INSERT INTO customer_login_attempts (email, ip_address, was_successful, attempted_at)
             VALUES (:email, :ip, :success, NOW())'
        );
        $log->execute(['email' => $email, 'ip' => request_ip(), 'success' => $success ? 1 : 0]);
        if (!$success) {
            return false;
        }

        if (password_needs_rehash((string) $customer['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE customer_users SET password_hash = :hash WHERE id = :id')->execute([
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $customer['id'],
            ]);
        }
        $pdo->prepare('UPDATE customer_users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $customer['id']]);
        self::startSession((int) $customer['id'], (string) $customer['full_name'], (string) $customer['email'], (string) $customer['phone']);
        AuditService::record('customer.login', 'customer_user', (int) $customer['id']);
        return true;
    }

    public static function logout(): void
    {
        $customerId = self::id();
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
        Csrf::rotate();
        if ($customerId !== null) {
            AuditService::record('customer.logout', 'customer_user', $customerId);
        }
    }

    public static function requireCustomer(): void
    {
        if (!self::check()) {
            Flash::set('warning', 'Please sign in to manage your bookings.');
            redirect('customer/login.php');
        }
    }

    private static function startSession(int $id, string $name, string $email, string $phone): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone];
        Csrf::rotate();
    }
}
