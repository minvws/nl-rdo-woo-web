<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Judgement;

class InventoryDataHelper
{
    /**
     * Returns true when the given value resembles a value that can be considered to be true.
     */
    public static function isTrue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(strval($value)), ['true', 'ja', 'yes', '1', 'y', 'j']);
    }

    /**
     * Splits a string by separators, trims all values and removes any empty values.
     *
     * @param non-empty-string $separator
     *
     * @return string[]
     */
    public static function separateValues(mixed $value, string $separator = ';'): array
    {
        $values = explode($separator, strval($value));
        $values = array_map('trim', $values);

        return array_filter($values);
    }

    public static function judgement(mixed $value): Judgement
    {
        return Judgement::fromString(strval($value));
    }
}
