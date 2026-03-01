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
                'stk_pr_kolli' => trim((string) ($payload['stk_pr_kolli'] ?? $_POST['stk_pr_kolli'] ?? '')),
                'stk_1_4_pl' => trim((string) ($payload['stk_1_4_pl'] ?? $_POST['stk_1_4_pl'] ?? '')),
                'stk_1_2_pl' => trim((string) ($payload['stk_1_2_pl'] ?? $_POST['stk_1_2_pl'] ?? '')),
                'stk_1_1_pl' => trim((string) ($payload['stk_1_1_pl'] ?? $_POST['stk_1_1_pl'] ?? '')),
                'inkl_fragt' => $payload['inkl_fragt'] ?? $_POST['inkl_fragt'] ?? '',
                'bestil_interval' => trim((string) ($payload['bestil_interval'] ?? $_POST['bestil_interval'] ?? '')),
                'bestil_interval_unit' => trim((string) ($payload['bestil_interval_unit'] ?? $_POST['bestil_interval_unit'] ?? '')),
                'min_ordre' => trim((string) ($payload['min_ordre'] ?? $_POST['min_ordre'] ?? '')),
                'leveringstid' => trim((string) ($payload['leveringstid'] ?? $_POST['leveringstid'] ?? '')),
                'produktionstid' => trim((string) ($payload['produktionstid'] ?? $_POST['produktionstid'] ?? '')),
                'net_weight_grams' => trim((string) ($payload['net_weight_grams'] ?? $_POST['net_weight_grams'] ?? '')),
                'gross_weight_grams' => trim((string) ($payload['gross_weight_grams'] ?? $_POST['gross_weight_grams'] ?? '')),
                'holdbarhed_months' => trim((string) ($payload['holdbarhed_months'] ?? $_POST['holdbarhed_months'] ?? '')),
                'glutenfri' => $payload['glutenfri'] ?? $_POST['glutenfri'] ?? '',
                'veggie' => $payload['veggie'] ?? $_POST['veggie'] ?? '',
                'vegan' => $payload['vegan'] ?? $_POST['vegan'] ?? '',
                'komposterbar' => $payload['komposterbar'] ?? $_POST['komposterbar'] ?? '',
                'smagsvarianter' => $payload['smagsvarianter'] ?? $_POST['smagsvarianter'] ?? [],
                'form_varianter' => $payload['form_varianter'] ?? $_POST['form_varianter'] ?? [],
                'folie_varianter' => $payload['folie_varianter'] ?? $_POST['folie_varianter'] ?? [],
                'finish' => $payload['finish'] ?? $_POST['finish'] ?? [],
                'description' => trim((string) ($payload['description'] ?? $_POST['description'] ?? '')),
                'category' => $payload['category'] ?? $_POST['category'] ?? '',
                'price' => trim((string) ($payload['price'] ?? $_POST['price'] ?? '')),
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
                    'levering_text' => $savedExtraData['levering_text'] ?? null,
                    'product_photo_url' => $savedExtraData['product_photo_url'] ?? null,
                    'datablad_url' => $savedExtraData['datablad_url'] ?? null,
                    'smagsvarianter' => $savedExtraData['smagsvarianter'] ?? [],
                    'form_varianter' => $savedExtraData['form_varianter'] ?? [],
                    'folie_varianter' => $savedExtraData['folie_varianter'] ?? [],
                    'finish' => $savedExtraData['finish'] ?? [],
                ],
            ]);
            exit;
        }

        if ($action === 'update') {
            $id = (int) ($payload['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'id is required']);
                exit;
            }

            $row = [
                'sheet_name' => trim((string) ($payload['sheet_name'] ?? $_POST['sheet_name'] ?? 'SIGDETSØDT')),
                'sku' => trim((string) ($payload['sku'] ?? $_POST['sku'] ?? '')),
                'product_name' => trim((string) ($payload['product_name'] ?? $_POST['product_name'] ?? '')),
                'active' => $payload['active'] ?? $_POST['active'] ?? '',
                'barcode' => trim((string) ($payload['barcode'] ?? $_POST['barcode'] ?? '')),
                'hostedshop_id' => trim((string) ($payload['hostedshop_id'] ?? $_POST['hostedshop_id'] ?? '')),
                'supplier' => trim((string) ($payload['supplier'] ?? $_POST['supplier'] ?? '')),
                'brand' => trim((string) ($payload['brand'] ?? $_POST['brand'] ?? '')),
                'stk_pr_kolli' => trim((string) ($payload['stk_pr_kolli'] ?? $_POST['stk_pr_kolli'] ?? '')),
                'stk_1_4_pl' => trim((string) ($payload['stk_1_4_pl'] ?? $_POST['stk_1_4_pl'] ?? '')),
                'stk_1_2_pl' => trim((string) ($payload['stk_1_2_pl'] ?? $_POST['stk_1_2_pl'] ?? '')),
                'stk_1_1_pl' => trim((string) ($payload['stk_1_1_pl'] ?? $_POST['stk_1_1_pl'] ?? '')),
                'inkl_fragt' => $payload['inkl_fragt'] ?? $_POST['inkl_fragt'] ?? '',
                'bestil_interval' => trim((string) ($payload['bestil_interval'] ?? $_POST['bestil_interval'] ?? '')),
                'bestil_interval_unit' => trim((string) ($payload['bestil_interval_unit'] ?? $_POST['bestil_interval_unit'] ?? '')),
                'min_ordre' => trim((string) ($payload['min_ordre'] ?? $_POST['min_ordre'] ?? '')),
                'leveringstid' => trim((string) ($payload['leveringstid'] ?? $_POST['leveringstid'] ?? '')),
                'produktionstid' => trim((string) ($payload['produktionstid'] ?? $_POST['produktionstid'] ?? '')),
                'net_weight_grams' => trim((string) ($payload['net_weight_grams'] ?? $_POST['net_weight_grams'] ?? '')),
                'gross_weight_grams' => trim((string) ($payload['gross_weight_grams'] ?? $_POST['gross_weight_grams'] ?? '')),
                'holdbarhed_months' => trim((string) ($payload['holdbarhed_months'] ?? $_POST['holdbarhed_months'] ?? '')),
                'glutenfri' => $payload['glutenfri'] ?? $_POST['glutenfri'] ?? '',
                'veggie' => $payload['veggie'] ?? $_POST['veggie'] ?? '',
                'vegan' => $payload['vegan'] ?? $_POST['vegan'] ?? '',
                'komposterbar' => $payload['komposterbar'] ?? $_POST['komposterbar'] ?? '',
                'smagsvarianter' => $payload['smagsvarianter'] ?? $_POST['smagsvarianter'] ?? [],
                'form_varianter' => $payload['form_varianter'] ?? $_POST['form_varianter'] ?? [],
                'folie_varianter' => $payload['folie_varianter'] ?? $_POST['folie_varianter'] ?? [],
                'finish' => $payload['finish'] ?? $_POST['finish'] ?? [],
                'description' => trim((string) ($payload['description'] ?? $_POST['description'] ?? '')),
                'category' => $payload['category'] ?? $_POST['category'] ?? '',
                'price' => trim((string) ($payload['price'] ?? $_POST['price'] ?? '')),
            ];

            if (($row['product_name'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'product_name is required']);
                exit;
            }

            $saved = $repository->updateProductById($id, $row);
            if (!$saved) {
                http_response_code(400);
                echo json_encode(['error' => 'Could not update product']);
                exit;
            }

            $updatedProduct = $repository->getProductById($id);
            $updatedExtraData = is_array($updatedProduct['extra_data'] ?? null) ? $updatedProduct['extra_data'] : [];

            echo json_encode([
                'data' => [
                    'saved' => true,
                    'id' => $id,
                    'sheet_name' => $updatedProduct['sheet_name'] ?? null,
                    'change_log' => $updatedExtraData['change_log'] ?? null,
                    'tara_weight_grams' => $updatedExtraData['tara_weight_grams'] ?? null,
                    'holdbarhed_text' => $updatedExtraData['holdbarhed_text'] ?? null,
                    'levering_text' => $updatedExtraData['levering_text'] ?? null,
                    'product_photo_url' => $updatedExtraData['product_photo_url'] ?? null,
                    'datablad_url' => $updatedExtraData['datablad_url'] ?? null,
                    'smagsvarianter' => $updatedExtraData['smagsvarianter'] ?? [],
                    'form_varianter' => $updatedExtraData['form_varianter'] ?? [],
                    'folie_varianter' => $updatedExtraData['folie_varianter'] ?? [],
                    'finish' => $updatedExtraData['finish'] ?? [],
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
    if (isset($_GET['categories'])) {
        echo json_encode([
            'data' => [
                'categories' => $repository->getDistinctCategories(),
            ],
        ]);
        exit;
    }

    if (isset($_GET['smagsvarianter'])) {
        echo json_encode([
            'data' => [
                'smagsvarianter' => $repository->getDistinctSmagsvarianter(),
            ],
        ]);
        exit;
    }

    if (isset($_GET['formvarianter'])) {
        echo json_encode([
            'data' => [
                'formvarianter' => $repository->getDistinctFormVarianter(),
            ],
        ]);
        exit;
    }

    if (isset($_GET['folievarianter'])) {
        echo json_encode([
            'data' => [
                'folievarianter' => $repository->getDistinctFolieVarianter(),
            ],
        ]);
        exit;
    }

    if (isset($_GET['finish'])) {
        echo json_encode([
            'data' => [
                'finish' => $repository->getDistinctFinishOptions(),
            ],
        ]);
        exit;
    }

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
