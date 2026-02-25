<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (is_file($autoloadPath)) {
    require_once $autoloadPath;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ProductRepository.php';
require_once __DIR__ . '/../services/ExcelImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDbConnection();
    $repository = new ProductRepository($pdo);
    $importService = new ExcelImportService($repository);

    $action = trim((string) ($_POST['action'] ?? 'start'));

    if ($action === 'process') {
        $jobId = trim((string) ($_POST['job_id'] ?? ''));
        $chunkSize = (int) ($_POST['chunk_size'] ?? 250);

        if ($jobId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing job_id for chunk processing.']);
            exit;
        }

        $result = $importService->processImportChunk($jobId, $chunkSize);
    } else {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded.']);
            exit;
        }

        $sheetName = trim((string) ($_POST['sheet_name'] ?? 'AQUADANA'));
        if ($sheetName === '') {
            $sheetName = 'AQUADANA';
        }

        $file = $_FILES['file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Upload failed.']);
            exit;
        }

        $tmpPath = (string) $file['tmp_name'];
        $originalName = (string) $file['name'];

        $result = $importService->startImport($tmpPath, $originalName, $sheetName);
    }

    if (($result['success'] ?? false) === false) {
        http_response_code(400);
    }

    echo json_encode(['data' => $result]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error while importing file.',
        'details' => $exception->getMessage(),
    ]);
}
