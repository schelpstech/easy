<?php

declare(strict_types=1);

namespace App;

use Throwable;

final class AuditService
{
    /** @param array<string, mixed> $context */
    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, array $context = []): void
    {
        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO audit_logs (staff_user_id, action, entity_type, entity_id, context_json, ip_address, created_at)
                 VALUES (:staff_user_id, :action, :entity_type, :entity_id, :context_json, :ip_address, NOW())'
            );
            $statement->execute([
                'staff_user_id' => Auth::id(),
                'action' => mb_substr($action, 0, 120),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'context_json' => $context === [] ? null : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'ip_address' => request_ip(),
            ]);
        } catch (Throwable $exception) {
            error_log('Easyway audit write failed: ' . $exception->getMessage());
        }
    }
}

