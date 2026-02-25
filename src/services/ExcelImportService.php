<?php

declare(strict_types=1);

final class ExcelImportService
{
    public function __construct(private ProductRepository $repository)
    {
    }

    public function import(string $filePath, string $originalName, string $sheetName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => $this->importCsv($filePath, $originalName, $sheetName),
            'xlsx' => $this->importXlsx($filePath, $originalName, $sheetName),
            default => [
                'success' => false,
                'rowsImported' => 0,
                'rowsSkipped' => 0,
                'message' => 'Unsupported file type. Use CSV or XLSX.',
            ],
        };
    }

    private function importCsv(string $filePath, string $originalName, string $sheetName): array
    {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return [
                'success' => false,
                'rowsImported' => 0,
                'rowsSkipped' => 0,
                'message' => 'Could not open CSV file.',
            ];
        }

        $headers = null;
        $delimiter = null;
        $imported = 0;
        $skipped = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line == '') {
                continue;
            }

            if ($delimiter === null) {
                $delimiter = substr_count($line, ';') >= substr_count($line, ',') ? ';' : ',';
            }

            $data = str_getcsv($line, $delimiter);

            if ($headers === null) {
                $headers = array_map(static fn ($h) => (string) $h, $data);
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? null;
            }

            $ok = $this->repository->upsertProduct($row, $sheetName);
            if ($ok) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);

        $message = "CSV import finished for {$sheetName}.";
        $this->repository->writeImportLog($sheetName, $originalName, $imported, $skipped, $message);

        return [
            'success' => true,
            'rowsImported' => $imported,
            'rowsSkipped' => $skipped,
            'message' => $message,
        ];
    }

    private function importXlsx(string $filePath, string $originalName, string $sheetName): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return [
                'success' => false,
                'rowsImported' => 0,
                'rowsSkipped' => 0,
                'message' => 'XLSX import requires phpoffice/phpspreadsheet. Run composer require phpoffice/phpspreadsheet or export the sheet as CSV.',
            ];
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray();
        if (count($rows) < 2) {
            return [
                'success' => false,
                'rowsImported' => 0,
                'rowsSkipped' => 0,
                'message' => 'XLSX file has no data rows.',
            ];
        }

        $headers = array_map(static fn ($h) => (string) $h, (array) $rows[0]);

        $imported = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $line) {
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $line[$i] ?? null;
            }

            $ok = $this->repository->upsertProduct($row, $sheetName);
            if ($ok) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        $message = "XLSX import finished for {$sheetName}.";
        $this->repository->writeImportLog($sheetName, $originalName, $imported, $skipped, $message);

        return [
            'success' => true,
            'rowsImported' => $imported,
            'rowsSkipped' => $skipped,
            'message' => $message,
        ];
    }
}
