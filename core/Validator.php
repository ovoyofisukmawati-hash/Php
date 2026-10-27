<?php

namespace Core;

final class Validator
{
    public static function required(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || trim((string) $data[$field]) === '') {
                $errors[$field] = "Field {$field} wajib diisi.";
            }
        }

        return $errors;
    }

    public static function email(string $email): ?string
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? null : 'Format email tidak valid.';
    }

    public static function numeric(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && (!is_numeric($data[$field]) || $data[$field] === '')) {
                $errors[$field] = "Field {$field} harus berupa angka.";
            }
        }

        return $errors;
    }

    public static function in(array $data, string $field, array $allowed): ?string
    {
        if (!array_key_exists($field, $data)) {
            return null;
        }

        return in_array($data[$field], $allowed, true)
            ? null
            : "Field {$field} harus salah satu dari: " . implode(', ', $allowed) . ".";
    }

    public static function min(array $data, string $field, float $min): ?string
    {
        if (!array_key_exists($field, $data) || !is_numeric($data[$field])) {
            return null;
        }

        return (float) $data[$field] >= $min
            ? null
            : "Field {$field} minimal bernilai {$min}.";
    }
}
