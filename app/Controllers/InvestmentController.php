<?php

namespace App\Controllers;

use App\Exceptions\HttpException;
use App\Models\InvestmentPlanModel;
use App\Models\TransactionModel;
use Core\BaseController;
use Core\Request;
use Core\Validator;

class InvestmentController extends BaseController
{
    public function plans(): array
    {
        $model = new InvestmentPlanModel();
        return $this->success(['plans' => $model->all()], 'Skema investasi berhasil dimuat.');
    }

    public function invest(array $input = []): array
    {
        $payload = $input ?: Request::input();
        $errors = Validator::required($payload, ['user_id', 'plan_id', 'amount']);
        if ($errors) {
            throw new HttpException(422, 'Validasi gagal.', $errors);
        }

        $transactionModel = new TransactionModel();
        $transactionId = $transactionModel->create([
            'user_id' => (int) $payload['user_id'],
            'type' => 'investment',
            'amount' => (float) $payload['amount'],
            'currency' => $payload['currency'] ?? 'IDR',
            'status' => 'pending',
            'description' => 'Investasi skema #' . (int) $payload['plan_id'],
        ]);

        return $this->success(['transaction_id' => $transactionId], 'Investasi berhasil dibuat dan menunggu review.');
    }

    public function history(): array
    {
        $userId = (int) ($_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new HttpException(400, 'user_id wajib diisi.');
        }

        $db = new \PDO(
            'mysql:host=' . getenv('DB_HOST') ?: '127.0.0.1' . ';dbname=' . getenv('DB_DATABASE') ?: 'php_app' . ';charset=utf8mb4',
            getenv('DB_USERNAME') ?: 'root',
            getenv('DB_PASSWORD') ?: ''
        );

        $stmt = $db->prepare('SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute([':user_id' => $userId]);

        return $this->success(['history' => $stmt->fetchAll()], 'Riwayat transaksi berhasil dimuat.');
    }
}
