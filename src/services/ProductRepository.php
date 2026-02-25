<?php

declare(strict_types=1);

final class ProductRepository
{
    private array $fieldMapping;

    public function __construct(private PDO $pdo)
    {
        $mappingPath = __DIR__ . '/../config/field-mapping.php';
        $this->fieldMapping = is_file($mappingPath) ? (array) require $mappingPath : ['fields' => [], 'sheets' => []];
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
        $normalized = $this->normalizeRow($row, $sheetName);

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

    private function normalizeRow(array $row, string $sheetName): array
    {
        $map = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeHeader((string) $key);
            $map[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        $aliases = $this->getAliasesForSheet($sheetName);

        $sku = $this->pickMappedValue($map, $aliases, 'sku');
        $productName = $this->pickMappedValue($map, $aliases, 'product_name');

        $knownAliasKeys = [];
        foreach ($aliases as $fieldAliases) {
            foreach ($fieldAliases as $fieldAlias) {
                $knownAliasKeys[$fieldAlias] = true;
            }
        }

        foreach (array_keys($aliases) as $canonicalField) {
            $knownAliasKeys[$canonicalField] = true;
        }

        $extraData = [];
        foreach ($map as $key => $value) {
            if (!isset($knownAliasKeys[$key])) {
                $extraData[$key] = $value;
            }
        }

        return [
            'sku' => $sku,
            'product_name' => $productName,
            'description' => $this->pickMappedValue($map, $aliases, 'description'),
            'category' => $this->pickMappedValue($map, $aliases, 'category'),
            'price' => $this->pickMappedValue($map, $aliases, 'price'),
            'currency' => $this->pickMappedValue($map, $aliases, 'currency'),
            'weight' => $this->pickMappedValue($map, $aliases, 'weight'),
            'dimensions' => $this->pickMappedValue($map, $aliases, 'dimensions'),
            'shipping_info' => $this->pickMappedValue($map, $aliases, 'shipping_info'),
            'extra_data' => $extraData,
        ];
    }

    private function getAliasesForSheet(string $sheetName): array
    {
        $global = (array) ($this->fieldMapping['fields'] ?? []);
        $sheets = (array) ($this->fieldMapping['sheets'] ?? []);

        $normalizedSheets = [];
        foreach ($sheets as $name => $mapping) {
            $normalizedSheets[$this->normalizeSheetName((string) $name)] = (array) $mapping;
        }

        $sheetSpecific = (array) ($normalizedSheets[$this->normalizeSheetName($sheetName)] ?? []);
        $fields = array_unique(array_merge(array_keys($global), array_keys($sheetSpecific)));

        $aliases = [];
        foreach ($fields as $field) {
            $merged = array_merge((array) ($sheetSpecific[$field] ?? []), (array) ($global[$field] ?? []), [$field]);
            $normalized = [];
            foreach ($merged as $alias) {
                $normalizedAlias = $this->normalizeHeader((string) $alias);
                if ($normalizedAlias !== '' && !in_array($normalizedAlias, $normalized, true)) {
                    $normalized[] = $normalizedAlias;
                }
            }
            $aliases[$field] = $normalized;
        }

        return $aliases;
    }

    private function pickMappedValue(array $map, array $aliases, string $field): mixed
    {
        foreach ((array) ($aliases[$field] ?? []) as $alias) {
            if (array_key_exists($alias, $map) && $this->hasValue($map[$alias])) {
                return $map[$alias];
            }
        }

        return null;
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    private function normalizeSheetName(string $sheetName): string
    {
        return $this->normalizeHeader($sheetName);
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
