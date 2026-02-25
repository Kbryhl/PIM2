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

        if ($action !== 'delete') {
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported action']);
            exit;
        }

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
