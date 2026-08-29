<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;
use Throwable;

final class CorporateService
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT a.*,
                    (SELECT COALESCE(SUM(l.debit_amount - l.credit_amount), 0) FROM corporate_ledger l WHERE l.corporate_account_id = a.id) AS outstanding,
                    (SELECT COUNT(*) FROM corporate_members m WHERE m.corporate_account_id = a.id AND m.status = "active") AS member_count
             FROM corporate_accounts a ORDER BY a.status = "active" DESC, a.company_name'
        )->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data, int $staffId): int
    {
        $number = self::accountNumber();
        $statement = Database::connection()->prepare(
            'INSERT INTO corporate_accounts (account_number, company_name, billing_email, billing_phone, billing_address,
                tax_id, credit_limit, payment_terms_days, currency, status, account_manager_id, created_by, created_at, updated_at)
             VALUES (:number, :company, :email, :phone, :address, :tax_id, :credit_limit, :terms, :currency,
                "active", :manager, :staff, NOW(), NOW())'
        );
        $statement->execute([
            'number' => $number, 'company' => $data['company_name'], 'email' => $data['billing_email'],
            'phone' => $data['billing_phone'], 'address' => $data['billing_address'] ?: null, 'tax_id' => $data['tax_id'] ?: null,
            'credit_limit' => $data['credit_limit'], 'terms' => $data['payment_terms_days'], 'currency' => $data['currency'],
            'manager' => $data['account_manager_id'] ?: null, 'staff' => $staffId,
        ]);
        $id = (int) Database::connection()->lastInsertId();
        AuditService::record('corporate.created', 'corporate_account', $id, ['account_number' => $number]);
        return $id;
    }

    public static function addMemberByEmail(int $accountId, string $email, string $role, int $staffId): void
    {
        $customer = Database::connection()->prepare('SELECT id FROM customer_users WHERE email = :email AND status = "active" LIMIT 1');
        $customer->execute(['email' => mb_strtolower(trim($email))]);
        $customerId = $customer->fetchColumn();
        if ($customerId === false) { throw new RuntimeException('No active customer account uses that email address.'); }
        Database::connection()->prepare(
            'INSERT INTO corporate_members (corporate_account_id, customer_id, member_role, status, created_at)
             VALUES (:account, :customer, :role, "active", NOW())
             ON DUPLICATE KEY UPDATE member_role = VALUES(member_role), status = "active"'
        )->execute(['account' => $accountId, 'customer' => $customerId, 'role' => $role]);
        AuditService::record('corporate.member_added', 'corporate_account', $accountId, ['customer_id' => (int) $customerId, 'staff_id' => $staffId]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forCustomer(int $customerId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.*, m.member_role, COALESCE(SUM(l.debit_amount - l.credit_amount), 0) AS outstanding
             FROM corporate_members m JOIN corporate_accounts a ON a.id = m.corporate_account_id
             LEFT JOIN corporate_ledger l ON l.corporate_account_id = a.id
             WHERE m.customer_id = :customer AND m.status = "active" AND a.status = "active"
             GROUP BY a.id, m.member_role ORDER BY a.company_name'
        );
        $statement->execute(['customer' => $customerId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function findForCustomer(int $accountId, int $customerId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.*, m.member_role, COALESCE(SUM(l.debit_amount - l.credit_amount), 0) AS outstanding
             FROM corporate_accounts a JOIN corporate_members m ON m.corporate_account_id = a.id
             LEFT JOIN corporate_ledger l ON l.corporate_account_id = a.id
             WHERE a.id = :account AND m.customer_id = :customer AND m.status = "active"
             GROUP BY a.id, m.member_role LIMIT 1'
        );
        $statement->execute(['account' => $accountId, 'customer' => $customerId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $accountId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.*, COALESCE(SUM(l.debit_amount - l.credit_amount), 0) AS outstanding
             FROM corporate_accounts a LEFT JOIN corporate_ledger l ON l.corporate_account_id = a.id
             WHERE a.id = :account GROUP BY a.id LIMIT 1'
        );
        $statement->execute(['account' => $accountId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function members(int $accountId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.*, c.full_name, c.email, c.phone FROM corporate_members m
             JOIN customer_users c ON c.id = m.customer_id WHERE m.corporate_account_id = :account
             ORDER BY m.status = "active" DESC, m.member_role = "owner" DESC, c.full_name'
        );
        $statement->execute(['account' => $accountId]);
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function ledger(int $accountId, int $limit = 200): array
    {
        $statement = Database::connection()->prepare(
            'SELECT l.*, b.booking_number FROM corporate_ledger l LEFT JOIN bookings b ON b.id = l.booking_id
             WHERE l.corporate_account_id = :account ORDER BY l.created_at DESC, l.id DESC LIMIT :limit'
        );
        $statement->bindValue('account', $accountId, PDO::PARAM_INT);
        $statement->bindValue('limit', max(1, min(500, $limit)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public static function allocateBookingCredit(int $accountId, int $bookingId, int $customerId, ?int $batchId = null): void
    {
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $member = $pdo->prepare(
                'SELECT a.credit_limit, a.payment_terms_days, a.currency, a.status
                 FROM corporate_accounts a JOIN corporate_members m ON m.corporate_account_id = a.id
                 WHERE a.id = :account AND m.customer_id = :customer AND m.status = "active" FOR UPDATE'
            );
            $member->execute(['account' => $accountId, 'customer' => $customerId]);
            $account = $member->fetch(PDO::FETCH_ASSOC);
            if (!is_array($account) || $account['status'] !== 'active') { throw new RuntimeException('This corporate account is unavailable.'); }
            $bookingStatement = $pdo->prepare('SELECT * FROM bookings WHERE id = :booking AND customer_id = :customer FOR UPDATE');
            $bookingStatement->execute(['booking' => $bookingId, 'customer' => $customerId]);
            $booking = $bookingStatement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($booking) || $booking['status'] !== 'awaiting_payment' || $booking['payment_status'] !== 'unpaid') {
                throw new RuntimeException('Only an unpaid booking can be charged to a corporate account.');
            }
            if ($booking['currency'] !== $account['currency']) { throw new RuntimeException('The booking and corporate account currencies do not match.'); }
            $balance = $pdo->prepare('SELECT COALESCE(SUM(debit_amount - credit_amount), 0) FROM corporate_ledger WHERE corporate_account_id = :account');
            $balance->execute(['account' => $accountId]);
            $outstanding = (float) $balance->fetchColumn();
            if ($outstanding + (float) $booking['total_amount'] > (float) $account['credit_limit']) {
                throw new RuntimeException('This booking exceeds the corporate account\'s available credit.');
            }
            $pdo->prepare('INSERT INTO corporate_booking_links (corporate_account_id, booking_id, batch_id, created_at) VALUES (:account, :booking, :batch, NOW())')
                ->execute(['account' => $accountId, 'booking' => $bookingId, 'batch' => $batchId]);
            $reference = self::ledgerReference('CHG');
            $pdo->prepare(
                'INSERT INTO corporate_ledger (corporate_account_id, booking_id, entry_type, reference, description,
                    debit_amount, credit_amount, currency, due_at, created_at)
                 VALUES (:account, :booking, "booking_charge", :reference, :description, :amount, 0, :currency,
                    DATE_ADD(NOW(), INTERVAL :terms DAY), NOW())'
            )->execute([
                'account' => $accountId, 'booking' => $bookingId, 'reference' => $reference,
                'description' => 'Booking ' . $booking['booking_number'], 'amount' => $booking['total_amount'],
                'currency' => $booking['currency'], 'terms' => (int) $account['payment_terms_days'],
            ]);
            $pdo->prepare('UPDATE bookings SET status = "confirmed", payment_status = "corporate_credit", updated_at = NOW() WHERE id = :booking')
                ->execute(['booking' => $bookingId]);
            $pdo->prepare(
                'INSERT INTO booking_status_history (booking_id, status, note, actor_type, actor_id, created_at)
                 VALUES (:booking, "confirmed", "Approved against corporate credit", "customer", :customer, NOW())'
            )->execute(['booking' => $bookingId, 'customer' => $customerId]);
            $pdo->commit();
            NotificationService::queueBooking($bookingId, 'corporate_booking_confirmed', 'Corporate booking confirmed', 'Booking ' . $booking['booking_number'] . ' was approved against your corporate credit account.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public static function recordPayment(int $accountId, float $amount, string $reference, string $description, int $staffId): void
    {
        if (!is_finite($amount) || $amount <= 0 || $amount > 1000000000) { throw new RuntimeException('Enter a valid corporate payment amount.'); }
        $account = self::find($accountId);
        if ($account === null) { throw new RuntimeException('Corporate account not found.'); }
        Database::connection()->prepare(
            'INSERT INTO corporate_ledger (corporate_account_id, entry_type, reference, description, debit_amount,
                credit_amount, currency, posted_by, created_at)
             VALUES (:account, "payment", :reference, :description, 0, :amount, :currency, :staff, NOW())'
        )->execute([
            'account' => $accountId, 'reference' => $reference !== '' ? $reference : self::ledgerReference('PAY'),
            'description' => $description !== '' ? $description : 'Corporate account payment',
            'amount' => round($amount, 2), 'currency' => $account['currency'], 'staff' => $staffId,
        ]);
        AuditService::record('corporate.payment_recorded', 'corporate_account', $accountId, ['amount' => round($amount, 2)]);
    }

    private static function accountNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = 'CORP-' . date('ym') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $check = Database::connection()->prepare('SELECT 1 FROM corporate_accounts WHERE account_number = :number');
            $check->execute(['number' => $number]);
            if (!$check->fetchColumn()) { return $number; }
        }
        throw new RuntimeException('Unable to create a corporate account number.');
    }

    private static function ledgerReference(string $prefix): string
    {
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
    }
}
