<?php

namespace Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTimeImmutable;

final class Jwt
{
    private const ALGORITHM = 'HS256';

    public static function encode(array $payload, string $secret): string
    {
        $now = new DateTimeImmutable();

        $payload = array_merge([
            'iat' => $now->getTimestamp(),
            'nbf' => $now->getTimestamp(),
            'exp' => $now->modify('+7 days')->getTimestamp(),
        ], $payload);

        return JWT::encode($payload, $secret, self::ALGORITHM);
    }

    public static function decode(string $token, string $secret): array
    {
        return (array) JWT::decode($token, new Key($secret, self::ALGORITHM));
    }

    public static function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authorization = $headers['Authorization'] ?? null;

        if (!$authorization) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
