<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    private const SESSION_KEY = 'easyway_staff';
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    /** @return array{id:int,name:string,email:string,role:string}|null */
    public static function user(): ?array
    {
        $user = $_SESSION[self::SESSION_KEY] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user === null ? null : (int) $user['id'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = mb_strtolower(trim($email));
        $pdo = Database::connection();

        $attemptStatement = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE email = :email AND ip_address = :ip_address
               AND was_successful = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)'
        );
        $attemptStatement->execute(['email' => $email, 'ip_address' => request_ip()]);
        if ((int) $attemptStatement->fetchColumn() >= self::MAX_ATTEMPTS) {
            return false;
        }

        $statement = $pdo->prepare(
            'SELECT id, full_name, email, password_hash, role
             FROM staff_users WHERE email = :email AND status = "active" LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        $success = is_array($user) && password_verify($password, (string) $user['password_hash']);

        $log = $pdo->prepare(
            'INSERT INTO login_attempts (email, ip_address, was_successful, attempted_at)
             VALUES (:email, :ip_address, :was_successful, NOW())'
        );
        $log->execute([
            'email' => $email,
            'ip_address' => request_ip(),
            'was_successful' => $success ? 1 : 0,
        ]);

        if (!$success) {
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $rehash = $pdo->prepare('UPDATE staff_users SET password_hash = :password_hash WHERE id = :id');
            $rehash->execute(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['full_name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
        $pdo->prepare('UPDATE staff_users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $user['id']]);
        Csrf::rotate();
        AuditService::record('staff.login', 'staff_user', (int) $user['id']);

        return true;
    }

    public static function logout(): void
    {
        $userId = self::id();
        if ($userId !== null) {
            AuditService::record('staff.logout', 'staff_user', $userId);
        }
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
        Csrf::rotate();
    }

    public static function requireStaff(): void
    {
        if (!self::check()) {
            Flash::set('warning', 'Please sign in to continue.');
            redirect('staff/login.php');
        }
    }

    /** @param array<int, string> $roles */
    public static function requireRole(array $roles): void
    {
        self::requireStaff();
        $user = self::user();
        if ($user === null || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            exit('You are not authorized to perform this action.');
        }
    }
}

