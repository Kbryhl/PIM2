<?php

declare(strict_types=1);

final class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listProducts(string $sheet = '', string $query = '', int $page = 1, int $perPage = 20): array
    {
        $page = max($page, 1);
        $perPage = min(max($perPage, 1), 100);
        $offset = ($page - 1) * $perPage;

        $whereParts = [];
        $params = [];

        if ($sheet !== '') {
            $whereParts[] = 'sheet_name = :sheet';
            $params['sheet'] = $sheet;
        }

        if ($query !== '') {
            $whereParts[] = '(product_name LIKE :query OR sku LIKE :query OR category LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        $countSql = "SELECT COUNT(*) FROM products {$whereSql}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "
            SELECT id, sheet_name, sku, product_name, description, category, price, currency, weight, dimensions, shipping_info, updated_at
            FROM products
            {$whereSql}
            ORDER BY updated_at DESC, id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    public function getProductById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        $product['extra_data'] = $product['extra_data'] ? json_decode((string) $product['extra_data'], true) : [];

        return $product;
    }

    public function upsertProduct(array $row, string $sheetName): bool
    {
        $normalized = $this->normalizeRow($row);

        $sku = $normalized['sku'] ?? null;
        $productName = $normalized['product_name'] ?? null;

        if (!$productName) {
            return false;
        }

        $sql = '
            INSERT INTO products (
                sheet_name, sku, product_name, description, category, price, currency, weight, dimensions, shipping_info, extra_data
            ) VALUES (
                :sheet_name, :sku, :product_name, :description, :category, :price, :currency, :weight, :dimensions, :shipping_info, :extra_data
            )
            ON DUPLICATE KEY UPDATE
                product_name = VALUES(product_name),
                description = VALUES(description),
                category = VALUES(category),
                price = VALUES(price),
                currency = VALUES(currency),
                weight = VALUES(weight),
                dimensions = VALUES(dimensions),
                shipping_info = VALUES(shipping_info),
                extra_data = VALUES(extra_data)
        ';

        $stmt = $this->pdo->prepare($sql);

        $extraData = $normalized['extra_data'] ?? [];

        return $stmt->execute([
            'sheet_name' => $sheetName,
            'sku' => $sku,
            'product_name' => $productName,
            'description' => $normalized['description'] ?? null,
            'category' => $normalized['category'] ?? null,
            'price' => $this->toNullableDecimal($normalized['price'] ?? null),
            'currency' => $normalized['currency'] ?? null,
            'weight' => $normalized['weight'] ?? null,
            'dimensions' => $normalized['dimensions'] ?? null,
            'shipping_info' => $normalized['shipping_info'] ?? null,
            'extra_data' => json_encode($extraData, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function writeImportLog(string $sheetName, string $fileName, int $rowsImported, int $rowsSkipped, string $message = ''): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO import_logs (sheet_name, file_name, rows_imported, rows_skipped, message)
            VALUES (:sheet_name, :file_name, :rows_imported, :rows_skipped, :message)
        ');

        $stmt->execute([
            'sheet_name' => $sheetName,
            'file_name' => $fileName,
            'rows_imported' => $rowsImported,
            'rows_skipped' => $rowsSkipped,
            'message' => $message,
        ]);
    }

    private function normalizeRow(array $row): array
    {
        $map = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeHeader((string) $key);
            $map[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        $sku = $map['sku'] ?? $map['varenummer'] ?? $map['itemnumber'] ?? $map['item_no'] ?? null;
        $productName = $map['product_name'] ?? $map['name'] ?? $map['produktnavn'] ?? $map['product'] ?? null;

        $knownKeys = [
            'sku', 'varenummer', 'itemnumber', 'item_no',
            'product_name', 'name', 'produktnavn', 'product',
            'description', 'beskrivelse',
            'category', 'kategori',
            'price', 'pris',
            'currency', 'valuta',
            'weight', 'vaegt',
            'dimensions', 'dimensioner',
            'shipping_info', 'shipping',
        ];

        $extraData = [];
        foreach ($map as $key => $value) {
            if (!in_array($key, $knownKeys, true)) {
                $extraData[$key] = $value;
            }
        }

        return [
            'sku' => $sku,
            'product_name' => $productName,
            'description' => $map['description'] ?? $map['beskrivelse'] ?? null,
            'category' => $map['category'] ?? $map['kategori'] ?? null,
            'price' => $map['price'] ?? $map['pris'] ?? null,
            'currency' => $map['currency'] ?? $map['valuta'] ?? null,
            'weight' => $map['weight'] ?? $map['vaegt'] ?? null,
            'dimensions' => $map['dimensions'] ?? $map['dimensioner'] ?? null,
            'shipping_info' => $map['shipping_info'] ?? $map['shipping'] ?? null,
            'extra_data' => $extraData,
        ];
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = str_replace(['æ', 'ø', 'å'], ['ae', 'oe', 'aa'], $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    private function toNullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = str_replace([' ', ','], ['', '.'], (string) $value);

        if (!is_numeric($string)) {
            return null;
        }

        return number_format((float) $string, 2, '.', '');
    }
}
