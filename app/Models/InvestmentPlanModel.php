<?php

namespace App\Models;

use Core\Database;
use PDO;

class InvestmentPlanModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM investment_plans ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $payload): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO investment_plans (name, minimum_amount, interest_rate, duration_days, is_active, description, created_at, updated_at) VALUES (:name, :minimum_amount, :interest_rate, :duration_days, :is_active, :description, NOW(), NOW())'
        );

        return $stmt->execute([
            ':name' => trim((string) $payload['name']),
            ':minimum_amount' => (float) $payload['minimum_amount'],
            ':interest_rate' => (float) $payload['interest_rate'],
            ':duration_days' => (int) $payload['duration_days'],
            ':is_active' => (int) ($payload['is_active'] ?? 1),
            ':description' => trim((string) ($payload['description'] ?? '')),
        ]);
    }

    public function update(int $id, array $payload): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE investment_plans SET name = :name, minimum_amount = :minimum_amount, interest_rate = :interest_rate, duration_days = :duration_days, is_active = :is_active, description = :description, updated_at = NOW() WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim((string) $payload['name']),
            ':minimum_amount' => (float) $payload['minimum_amount'],
            ':interest_rate' => (float) $payload['interest_rate'],
            ':duration_days' => (int) $payload['duration_days'],
            ':is_active' => (int) ($payload['is_active'] ?? 1),
            ':description' => trim((string) ($payload['description'] ?? '')),
        ]);
    }
}
