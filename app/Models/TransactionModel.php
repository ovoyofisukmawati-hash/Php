<?php

namespace App\Models;

use Core\Database;
use PDO;

class TransactionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->connection();
    }

    public function listAll(?string $type = null): array
    {
        $sql = 'SELECT * FROM transactions';
        $params = [];

        if ($type) {
            $sql .= ' WHERE type = :type';
            $params[':type'] = $type;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function create(array $payload): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions (user_id, type, amount, currency, status, description, created_at) VALUES (:user_id, :type, :amount, :currency, :status, :description, NOW())'
        );
        $stmt->execute([
            ':user_id' => (int) $payload['user_id'],
            ':type' => $payload['type'],
            ':amount' => (float) $payload['amount'],
            ':currency' => $payload['currency'] ?? 'IDR',
            ':status' => $payload['status'] ?? 'pending',
            ':description' => $payload['description'] ?? '',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function approve(int $transactionId, int $adminId): bool
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

        return $stmt->rowCount() > 0;
    }

    public function reject(int $transactionId, int $adminId, string $reason = ''): bool
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

        return $stmt->rowCount() > 0;
    }

    public function summary(): array
    {
        return [
            'total_deposit' => (float) $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND status = 'approved'")->fetchColumn(),
            'total_withdrawal' => (float) $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdrawal' AND status = 'approved'")->fetchColumn(),
            'pending' => (int) $this->db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn(),
        ];
    }
}
