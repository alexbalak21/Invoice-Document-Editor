CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL DEFAULT '',
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    auth_version  INT UNSIGNED NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT NOW(),
    updated_at    DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45)  NOT NULL,
    email        VARCHAR(190) NOT NULL DEFAULT '',
    attempted_at DATETIME     NOT NULL DEFAULT NOW(),
    INDEX idx_attempts_ip_time (ip, attempted_at),
    INDEX idx_attempts_pair_time (ip, email, attempted_at),
    INDEX idx_attempts_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
