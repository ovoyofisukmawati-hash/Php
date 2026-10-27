<?php

namespace Core;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';

        return '/' . trim($uri, '/');
    }

    public static function input(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return $_POST ?? [];
        }

        $json = json_decode($raw, true);

        return is_array($json) ? $json : ($_POST ?? []);
    }

    public static function query(): array
    {
        return $_GET ?? [];
    }

    public static function header(string $key): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        return $_SERVER[$key] ?? null;
    }
}
