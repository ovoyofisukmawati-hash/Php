<?php

namespace App\Controllers;

use App\Exceptions\HttpException;
use App\Models\InvestmentPlanModel;
use App\Models\SettingsModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use Core\BaseController;
use Core\Database;
use Core\Request;
use Core\Validator;

class AdminController extends BaseController
{
    public function dashboard(): array
    {
        $db = Database::getInstance()->connection();

        $summary = [
            'total_users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'active_users' => (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'total_balance' => (float) $db->query('SELECT COALESCE(SUM(balance), 0) FROM user_accounts')->fetchColumn(),
            'total_deposit' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND status = 'approved'")->fetchColumn(),
            'total_withdrawal' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdrawal' AND status = 'approved'")->fetchColumn(),
            'pending_transactions' => (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn(),
            'active_investment_plans' => (int) $db->query("SELECT COUNT(*) FROM investment_plans WHERE is_active = 1")->fetchColumn(),
            'total_referral_commissions' => (float) $db->query('SELECT COALESCE(SUM(amount), 0) FROM referral_commissions')->fetchColumn(),
        ];

        $recentTransactions = $db->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 10")->fetchAll();
        $recentInvestments = $db->query("SELECT * FROM investments ORDER BY created_at DESC LIMIT 10")->fetchAll();

        return $this->success([
            'summary' => $summary,
            'recent_transactions' => $recentTransactions,
            'recent_investments' => $recentInvestments,
        ], 'Dashboard admin berhasil dimuat.');
    }

    public function users(): array
    {
        $userModel = new UserModel();
        return $this->success(['users' => $userModel->listAll()], 'Daftar pengguna berhasil dimuat.');
    }

    public function updateUserStatus(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $errors = Validator::required($payload, ['user_id', 'status']);
        if ($errors) {
            throw new HttpException(422, 'Validasi gagal.', $errors);
        }

        $userId = (int) $payload['user_id'];
        $status = trim((string) $payload['status']);
        $allowed = ['active', 'suspended', 'blocked', 'pending'];

        if (!in_array($status, $allowed, true)) {
            throw new HttpException(422, 'Status user tidak valid.', ['status' => 'Status harus salah satu dari: ' . implode(', ', $allowed)]);
        }

        $userModel = new UserModel();
        $updated = $userModel->updateStatus($userId, $status);
        if (!$updated) {
            throw new HttpException(404, 'User tidak ditemukan atau status gagal diubah.');
        }

        return $this->success([], 'Status user berhasil diubah.');
    }

    public function transactions(): array
    {
        $type = trim((string) ($_GET['type'] ?? ''));
        $model = new TransactionModel();
        return $this->success(['transactions' => $model->listAll($type !== '' ? $type : null)], 'Daftar transaksi berhasil dimuat.');
    }

    public function approveTransaction(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $errors = Validator::required($payload, ['transaction_id', 'admin_id']);
        if ($errors) {
            throw new HttpException(422, 'Validasi gagal.', $errors);
        }

        $transactionId = (int) $payload['transaction_id'];
        $adminId = (int) $payload['admin_id'];

        $model = new TransactionModel();
        $approved = $model->approve($transactionId, $adminId);
        if (!$approved) {
            throw new HttpException(404, 'Transaksi tidak ditemukan atau sudah diproses.');
        }

        return $this->success([], 'Transaksi berhasil disetujui.');
    }

    public function rejectTransaction(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $errors = Validator::required($payload, ['transaction_id', 'admin_id']);
        if ($errors) {
            throw new HttpException(422, 'Validasi gagal.', $errors);
        }

        $transactionId = (int) $payload['transaction_id'];
        $adminId = (int) $payload['admin_id'];
        $reason = trim((string) ($payload['reason'] ?? ''));

        $model = new TransactionModel();
        $rejected = $model->reject($transactionId, $adminId, $reason);
        if (!$rejected) {
            throw new HttpException(404, 'Transaksi tidak ditemukan atau sudah diproses.');
        }

        return $this->success([], 'Transaksi berhasil ditolak.');
    }

    public function investmentPlans(): array
    {
        $model = new InvestmentPlanModel();
        return $this->success(['plans' => $model->all()], 'Daftar skema investasi berhasil dimuat.');
    }

    public function saveInvestmentPlan(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $errors = Validator::required($payload, ['name', 'minimum_amount', 'interest_rate', 'duration_days']);
        if ($errors) {
            throw new HttpException(422, 'Validasi gagal.', $errors);
        }

        $model = new InvestmentPlanModel();
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $saved = $id > 0 ? $model->update($id, $payload) : $model->create($payload);

        if (!$saved) {
            throw new HttpException(500, 'Skema investasi gagal disimpan.');
        }

        return $this->success([], $id > 0 ? 'Skema investasi berhasil diperbarui.' : 'Skema investasi berhasil dibuat.');
    }

    public function settings(): array
    {
        $settingsModel = new SettingsModel();
        return $this->success(['settings' => $settingsModel->getAll()], 'Pengaturan aplikasi berhasil dimuat.');
    }

    public function saveSettings(array $input = []): array
    {
        $payload = $input ?: Request::input();
        if (!isset($payload['site_name']) || trim((string) $payload['site_name']) === '') {
            throw new HttpException(422, 'site_name wajib diisi.');
        }

        $settingsModel = new SettingsModel();
        $settingsModel->update($payload);

        return $this->success([], 'Pengaturan aplikasi berhasil disimpan.');
    }
}
