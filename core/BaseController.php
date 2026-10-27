<?php

namespace Core;

abstract class BaseController
{
    protected function success(array $data = [], string $message = 'Success', int $status = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function error(string $message, int $status = 400, array $details = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'details' => $details,
            'status' => $status,
        ];
    }
}
