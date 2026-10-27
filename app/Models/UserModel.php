<?php

namespace App\Models;

use Core\Database;
use PDO;
use PDOException;

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->connection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email))]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $payload): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, status, created_at) VALUES (:name, :email, :password_hash, :status, NOW())'
        );
        $stmt->execute([
            ':name' => trim((string) $payload['name']),
            ':email' => strtolower(trim((string) $payload['email'])),
            ':password_hash' => password_hash((string) $payload['password'], PASSWORD_BCRYPT),
            ':status' => $payload['status'] ?? 'active',
        ]);

        $userId = $this->db->lastInsertId();

        $accountStmt = $this->db->prepare(
            'INSERT INTO user_accounts (user_id, balance, currency, created_at) VALUES (:user_id, 0, :currency, NOW())'
        );
        $accountStmt->execute([
            ':user_id' => $userId,
            ':currency' => $payload['currency'] ?? 'IDR',
        ]);

        return ['id' => (int) $userId];
    }

    public function listAll(): array
    {
        $stmt = $this->db->query(
            'SELECT u.*, ua.balance FROM users u LEFT JOIN user_accounts ua ON ua.user_id = u.id ORDER BY u.created_at DESC'
        );

        return $stmt->fetchAll();
    }

    public function updateStatus(int $userId, string $status): bool
    {
        $allowed = ['active', 'suspended', 'blocked', 'pending'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function setTwoFactor(int $userId, bool $enabled): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET two_factor_enabled = :enabled WHERE id = :id');
        $stmt->execute([':enabled' => $enabled ? 1 : 0, ':id' => $userId]);

        return $stmt->rowCount() > 0;
    }
}
