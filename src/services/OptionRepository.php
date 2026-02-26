<?php

declare(strict_types=1);

final class OptionRepository
{
    private const GROUPS = [
        'smagsvarianter',
        'form_varianter',
        'folie_varianter',
        'finish',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function isAllowedGroup(string $group): bool
    {
        return in_array($group, self::GROUPS, true);
    }

    public function getAllGroups(): array
    {
        return self::GROUPS;
    }

    public function listByGroup(string $group): array
    {
        if (!$this->isAllowedGroup($group)) {
            return [];
        }

        $stmt = $this->pdo->prepare('SELECT option_value FROM managed_options WHERE option_group = :group ORDER BY option_value ASC');
        $stmt->execute(['group' => $group]);

        $values = [];
        foreach ($stmt->fetchAll() as $row) {
            $value = trim((string) ($row['option_value'] ?? ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function listGrouped(): array
    {
        $result = [];
        foreach (self::GROUPS as $group) {
            $result[$group] = $this->listByGroup($group);
        }

        return $result;
    }

    public function addOption(string $group, string $value): bool
    {
        $group = trim($group);
        $value = trim($value);

        if (!$this->isAllowedGroup($group) || $value === '') {
            return false;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO managed_options (option_group, option_value)
            VALUES (:group, :value)
            ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)
        ');

        return $stmt->execute([
            'group' => $group,
            'value' => $value,
        ]);
    }

    public function renameOption(string $group, string $oldValue, string $newValue): bool
    {
        $group = trim($group);
        $oldValue = trim($oldValue);
        $newValue = trim($newValue);

        if (!$this->isAllowedGroup($group) || $oldValue === '' || $newValue === '') {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE managed_options SET option_value = :new_value WHERE option_group = :group AND option_value = :old_value');

        return $stmt->execute([
            'group' => $group,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    public function applyRenameToProducts(string $group, string $oldValue, string $newValue): int
    {
        $group = trim($group);
        $oldValue = trim($oldValue);
        $newValue = trim($newValue);

        if (!$this->isAllowedGroup($group) || $oldValue === '' || $newValue === '') {
            return 0;
        }

        $products = $this->loadProductsWithExtraData();
        $updatedCount = 0;

        foreach ($products as $product) {
            $id = (int) ($product['id'] ?? 0);
            $decoded = $this->decodeExtraData((string) ($product['extra_data'] ?? ''));
            if ($id <= 0 || !is_array($decoded)) {
                continue;
            }

            $list = $this->normalizeList($decoded[$group] ?? []);
            if ($list === []) {
                continue;
            }

            $changed = false;
            foreach ($list as $index => $value) {
                if (mb_strtolower($value) === mb_strtolower($oldValue)) {
                    $list[$index] = $newValue;
                    $changed = true;
                }
            }

            if (!$changed) {
                continue;
            }

            $decoded[$group] = $this->normalizeList($list);
            if ($this->updateProductExtraData($id, $decoded)) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    public function deleteOption(string $group, string $value): bool
    {
        $group = trim($group);
        $value = trim($value);

        if (!$this->isAllowedGroup($group) || $value === '') {
            return false;
        }

        $stmt = $this->pdo->prepare('DELETE FROM managed_options WHERE option_group = :group AND option_value = :value');

        return $stmt->execute([
            'group' => $group,
            'value' => $value,
        ]);
    }

    public function applyDeleteToProducts(string $group, string $value): int
    {
        $group = trim($group);
        $value = trim($value);

        if (!$this->isAllowedGroup($group) || $value === '') {
            return 0;
        }

        $products = $this->loadProductsWithExtraData();
        $updatedCount = 0;

        foreach ($products as $product) {
            $id = (int) ($product['id'] ?? 0);
            $decoded = $this->decodeExtraData((string) ($product['extra_data'] ?? ''));
            if ($id <= 0 || !is_array($decoded)) {
                continue;
            }

            $list = $this->normalizeList($decoded[$group] ?? []);
            if ($list === []) {
                continue;
            }

            $filtered = array_values(array_filter($list, static fn (string $item): bool => mb_strtolower($item) !== mb_strtolower($value)));
            if (count($filtered) === count($list)) {
                continue;
            }

            $decoded[$group] = $filtered;
            if ($this->updateProductExtraData($id, $decoded)) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    private function loadProductsWithExtraData(): array
    {
        $stmt = $this->pdo->query("SELECT id, extra_data FROM products WHERE extra_data IS NOT NULL AND TRIM(extra_data) <> ''");
        return $stmt ? $stmt->fetchAll() : [];
    }

    private function decodeExtraData(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeList(mixed $value): array
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

    private function updateProductExtraData(int $id, array $extraData): bool
    {
        $stmt = $this->pdo->prepare('UPDATE products SET extra_data = :extra_data WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'extra_data' => json_encode($extraData, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
