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

    public static function toDateTimeImmutable(string $value): \DateTimeImmutable
    {
        if (empty($value)) {
            throw new \RuntimeException('Cannot parse empty date string');
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date) {
            return $date;
        }

        $date = \DateTimeImmutable::createFromFormat('!d-m-Y', $value);
        if ($date) {
            return $date;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y/m/d', $value);
        if ($date) {
            return $date;
        }

        $date = \DateTimeImmutable::createFromFormat('!d/m/Y', $value);
        if ($date) {
            return $date;
        }

        $date = \DateTimeImmutable::createFromFormat('d/m/Y H:i', $value);
        if ($date) {
            return $date;
        }

        $date = \DateTimeImmutable::createFromFormat('m/d/Y h:i a T', $value);
        if ($date) {
            return $date;
        }

        throw new \RuntimeException('Cannot parse empty date string');
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
        if ($value === null) {
            return [];
        }

        $values = explode($separator, strval($value));
        $values = array_map('trim', $values);

        return array_filter($values);
    }

    public static function judgement(mixed $value): Judgement
    {
        return Judgement::fromString(strval($value));
    }

    /**
     * Splits a string by separator and normalizes values.
     *
     * @return string[]
     */
    public static function getGrounds(string $input): array
    {
        $grounds = static::separateValues($input);

        $normalizations = [
            '5.1.1.a' => '5.1.1a',
            '5.1.1.b' => '5.1.1b',
            '5.1.1.c' => '5.1.1c',
            '5.1.1.d' => '5.1.1d',
            '5.1.1.e' => '5.1.1e',
            '5.1.2.a' => '5.1.2a',
            '5.1.2.b' => '5.1.2b',
            '5.1.2.c' => '5.1.2c',
            '5.1.2.d' => '5.1.2d',
            '5.1.2.e' => '5.1.2e',
            '5.1.2.f' => '5.1.2f',
            '5.1.2.g' => '5.1.2g',
            '5.1.2.h' => '5.1.2h',
            '5.1.2.i' => '5.1.2i',
            '10.1.a' => '10.1a',
            '10.1.b' => '10.1b',
            '10.1.c' => '10.1c',
            '10.1.d' => '10.1d',
            '10.2.a' => '10.2a',
            '10.2.b' => '10.2b',
            '10.2.c' => '10.2c',
            '10.2.d' => '10.2d',
            '10.2.e' => '10.2e',
            '10.2.g' => '10.2g',
        ];

        foreach ($grounds as $key => $ground) {
            if (array_key_exists($ground, $normalizations)) {
                $grounds[$key] = $normalizations[$ground];
            }
        }

        return $grounds;
    }
}
