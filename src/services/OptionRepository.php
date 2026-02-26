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
}
