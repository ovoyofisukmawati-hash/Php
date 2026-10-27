<?php

namespace Core;

use App\Exceptions\HttpException;

final class ErrorHandler
{
    public static function handle(\Throwable $e): void
    {
        if ($e instanceof HttpException) {
            http_response_code($e->statusCode);
            echo json_encode([
                'success' => false,
                'message' => $e->message,
                'details' => $e->details,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $debug = getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1';

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan pada server.',
            'details' => $debug ? [$e->getMessage()] : [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
