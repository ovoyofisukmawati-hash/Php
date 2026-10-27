<?php

namespace App\Models;

use Core\Database;
use PDO;

class SettingsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->connection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM app_settings');
        $settings = [];

        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public function update(array $payload): bool
    {
        $allowed = ['site_name', 'currency', 'timezone', 'maintenance_mode', 'google_recaptcha_key', 'tawk_id'];

        $stmt = $this->db->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (:key, :value, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );

        foreach ($payload as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            $stmt->execute([
                ':key' => $key,
                ':value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            ]);
        }

        return true;
    }
}
