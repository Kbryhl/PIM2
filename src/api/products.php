<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ProductRepository.php';

try {
    $pdo = getDbConnection();
    $repository = new ProductRepository($pdo);

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
