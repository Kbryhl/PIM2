CREATE DATABASE IF NOT EXISTS pim2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pim2;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sheet_name VARCHAR(120) NOT NULL,
  sku VARCHAR(190) NULL,
  product_name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category VARCHAR(190) NULL,
  price DECIMAL(12,2) NULL,
  currency VARCHAR(10) NULL,
  weight VARCHAR(60) NULL,
  dimensions VARCHAR(120) NULL,
  shipping_info TEXT NULL,
  extra_data JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_sheet_sku (sheet_name, sku),
  INDEX idx_sheet_name (sheet_name),
  INDEX idx_product_name (product_name)
);

CREATE TABLE IF NOT EXISTS import_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sheet_name VARCHAR(120) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  rows_imported INT UNSIGNED NOT NULL DEFAULT 0,
  rows_skipped INT UNSIGNED NOT NULL DEFAULT 0,
  message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_sheet_name (sheet_name)
);
