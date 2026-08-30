<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

final class InquiryInboxService
{
    public const TYPES = ['quote', 'contact'];
    public const CURRENCIES = ['NGN', 'USD', 'GBP', 'EUR'];

    public static function installed(): bool
    {
        try { Database::connection()->query('SELECT id FROM inquiry_activities LIMIT 0'); return true; }
        catch (PDOException $e) { if ($e->getCode() === '42S02') { return false; } throw $e; }
    }

    private static function requireOperator(): void
    {
        if (!in_array(Auth::user()['role'] ?? '', ['admin', 'dispatcher'], true)) {
            throw new RuntimeException('Only administrators and dispatchers can work on inquiries.');
        }
    }

    private static function table(string $type): string
    {
        return match ($type) { 'quote' => 'quote_requests', 'contact' => 'contact_messages', default => throw new RuntimeException('Unknown inquiry type.') };
    }

    public static function statuses(string $type): array
    {
        self::table($type);
        $statuses = ['new' => 'New', 'in_progress' => 'In progress', 'replied' => 'Replied'];
        if ($type === 'quote') { $statuses += ['quoted' => 'Quoted', 'accepted' => 'Accepted', 'declined' => 'Declined']; }
        return $statuses + ['closed' => 'Closed'];
    }

    public static function listing(string $type, string $status = '', string $search = '', int $page = 1): array
    {
        self::requireOperator();
        $table = self::table($type);
        $where = []; $params = [];
        if ($status !== '') {
            if (!isset(self::statuses($type)[$status])) { throw new RuntimeException('Unknown inquiry status.'); }
            $where[] = 'status = :status'; $params['status'] = $status;
        }
        $search = mb_substr(trim($search), 0, 120);
        if ($search !== '') {
            $term = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
            $where[] = "(reference LIKE :reference ESCAPE '!' OR full_name LIKE :name ESCAPE '!' OR email LIKE :email ESCAPE '!')";
            $params += ['reference' => $term, 'name' => $term, 'email' => $term];
        }
        $clause = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $pdo = Database::connection();
        $count = $pdo->prepare('SELECT COUNT(*) FROM ' . $table . $clause); $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 20)); $page = max(1, min($page, $pages));
        $query = $pdo->prepare('SELECT * FROM ' . $table . $clause . ' ORDER BY created_at DESC,id DESC LIMIT 20 OFFSET :offset');
        foreach ($params as $key => $value) { $query->bindValue($key, $value, PDO::PARAM_STR); }
        $query->bindValue('offset', ($page - 1) * 20, PDO::PARAM_INT); $query->execute();
        return ['rows' => $query->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function find(string $type, int $id): ?array
    {
        self::requireOperator();
        $statement = Database::connection()->prepare('SELECT * FROM ' . self::table($type) . ' WHERE id=:id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public static function history(string $type, int $id): array
    {
        self::requireOperator(); self::table($type);
        if (!self::installed()) { return []; }
        $query = Database::connection()->prepare('SELECT a.*,s.full_name AS staff_name,n.status AS delivery_status,n.attempts,n.last_error,n.sent_at
            FROM inquiry_activities a LEFT JOIN staff_users s ON s.id=a.staff_user_id
            LEFT JOIN notification_outbox n ON n.id=a.notification_id
            WHERE a.inquiry_type=:type AND a.inquiry_id=:id ORDER BY a.id DESC LIMIT 100');
        $query->execute(['type' => $type, 'id' => $id]);
        return $query->fetchAll();
    }

    public static function emailReadiness(): array
    {
        self::requireOperator();
        try {
            $settings = NotificationSettings::get('email', true);
            if (!$settings['enabled']) { throw new RuntimeException('Email delivery is disabled. An administrator must enable and test SMTP in Delivery Settings before replies can be queued.'); }
            if ($settings['transport'] !== 'smtp') { throw new RuntimeException('Select Authenticated SMTP in Delivery Settings before sending inquiry replies. Server mail is not used for this workflow.'); }
            NotificationSettings::validate('email', $settings);
            NotificationTransport::assertRuntimeAvailable('email', $settings);
            return ['ready' => true, 'message' => 'Uses the saved SMTP configuration and scheduled notification worker.', 'sender' => $settings['from_email']];
        } catch (RuntimeException $e) { return ['ready' => false, 'message' => $e->getMessage(), 'sender' => '']; }
    }

    public static function phoneLinks(string $phone, string $reference): array
    {
        $clean = preg_replace('/[\s()\-]/', '', trim($phone));
        if (str_starts_with($clean, '00')) { $clean = '+' . substr($clean, 2); }
        if (preg_match('/^0[789][0-9]{9}$/D', $clean)) { $clean = '+234' . substr($clean, 1); }
        elseif (preg_match('/^234[0-9]{10}$/D', $clean)) { $clean = '+' . $clean; }
        $call = preg_match('/^\+?[0-9]{7,15}$/D', $clean) ? 'tel:' . $clean : null;
        $whatsapp = preg_match('/^\+[1-9][0-9]{7,14}$/D', $clean)
            ? 'https://wa.me/' . substr($clean, 1) . '?text=' . rawurlencode('Hello, this is Easyway Logistics regarding your inquiry ' . $reference . '.') : null;
        return ['call' => $call, 'whatsapp' => $whatsapp];
    }

    private static function text(mixed $value, string $label, int $max, bool $singleLine = false): string
    {
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) { throw new RuntimeException('Enter a valid ' . $label . '.'); }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) || ($singleLine && preg_match('/[\r\n]/', $value))) {
            throw new RuntimeException('The ' . $label . ' contains invalid control characters.');
        }
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > $max) { throw new RuntimeException('Enter a ' . $label . ' of 1 to ' . $max . ' characters.'); }
        return $value;
    }

    public static function amount(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/^(?:0|[1-9][0-9]{0,11})(?:\.[0-9]{1,2})?$/D', $value)) {
            throw new RuntimeException('Enter a positive quotation amount with no commas and at most two decimal places.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');
        if ((int) $whole === 0 && (int) $fraction === 0) { throw new RuntimeException('The quotation amount must be greater than zero.'); }
        return $whole . '.' . $fraction;
    }

    /** Queue a reply/quotation, save an internal note, or update status atomically. */
    public static function act(string $type, int $id, string $kind, array $input): int
    {
        self::requireOperator(); $table = self::table($type);
        if (!self::installed()) { throw new RuntimeException('Run php tools/install_inquiry_inbox.php before using inbox actions.'); }
        if (!in_array($kind, ['reply', 'quotation', 'note', 'status'], true) || ($kind === 'quotation' && $type !== 'quote')) {
            throw new RuntimeException('This action is not available for this inquiry.');
        }
        $token = $input['request_token'] ?? '';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) { throw new RuntimeException('Reload the inquiry form and try again.'); }
        $revision = filter_var($input['revision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($revision === false || $id < 1) { throw new RuntimeException('Invalid inquiry or form revision.'); }
        $data = [];
        if ($kind === 'reply') { $data = ['subject' => self::text($input['subject'] ?? '', 'subject', 140, true), 'body' => self::text($input['body'] ?? '', 'reply', 6000)]; }
        if ($kind === 'note') { $data['body'] = self::text($input['note'] ?? '', 'staff note', 6000); }
        if ($kind === 'status') {
            $data['status'] = Validator::choice($input['status'] ?? '', array_keys(self::statuses($type)));
            if ($data['status'] === '') { throw new RuntimeException('Select a valid inquiry status.'); }
        }
        if ($kind === 'quotation') {
            $data = ['amount' => self::amount($input['amount'] ?? ''), 'currency' => Validator::choice($input['currency'] ?? '', self::CURRENCIES),
                'terms' => self::text($input['terms'] ?? '', 'quotation terms', 6000)];
            if ($data['currency'] === '') { throw new RuntimeException('Select a supported quotation currency.'); }
        }
        $actor = (int) Auth::id();
        $hash = hash('sha256', json_encode([$actor, $type, $id, $kind, $data], JSON_THROW_ON_ERROR));
        $pdo = Database::connection(); $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) { $pdo->beginTransaction(); }
        try {
            $lock = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE id=:id FOR UPDATE'); $lock->execute(['id' => $id]);
            $inquiry = $lock->fetch();
            if (!$inquiry) { throw new RuntimeException('Inquiry not found.'); }
            $duplicate = $pdo->prepare('SELECT id,request_hash FROM inquiry_activities WHERE request_token=:token'); $duplicate->execute(['token' => $token]);
            if ($saved = $duplicate->fetch()) {
                if (!hash_equals($saved['request_hash'], $hash)) { throw new RuntimeException('This form was already used for a different action. Reload the inquiry.'); }
                if ($ownsTransaction) { $pdo->commit(); }
                return (int) $saved['id'];
            }
            $latest = $pdo->prepare('SELECT COALESCE(MAX(id),0) FROM inquiry_activities WHERE inquiry_type=:type AND inquiry_id=:id');
            $latest->execute(['type' => $type, 'id' => $id]);
            if ((int) $latest->fetchColumn() !== $revision) { throw new RuntimeException('This inquiry changed in another session. Review the latest history before submitting again. Your text has been kept.'); }
            $notificationId = null; $subject = null; $body = ''; $metadata = [];
            $newStatus = (string) $inquiry['status'];
            if (in_array($kind, ['reply', 'quotation'], true)) {
                $ready = self::emailReadiness();
                if (!$ready['ready']) { throw new RuntimeException($ready['message']); }
                $recipient = (string) $inquiry['email'];
                if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n\x00]/', $recipient)) { throw new RuntimeException('This inquiry does not have a valid customer email address.'); }
                $reference = (string) $inquiry['reference'];
                if ($kind === 'reply') {
                    $subject = '[' . $reference . '] ' . $data['subject'];
                    $body = 'Hello ' . $inquiry['full_name'] . ",\n\n" . $data['body'] . "\n\nInquiry reference: " . $reference . "\n\nEasyway Logistics";
                } else {
                    $subject = '[' . $reference . '] Your Easyway quotation';
                    $body = 'Hello ' . $inquiry['full_name'] . ",\n\nThank you for your request. Please find your confirmed quotation below.\n\nReference: " . $reference
                        . "\nRoute: " . $inquiry['from_location'] . ' to ' . $inquiry['to_location'] . "\nService: " . $inquiry['delivery_type']
                        . "\nShipment: " . $inquiry['shipment_type'] . ' / ' . $inquiry['weight_range'] . ' / ' . $inquiry['quantity'] . ' piece(s)'
                        . "\nTotal quoted amount: " . $data['currency'] . ' ' . $data['amount'] . "\n\nTerms and conditions:\n" . $data['terms']
                        . "\n\nPlease reply to this email to discuss or accept the quotation. This quotation is not an invoice or payment confirmation.\n\nEasyway Logistics";
                    $metadata = $data;
                }
                $metadata += ['recipient' => $recipient, 'sender_at_queue' => $ready['sender']];
                $outbox = $pdo->prepare('INSERT INTO notification_outbox (channel,recipient,template_code,subject,message,status,attempts) VALUES ("email",:recipient,:template,:subject,:message,"pending",0)');
                $outbox->execute(['recipient' => $recipient, 'template' => 'inquiry_' . $kind, 'subject' => $subject, 'message' => $body]);
                $notificationId = (int) $pdo->lastInsertId();
                // Queued is not delivered. Operators can set Replied/Quoted after follow-up.
                if ($newStatus === 'new') { $newStatus = 'in_progress'; }
            } elseif ($kind === 'note') { $body = $data['body']; }
            else { $newStatus = $data['status']; $body = 'Status changed from ' . $inquiry['status'] . ' to ' . $newStatus . '.'; }
            $metadata += ['previous_status' => $inquiry['status'], 'status' => $newStatus];
            $params = ['status' => $newStatus, 'id' => $id];
            $sql = 'UPDATE ' . $table . ' SET status=:status,updated_at=NOW()';
            if ($kind === 'quotation') { $sql .= ',quoted_amount=:amount,currency=:currency'; $params += ['amount' => $data['amount'], 'currency' => $data['currency']]; }
            $pdo->prepare($sql . ' WHERE id=:id')->execute($params);
            $activity = $pdo->prepare('INSERT INTO inquiry_activities (inquiry_type,inquiry_id,inquiry_reference,kind,staff_user_id,notification_id,subject,body,metadata_json,request_token,request_hash)
                VALUES (:type,:inquiry,:reference,:kind,:staff,:notification,:subject,:body,:metadata,:token,:hash)');
            $activity->execute(['type' => $type, 'inquiry' => $id, 'reference' => $inquiry['reference'], 'kind' => $kind, 'staff' => $actor,
                'notification' => $notificationId, 'subject' => $subject, 'body' => $body, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'token' => $token, 'hash' => $hash]);
            $activityId = (int) $pdo->lastInsertId();
            AuditService::record('inquiry.' . $kind, $type === 'quote' ? 'quote_request' : 'contact_message', $id,
                ['activity_id' => $activityId, 'notification_id' => $notificationId, 'status' => $newStatus]);
            if ($ownsTransaction) { $pdo->commit(); }
            return $activityId;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
            if ($e instanceof PDOException) { throw new RuntimeException('The inquiry action could not be saved. Reload and try again.'); }
            throw $e;
        }
    }
}
