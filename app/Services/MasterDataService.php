<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class MasterDataService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function productTypes(): array
    {
        $column = $this->db->fetch(
            "SELECT COLUMN_TYPE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'productos'
               AND COLUMN_NAME = 'tipo_producto'
             LIMIT 1"
        );

        if (!$column) {
            return ['PLATO', 'BEBIDA', 'COMBO', 'MENU', 'OTRO'];
        }

        return $this->parseEnumValues((string) $column['COLUMN_TYPE']);
    }

    public function addProductType(string $type): void
    {
        $type = $this->normalizeType($type);
        $types = $this->productTypes();

        if (in_array($type, $types, true)) {
            throw new RuntimeException('Ese tipo ya existe.');
        }

        $types[] = $type;

        $this->applyProductTypeEnum($types);
    }

    public function renameProductType(string $oldType, string $newType): void
    {
        $oldType = $this->normalizeType($oldType);
        $newType = $this->normalizeType($newType);
        $types = $this->productTypes();

        if (!in_array($oldType, $types, true)) {
            throw new RuntimeException('El tipo original no existe.');
        }

        if ($oldType === $newType) {
            return;
        }

        $temporaryTypes = $types;

        if (!in_array($newType, $temporaryTypes, true)) {
            $temporaryTypes[] = $newType;
            $this->applyProductTypeEnum($temporaryTypes);
        }

        $this->db->execute(
            "UPDATE productos
             SET tipo_producto = :new_type
             WHERE tipo_producto = :old_type",
            [
                'new_type' => $newType,
                'old_type' => $oldType,
            ]
        );

        $finalTypes = [];

        foreach ($temporaryTypes as $type) {
            if ($type === $oldType) {
                continue;
            }

            if (!in_array($type, $finalTypes, true)) {
                $finalTypes[] = $type;
            }
        }

        $this->applyProductTypeEnum($finalTypes, $newType);
    }

    public function deleteProductType(string $type, ?string $replacement = null): void
    {
        $type = $this->normalizeType($type);
        $replacement = $replacement !== null && trim($replacement) !== ''
            ? $this->normalizeType($replacement)
            : null;

        $types = $this->productTypes();

        if (!in_array($type, $types, true)) {
            throw new RuntimeException('El tipo a eliminar no existe.');
        }

        if (count($types) <= 1) {
            throw new RuntimeException('No puedes eliminar el único tipo disponible.');
        }

        $usageCount = (int) $this->db->value(
            "SELECT COUNT(*)
             FROM productos
             WHERE tipo_producto = :type",
            ['type' => $type]
        );

        if ($usageCount > 0) {
            if ($replacement === null) {
                throw new RuntimeException('Este tipo está en uso. Selecciona un tipo de reemplazo.');
            }

            if ($replacement === $type) {
                throw new RuntimeException('El tipo de reemplazo debe ser distinto.');
            }

            if (!in_array($replacement, $types, true)) {
                throw new RuntimeException('El tipo de reemplazo no existe.');
            }

            $this->db->execute(
                "UPDATE productos
                 SET tipo_producto = :replacement
                 WHERE tipo_producto = :old_type",
                [
                    'replacement' => $replacement,
                    'old_type' => $type,
                ]
            );
        }

        $finalTypes = array_values(array_filter(
            $types,
            static fn (string $item): bool => $item !== $type
        ));

        $default = $replacement && in_array($replacement, $finalTypes, true)
            ? $replacement
            : $finalTypes[0];

        $this->applyProductTypeEnum($finalTypes, $default);
    }

    private function applyProductTypeEnum(array $types, ?string $default = null): void
    {
        $types = array_values(array_unique(array_map([$this, 'normalizeType'], $types)));

        if ($types === []) {
            throw new RuntimeException('La lista de tipos no puede quedar vacía.');
        }

        $currentDefault = $this->db->value(
            "SELECT COLUMN_DEFAULT
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'productos'
               AND COLUMN_NAME = 'tipo_producto'"
        );

        $default = $default !== null && in_array($default, $types, true)
            ? $default
            : (in_array((string) $currentDefault, $types, true) ? (string) $currentDefault : $types[0]);

        $quotedTypes = array_map(
            fn (string $type): string => $this->db->pdo()->quote($type),
            $types
        );

        $sql = sprintf(
            "ALTER TABLE productos MODIFY tipo_producto ENUM(%s) NOT NULL DEFAULT %s",
            implode(', ', $quotedTypes),
            $this->db->pdo()->quote($default)
        );

        $this->db->pdo()->exec($sql);
    }

    private function parseEnumValues(string $columnType): array
    {
        if (!preg_match('/^enum\\((.*)\\)$/i', $columnType, $matches)) {
            return [];
        }

        $values = str_getcsv($matches[1], ',', "'");

        return array_values(array_filter(
            array_map(static fn (string $value): string => trim($value), $values),
            static fn (string $value): bool => $value !== ''
        ));
    }

    private function normalizeType(string $type): string
    {
        $type = strtoupper(trim($type));

        if ($type === '') {
            throw new RuntimeException('El tipo no puede estar vacío.');
        }

        if (strlen($type) > 50) {
            throw new RuntimeException('El tipo no puede superar 50 caracteres.');
        }

        return $type;
    }
}
