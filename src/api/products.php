<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ProductRepository.php';

try {
    $pdo = getDbConnection();
    $repository = new ProductRepository($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawBody = file_get_contents('php://input');
        $payload = is_string($rawBody) && $rawBody !== '' ? json_decode($rawBody, true) : [];

        if (!is_array($payload)) {
            $payload = [];
        }

        $action = trim((string) ($payload['action'] ?? $_POST['action'] ?? ''));

        if ($action === 'delete') {
            $ids = $payload['ids'] ?? $_POST['ids'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }

            $deletedCount = $repository->deleteProductsByIds($ids);

            echo json_encode([
                'data' => [
                    'deletedCount' => $deletedCount,
                ],
            ]);
            exit;
        }

        if ($action === 'create') {
            $sheetName = trim((string) ($payload['sheet_name'] ?? $_POST['sheet_name'] ?? 'SIGDETSØDT'));
            if ($sheetName === '') {
                $sheetName = 'SIGDETSØDT';
            }

            $row = [
                'sku' => trim((string) ($payload['sku'] ?? $_POST['sku'] ?? '')),
                'product_name' => trim((string) ($payload['product_name'] ?? $_POST['product_name'] ?? '')),
                'active' => $payload['active'] ?? $_POST['active'] ?? '',
                'barcode' => trim((string) ($payload['barcode'] ?? $_POST['barcode'] ?? '')),
                'hostedshop_id' => trim((string) ($payload['hostedshop_id'] ?? $_POST['hostedshop_id'] ?? '')),
                'supplier' => trim((string) ($payload['supplier'] ?? $_POST['supplier'] ?? '')),
                'brand' => trim((string) ($payload['brand'] ?? $_POST['brand'] ?? '')),
                'net_weight_grams' => trim((string) ($payload['net_weight_grams'] ?? $_POST['net_weight_grams'] ?? '')),
                'gross_weight_grams' => trim((string) ($payload['gross_weight_grams'] ?? $_POST['gross_weight_grams'] ?? '')),
                'holdbarhed_months' => trim((string) ($payload['holdbarhed_months'] ?? $_POST['holdbarhed_months'] ?? '')),
                'glutenfri' => $payload['glutenfri'] ?? $_POST['glutenfri'] ?? '',
                'veggie' => $payload['veggie'] ?? $_POST['veggie'] ?? '',
                'vegan' => $payload['vegan'] ?? $_POST['vegan'] ?? '',
                'komposterbar' => $payload['komposterbar'] ?? $_POST['komposterbar'] ?? '',
                'description' => trim((string) ($payload['description'] ?? $_POST['description'] ?? '')),
                'category' => trim((string) ($payload['category'] ?? $_POST['category'] ?? '')),
                'price' => trim((string) ($payload['price'] ?? $_POST['price'] ?? '')),
                'currency' => trim((string) ($payload['currency'] ?? $_POST['currency'] ?? '')),
                'weight' => trim((string) ($payload['weight'] ?? $_POST['weight'] ?? '')),
                'dimensions' => trim((string) ($payload['dimensions'] ?? $_POST['dimensions'] ?? '')),
                'shipping_info' => trim((string) ($payload['shipping_info'] ?? $_POST['shipping_info'] ?? '')),
            ];

            if (($row['product_name'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'product_name is required']);
                exit;
            }

            $saved = $repository->upsertProduct($row, $sheetName);
            if (!$saved) {
                http_response_code(400);
                echo json_encode(['error' => 'Could not save product']);
                exit;
            }

            $savedProduct = $repository->getProductBySheetAndSku($sheetName, (string) $row['sku']);
            $savedExtraData = is_array($savedProduct['extra_data'] ?? null) ? $savedProduct['extra_data'] : [];

            echo json_encode([
                'data' => [
                    'saved' => true,
                    'sheet_name' => $sheetName,
                    'change_log' => $savedExtraData['change_log'] ?? null,
                    'tara_weight_grams' => $savedExtraData['tara_weight_grams'] ?? null,
                    'holdbarhed_text' => $savedExtraData['holdbarhed_text'] ?? null,
                    'product_photo_url' => $savedExtraData['product_photo_url'] ?? null,
                    'datablad_url' => $savedExtraData['datablad_url'] ?? null,
                ],
            ]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Unsupported action']);
        exit;
    }

    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $product = $repository->getProductById($id);

        if ($product === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            exit;
        }

        echo json_encode(['data' => $product]);
        exit;
    }

    $sheet = isset($_GET['sheet']) ? trim((string) $_GET['sheet']) : '';
    $query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $perPage = isset($_GET['perPage']) ? (int) $_GET['perPage'] : 20;

    $result = $repository->listProducts($sheet, $query, $page, $perPage);

    echo json_encode(['data' => $result]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error while loading products.',
        'details' => $exception->getMessage(),
    ]);
}
