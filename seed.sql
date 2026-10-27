-- =========================================================
-- Seed Data Example
-- =========================================================
-- Gunakan hash bcrypt berikut sebagai contoh default password:
-- password -> $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- Anda bisa generate hash password yang baru via PHP:
-- password_hash('admin123', PASSWORD_BCRYPT)

INSERT INTO users (
    id,
    name,
    email,
    password_hash,
    phone,
    status,
    role,
    two_factor_enabled,
    referral_code,
    referred_by,
    created_at,
    updated_at
) VALUES
(
    1,
    'Admin Sistem',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '081234567890',
    'active',
    'admin',
    0,
    'ADM123',
    NULL,
    NOW(),
    NOW()
),
(
    2,
    'Investor Demo',
    'user@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '081298765432',
    'active',
    'user',
    0,
    'USR456',
    NULL,
    NOW(),
    NOW()
);

INSERT INTO user_accounts (user_id, balance, currency, created_at, updated_at)
VALUES
(1, 100000000.00, 'IDR', NOW(), NOW()),
(2, 5000000.00, 'IDR', NOW(), NOW());

INSERT INTO investment_plans (
    name,
    minimum_amount,
    interest_rate,
    duration_days,
    is_active,
    description,
    created_at,
    updated_at
) VALUES
('Silver Plan', 1000000.00, 12.50, 30, 1, 'Plan investasi harian/silver', NOW(), NOW()),
('Gold Plan', 5000000.00, 15.00, 60, 1, 'Plan investasi menengah/gold', NOW(), NOW()),
('Platinum Plan', 10000000.00, 20.00, 90, 1, 'Plan investasi premium/platinum', NOW(), NOW());

INSERT INTO app_settings (
    setting_key,
    setting_value,
    created_at,
    updated_at
) VALUES
('site_name', 'Php Investment Platform', NOW(), NOW()),
('currency', 'IDR', NOW(), NOW()),
('timezone', 'Asia/Jakarta', NOW(), NOW()),
('maintenance_mode', '0', NOW(), NOW());

INSERT INTO transactions (
    user_id,
    type,
    status,
    amount,
    currency,
    description,
    created_at,
    updated_at
) VALUES
(2, 'deposit', 'approved', 5000000.00, 'IDR', 'Deposit awal user', NOW(), NOW()),
(2, 'investment', 'approved', 2000000.00, 'IDR', 'Investasi ke Silver Plan', NOW(), NOW());
