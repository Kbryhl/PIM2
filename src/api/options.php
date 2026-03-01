<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/OptionRepository.php';
require_once __DIR__ . '/../services/ProductRepository.php';

try {
    $pdo = getDbConnection();
    $optionRepository = new OptionRepository($pdo);
    $productRepository = new ProductRepository($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $group = trim((string) ($_GET['group'] ?? ''));

        if ($group !== '') {
            if (!$optionRepository->isAllowedGroup($group)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid group']);
                exit;
            }

            $values = $optionRepository->listByGroup($group);
            if ($values === []) {
                $seedValues = match ($group) {
                    'smagsvarianter' => $productRepository->getDistinctSmagsvarianter(),
                    'form_varianter' => $productRepository->getDistinctFormVarianter(),
                    'folie_varianter' => $productRepository->getDistinctFolieVarianter(),
                    'finish' => $productRepository->getDistinctFinishOptions(),
                    'bestil_interval_unit' => $productRepository->getDistinctBestilIntervalUnits(),
                    default => [],
                };

                foreach ($seedValues as $seedValue) {
                    $optionRepository->addOption($group, (string) $seedValue);
                }

                $values = $optionRepository->listByGroup($group);
            }

            echo json_encode(['data' => ['group' => $group, 'values' => $values]]);
            exit;
        }

        echo json_encode(['data' => ['groups' => $optionRepository->listGrouped()]]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawBody = file_get_contents('php://input');
        $payload = is_string($rawBody) && $rawBody !== '' ? json_decode($rawBody, true) : [];
        if (!is_array($payload)) {
            $payload = [];
        }

        $action = trim((string) ($payload['action'] ?? ''));
        $group = trim((string) ($payload['group'] ?? ''));

        if (!$optionRepository->isAllowedGroup($group)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid group']);
            exit;
        }

        if ($action === 'add') {
            $value = trim((string) ($payload['value'] ?? ''));
            if ($value === '') {
                http_response_code(400);
                echo json_encode(['error' => 'value is required']);
                exit;
            }

            $ok = $optionRepository->addOption($group, $value);
            echo json_encode(['data' => ['success' => $ok, 'values' => $optionRepository->listByGroup($group)]]);
            exit;
        }

        if ($action === 'rename') {
            $oldValue = trim((string) ($payload['old_value'] ?? ''));
            $newValue = trim((string) ($payload['new_value'] ?? ''));
            if ($oldValue === '' || $newValue === '') {
                http_response_code(400);
                echo json_encode(['error' => 'old_value and new_value are required']);
                exit;
            }

            $ok = $optionRepository->renameOption($group, $oldValue, $newValue);
            $affectedProducts = 0;
            if ($ok) {
                $affectedProducts = $optionRepository->applyRenameToProducts($group, $oldValue, $newValue);
            }

            echo json_encode([
                'data' => [
                    'success' => $ok,
                    'affected_products' => $affectedProducts,
                    'values' => $optionRepository->listByGroup($group),
                ],
            ]);
            exit;
        }

        if ($action === 'delete') {
            $value = trim((string) ($payload['value'] ?? ''));
            if ($value === '') {
                http_response_code(400);
                echo json_encode(['error' => 'value is required']);
                exit;
            }

            $ok = $optionRepository->deleteOption($group, $value);
            $affectedProducts = 0;
            if ($ok) {
                $affectedProducts = $optionRepository->applyDeleteToProducts($group, $value);
            }

            echo json_encode([
                'data' => [
                    'success' => $ok,
                    'affected_products' => $affectedProducts,
                    'values' => $optionRepository->listByGroup($group),
                ],
            ]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Unsupported action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error while handling options.',
        'details' => $exception->getMessage(),
    ]);
}
