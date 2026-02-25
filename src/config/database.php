<?php

declare(strict_types=1);

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = envValue('DB_URL', envValue('MYSQL_URL', ''));

    if ($databaseUrl !== '') {
        [$host, $port, $dbName, $username, $password] = parseDatabaseUrl($databaseUrl);
    } else {
        $host = envValue('DB_HOST', '127.0.0.1');
        $port = envValue('DB_PORT', '3306');
        $dbName = envValue('DB_NAME', 'pim2');
        $username = envValue('DB_USER', 'root');
        $password = envValue('DB_PASS', '');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    ensureSchema($pdo);

    return $pdo;
}

function envValue(string $key, string $default): string
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function parseDatabaseUrl(string $databaseUrl): array
{
    $parts = parse_url($databaseUrl);

    if (!is_array($parts)) {
        return ['127.0.0.1', '3306', 'pim2', 'root', ''];
    }

    $host = (string) ($parts['host'] ?? '127.0.0.1');
    $port = (string) ($parts['port'] ?? 3306);
    $path = (string) ($parts['path'] ?? '/pim2');
    $dbName = ltrim($path, '/');
    $username = (string) ($parts['user'] ?? 'root');
    $password = (string) ($parts['pass'] ?? '');

    return [$host, $port, $dbName, $username, $password];
}

function ensureSchema(PDO $pdo): void
{
        $pdo->exec('
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
                )
        ');

        $pdo->exec('
                CREATE TABLE IF NOT EXISTS import_logs (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    sheet_name VARCHAR(120) NOT NULL,
                    file_name VARCHAR(255) NOT NULL,
                    rows_imported INT UNSIGNED NOT NULL DEFAULT 0,
                    rows_skipped INT UNSIGNED NOT NULL DEFAULT 0,
                    message TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_logs_sheet_name (sheet_name)
                )
        ');
}
