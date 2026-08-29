<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class PaystackGateway
{
    public static function enabled(): bool
    {
        return Config::bool('PAYSTACK_ENABLED', false) && trim((string) Config::get('PAYSTACK_SECRET_KEY', '')) !== '';
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public static function initialize(array $payload): array
    {
        return self::request('POST', '/transaction/initialize', $payload);
    }

    /** @return array<string, mixed> */
    public static function verify(string $reference): array
    {
        return self::request('GET', '/transaction/verify/' . rawurlencode($reference));
    }

    public static function validSignature(string $payload, string $signature): bool
    {
        $secret = (string) Config::get('PAYSTACK_SECRET_KEY', '');
        return $secret !== '' && $signature !== '' && hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private static function request(string $method, string $path, array $payload = []): array
    {
        if (!self::enabled()) {
            throw new RuntimeException('Online payment is not enabled yet. Please contact Easyway support.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL is required for Paystack payments.');
        }
        $url = rtrim((string) Config::get('PAYSTACK_API_URL', 'https://api.paystack.co'), '/') . $path;
        $handle = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . Config::get('PAYSTACK_SECRET_KEY', ''),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES);
        }
        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($body === false || $error !== '') {
            throw new RuntimeException('The payment gateway is temporarily unavailable.');
        }
        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded) || $status < 200 || $status >= 300 || empty($decoded['status'])) {
            $message = is_array($decoded) ? (string) ($decoded['message'] ?? 'Payment gateway request failed.') : 'Payment gateway returned an invalid response.';
            throw new RuntimeException($message);
        }
        return $decoded;
    }
}
