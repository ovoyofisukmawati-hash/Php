<?php

namespace Core;

use App\Exceptions\HttpException;

final class AuthMiddleware
{
    public static function handle(string $secret): callable
    {
        return function (array $request, callable $next) use ($secret): mixed {
            $token = Jwt::getBearerToken();

            if (!$token) {
                throw new HttpException(401, 'Token tidak ditemukan.');
            }

            try {
                $claims = Jwt::decode($token, $secret);
            } catch (\Throwable $e) {
                throw new HttpException(401, 'Token tidak valid atau sudah kedaluwarsa.');
            }

            if (!isset($claims['user_id'])) {
                throw new HttpException(401, 'Token tidak memuat user_id.');
            }

            $request['user'] = [
                'id' => (int) $claims['user_id'],
                'email' => $claims['email'] ?? null,
                'role' => $claims['role'] ?? 'user',
            ];

            return $next($request);
        };
    }
}
