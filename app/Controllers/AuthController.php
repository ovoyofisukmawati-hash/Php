<?php

namespace App\Controllers;

use App\Models\UserModel;
use Core\BaseController;
use Core\Database;
use Core\Request;
use Core\Response;
use PDO;

class AuthController extends BaseController
{
    public function register(): array
    {
        $payload = Request::input();

        foreach (['name', 'email', 'password'] as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                return $this->error("Field {$field} wajib diisi.");
            }
        }

        $userModel = new UserModel();
        $existing = $userModel->findByEmail($payload['email']);

        if ($existing) {
            return $this->error('Email sudah terdaftar.');
        }

        $userModel->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'currency' => $payload['currency'] ?? 'IDR',
        ]);

        return $this->success(['message' => 'Registrasi berhasil.'], 'Registrasi berhasil.');
    }

    public function login(): array
    {
        $payload = Request::input();

        if (!isset($payload['email'], $payload['password'])) {
            return $this->error('Email dan password wajib diisi.');
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail($payload['email']);

        if (!$user || !password_verify((string) $payload['password'], (string) $user['password_hash'])) {
            return $this->error('Kredensial tidak valid.');
        }

        return $this->success([
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'status' => $user['status'],
                'two_factor_enabled' => (bool) $user['two_factor_enabled'],
            ],
            'token' => bin2hex(random_bytes(32)),
        ], 'Login berhasil.');
    }

    public function profile(): array
    {
        $userId = (int) ($_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            return $this->error('user_id wajib diisi.');
        }

        $userModel = new UserModel();
        $user = $userModel->findById($userId);

        if (!$user) {
            return $this->error('User tidak ditemukan.');
        }

        unset($user['password_hash']);
        return $this->success(['user' => $user], 'Profil berhasil dimuat.');
    }

    public function enableTwoFactor(): array
    {
        $payload = Request::input();
        $userId = (int) ($payload['user_id'] ?? 0);
        $enabled = (bool) ($payload['enabled'] ?? true);

        if ($userId <= 0) {
            return $this->error('user_id tidak valid.');
        }

        $userModel = new UserModel();
        $updated = $userModel->setTwoFactor($userId, $enabled);

        if (!$updated) {
            return $this->error('User tidak ditemukan.');
        }

        return $this->success([], $enabled ? '2FA diaktifkan.' : '2FA dinonaktifkan.');
    }
}
