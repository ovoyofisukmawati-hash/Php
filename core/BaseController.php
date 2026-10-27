<?php

namespace Core;

abstract class BaseController
{
    protected function success(array $data = [], string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function error(string $message, int $statusCode = 400, array $details = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'details' => $details,
            'status' => $statusCode,
        ];
    }
}
