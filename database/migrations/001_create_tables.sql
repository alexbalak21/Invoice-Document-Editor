-- DocEditor — MySQL schema. Import it into the database you selected
-- (php database/migrate.php does this for you, or use phpMyAdmin's Import tab).

CREATE TABLE IF NOT EXISTS documents (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type        VARCHAR(50)  NOT NULL DEFAULT 'INVOICE',
    number      VARCHAR(100) NOT NULL DEFAULT '',
    date        DATE         NOT NULL,
    status      ENUM('draft','final','paid') NOT NULL DEFAULT 'draft',
    customer    VARCHAR(255) NOT NULL DEFAULT '',
    data        JSON         NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT NOW(),
    updated_at  DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_type     (type),
    INDEX idx_status   (status),
    INDEX idx_customer (customer),
    INDEX idx_updated  (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_numbers (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prefix    VARCHAR(10)  NOT NULL,
    date_key  VARCHAR(8)   NOT NULL,
    counter   INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_prefix_date (prefix, date_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL DEFAULT '',
    address    VARCHAR(255) NOT NULL DEFAULT '',
    city       VARCHAR(255) NOT NULL DEFAULT '',
    contact    VARCHAR(255) NOT NULL DEFAULT '',
    phone      VARCHAR(100) NOT NULL DEFAULT '',
    vat        VARCHAR(100) NOT NULL DEFAULT '',
    created_at DATETIME     NOT NULL DEFAULT NOW(),
    updated_at DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference   VARCHAR(100) NOT NULL DEFAULT '',
    title       VARCHAR(255) NOT NULL DEFAULT '',
    unit        VARCHAR(100) NOT NULL DEFAULT '',
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT         NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT NOW(),
    updated_at  DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_reference (reference),
    INDEX idx_title     (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
