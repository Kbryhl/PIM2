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
            SELECT id, sheet_name, sku, product_name, description, category, extra_data, updated_at
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

        foreach ($items as &$item) {
            $extraData = [];
            if (!empty($item['extra_data'])) {
                $decoded = json_decode((string) $item['extra_data'], true);
                if (is_array($decoded)) {
                    $extraData = $decoded;
                }
            }

            $item['active'] = (int) ($extraData['active'] ?? 0) === 1;
            $item['product_photo_url'] = (string) ($extraData['product_photo_url'] ?? '');
            unset($item['extra_data']);
        }
        unset($item);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
        ];
    }

    public function getDistinctCategories(): array
    {
        $stmt = $this->pdo->query("SELECT category FROM products WHERE category IS NOT NULL AND TRIM(category) <> ''");
        $rows = $stmt ? $stmt->fetchAll() : [];

        $categories = [];
        foreach ($rows as $row) {
            $raw = (string) ($row['category'] ?? '');
            foreach (explode(',', $raw) as $part) {
                $trimmed = trim($part);
                if ($trimmed !== '') {
                    $categories[$trimmed] = true;
                }
            }
        }

        $list = array_keys($categories);
        natcasesort($list);

        return array_values($list);
    }

    public function getDistinctSmagsvarianter(): array
    {
        return $this->getDistinctExtraDataList('smagsvarianter');
    }

    public function getDistinctFormVarianter(): array
    {
        return $this->getDistinctExtraDataList('form_varianter');
    }

    public function getDistinctFolieVarianter(): array
    {
        return $this->getDistinctExtraDataList('folie_varianter');
    }

    public function getDistinctFinishOptions(): array
    {
        return $this->getDistinctExtraDataList('finish');
    }

    public function getDistinctBestilIntervalUnits(): array
    {
        return $this->getDistinctExtraDataList('bestil_interval_unit');
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

        $existingProduct = $this->findBySheetAndSku($sheetName, $sku);
        $managedMeta = $this->buildManagedMeta($normalized, $existingProduct);

        $stmt = $this->pdo->prepare($sql);

        $extraData = $normalized['extra_data'] ?? [];
        if (is_array($managedMeta)) {
            $extraData = array_merge($extraData, $managedMeta);
        }

        return $stmt->execute([
            'sheet_name' => $sheetName,
            'sku' => $sku,
            'product_name' => $productName,
            'description' => $normalized['description'] ?? null,
            'category' => $this->normalizeCategoryForStorage($normalized['category'] ?? null),
            'price' => $this->toNullableDecimal($normalized['price'] ?? null),
            'currency' => $normalized['currency'] ?? null,
            'weight' => $normalized['weight'] ?? null,
            'dimensions' => $normalized['dimensions'] ?? null,
            'shipping_info' => $normalized['shipping_info'] ?? null,
            'extra_data' => json_encode($extraData, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function updateProductById(int $id, array $row): bool
    {
        if ($id <= 0) {
            return false;
        }

        $existing = $this->getProductById($id);
        if ($existing === null) {
            return false;
        }

        $sheetName = trim((string) ($row['sheet_name'] ?? $existing['sheet_name'] ?? 'SIGDETSØDT'));
        if ($sheetName === '') {
            $sheetName = 'SIGDETSØDT';
        }

        $normalized = $this->normalizeRow($row, $sheetName);

        $sku = $normalized['sku'] ?? $this->toNullableString($existing['sku'] ?? null);
        $productName = $normalized['product_name'] ?? $this->toNullableString($existing['product_name'] ?? null);

        if (!$productName) {
            return false;
        }

        $managedMeta = $this->buildManagedMeta($normalized, $existing);
        $extraData = is_array($normalized['extra_data'] ?? null) ? $normalized['extra_data'] : [];
        if (is_array($managedMeta)) {
            $extraData = array_merge($extraData, $managedMeta);
        }

        $sql = '
            UPDATE products
            SET
                sheet_name = :sheet_name,
                sku = :sku,
                product_name = :product_name,
                description = :description,
                category = :category,
                price = :price,
                currency = :currency,
                weight = :weight,
                dimensions = :dimensions,
                shipping_info = :shipping_info,
                extra_data = :extra_data
            WHERE id = :id
        ';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'sheet_name' => $sheetName,
            'sku' => $sku,
            'product_name' => $productName,
            'description' => $normalized['description'] ?? $this->toNullableString($existing['description'] ?? null),
            'category' => $this->normalizeCategoryForStorage($normalized['category'] ?? ($existing['category'] ?? null)),
            'price' => $this->toNullableDecimal($normalized['price'] ?? ($existing['price'] ?? null)),
            'currency' => $normalized['currency'] ?? $this->toNullableString($existing['currency'] ?? null),
            'weight' => $normalized['weight'] ?? $this->toNullableString($existing['weight'] ?? null),
            'dimensions' => $normalized['dimensions'] ?? $this->toNullableString($existing['dimensions'] ?? null),
            'shipping_info' => $normalized['shipping_info'] ?? $this->toNullableString($existing['shipping_info'] ?? null),
            'extra_data' => json_encode($extraData, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function getProductBySheetAndSku(string $sheetName, ?string $sku): ?array
    {
        return $this->findBySheetAndSku($sheetName, $sku);
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

    public function deleteProductsByIds(array $ids): int
    {
        $normalizedIds = [];
        foreach ($ids as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $normalizedIds[] = $intId;
            }
        }

        $normalizedIds = array_values(array_unique($normalizedIds));
        if ($normalizedIds === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id IN ({$placeholders})");
        $stmt->execute($normalizedIds);

        return $stmt->rowCount();
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
            'active' => $this->pickMappedValue($map, $aliases, 'active'),
            'barcode' => $this->pickMappedValue($map, $aliases, 'barcode'),
            'hostedshop_id' => $this->pickMappedValue($map, $aliases, 'hostedshop_id'),
            'supplier' => $this->pickMappedValue($map, $aliases, 'supplier'),
            'brand' => $this->pickMappedValue($map, $aliases, 'brand'),
            'stk_pr_kolli' => $this->pickMappedValue($map, $aliases, 'stk_pr_kolli'),
            'stk_1_4_pl' => $this->pickMappedValue($map, $aliases, 'stk_1_4_pl'),
            'stk_1_2_pl' => $this->pickMappedValue($map, $aliases, 'stk_1_2_pl'),
            'stk_1_1_pl' => $this->pickMappedValue($map, $aliases, 'stk_1_1_pl'),
            'inkl_fragt' => $this->pickMappedValue($map, $aliases, 'inkl_fragt'),
            'bestil_interval' => $this->pickMappedValue($map, $aliases, 'bestil_interval'),
            'bestil_interval_unit' => $this->pickMappedValue($map, $aliases, 'bestil_interval_unit'),
            'net_weight_grams' => $this->pickMappedValue($map, $aliases, 'net_weight_grams'),
            'gross_weight_grams' => $this->pickMappedValue($map, $aliases, 'gross_weight_grams'),
            'holdbarhed_months' => $this->pickMappedValue($map, $aliases, 'holdbarhed_months'),
            'glutenfri' => $this->pickMappedValue($map, $aliases, 'glutenfri'),
            'veggie' => $this->pickMappedValue($map, $aliases, 'veggie'),
            'vegan' => $this->pickMappedValue($map, $aliases, 'vegan'),
            'komposterbar' => $this->pickMappedValue($map, $aliases, 'komposterbar'),
            'smagsvarianter' => $this->pickMappedValue($map, $aliases, 'smagsvarianter'),
            'form_varianter' => $this->pickMappedValue($map, $aliases, 'form_varianter'),
            'folie_varianter' => $this->pickMappedValue($map, $aliases, 'folie_varianter'),
            'finish' => $this->pickMappedValue($map, $aliases, 'finish'),
            'extra_data' => $extraData,
        ];
    }

    private function findBySheetAndSku(string $sheetName, ?string $sku): ?array
    {
        $trimmedSku = trim((string) $sku);
        if ($trimmedSku === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE sheet_name = :sheet_name AND sku = :sku LIMIT 1');
        $stmt->execute([
            'sheet_name' => $sheetName,
            'sku' => $trimmedSku,
        ]);

        $product = $stmt->fetch();
        if (!$product) {
            return null;
        }

        $product['extra_data'] = $product['extra_data'] ? json_decode((string) $product['extra_data'], true) : [];

        return $product;
    }

    private function buildManagedMeta(array $normalized, ?array $existingProduct): array
    {
        $active = $this->toBooleanFlag($normalized['active'] ?? null);
        $barcode = $this->toNullableString($normalized['barcode'] ?? null);
        $hostedshopId = $this->toNullableString($normalized['hostedshop_id'] ?? null);
        $supplier = $this->toNullableString($normalized['supplier'] ?? null);
        $brand = $this->toNullableString($normalized['brand'] ?? null);
        $stkPrKolli = $this->toNullableInt($normalized['stk_pr_kolli'] ?? null);
        $stkQuarterPl = $this->toNullableInt($normalized['stk_1_4_pl'] ?? null);
        $stkHalfPl = $this->toNullableInt($normalized['stk_1_2_pl'] ?? null);
        $stkFullPl = $this->toNullableInt($normalized['stk_1_1_pl'] ?? null);
        $inklFragt = $this->toBooleanFlag($normalized['inkl_fragt'] ?? null);
        $bestilInterval = $this->toNullableInt($normalized['bestil_interval'] ?? null);
        $bestilIntervalUnit = $this->toNullableString($normalized['bestil_interval_unit'] ?? null);
        $netWeight = $this->toNullableInt($normalized['net_weight_grams'] ?? null);
        $grossWeight = $this->toNullableInt($normalized['gross_weight_grams'] ?? null);
        $taraWeight = $this->calculateTara($grossWeight, $netWeight);
        $holdbarhedMonths = $this->toNullableInt($normalized['holdbarhed_months'] ?? null);
        $holdbarhedText = $this->buildHoldbarhedText($holdbarhedMonths);
        $glutenfri = $this->toBooleanFlag($normalized['glutenfri'] ?? null);
        $veggie = $this->toBooleanFlag($normalized['veggie'] ?? null);
        $vegan = $this->toBooleanFlag($normalized['vegan'] ?? null);
        $komposterbar = $this->toBooleanFlag($normalized['komposterbar'] ?? null);
        $smagsvarianter = $this->normalizeStringList($normalized['smagsvarianter'] ?? []);
        $formVarianter = $this->normalizeStringList($normalized['form_varianter'] ?? []);
        $folieVarianter = $this->normalizeStringList($normalized['folie_varianter'] ?? []);
        $finish = $this->normalizeStringList($normalized['finish'] ?? []);

        $sku = $this->toNullableString($normalized['sku'] ?? null);
        $productPhotoUrl = $this->buildSigdetsoedtAssetUrl($sku, 'produktfoto', 'png');
        $databladUrl = $this->buildSigdetsoedtAssetUrl($sku, 'datablade', 'pdf');

        $currentSnapshot = [
            'sku' => $sku,
            'product_name' => $this->toNullableString($normalized['product_name'] ?? null),
            'active' => $active,
            'barcode' => $barcode,
            'hostedshop_id' => $hostedshopId,
            'supplier' => $supplier,
            'brand' => $brand,
            'stk_pr_kolli' => $stkPrKolli,
            'stk_1_4_pl' => $stkQuarterPl,
            'stk_1_2_pl' => $stkHalfPl,
            'stk_1_1_pl' => $stkFullPl,
            'inkl_fragt' => $inklFragt,
            'bestil_interval' => $bestilInterval,
            'bestil_interval_unit' => $bestilIntervalUnit,
            'net_weight_grams' => $netWeight,
            'gross_weight_grams' => $grossWeight,
            'tara_weight_grams' => $taraWeight,
            'holdbarhed_months' => $holdbarhedMonths,
            'holdbarhed_text' => $holdbarhedText,
            'glutenfri' => $glutenfri,
            'veggie' => $veggie,
            'vegan' => $vegan,
            'komposterbar' => $komposterbar,
            'smagsvarianter' => $smagsvarianter,
            'form_varianter' => $formVarianter,
            'folie_varianter' => $folieVarianter,
            'finish' => $finish,
            'product_photo_url' => $productPhotoUrl,
            'datablad_url' => $databladUrl,
        ];

        $previousSnapshot = $this->extractSnapshotFromExisting($existingProduct);
        $changeLog = $this->buildChangeLogText($currentSnapshot, $previousSnapshot);

        return [
            'active' => $active,
            'barcode' => $barcode,
            'hostedshop_id' => $hostedshopId,
            'supplier' => $supplier,
            'brand' => $brand,
            'stk_pr_kolli' => $stkPrKolli,
            'stk_1_4_pl' => $stkQuarterPl,
            'stk_1_2_pl' => $stkHalfPl,
            'stk_1_1_pl' => $stkFullPl,
            'inkl_fragt' => $inklFragt,
            'bestil_interval' => $bestilInterval,
            'bestil_interval_unit' => $bestilIntervalUnit,
            'net_weight_grams' => $netWeight,
            'gross_weight_grams' => $grossWeight,
            'tara_weight_grams' => $taraWeight,
            'holdbarhed_months' => $holdbarhedMonths,
            'holdbarhed_text' => $holdbarhedText,
            'glutenfri' => $glutenfri,
            'veggie' => $veggie,
            'vegan' => $vegan,
            'komposterbar' => $komposterbar,
            'smagsvarianter' => $smagsvarianter,
            'form_varianter' => $formVarianter,
            'folie_varianter' => $folieVarianter,
            'finish' => $finish,
            'product_photo_url' => $productPhotoUrl,
            'datablad_url' => $databladUrl,
            'change_log' => $changeLog,
            'last_saved_at' => date(DATE_ATOM),
        ];
    }

    private function extractSnapshotFromExisting(?array $existingProduct): ?array
    {
        if (!is_array($existingProduct)) {
            return null;
        }

        $extra = is_array($existingProduct['extra_data'] ?? null) ? $existingProduct['extra_data'] : [];

        return [
            'sku' => $this->toNullableString($existingProduct['sku'] ?? null),
            'product_name' => $this->toNullableString($existingProduct['product_name'] ?? null),
            'active' => $this->toNullableInt($extra['active'] ?? null),
            'barcode' => $this->toNullableString($extra['barcode'] ?? null),
            'hostedshop_id' => $this->toNullableString($extra['hostedshop_id'] ?? null),
            'supplier' => $this->toNullableString($extra['supplier'] ?? null),
            'brand' => $this->toNullableString($extra['brand'] ?? null),
            'stk_pr_kolli' => $this->toNullableInt($extra['stk_pr_kolli'] ?? null),
            'stk_1_4_pl' => $this->toNullableInt($extra['stk_1_4_pl'] ?? null),
            'stk_1_2_pl' => $this->toNullableInt($extra['stk_1_2_pl'] ?? null),
            'stk_1_1_pl' => $this->toNullableInt($extra['stk_1_1_pl'] ?? null),
            'inkl_fragt' => $this->toNullableInt($extra['inkl_fragt'] ?? null),
            'bestil_interval' => $this->toNullableInt($extra['bestil_interval'] ?? null),
            'bestil_interval_unit' => $this->toNullableString($extra['bestil_interval_unit'] ?? null),
            'net_weight_grams' => $this->toNullableInt($extra['net_weight_grams'] ?? null),
            'gross_weight_grams' => $this->toNullableInt($extra['gross_weight_grams'] ?? null),
            'tara_weight_grams' => $this->toNullableInt($extra['tara_weight_grams'] ?? null),
            'holdbarhed_months' => $this->toNullableInt($extra['holdbarhed_months'] ?? null),
            'holdbarhed_text' => $this->toNullableString($extra['holdbarhed_text'] ?? null),
            'glutenfri' => $this->toNullableInt($extra['glutenfri'] ?? null),
            'veggie' => $this->toNullableInt($extra['veggie'] ?? null),
            'vegan' => $this->toNullableInt($extra['vegan'] ?? null),
            'komposterbar' => $this->toNullableInt($extra['komposterbar'] ?? null),
            'smagsvarianter' => $this->normalizeStringList($extra['smagsvarianter'] ?? []),
            'form_varianter' => $this->normalizeStringList($extra['form_varianter'] ?? []),
            'folie_varianter' => $this->normalizeStringList($extra['folie_varianter'] ?? []),
            'finish' => $this->normalizeStringList($extra['finish'] ?? []),
            'product_photo_url' => $this->toNullableString($extra['product_photo_url'] ?? null),
            'datablad_url' => $this->toNullableString($extra['datablad_url'] ?? null),
        ];
    }

    private function buildChangeLogText(array $currentSnapshot, ?array $previousSnapshot): string
    {
        $timestamp = date('Y-m-d H:i');
        if ($previousSnapshot === null) {
            return $timestamp . ' - Created product';
        }

        $labels = [
            'sku' => 'SKU',
            'product_name' => 'Product name',
            'active' => 'Active',
            'barcode' => 'Barcode',
            'hostedshop_id' => 'HostedShop ID',
            'supplier' => 'Supplier',
            'brand' => 'Brand',
            'stk_pr_kolli' => 'Stk pr Kolli',
            'stk_1_4_pl' => 'Stk 1/4 pl',
            'stk_1_2_pl' => 'Stk 1/2 pl',
            'stk_1_1_pl' => 'Stk 1/1 pl',
            'inkl_fragt' => 'Inkl. Fragt',
            'bestil_interval' => 'Bestil Interval',
            'bestil_interval_unit' => 'Bestil Interval enhed',
            'net_weight_grams' => 'Nettovægt',
            'gross_weight_grams' => 'Bruttovægt',
            'tara_weight_grams' => 'Tara Weight',
            'holdbarhed_months' => 'Holdbarhed',
            'holdbarhed_text' => 'Holdbarhed tekst',
            'glutenfri' => 'Glutenfri',
            'veggie' => 'Veggie',
            'vegan' => 'Vegan',
            'komposterbar' => 'Komposterbar',
            'smagsvarianter' => 'Smagsvarianter',
            'form_varianter' => 'Form varianter',
            'folie_varianter' => 'Folie varianter',
            'finish' => 'Finish',
            'product_photo_url' => 'Product Photo',
            'datablad_url' => 'Datablad',
        ];

        $changed = [];
        foreach ($labels as $field => $label) {
            $before = $previousSnapshot[$field] ?? null;
            $after = $currentSnapshot[$field] ?? null;

            if ($this->valuesDiffer($before, $after)) {
                $changed[] = $label;
            }
        }

        if ($changed === []) {
            return $timestamp . ' - Saved (no field changes)';
        }

        return $timestamp . ' - Changed: ' . implode(', ', $changed);
    }

    private function toBooleanFlag(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $string = mb_strtolower(trim((string) $value));
        return in_array($string, ['1', 'true', 'yes', 'on', 'ja'], true) ? 1 : 0;
    }

    private function toNullableString(mixed $value): ?string
    {
        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = preg_replace('/[^0-9\-]/', '', (string) $value) ?? '';
        if ($string === '' || !is_numeric($string)) {
            return null;
        }

        return (int) $string;
    }

    private function calculateTara(?int $grossWeight, ?int $netWeight): ?int
    {
        if ($grossWeight === null || $netWeight === null) {
            return null;
        }

        return $grossWeight - $netWeight;
    }

    private function buildHoldbarhedText(?int $months): ?string
    {
        if ($months === null || $months <= 0) {
            return null;
        }

        return 'ca. ' . $months . ' måneder, ved korrekt opbevaring';
    }

    private function buildSigdetsoedtAssetUrl(?string $sku, string $folder, string $extension): ?string
    {
        $trimmedSku = trim((string) $sku);
        if ($trimmedSku === '') {
            return null;
        }

        $encodedSku = rawurlencode($trimmedSku);

        return 'https://filbank.dk/database/sigdetsoedt/' . $folder . '/' . $encodedSku . '.' . $extension;
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

    private function normalizeCategoryForStorage(mixed $value): ?string
    {
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $trimmed = trim((string) $item);
                if ($trimmed !== '') {
                    $parts[$trimmed] = true;
                }
            }

            if ($parts === []) {
                return null;
            }

            return implode(', ', array_keys($parts));
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        $parts = [];
        foreach (explode(',', $string) as $item) {
            $trimmed = trim($item);
            if ($trimmed !== '') {
                $parts[$trimmed] = true;
            }
        }

        if ($parts === []) {
            return null;
        }

        return implode(', ', array_keys($parts));
    }

    private function normalizeStringList(mixed $value): array
    {
        $items = [];

        if (is_array($value)) {
            $items = $value;
        } elseif ($value !== null) {
            $string = trim((string) $value);
            if ($string !== '') {
                $delimiter = str_contains($string, '|') ? '|' : ',';
                $items = explode($delimiter, $string);
            }
        }

        $normalized = [];
        foreach ($items as $item) {
            $trimmed = trim((string) $item);
            if ($trimmed !== '') {
                $normalized[$trimmed] = true;
            }
        }

        return array_values(array_keys($normalized));
    }

    private function getDistinctExtraDataList(string $key): array
    {
        $stmt = $this->pdo->query("SELECT extra_data FROM products WHERE extra_data IS NOT NULL AND TRIM(extra_data) <> ''");
        $rows = $stmt ? $stmt->fetchAll() : [];

        $variants = [];
        foreach ($rows as $row) {
            $raw = (string) ($row['extra_data'] ?? '');
            if ($raw === '') {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            $list = $this->normalizeStringList($decoded[$key] ?? []);
            foreach ($list as $variant) {
                $variants[$variant] = true;
            }
        }

        $result = array_keys($variants);
        natcasesort($result);

        return array_values($result);
    }

    private function valuesDiffer(mixed $before, mixed $after): bool
    {
        if (is_array($before) || is_array($after)) {
            $beforeJson = json_encode($before, JSON_UNESCAPED_UNICODE);
            $afterJson = json_encode($after, JSON_UNESCAPED_UNICODE);
            return $beforeJson !== $afterJson;
        }

        return (string) $before !== (string) $after;
    }
}
