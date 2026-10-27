CREATE DATABASE IF NOT EXISTS php_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE php_app;

-- =========================================================
-- 1. USERS
-- =========================================================
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    status ENUM('pending', 'active', 'suspended', 'blocked') NOT NULL DEFAULT 'pending',
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    referral_code VARCHAR(50) NULL,
    referred_by BIGINT UNSIGNED NULL,
    remember_token VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_referral_code (referral_code),
    KEY idx_users_status (status),
    KEY idx_users_role (role),
    KEY idx_users_referred_by (referred_by),
    CONSTRAINT fk_users_referred_by
        FOREIGN KEY (referred_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 2. USER ACCOUNTS
-- =========================================================
CREATE TABLE user_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_accounts_user_currency (user_id, currency),
    KEY idx_user_accounts_user_id (user_id),
    CONSTRAINT fk_user_accounts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 3. TRANSACTIONS
-- =========================================================
CREATE TABLE transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('deposit', 'withdrawal', 'investment', 'bonus', 'commission', 'referral', 'manual_adjustment') NOT NULL,
    status ENUM('pending', 'processing', 'approved', 'rejected', 'failed') NOT NULL DEFAULT 'pending',
    amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
    payment_method_id BIGINT UNSIGNED NULL,
    reference_code VARCHAR(100) NULL,
    description TEXT NULL,
    admin_note TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_transactions_user_id (user_id),
    KEY idx_transactions_type (type),
    KEY idx_transactions_status (status),
    KEY idx_transactions_created_at (created_at),
    CONSTRAINT fk_transactions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_transactions_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. INVESTMENT PLANS
-- =========================================================
CREATE TABLE investment_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    minimum_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    interest_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration_days INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_investment_plans_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. INVESTMENTS
-- =========================================================
CREATE TABLE investments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
    status ENUM('pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    profit_earned DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_investments_user_id (user_id),
    KEY idx_investments_plan_id (plan_id),
    KEY idx_investments_status (status),
    CONSTRAINT fk_investments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_investments_plan
        FOREIGN KEY (plan_id) REFERENCES investment_plans(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 6. REFERRAL COMMISSIONS
-- =========================================================
CREATE TABLE referral_commissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    referral_user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
    source VARCHAR(100) NOT NULL DEFAULT 'registration',
    status ENUM('pending', 'approved', 'paid') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_referral_commissions_user_id (user_id),
    KEY idx_referral_commissions_referral_user_id (referral_user_id),
    CONSTRAINT fk_referral_commissions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_referral_commissions_referral_user
        FOREIGN KEY (referral_user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 7. APP SETTINGS
-- =========================================================
CREATE TABLE app_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_settings_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 8. OPTIONAL: PAYMENT METHODS
-- =========================================================
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL,
    type ENUM('bank_transfer', 'ewallet', 'virtual_account', 'qris', 'manual') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_methods_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 9. OPTIONAL: USER SESSIONS
-- =========================================================
CREATE TABLE user_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    refresh_token VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_sessions_user_id (user_id),
    KEY idx_user_sessions_expires_at (expires_at),
    CONSTRAINT fk_user_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 10. OPTIONAL: ACTIVITY LOGS
-- =========================================================
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_logs_user_id (user_id),
    KEY idx_activity_logs_admin_id (admin_id),
    KEY idx_activity_logs_created_at (created_at),
    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_activity_logs_admin
        FOREIGN KEY (admin_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$

CREATE TRIGGER trg_transactions_balance_after_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' THEN
        INSERT INTO user_accounts (user_id, balance, currency, created_at, updated_at)
        VALUES (NEW.user_id, 0, NEW.currency, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            balance = balance + CASE
                WHEN NEW.type IN ('deposit', 'bonus', 'commission', 'referral') THEN NEW.amount
                WHEN NEW.type IN ('withdrawal', 'investment') THEN -NEW.amount
                ELSE 0
            END,
            updated_at = NOW();
    END IF;
END$$

CREATE TRIGGER trg_transactions_balance_after_update
AFTER UPDATE ON transactions
FOR EACH ROW
BEGIN
    IF OLD.status <> 'approved' AND NEW.status = 'approved' THEN
        INSERT INTO user_accounts (user_id, balance, currency, created_at, updated_at)
        VALUES (NEW.user_id, 0, NEW.currency, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            balance = balance + CASE
                WHEN NEW.type IN ('deposit', 'bonus', 'commission', 'referral') THEN NEW.amount
                WHEN NEW.type IN ('withdrawal', 'investment') THEN -NEW.amount
                ELSE 0
            END,
            updated_at = NOW();
    END IF;

    IF OLD.status = 'approved' AND NEW.status <> 'approved' THEN
        INSERT INTO user_accounts (user_id, balance, currency, created_at, updated_at)
        VALUES (OLD.user_id, 0, OLD.currency, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            balance = balance - CASE
                WHEN OLD.type IN ('deposit', 'bonus', 'commission', 'referral') THEN OLD.amount
                WHEN OLD.type IN ('withdrawal', 'investment') THEN -OLD.amount
                ELSE 0
            END,
            updated_at = NOW();
    END IF;
END$$

DELIMITER ;
