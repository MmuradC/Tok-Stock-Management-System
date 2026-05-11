-- ============================================================
-- TOK-STOCK DATABASE v2.0
-- Multi-company stock management system
-- ============================================================

CREATE DATABASE IF NOT EXISTS zencart_db;
CREATE DATABASE IF NOT EXISTS osticket_db;
CREATE DATABASE IF NOT EXISTS owncloud_db;
CREATE DATABASE IF NOT EXISTS tok_stock_db;

-- Dedicated users for third-party services.
-- Using CREATE USER + GRANT in the same init script is reliable in MariaDB 10.11.
-- tok_admin is already created by the Docker entrypoint for tok_stock_db only.
CREATE USER IF NOT EXISTS 'osticket_user'@'%' IDENTIFIED BY 'osticket_pass';
GRANT ALL PRIVILEGES ON osticket_db.* TO 'osticket_user'@'%';

CREATE USER IF NOT EXISTS 'owncloud_user'@'%' IDENTIFIED BY 'owncloud_pass';
GRANT ALL PRIVILEGES ON owncloud_db.* TO 'owncloud_user'@'%';

CREATE USER IF NOT EXISTS 'zencart_user'@'%' IDENTIFIED BY 'zencart_pass';
GRANT ALL PRIVILEGES ON zencart_db.* TO 'zencart_user'@'%';

USE tok_stock_db;

-- 1. Companies
CREATE TABLE IF NOT EXISTS companies (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Users
--    company_id NULL  => system_admin (cross-company access)
--    role: system_admin | company_admin | staff
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    company_id   INT DEFAULT NULL,
    name         VARCHAR(255) NOT NULL,
    email        VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role         ENUM('system_admin','company_admin','staff') NOT NULL DEFAULT 'staff',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 3. Categories (scoped by company)
CREATE TABLE IF NOT EXISTS categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name       VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- 4. Products (SKU unique per company)
CREATE TABLE IF NOT EXISTS products (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    company_id     INT NOT NULL,
    sku            VARCHAR(50) NOT NULL,
    name           VARCHAR(255) NOT NULL,
    category_id    INT DEFAULT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT NOT NULL DEFAULT 5,
    price_purchase DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    price_sale     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    supplier       VARCHAR(255) DEFAULT NULL,
    excel_sync_id  VARCHAR(100) DEFAULT NULL,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sku (sku),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 5. Stock movement log
CREATE TABLE IF NOT EXISTS stock_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    company_id   INT NOT NULL,
    product_id   INT NOT NULL,
    change_amount INT NOT NULL,
    action_type  ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
    user_id      INT DEFAULT NULL,
    notes        TEXT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 6. Orders
CREATE TABLE IF NOT EXISTS orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    company_id     INT NOT NULL,
    customer_name  VARCHAR(255) DEFAULT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    status         ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes          TEXT DEFAULT NULL,
    created_by     INT DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 7. Order items
CREATE TABLE IF NOT EXISTS order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT NOT NULL,
    product_id   INT NOT NULL,
    quantity     INT NOT NULL,
    price_at_sale DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED: Default company only.
-- User accounts are created via setup.php on first run.
-- ============================================================
INSERT INTO companies (name) VALUES ('Tok-Stock Inc.');