<?php

namespace Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsed = parse_url($uri, PHP_URL_PATH) ?? '/';
        return '/' . trim($parsed, '/');
    }

    public static function input(): array
    {
        $content = file_get_contents('php://input');

        if ($content !== false && trim($content) !== '') {
            $json = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        return $_POST ?? [];
    }

    public static function query(): array
    {
        return $_GET ?? [];
    }
}
