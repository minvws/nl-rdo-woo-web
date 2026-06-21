<?php

declare(strict_types=1);

namespace Shared\Service;

use DateTimeImmutable;
use IntlDateFormatter;
use Shared\ValueObject\PlainDate;
use Webmozart\Assert\Assert;

use function mb_ucfirst;

/**
 * This class will convert a date range into a human-readable string.
 *
 * Examples:
 *    2020-01-01 - 2020-12-31 => Januari t/m december 2020
 *    2020-01-01 - 2021-08-31 => Januari 2020 t/m augustus 2021
 *    2020-03-01 - 2020-03-31 => Maart 2020
 */
class DateRangeConverter
{
    /**
     * Converts a date range into a human-readable string.
     */
    public static function convertToString(DateTimeImmutable|PlainDate|null $from, DateTimeImmutable|PlainDate|null $to): string
    {
        $from = self::convertToDateTimeImmutableOrNull($from);
        $to = self::convertToDateTimeImmutableOrNull($to);

        if ($from === null || $to === null) {
            return self::handleRangeWithNull($from, $to);
        }

        return self::handleRangeWithTwoDates($from, $to);
    }

    private static function getMonth(DateTimeImmutable $date): string
    {
        return self::formatDate($date, 'MMMM');
    }

    private static function getMonthAndYear(DateTimeImmutable $date): string
    {
        return self::formatDate($date, 'MMMM yyyy');
    }

    private static function formatDate(DateTimeImmutable $date, string $pattern): string
    {
        return IntlDateFormatter::formatObject($date, $pattern, 'nl_NL');
    }

    private static function handleRangeWithNull(?DateTimeImmutable $from, ?DateTimeImmutable $to): string
    {
        if ($from === null && $to === null) {
            return 'Alles';
        }

        if ($from === null) {
            return 'Tot ' . self::getMonthAndYear($to);
        }

        return 'Vanaf ' . self::getMonthAndYear($from);
    }

    private static function handleRangeWithTwoDates(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        // Spanning multiple years
        if ($from->format('y') !== $to->format('y')) {
            return mb_ucfirst(self::getMonthAndYear($from) . ' t/m ' . self::getMonthAndYear($to));
        }

        // Single month
        if ($from->format('m') === $to->format('m')) {
            return mb_ucfirst(self::getMonthAndYear($from));
        }

        // Spanning multiple months within a single year
        return mb_ucfirst(self::getMonth($from) . ' t/m ' . self::getMonthAndYear($to));
    }

    private static function convertToDateTimeImmutableOrNull(DateTimeImmutable|PlainDate|null $date): ?DateTimeImmutable
    {
        if ($date instanceof PlainDate) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $date->format('Y-m-d'));
            Assert::isInstanceOf($date, DateTimeImmutable::class);
        }

        return $date;
    }
}
