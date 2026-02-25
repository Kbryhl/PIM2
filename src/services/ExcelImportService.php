<?php

declare(strict_types=1);

final class ExcelImportService
{
    private string $jobsDir;
    private string $uploadsDir;

    public function __construct(private ProductRepository $repository)
    {
        $this->jobsDir = dirname(__DIR__, 2) . '/storage/import-jobs';
        $this->uploadsDir = dirname(__DIR__, 2) . '/public/uploads';

        $this->ensureDirectory($this->jobsDir);
        $this->ensureDirectory($this->uploadsDir);
    }

    public function startImport(string $tmpPath, string $originalName, string $sheetName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            return [
                'success' => false,
                'message' => 'Unsupported file type. Use CSV or XLSX.',
            ];
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName) ?? 'import_file';
        $jobId = bin2hex(random_bytes(8));
        $storedFilePath = $this->uploadsDir . '/' . $jobId . '_' . $safeName;

        if (!move_uploaded_file($tmpPath, $storedFilePath)) {
            if (!@rename($tmpPath, $storedFilePath)) {
                return [
                    'success' => false,
                    'message' => 'Could not store uploaded file.',
                ];
            }
        }

        $metadata = $extension === 'csv'
            ? $this->analyzeCsv($storedFilePath)
            : $this->analyzeXlsx($storedFilePath, $sheetName);

        if (($metadata['success'] ?? false) === false) {
            @unlink($storedFilePath);
            return [
                'success' => false,
                'message' => (string) ($metadata['message'] ?? 'Could not prepare import.'),
            ];
        }

        $job = [
            'jobId' => $jobId,
            'sheetName' => $sheetName,
            'originalName' => $originalName,
            'filePath' => $storedFilePath,
            'extension' => $extension,
            'totalRows' => (int) ($metadata['totalRows'] ?? 0),
            'processedRows' => 0,
            'rowsImported' => 0,
            'rowsSkipped' => 0,
            'status' => 'pending',
            'createdAt' => date(DATE_ATOM),
            'delimiter' => $metadata['delimiter'] ?? null,
            'headers' => $metadata['headers'] ?? [],
            'fileOffset' => $metadata['fileOffset'] ?? null,
            'xlsxSheetName' => $metadata['xlsxSheetName'] ?? null,
            'xlsxHighestColumnIndex' => $metadata['xlsxHighestColumnIndex'] ?? null,
        ];

        $this->saveJob($job);

        return [
            'success' => true,
            'jobId' => $jobId,
            'sheetName' => $sheetName,
            'totalRows' => $job['totalRows'],
            'processedRows' => 0,
            'progressPercent' => 0,
            'isComplete' => false,
            'message' => 'Upload complete. Import job created.',
        ];
    }

    public function processImportChunk(string $jobId, int $chunkSize = 250): array
    {
        $chunkSize = min(max($chunkSize, 25), 1000);
        $job = $this->loadJob($jobId);

        if ($job === null) {
            return [
                'success' => false,
                'message' => 'Import job not found.',
            ];
        }

        if (($job['status'] ?? '') === 'completed') {
            return $this->formatJobResponse($job, 'Import already completed.');
        }

        $job['status'] = 'running';

        if ((int) $job['totalRows'] === 0) {
            $job['status'] = 'completed';
            $this->saveJob($job);

            return $this->formatJobResponse($job, 'No data rows found in file.');
        }

        $result = ($job['extension'] ?? '') === 'csv'
            ? $this->processCsvChunk($job, $chunkSize)
            : $this->processXlsxChunk($job, $chunkSize);

        if (($result['success'] ?? false) === false) {
            $job['status'] = 'failed';
            $this->saveJob($job);

            return [
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Import chunk failed.'),
            ];
        }

        $job['processedRows'] = min((int) $job['totalRows'], (int) $job['processedRows'] + (int) $result['processedRows']);
        $job['rowsImported'] = (int) $job['rowsImported'] + (int) $result['rowsImported'];
        $job['rowsSkipped'] = (int) $job['rowsSkipped'] + (int) $result['rowsSkipped'];

        if (array_key_exists('fileOffset', $result)) {
            $job['fileOffset'] = $result['fileOffset'];
        }

        $isComplete = (int) $job['processedRows'] >= (int) $job['totalRows'];

        if ($isComplete) {
            $job['status'] = 'completed';
            $this->repository->writeImportLog(
                (string) $job['sheetName'],
                (string) $job['originalName'],
                (int) $job['rowsImported'],
                (int) $job['rowsSkipped'],
                'Chunk import finished.'
            );
        }

        $this->saveJob($job);

        return $this->formatJobResponse($job, $isComplete ? 'Import completed.' : 'Chunk processed.');
    }

    private function analyzeCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return ['success' => false, 'message' => 'Could not open CSV file.'];
        }

        $headerLine = null;
        $delimiter = ';';
        $headers = [];
        $totalRows = 0;
        $fileOffset = 0;

        while (($line = fgets($handle)) !== false) {
            if (trim($line) === '') {
                continue;
            }

            $headerLine = $line;
            break;
        }

        if ($headerLine === null) {
            fclose($handle);

            return [
                'success' => true,
                'totalRows' => 0,
                'headers' => [],
                'delimiter' => ';',
                'fileOffset' => 0,
            ];
        }

        $delimiter = substr_count($headerLine, ';') >= substr_count($headerLine, ',') ? ';' : ',';
        $headers = array_map(static fn ($value) => (string) $value, str_getcsv(trim($headerLine), $delimiter));
        $fileOffset = ftell($handle) ?: 0;

        while (($line = fgets($handle)) !== false) {
            if (trim($line) !== '') {
                $totalRows++;
            }
        }

        fclose($handle);

        return [
            'success' => true,
            'totalRows' => $totalRows,
            'headers' => $headers,
            'delimiter' => $delimiter,
            'fileOffset' => $fileOffset,
        ];
    }

    private function analyzeXlsx(string $filePath, string $sheetName): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return [
                'success' => false,
                'message' => 'XLSX import requires phpoffice/phpspreadsheet. Please make sure dependencies are installed.',
            ];
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headers = [];
        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $headers[] = (string) $sheet->getCell([$column, 1])->getFormattedValue();
        }

        $selectedSheetTitle = (string) $sheet->getTitle();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'success' => true,
            'totalRows' => max(0, $highestRow - 1),
            'headers' => $headers,
            'xlsxSheetName' => $selectedSheetTitle,
            'xlsxHighestColumnIndex' => $highestColumnIndex,
        ];
    }

    private function processCsvChunk(array $job, int $chunkSize): array
    {
        $handle = fopen((string) $job['filePath'], 'rb');
        if ($handle === false) {
            return ['success' => false, 'message' => 'Could not open CSV for chunk import.'];
        }

        $delimiter = (string) ($job['delimiter'] ?? ';');
        $headers = is_array($job['headers'] ?? null) ? $job['headers'] : [];
        $fileOffset = (int) ($job['fileOffset'] ?? 0);

        if ($fileOffset > 0) {
            fseek($handle, $fileOffset);
        }

        $processedRows = 0;
        $rowsImported = 0;
        $rowsSkipped = 0;

        while (($line = fgets($handle)) !== false) {
            $fileOffset = ftell($handle) ?: $fileOffset;
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv(trim($line), $delimiter);
            $row = $this->combineRow($headers, $values);

            $ok = $this->repository->upsertProduct($row, (string) $job['sheetName']);
            if ($ok) {
                $rowsImported++;
            } else {
                $rowsSkipped++;
            }

            $processedRows++;

            if ($processedRows >= $chunkSize) {
                break;
            }
        }

        fclose($handle);

        return [
            'success' => true,
            'processedRows' => $processedRows,
            'rowsImported' => $rowsImported,
            'rowsSkipped' => $rowsSkipped,
            'fileOffset' => $fileOffset,
        ];
    }

    private function processXlsxChunk(array $job, int $chunkSize): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return ['success' => false, 'message' => 'XLSX support is not available on server.'];
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load((string) $job['filePath']);
        $sheet = $spreadsheet->getSheetByName((string) ($job['xlsxSheetName'] ?? ''))
            ?? $spreadsheet->getSheetByName((string) $job['sheetName'])
            ?? $spreadsheet->getActiveSheet();

        $highestRow = (int) $sheet->getHighestDataRow();
        $highestColumnIndex = (int) ($job['xlsxHighestColumnIndex'] ?? 0);
        if ($highestColumnIndex <= 0) {
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        }

        $headers = is_array($job['headers'] ?? null) ? $job['headers'] : [];
        $startRow = 2 + (int) $job['processedRows'];
        $endRow = min($highestRow, $startRow + $chunkSize - 1);

        $processedRows = 0;
        $rowsImported = 0;
        $rowsSkipped = 0;

        for ($rowIndex = $startRow; $rowIndex <= $endRow; $rowIndex++) {
            $values = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $values[] = $sheet->getCell([$column, $rowIndex])->getFormattedValue();
            }

            $row = $this->combineRow($headers, $values);
            $ok = $this->repository->upsertProduct($row, (string) $job['sheetName']);
            if ($ok) {
                $rowsImported++;
            } else {
                $rowsSkipped++;
            }

            $processedRows++;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'success' => true,
            'processedRows' => $processedRows,
            'rowsImported' => $rowsImported,
            'rowsSkipped' => $rowsSkipped,
        ];
    }

    private function combineRow(array $headers, array $values): array
    {
        $row = [];
        foreach ($headers as $index => $header) {
            $row[(string) $header] = $values[$index] ?? null;
        }

        return $row;
    }

    private function formatJobResponse(array $job, string $message): array
    {
        $totalRows = max(0, (int) ($job['totalRows'] ?? 0));
        $processedRows = max(0, (int) ($job['processedRows'] ?? 0));
        $progressPercent = $totalRows > 0 ? (int) floor(($processedRows / $totalRows) * 100) : 100;

        return [
            'success' => true,
            'jobId' => (string) $job['jobId'],
            'sheetName' => (string) $job['sheetName'],
            'status' => (string) ($job['status'] ?? 'running'),
            'processedRows' => $processedRows,
            'totalRows' => $totalRows,
            'rowsImported' => (int) ($job['rowsImported'] ?? 0),
            'rowsSkipped' => (int) ($job['rowsSkipped'] ?? 0),
            'progressPercent' => min(100, $progressPercent),
            'isComplete' => ($job['status'] ?? '') === 'completed',
            'message' => $message,
        ];
    }

    private function loadJob(string $jobId): ?array
    {
        $path = $this->jobFilePath($jobId);
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function saveJob(array $job): void
    {
        file_put_contents(
            $this->jobFilePath((string) $job['jobId']),
            json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function jobFilePath(string $jobId): string
    {
        return $this->jobsDir . '/' . $jobId . '.json';
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
