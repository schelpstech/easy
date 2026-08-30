<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class RateCatalogService
{
    public static function installed(): bool
    {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'rate_services'")->fetchColumn() > 0;
    }

    public static function assertAdmin(int $staffId): void
    {
        $statement = Database::connection()->prepare('SELECT id FROM staff_users WHERE id = ? AND role = "admin" AND status = "active"');
        $statement->execute([$staffId]);
        if (!$statement->fetchColumn()) { throw new RuntimeException('Only an active administrator can manage rates.'); }
    }

    public static function table(string $kind): string
    {
        return match ($kind) {
            'zone' => 'rate_zones',
            'service' => 'rate_services',
            default => throw new RuntimeException('Choose a valid catalogue.'),
        };
    }

    public static function all(string $kind): array
    {
        if ($kind === 'service' && !self::installed()) { return []; }
        return Database::connection()->query('SELECT * FROM ' . self::table($kind) . ' ORDER BY status = "active" DESC, name, id')->fetchAll();
    }

    public static function find(string $kind, int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM ' . self::table($kind) . ' WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    /** A content fingerprint catches edits even when timestamps share a second. */
    public static function version(array $row): string
    {
        $fields = array_intersect_key($row, array_flip([
            'id','code','name','country_code','status','origin_zone_id','destination_zone_id','service_code',
            'service_name','currency','base_fee','base_weight_kg','extra_kg_fee','minimum_fee','fuel_percent',
            'insurance_percent','packaging_fee','tax_percent','volumetric_divisor','estimated_days_min','estimated_days_max','updated_at',
        ]));
        ksort($fields);
        return hash('sha256', json_encode($fields, JSON_THROW_ON_ERROR));
    }

    public static function text(mixed $value, int $max, string $label): string
    {
        if (!is_scalar($value) || !mb_check_encoding((string) $value, 'UTF-8')) { throw new RuntimeException('Enter a valid ' . $label . '.'); }
        $value = trim((string) $value);
        if ($value === '' || mb_strlen($value) > $max || preg_match('/[\x00-\x1f\x7f]/u', $value)) {
            throw new RuntimeException('Enter a valid ' . $label . ' (up to ' . $max . ' characters).');
        }
        return $value;
    }

    public static function id(mixed $value, bool $allowZero = false): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $allowZero ? 0 : 1]]);
        if ($id === false) { throw new RuntimeException('Choose a valid record.'); }
        return $id;
    }

    public static function assertVersion(array $row, mixed $version): void
    {
        if (!is_string($version) || !hash_equals(self::version($row), $version)) {
            throw new RuntimeException('This record changed in another session. Reload it before saving again.');
        }
    }

    public static function save(string $kind, array $input, int $staffId): int
    {
        self::assertAdmin($staffId);
        $table = self::table($kind);
        if ($kind === 'service' && !self::installed()) { throw new RuntimeException('Run php tools/install_rate_management.php to enable service management.'); }
        $id = self::id($input['id'] ?? 0, true);
        $name = self::text($input['name'] ?? '', $kind === 'zone' ? 120 : 80, 'name');
        $code = self::text($input['code'] ?? '', 40, 'code');
        $code = $kind === 'zone' ? strtoupper($code) : strtolower($code);
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{1,39}$/D', $code)) { throw new RuntimeException('Use a 2–40 character code starting with a letter; letters, numbers, hyphens and underscores only.'); }
        $status = self::text($input['status'] ?? 'active', 20, 'status');
        if (!in_array($status, ['active','inactive'], true)) { throw new RuntimeException('Choose Active or Inactive.'); }
        $country = $kind === 'zone' ? strtoupper(self::text($input['country_code'] ?? 'NG', 2, 'two-letter country code')) : null;
        if ($country !== null && !preg_match('/^[A-Z]{2}$/D', $country)) { throw new RuntimeException('Enter a two-letter country code, such as NG, GB or US; use ZZ for a mixed-country zone.'); }
        $pdo = Database::connection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) { $pdo->beginTransaction(); }
        try {
            if ($id > 0) {
                $query = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE id = ? FOR UPDATE');
                $query->execute([$id]); $existing = $query->fetch();
                if (!is_array($existing)) { throw new RuntimeException('This record no longer exists.'); }
                self::assertVersion($existing, $input['version'] ?? null);
                if (strcasecmp((string) $existing['code'], $code) !== 0) { throw new RuntimeException('Codes cannot be changed after creation. Add a new record if a different code is needed.'); }
                $code = (string) $existing['code'];
            }
            $duplicate = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE id <> :id AND (code = :code OR (name = :name' . ($kind === 'zone' ? ' AND country_code = :country' : '') . ')) LIMIT 1');
            $params = ['id' => $id, 'code' => $code, 'name' => $name];
            if ($kind === 'zone') { $params['country'] = $country; }
            $duplicate->execute($params);
            if ($duplicate->fetchColumn()) { throw new RuntimeException('That code or name already exists. Edit the existing entry instead.'); }
            $params = ['name' => $name, 'status' => $status];
            if ($kind === 'zone') { $params['country'] = $country; }
            $countrySql = $kind === 'zone' ? ', country_code = :country' : '';
            if ($id > 0) {
                $params['id'] = $id;
                $pdo->prepare('UPDATE ' . $table . ' SET name = :name, status = :status' . $countrySql . ', updated_at = NOW() WHERE id = :id')->execute($params);
            } else {
                $params['code'] = $code;
                $pdo->prepare('INSERT INTO ' . $table . ' SET code = :code, name = :name, status = :status' . $countrySql . ', created_at = NOW(), updated_at = NOW()')->execute($params);
                $id = (int) $pdo->lastInsertId();
            }
            AuditService::record('pricing.' . $kind . '_saved', $kind === 'zone' ? 'rate_zone' : 'rate_service', $id, ['code' => $code, 'status' => $status, 'actor_id' => $staffId]);
            if ($ownsTransaction) { $pdo->commit(); }
            return $id;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
            if ($exception instanceof PDOException && (int) ($exception->errorInfo[1] ?? 0) === 1062) { throw new RuntimeException('That code already exists. Edit the existing entry instead.'); }
            throw $exception;
        }
    }
}
