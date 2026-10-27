<?php

namespace App\Controllers;

use App\Exceptions\HttpException;
use App\Models\UserModel;
use Core\BaseController;
use Core\Jwt;
use Core\Request;
use Core\Validator;

class AuthController extends BaseController
{
    public function register(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $errors = Validator::required($payload, ['name', 'email', 'password']);

        if (isset($payload['email'])) {
            $emailError = Validator::email((string) $payload['email']);
            if ($emailError) {
                $errors['email'] = $emailError;
            }
        }

        if ($errors) {
            throw new HttpException(422, 'Validasi gagal.', $errors);
        }

        $userModel = new UserModel();
        if ($userModel->findByEmail((string) $payload['email'])) {
            throw new HttpException(409, 'Email sudah terdaftar.');
        }

        $userId = $userModel->create([
            'name' => trim((string) $payload['name']),
            'email' => trim((string) $payload['email']),
            'password' => (string) $payload['password'],
            'status' => $payload['status'] ?? 'active',
            'role' => $payload['role'] ?? 'user',
            'currency' => $payload['currency'] ?? 'IDR',
        ]);

        $user = $userModel->findById($userId);
        unset($user['password_hash']);

        return $this->success([
            'user' => $user,
        ], 'Registrasi berhasil.');
    }

    public function login(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $errors = Validator::required($payload, ['email', 'password']);

        if (isset($payload['email'])) {
            $emailError = Validator::email((string) $payload['email']);
            if ($emailError) {
                $errors['email'] = $emailError;
            }
        }

        if ($errors) {
            throw new HttpException(422, 'Validasi gagal.', $errors);
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail((string) $payload['email']);

        if (!$user || !password_verify((string) $payload['password'], (string) $user['password_hash'])) {
            throw new HttpException(401, 'Email atau password salah.');
        }

        $secret = getenv('JWT_SECRET') ?: 'default-secret-key';
        $token = Jwt::encode([
            'user_id' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user',
        ], $secret);

        $safeUser = $user;
        unset($safeUser['password_hash']);

        return $this->success([
            'token' => $token,
            'user' => $safeUser,
        ], 'Login berhasil.');
    }

    public function profile(array $input = []): array
    {
        $payload = $input ?: Request::query();
        $userId = (int) ($payload['user_id'] ?? 0);

        if ($userId <= 0) {
            throw new HttpException(400, 'user_id wajib diisi.');
        }

        $userModel = new UserModel();
        $user = $userModel->findById($userId);
        if (!$user) {
            throw new HttpException(404, 'User tidak ditemukan.');
        }

        unset($user['password_hash']);
        return $this->success(['user' => $user], 'Profil berhasil dimuat.');
    }

    public function enableTwoFactor(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $userId = (int) ($payload['user_id'] ?? 0);
        $enabled = filter_var($payload['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($userId <= 0) {
            throw new HttpException(400, 'user_id tidak valid.');
        }

        $userModel = new UserModel();
        $updated = $userModel->setTwoFactor($userId, $enabled);
        if (!$updated) {
            throw new HttpException(404, 'User tidak ditemukan.');
        }

        return $this->success([], $enabled ? '2FA berhasil diaktifkan.' : '2FA berhasil dinonaktifkan.');
    }
}
