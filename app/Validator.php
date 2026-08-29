<?php

declare(strict_types=1);

namespace App;

final class Validator
{
    public static function text(mixed $value, int $maxLength = 255): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return mb_substr($value, 0, $maxLength);
    }

    public static function email(mixed $value): string
    {
        $email = mb_strtolower(self::text($value, 190));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    public static function phone(mixed $value): string
    {
        $phone = self::text($value, 30);
        return preg_match('/^[0-9+()\-\s]{7,30}$/', $phone) === 1 ? $phone : '';
    }

    /** @param array<int, string> $allowed */
    public static function choice(mixed $value, array $allowed, string $default = ''): string
    {
        $value = self::text($value, 80);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** @return array<string, string> */
    public static function contact(array $input): array
    {
        $data = [
            'full_name' => self::text($input['full_name'] ?? '', 120),
            'company_name' => self::text($input['company_name'] ?? '', 160),
            'email' => self::email($input['email'] ?? ''),
            'phone' => self::phone($input['phone'] ?? ''),
            'subject' => self::text($input['subject'] ?? 'General enquiry', 160),
            'message' => self::text($input['message'] ?? '', 3000),
        ];

        $errors = [];
        if (mb_strlen($data['full_name']) < 2) {
            $errors['full_name'] = 'Please enter your full name.';
        }
        if ($data['email'] === '') {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (($input['phone'] ?? '') !== '' && $data['phone'] === '') {
            $errors['phone'] = 'Please enter a valid phone number.';
        }
        if (mb_strlen($data['message']) < 10) {
            $errors['message'] = 'Please tell us how we can help.';
        }

        return [$data, $errors];
    }

    /** @return array{0:array<string, string|int>, 1:array<string, string>} */
    public static function quote(array $input): array
    {
        $quantity = filter_var($input['quantity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 10000],
        ]);

        $data = [
            'shipment_type' => self::choice($input['shipment_type_option'] ?? '', ['Domestic', 'International']),
            'from_location' => self::text($input['from_location'] ?? '', 190),
            'to_location' => self::text($input['to_location'] ?? '', 190),
            'weight_range' => self::choice($input['weight_range'] ?? '', [
                'Below 1kg', '1kg - 5kg', '6kg - 15kg', '16kg - 30kg', 'Above 30kg',
            ]),
            'quantity' => $quantity === false ? 0 : $quantity,
            'delivery_type' => self::choice($input['delivery_type'] ?? '', [
                'Standard Delivery', 'Express Delivery', 'Same-Day Delivery', 'Cargo / Freight',
            ]),
            'full_name' => self::text($input['fullname'] ?? '', 120),
            'email' => self::email($input['email'] ?? ''),
            'phone' => self::phone($input['phone'] ?? ''),
            'notes' => self::text($input['notes'] ?? '', 2000),
        ];

        $errors = [];
        foreach (['shipment_type', 'from_location', 'to_location', 'weight_range', 'delivery_type', 'full_name'] as $field) {
            if ($data[$field] === '') {
                $errors[$field] = 'This field is required.';
            }
        }
        if ($data['quantity'] < 1) {
            $errors['quantity'] = 'Enter a valid quantity.';
        }
        if ($data['email'] === '') {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($data['phone'] === '') {
            $errors['phone'] = 'Enter a valid phone number.';
        }

        return [$data, $errors];
    }
}

