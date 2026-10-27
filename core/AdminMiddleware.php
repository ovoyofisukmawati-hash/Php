<?php

namespace Core;

use App\Exceptions\HttpException;

final class AdminMiddleware
{
    public static function handle(string $secret): callable
    {
        return function (array $request, callable $next) use ($secret): mixed {
            $token = Jwt::getBearerToken();

            if (!$token) {
                throw new HttpException(401, 'Token tidak ditemukan.');
            }

            $claims = Jwt::decode($token, $secret);
            if (!$claims || !isset($claims['user_id'])) {
                throw new HttpException(401, 'Token tidak valid atau sudah kedaluwarsa.');
            }

            if (($claims['role'] ?? '') !== 'admin') {
                throw new HttpException(403, 'Akses ditolak. Hanya admin yang dapat mengakses route ini.');
            }

            $request['user'] = [
                'id' => (int) $claims['user_id'],
                'email' => $claims['email'] ?? null,
                'role' => 'admin',
            ];

            return $next($request);
        };
    }
}
