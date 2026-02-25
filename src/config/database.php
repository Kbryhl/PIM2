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
