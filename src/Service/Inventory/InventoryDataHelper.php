<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use DateTimeImmutable;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_key_exists;
use function array_map;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function preg_quote;
use function preg_split;
use function strtolower;
use function strval;
use function trim;

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

    public static function toDateTimeImmutable(string $value): DateTimeImmutable
    {
        if ($value === '') {
            throw new RuntimeException('Cannot parse empty date string');
        }

        $dateFormats = [
            '!Y-m-d',
            '!d-m-Y',
            '!Y/m/d',
            '!d/m/Y',
            'd/m/Y H:i',
            'm/d/Y h:i a T',
        ];

        foreach ($dateFormats as $dateFormat) {
            $date = DateTimeImmutable::createFromFormat($dateFormat, $value);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        throw new RuntimeException('Cannot parse empty date string');
    }

    /**
     * Splits a string by separators, trims all values and removes any empty values.
     *
     * @param non-empty-string|array<non-empty-string> $separators
     *
     * @return string[]
     */
    public static function separateValues(mixed $value, string|array $separators = ';'): array
    {
        if ($value === null) {
            return [];
        }

        $value = trim(strval($value));
        if ($value === '') {
            return [];
        }

        if (! is_array($separators)) {
            $separators = [$separators];
        }

        $separators = array_map(
            static fn (string $separator) => preg_quote($separator, '/'),
            $separators
        );

        $values = preg_split('/(' . implode('|', $separators) . ')/', $value, -1);
        Assert::isArray($values);

        $values = array_map(trim(...), $values);

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
