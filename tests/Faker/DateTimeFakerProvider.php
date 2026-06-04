<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\DateTime;
use Shared\ValueObject\PlainDate;

final class DateTimeFakerProvider extends DateTime
{
    public function plainDate(string $max = 'now'): PlainDate
    {
        return PlainDate::create(static::dateTime($max)->format('Y-m-d'));
    }

    public function plainDateBetween(string $startDate = '-30 years', string $endDate = 'now'): PlainDate
    {
        return PlainDate::create(static::dateTimeBetween($startDate, $endDate)->format('Y-m-d'));
    }
}
