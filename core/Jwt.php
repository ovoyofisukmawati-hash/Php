<?php

namespace Core;

final class Jwt
{
    public static function encode(array $payload, string $secret): string
    {
        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEnc = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $header . '.' . $payloadEnc, $secret, true);

        return $header . '.' . $payloadEnc . '.' . self::base64UrlEncode($signature);
    }

    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $expected = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
        $expectedEnc = self::base64UrlEncode($expected);

        if (!hash_equals($expectedEnc, $signature)) {
            return null;
        }

        $decoded = json_decode(self::base64UrlDecode($payload), true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authorization = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'));
    }
}
