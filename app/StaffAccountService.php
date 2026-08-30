<?php

declare(strict_types=1);

namespace App;

use PDOException;
use RuntimeException;

final class StaffAccountService
{
    public static function requireAdmin(): void
    {
        if ((Auth::user()['role'] ?? '') !== 'admin') {
            throw new RuntimeException('Only administrators can manage staff and delivery settings.');
        }
    }

    public static function validatePassword(string $password, string $confirmation): void
    {
        if (mb_strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new RuntimeException('Use at least 12 characters and no more than 72 bytes for the password.');
        }
        if (!hash_equals($password, $confirmation)) {
            throw new RuntimeException('The new passwords do not match.');
        }
    }

    public static function all(): array
    {
        self::requireAdmin();
        return Database::connection()->query('SELECT id, full_name, email, role, status, last_login_at, created_at FROM staff_users ORDER BY created_at DESC, id DESC')->fetchAll();
    }

    public static function create(array $input): int
    {
        self::requireAdmin();
        $name = Validator::text($input['full_name'] ?? '', 120);
        $email = Validator::email($input['email'] ?? '');
        $role = Validator::choice($input['role'] ?? '', ['admin', 'dispatcher']);
        if (mb_strlen($name) < 2 || $email === '' || strlen($email) > 190 || $role === '') {
            throw new RuntimeException('Enter a full name, valid email, and an administrator or dispatcher role.');
        }
        $password = (string) ($input['password'] ?? '');
        self::validatePassword($password, (string) ($input['password_confirmation'] ?? ''));
        if (!Auth::verifyPassword((string) ($input['current_password'] ?? ''))) {
            throw new RuntimeException('Your current password is incorrect or verification is temporarily locked.');
        }
        $pdo = Database::connection();
        try {
            $pdo->prepare('INSERT INTO staff_users (full_name,email,password_hash,role,status,created_at,updated_at) VALUES (:name,:email,:hash,:role,"active",NOW(),NOW())')
                ->execute(['name' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) { throw new RuntimeException('A staff account already uses that email address.'); }
            throw new RuntimeException('The staff account could not be created. Please try again.');
        }
        $id = (int) $pdo->lastInsertId();
        AuditService::record('staff.account_created', 'staff_user', $id, ['role' => $role]);
        return $id;
    }

    public static function changePassword(string $current, string $password, string $confirmation): void
    {
        $user = Auth::user();
        if ($user === null) { throw new RuntimeException('Please sign in again.'); }
        self::validatePassword($password, $confirmation);
        if (!Auth::verifyPassword($current)) { throw new RuntimeException('Your current password is incorrect or verification is temporarily locked.'); }
        if (hash_equals($current, $password)) { throw new RuntimeException('Choose a different password from your current one.'); }
        $pdo = Database::connection();
        $statement = $pdo->prepare('SELECT password_hash FROM staff_users WHERE id = :id');
        $statement->execute(['id' => $user['id']]);
        $oldHash = (string) $statement->fetchColumn();
        // Compare-and-swap also rejects a concurrent password change.
        if (!password_verify($current, $oldHash)) { throw new RuntimeException('Your password changed in another session. Please sign in again.'); }
        AuditService::record('staff.password_changed', 'staff_user', (int) $user['id']);
        $update = $pdo->prepare('UPDATE staff_users SET password_hash = :new, updated_at = NOW() WHERE id = :id AND password_hash = :old');
        $update->execute(['new' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id'], 'old' => $oldHash]);
        if ($update->rowCount() !== 1) { throw new RuntimeException('Your password changed in another session. Please sign in again.'); }
        Auth::logout();
    }
}
