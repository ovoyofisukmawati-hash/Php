<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;

class AdminDashboardController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function dashboard(): array
    {
        $summary = [
            'total_users' => (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'active_users' => (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'total_balance' => (float) $this->db->query('SELECT COALESCE(SUM(balance), 0) FROM user_accounts')->fetchColumn(),
            'total_deposit' => (float) $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND status = 'approved'")->fetchColumn(),
            'total_withdrawal' => (float) $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdrawal' AND status = 'approved'")->fetchColumn(),
            'pending_transactions' => (int) $this->db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn(),
            'active_investment_plans' => (int) $this->db->query("SELECT COUNT(*) FROM investment_plans WHERE is_active = 1")->fetchColumn(),
            'referral_commissions' => (float) $this->db->query('SELECT COALESCE(SUM(amount), 0) FROM referral_commissions')->fetchColumn(),
        ];

        $recentTransactions = $this->db->query(
            "SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        $recentInvestments = $this->db->query(
            "SELECT * FROM investments ORDER BY created_at DESC LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => [
                'summary' => $summary,
                'recent_transactions' => $recentTransactions,
                'recent_investments' => $recentInvestments,
            ],
        ];
    }

    public function listUsers(): array
    {
        $stmt = $this->db->query(
            'SELECT u.*, ua.balance FROM users u LEFT JOIN user_accounts ua ON ua.user_id = u.id ORDER BY u.created_at DESC'
        );

        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function createUser(array $payload): array
    {
        foreach (['name', 'email', 'password'] as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                return ['success' => false, 'message' => "Field {$field} wajib diisi."];
            }
        }

        $email = strtolower(trim((string) $payload['email']));
        $exists = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute([':email' => $email]);

        if ($exists->fetch()) {
            return ['success' => false, 'message' => 'Email sudah terdaftar.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'INSERT INTO users (name, email, password_hash, status, created_at) VALUES (:name, :email, :password_hash, :status, NOW())'
            );
            $stmt->execute([
                ':name' => trim((string) $payload['name']),
                ':email' => $email,
                ':password_hash' => password_hash((string) $payload['password'], PASSWORD_BCRYPT),
                ':status' => $payload['status'] ?? 'active',
            ]);

            $userId = (int) $this->db->lastInsertId();

            $account = $this->db->prepare(
                'INSERT INTO user_accounts (user_id, balance, currency, created_at) VALUES (:user_id, 0, :currency, NOW())'
            );
            $account->execute([
                ':user_id' => $userId,
                ':currency' => $payload['currency'] ?? 'IDR',
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'User berhasil ditambahkan.',
                'data' => ['user_id' => $userId],
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();

            return ['success' => false, 'message' => 'Gagal menambahkan user.', 'error' => $e->getMessage()];
        }
    }

    public function listTransactions(?string $type = null): array
    {
        $sql = 'SELECT * FROM transactions';
        $params = [];

        if ($type !== null && $type !== '') {
            $sql .= ' WHERE type = :type';
            $params[':type'] = $type;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function approveTransaction(int $transactionId, int $adminId): array
    {
        $stmt = $this->db->prepare(
            'UPDATE transactions SET status = :status, reviewed_by = :admin_id, reviewed_at = NOW() WHERE id = :id AND status = :pending'
        );

        $stmt->execute([
            ':status' => 'approved',
            ':admin_id' => $adminId,
            ':id' => $transactionId,
            ':pending' => 'pending',
        ]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Transaksi tidak ditemukan atau sudah diproses.'];
        }

        return ['success' => true, 'message' => 'Transaksi berhasil disetujui.'];
    }

    public function rejectTransaction(int $transactionId, int $adminId, string $reason = ''): array
    {
        $stmt = $this->db->prepare(
            'UPDATE transactions SET status = :status, reviewed_by = :admin_id, review_note = :reason, reviewed_at = NOW() WHERE id = :id AND status = :pending'
        );

        $stmt->execute([
            ':status' => 'rejected',
            ':admin_id' => $adminId,
            ':reason' => $reason,
            ':id' => $transactionId,
            ':pending' => 'pending',
        ]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Transaksi tidak ditemukan atau sudah diproses.'];
        }

        return ['success' => true, 'message' => 'Transaksi berhasil ditolak.'];
    }

    public function listInvestmentPlans(): array
    {
        $stmt = $this->db->query('SELECT * FROM investment_plans ORDER BY created_at DESC');

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function saveInvestmentPlan(array $payload): array
    {
        foreach (['name', 'minimum_amount', 'interest_rate', 'duration_days'] as $field) {
            if (!array_key_exists($field, $payload) || trim((string) $payload[$field]) === '') {
                return ['success' => false, 'message' => "Field {$field} wajib diisi."];
            }
        }

        $data = [
            ':name' => trim((string) $payload['name']),
            ':minimum_amount' => (float) $payload['minimum_amount'],
            ':interest_rate' => (float) $payload['interest_rate'],
            ':duration_days' => (int) $payload['duration_days'],
            ':is_active' => (int) ($payload['is_active'] ?? 1),
            ':description' => trim((string) ($payload['description'] ?? '')),
            ':updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($payload['id'])) {
            $sql = 'UPDATE investment_plans SET name = :name, minimum_amount = :minimum_amount, interest_rate = :interest_rate, duration_days = :duration_days, is_active = :is_active, description = :description, updated_at = :updated_at WHERE id = :id';
            $data[':id'] = (int) $payload['id'];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);

            return ['success' => true, 'message' => 'Skema investasi berhasil diperbarui.'];
        }

        $sql = 'INSERT INTO investment_plans (name, minimum_amount, interest_rate, duration_days, is_active, description, created_at, updated_at) VALUES (:name, :minimum_amount, :interest_rate, :duration_days, :is_active, :description, NOW(), :updated_at)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return ['success' => true, 'message' => 'Skema investasi berhasil dibuat.'];
    }

    public function listReferralReport(): array
    {
        $stmt = $this->db->query(
            'SELECT u.name, u.email, COUNT(r.id) AS total_referrals, COALESCE(SUM(r.amount), 0) AS total_commission FROM users u LEFT JOIN referral_commissions r ON r.user_id = u.id GROUP BY u.id ORDER BY total_commission DESC'
        );

        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function updateSettings(array $payload): array
    {
        foreach (['site_name', 'currency'] as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                return ['success' => false, 'message' => "Field {$field} wajib diisi."];
            }
        }

        $allowedKeys = ['site_name', 'currency', 'timezone', 'maintenance_mode', 'google_recaptcha_key', 'tawk_id'];

        $stmt = $this->db->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (:key, :value, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );

        foreach ($payload as $key => $value) {
            if (in_array($key, $allowedKeys, true)) {
                $stmt->execute([
                    ':key' => $key,
                    ':value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                ]);
            }
        }

        return ['success' => true, 'message' => 'Pengaturan aplikasi berhasil disimpan.'];
    }

    public function toggleUserStatus(int $userId, string $status): array
    {
        $allowed = ['active', 'suspended', 'blocked', 'pending'];
        if (!in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Status tidak valid.'];
        }

        $stmt = $this->db->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $userId]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'User tidak ditemukan.'];
        }

        return ['success' => true, 'message' => 'Status user berhasil diubah.'];
    }

    public function toggleTwoFactor(int $userId, bool $enabled): array
    {
        $stmt = $this->db->prepare('UPDATE users SET two_factor_enabled = :enabled WHERE id = :id');
        $stmt->execute([':enabled' => $enabled ? 1 : 0, ':id' => $userId]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'User tidak ditemukan.'];
        }

        return [
            'success' => true,
            'message' => $enabled ? '2FA berhasil diaktifkan.' : '2FA berhasil dinonaktifkan.',
        ];
    }
}
