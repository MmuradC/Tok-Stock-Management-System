-- Create databases
CREATE DATABASE IF NOT EXISTS zencart_db;
CREATE DATABASE IF NOT EXISTS osticket_db;
CREATE DATABASE IF NOT EXISTS owncloud_db;
CREATE DATABASE IF NOT EXISTS tok_stock_db;

USE tok_stock_db;

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    category_id INT,
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    price_purchase DECIMAL(10,2) DEFAULT 0.00,
    price_sale DECIMAL(10,2) DEFAULT 0.00,
    excel_sync_id VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 3. Stock Logs Table
CREATE TABLE IF NOT EXISTS stock_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    change_amount INT NOT NULL,
    action_type ENUM('IN', 'OUT', 'ADJUSTMENT') NOT NULL,
    user_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert dummy data for initial testing
INSERT INTO categories (name, description) VALUES ('Electronics', 'Electronic items and gadgets');
INSERT INTO products (sku, name, category_id, stock_quantity, price_purchase, price_sale) 
VALUES ('SKU-001', 'Test Laptop', 1, 10, 500.00, 750.00);
