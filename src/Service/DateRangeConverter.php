<?php

declare(strict_types=1);

namespace App\Service;

/**
 * This class will convert a date range into a human-readable string.
 *
 * Examples:
 *    2020-01-01 - 2020-12-31 => Heel 2020
 *    2020-01-01 - 2021-08-31 => Januari 2020 - Augustus 2021
 *    2020-03-01 - 2020-03-31 => Maart 2020
 */
class DateRangeConverter
{
    /**
     * Converts a date range into a human-readable string.
     */
    public static function convertToString(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): string
    {
        if ($from === null || $to === null) {
            return self::handleRangeWithNull($from, $to);
        }

        return self::handleRangeWithTwoDates($from, $to);
    }

    protected static function getMonth(\DateTimeImmutable $date): string
    {
        return self::formatDate($date, 'MMMM');
    }

    protected static function getYear(\DateTimeImmutable $date): string
    {
        return self::formatDate($date, 'yyyy');
    }

    protected static function getMonthAndYear(\DateTimeImmutable $date): string
    {
        return self::formatDate($date, 'MMMM yyyy');
    }

    protected static function formatDate(\DateTimeImmutable $date, string $pattern): string
    {
        return \IntlDateFormatter::formatObject($date, $pattern, 'nl_NL');
    }

    protected static function handleRangeWithNull(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): string
    {
        if ($from === null && $to === null) {
            return 'Alles';
        }

        if ($from === null) {
            return 'Tot ' . self::getMonthAndYear($to);
        }

        return 'Vanaf ' . self::getMonthAndYear($from);
    }

    protected static function handleRangeWithTwoDates(\DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        // Spanning a single year
        if ($from->format('m') === '01' && $to->format('m') === '12' && $from->format('y') === $to->format('y')) {
            return 'Heel ' . self::getYear($from);
        }

        // Spanning multiple years
        if ($from->format('y') !== $to->format('y')) {
            return ucfirst(self::getMonthAndYear($from) . ' - ' . self::getMonthAndYear($to));
        }

        // Single month
        if ($from->format('m') === $to->format('m')) {
            return ucfirst(self::getMonthAndYear($from));
        }

        // Spanning multiple months within a single year
        return ucfirst(self::getMonth($from) . ' - ' . self::getMonthAndYear($to));
    }
}
