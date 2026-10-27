<?php

namespace Core;

use App\Exceptions\HttpException;

final class ErrorHandler
{
    public static function handle(\Throwable $e): void
    {
        if ($e instanceof HttpException) {
            http_response_code($e->statusCode);
            Response::json([
                'success' => false,
                'message' => $e->message,
                'details' => $e->details,
            ], $e->statusCode);
            return;
        }

        $debug = getenv('APP_DEBUG') === '1' || getenv('APP_DEBUG') === 'true';

        http_response_code(500);
        Response::json([
            'success' => false,
            'message' => 'Terjadi kesalahan pada server.',
            'details' => $debug ? [$e->getMessage()] : [],
        ], 500);
    }
}
