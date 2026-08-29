<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class BulkShipmentService
{
    private const MAX_ROWS = 500;
    private const MAX_BYTES = 2097152;
    private const HEADERS = [
        'pickup_address_id', 'delivery_address_id', 'origin_zone_id', 'destination_zone_id', 'service_code',
        'package_description', 'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'declared_value',
        'packaging_required', 'is_fragile',
    ];

    /** @param array<string, mixed> $upload */
    public static function importUpload(int $accountId, int $customerId, array $upload): int
    {
        if ((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Choose a CSV file to upload.');
        }
        $path = (string) ($upload['tmp_name'] ?? '');
        $size = (int) ($upload['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_BYTES || !is_uploaded_file($path)) {
            throw new RuntimeException('The CSV must be a valid upload no larger than 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'], true)) {
            throw new RuntimeException('The bulk file must be CSV format.');
        }
        return self::importFile($accountId, $customerId, $path, (string) ($upload['name'] ?? 'bulk-shipments.csv'));
    }

    public static function importFile(int $accountId, int $customerId, string $path, string $filename = 'bulk-shipments.csv'): int
    {
        $account = CorporateService::findForCustomer($accountId, $customerId);
        if ($account === null || $account['status'] !== 'active') { throw new RuntimeException('Corporate account not found.'); }
        if (!is_file($path) || filesize($path) === false || filesize($path) > self::MAX_BYTES) { throw new RuntimeException('The bulk CSV is missing or too large.'); }
        $handle = fopen($path, 'rb');
        if ($handle === false) { throw new RuntimeException('Unable to read the bulk CSV.'); }
        $header = fgetcsv($handle);
        if (!is_array($header)) { fclose($handle); throw new RuntimeException('The bulk CSV is empty.'); }
        $header = array_map(static fn ($value): string => mb_strtolower(trim((string) $value, "\xEF\xBB\xBF \t\r\n")), $header);
        if ($header !== self::HEADERS) { fclose($handle); throw new RuntimeException('The CSV columns do not match the Easyway bulk template.'); }
        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($values, static fn ($value): bool => trim((string) $value) !== '')) === 0) { continue; }
            if (count($values) !== count(self::HEADERS)) { fclose($handle); throw new RuntimeException('CSV row ' . $line . ' has the wrong number of columns.'); }
            if (count($rows) >= self::MAX_ROWS) { fclose($handle); throw new RuntimeException('A bulk batch can contain at most ' . self::MAX_ROWS . ' shipments.'); }
            $rows[] = ['line' => $line, 'data' => array_combine(self::HEADERS, array_map(static fn ($value): string => trim((string) $value), $values))];
        }
        fclose($handle);
        if ($rows === []) { throw new RuntimeException('Add at least one shipment row to the CSV.'); }

        $batchNumber = 'BLK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO bulk_shipment_batches (batch_number, corporate_account_id, uploaded_by, source_filename,
                row_count, currency, status, created_at)
             VALUES (:number, :account, :customer, :filename, :rows, :currency, "processing", NOW())'
        )->execute([
            'number' => $batchNumber, 'account' => $accountId, 'customer' => $customerId,
            'filename' => mb_substr(basename($filename), 0, 190), 'rows' => count($rows), 'currency' => $account['currency'],
        ]);
        $batchId = (int) $pdo->lastInsertId();
        $success = 0; $failed = 0; $total = 0.0;
        foreach ($rows as $row) {
            $bookingId = null;
            try {
                $data = self::normalizeRow($row['data']);
                $bookingId = BookingService::create($data, $customerId);
                CorporateService::allocateBookingCredit($accountId, $bookingId, $customerId, $batchId);
                $booking = BookingService::find($bookingId);
                $amount = (float) ($booking['total_amount'] ?? 0);
                $pdo->prepare(
                    'INSERT INTO bulk_shipment_items (batch_id, source_line, booking_id, status, amount, source_json, created_at)
                     VALUES (:batch, :row, :booking, "created", :amount, :source, NOW())'
                )->execute(['batch' => $batchId, 'row' => $row['line'], 'booking' => $bookingId, 'amount' => $amount, 'source' => json_encode($row['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                $success++; $total += $amount;
            } catch (Throwable $exception) {
                if ($bookingId !== null) {
                    $pdo->prepare('UPDATE bookings SET status = "cancelled", updated_at = NOW() WHERE id = :id AND payment_status = "unpaid"')->execute(['id' => $bookingId]);
                    $pdo->prepare('UPDATE billing_documents SET status = "void" WHERE booking_id = :id AND document_type = "invoice"')->execute(['id' => $bookingId]);
                    $pdo->prepare('UPDATE notification_outbox SET status = "cancelled" WHERE booking_id = :id AND status = "pending"')->execute(['id' => $bookingId]);
                }
                $pdo->prepare(
                    'INSERT INTO bulk_shipment_items (batch_id, source_line, booking_id, status, error_message, amount, source_json, created_at)
                     VALUES (:batch, :row, :booking, "failed", :error, 0, :source, NOW())'
                )->execute([
                    'batch' => $batchId, 'row' => $row['line'], 'booking' => $bookingId,
                    'error' => mb_substr($exception->getMessage(), 0, 500),
                    'source' => json_encode($row['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
                $failed++;
            }
        }
        $status = $success === 0 ? 'failed' : ($failed > 0 ? 'completed_with_errors' : 'completed');
        $pdo->prepare(
            'UPDATE bulk_shipment_batches SET successful_count = :success, failed_count = :failed,
                total_amount = :total, status = :status, completed_at = NOW() WHERE id = :id'
        )->execute(['success' => $success, 'failed' => $failed, 'total' => round($total, 2), 'status' => $status, 'id' => $batchId]);
        return $batchId;
    }

    /** @return array<int, array<string, mixed>> */
    public static function batchesForCustomer(int $customerId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.*, a.company_name FROM bulk_shipment_batches b JOIN corporate_accounts a ON a.id = b.corporate_account_id
             JOIN corporate_members m ON m.corporate_account_id = a.id
             WHERE m.customer_id = :customer AND m.status = "active" ORDER BY b.created_at DESC'
        );
        $statement->execute(['customer' => $customerId]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function allBatches(): array
    {
        return Database::connection()->query(
            'SELECT b.*, a.company_name, c.full_name AS uploaded_by_name,
                    SUM(CASE WHEN i.booking_id IS NOT NULL AND bk.shipment_id IS NULL AND bk.payment_status = "corporate_credit" THEN 1 ELSE 0 END) AS awaiting_fulfilment,
                    SUM(CASE WHEN bk.shipment_id IS NOT NULL THEN 1 ELSE 0 END) AS shipment_count
             FROM bulk_shipment_batches b JOIN corporate_accounts a ON a.id = b.corporate_account_id
             JOIN customer_users c ON c.id = b.uploaded_by LEFT JOIN bulk_shipment_items i ON i.batch_id = b.id
             LEFT JOIN bookings bk ON bk.id = i.booking_id GROUP BY b.id, a.company_name, c.full_name
             ORDER BY b.created_at DESC LIMIT 300'
        )->fetchAll();
    }

    /** @return array{batch:array<string,mixed>,items:array<int,array<string,mixed>>}|null */
    public static function find(int $batchId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.*, a.company_name, c.full_name AS uploaded_by_name FROM bulk_shipment_batches b
             JOIN corporate_accounts a ON a.id = b.corporate_account_id JOIN customer_users c ON c.id = b.uploaded_by
             WHERE b.id = :batch LIMIT 1'
        );
        $statement->execute(['batch' => $batchId]);
        $batch = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($batch)) { return null; }
        $items = Database::connection()->prepare(
            'SELECT i.*, b.booking_number, b.status AS booking_status, b.payment_status, b.shipment_id, s.tracking_number
             FROM bulk_shipment_items i LEFT JOIN bookings b ON b.id = i.booking_id
             LEFT JOIN shipments s ON s.id = b.shipment_id WHERE i.batch_id = :batch ORDER BY i.source_line'
        );
        $items->execute(['batch' => $batchId]);
        return ['batch' => $batch, 'items' => $items->fetchAll()];
    }

    /** @return array{created:int,failed:int,skipped:int} */
    public static function convertBatch(int $batchId, int $staffId): array
    {
        $record = self::find($batchId);
        if ($record === null) { throw new RuntimeException('Bulk batch not found.'); }
        $result = ['created' => 0, 'failed' => 0, 'skipped' => 0];
        $pdo = Database::connection();
        foreach ($record['items'] as $item) {
            if (!$item['booking_id'] || $item['shipment_id'] || $item['payment_status'] !== 'corporate_credit' || $item['booking_status'] !== 'confirmed') {
                $result['skipped']++; continue;
            }
            try {
                BookingService::convertToShipment((int) $item['booking_id'], $staffId);
                $pdo->prepare('UPDATE bulk_shipment_items SET status = "shipment_created", error_message = NULL WHERE id = :id')->execute(['id' => $item['id']]);
                $result['created']++;
            } catch (Throwable $exception) {
                $pdo->prepare('UPDATE bulk_shipment_items SET status = "fulfilment_failed", error_message = :error WHERE id = :id')
                    ->execute(['error' => mb_substr($exception->getMessage(), 0, 500), 'id' => $item['id']]);
                $result['failed']++;
            }
        }
        AuditService::record('bulk.batch_converted', 'bulk_shipment_batch', $batchId, $result);
        return $result;
    }

    /** @return array{batch:array<string,mixed>,items:array<int,array<string,mixed>>}|null */
    public static function findForCustomer(int $batchId, int $customerId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.*, a.company_name FROM bulk_shipment_batches b JOIN corporate_accounts a ON a.id = b.corporate_account_id
             JOIN corporate_members m ON m.corporate_account_id = a.id
             WHERE b.id = :batch AND m.customer_id = :customer AND m.status = "active" LIMIT 1'
        );
        $statement->execute(['batch' => $batchId, 'customer' => $customerId]);
        $batch = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($batch)) { return null; }
        $items = Database::connection()->prepare(
            'SELECT i.*, b.booking_number, b.status AS booking_status FROM bulk_shipment_items i
             LEFT JOIN bookings b ON b.id = i.booking_id WHERE i.batch_id = :batch ORDER BY i.source_line'
        );
        $items->execute(['batch' => $batchId]);
        return ['batch' => $batch, 'items' => $items->fetchAll()];
    }

    /** @param array<string, string> $row @return array<string, mixed> */
    private static function normalizeRow(array $row): array
    {
        $positive = static function (string $value, string $field, bool $required = false): float {
            if ($value === '' && !$required) { return 0.0; }
            $number = filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($number === false || $number < ($required ? 0.01 : 0) || $number > 1000000000) { throw new RuntimeException('Invalid ' . $field . '.'); }
            return round((float) $number, 2);
        };
        $integer = static function (string $value, string $field): int {
            $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($number === false) { throw new RuntimeException('Invalid ' . $field . '.'); }
            return (int) $number;
        };
        $description = trim($row['package_description']);
        if (mb_strlen($description) < 3 || mb_strlen($description) > 500) { throw new RuntimeException('Package description must be 3 to 500 characters.'); }
        $service = $row['service_code'];
        if (!isset(PricingService::services()[$service])) { throw new RuntimeException('Invalid service code.'); }
        return [
            'pickup_address_id' => $integer($row['pickup_address_id'], 'pickup address'),
            'delivery_address_id' => $integer($row['delivery_address_id'], 'delivery address'),
            'origin_zone_id' => $integer($row['origin_zone_id'], 'origin zone'),
            'destination_zone_id' => $integer($row['destination_zone_id'], 'destination zone'),
            'service_code' => $service, 'package_description' => $description,
            'weight_kg' => $positive($row['weight_kg'], 'weight', true),
            'length_cm' => $positive($row['length_cm'], 'length'), 'width_cm' => $positive($row['width_cm'], 'width'),
            'height_cm' => $positive($row['height_cm'], 'height'), 'declared_value' => $positive($row['declared_value'], 'declared value'),
            'packaging_required' => in_array(mb_strtolower($row['packaging_required']), ['1', 'yes', 'true'], true),
            'is_fragile' => in_array(mb_strtolower($row['is_fragile']), ['1', 'yes', 'true'], true),
        ];
    }
}
