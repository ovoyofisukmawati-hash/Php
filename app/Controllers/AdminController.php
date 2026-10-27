<?php

namespace App\Controllers;

use App\Models\InvestmentPlanModel;
use App\Models\SettingsModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use Core\BaseController;
use Core\Request;

class AdminController extends BaseController
{
    public function dashboard(): array
    {
        $db = new \PDO(
            'mysql:host=' . getenv('DB_HOST') ?: '127.0.0.1' . ';dbname=' . getenv('DB_DATABASE') ?: 'php_app' . ';charset=utf8mb4',
            getenv('DB_USERNAME') ?: 'root',
            getenv('DB_PASSWORD') ?: ''
        );

        $summary = [
            'total_users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'active_users' => (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'total_balance' => (float) $db->query('SELECT COALESCE(SUM(balance), 0) FROM user_accounts')->fetchColumn(),
            'total_deposit' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND status = 'approved'")->fetchColumn(),
            'total_withdrawal' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdrawal' AND status = 'approved'")->fetchColumn(),
            'pending_transactions' => (int) $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn(),
            'active_investment_plans' => (int) $db->query("SELECT COUNT(*) FROM investment_plans WHERE is_active = 1")->fetchColumn(),
            'total_referrals' => (float) $db->query('SELECT COALESCE(SUM(amount), 0) FROM referral_commissions')->fetchColumn(),
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

    public function updateUserStatus(): array
    {
        $payload = Request::input();
        $userId = (int) ($payload['user_id'] ?? 0);
        $status = trim((string) ($payload['status'] ?? ''));

        if ($userId <= 0 || $status === '') {
            return $this->error('user_id dan status wajib diisi.');
        }

        $userModel = new UserModel();
        $updated = $userModel->updateStatus($userId, $status);

        if (!$updated) {
            return $this->error('Status user gagal diubah.');
        }

        return $this->success([], 'Status user berhasil diubah.');
    }

    public function transactions(): array
    {
        $type = trim((string) ($_GET['type'] ?? ''));
        $model = new TransactionModel();
        return $this->success(['transactions' => $model->listAll($type !== '' ? $type : null)], 'Daftar transaksi berhasil dimuat.');
    }

    public function approveTransaction(): array
    {
        $payload = Request::input();
        $transactionId = (int) ($payload['transaction_id'] ?? 0);
        $adminId = (int) ($payload['admin_id'] ?? 0);

        if ($transactionId <= 0 || $adminId <= 0) {
            return $this->error('transaction_id dan admin_id wajib diisi.');
        }

        $model = new TransactionModel();
        $approved = $model->approve($transactionId, $adminId);

        if (!$approved) {
            return $this->error('Transaksi tidak ditemukan atau sudah diproses.');
        }

        return $this->success([], 'Transaksi berhasil disetujui.');
    }

    public function rejectTransaction(): array
    {
        $payload = Request::input();
        $transactionId = (int) ($payload['transaction_id'] ?? 0);
        $adminId = (int) ($payload['admin_id'] ?? 0);
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($transactionId <= 0 || $adminId <= 0) {
            return $this->error('transaction_id dan admin_id wajib diisi.');
        }

        $model = new TransactionModel();
        $rejected = $model->reject($transactionId, $adminId, $reason);

        if (!$rejected) {
            return $this->error('Transaksi tidak ditemukan atau sudah diproses.');
        }

        return $this->success([], 'Transaksi berhasil ditolak.');
    }

    public function investmentPlans(): array
    {
        $model = new InvestmentPlanModel();
        return $this->success(['plans' => $model->all()], 'Daftar skema investasi berhasil dimuat.');
    }

    public function saveInvestmentPlan(): array
    {
        $payload = Request::input();
        $model = new InvestmentPlanModel();

        foreach (['name', 'minimum_amount', 'interest_rate', 'duration_days'] as $field) {
            if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                return $this->error("Field {$field} wajib diisi.");
            }
        }

        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $ok = $id > 0 ? $model->update($id, $payload) : $model->create($payload);

        if (!$ok) {
            return $this->error('Skema investasi gagal disimpan.');
        }

        return $this->success([], $id > 0 ? 'Skema investasi berhasil diperbarui.' : 'Skema investasi berhasil dibuat.');
    }

    public function settings(): array
    {
        $model = new SettingsModel();
        return $this->success(['settings' => $model->getAll()], 'Pengaturan aplikasi berhasil dimuat.');
    }

    public function saveSettings(): array
    {
        $payload = Request::input();

        if (!isset($payload['site_name']) || trim((string) $payload['site_name']) === '') {
            return $this->error('site_name wajib diisi.');
        }

        $model = new SettingsModel();
        $model->update($payload);

        return $this->success([], 'Pengaturan aplikasi berhasil disimpan.');
    }
}
