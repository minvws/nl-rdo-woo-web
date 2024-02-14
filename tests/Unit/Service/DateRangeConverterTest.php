<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\DateRangeConverter;
use PHPUnit\Framework\TestCase;

class DateRangeConverterTest extends TestCase
{
    public static function dateRangeProvider(): array
    {
        return [
            'Single month by first day' => [
                'from' => new \DateTimeImmutable('01-01-2020'),
                'to' => new \DateTimeImmutable('01-01-2020'),
                'expectedResult' => 'Januari 2020',
            ],
            'Spanning a single year' => [
                'from' => new \DateTimeImmutable('01-01-2020'),
                'to' => new \DateTimeImmutable('31-12-2020'),
                'expectedResult' => 'Heel 2020',
            ],
            'Spanning a single year by first of December' => [
                'from' => new \DateTimeImmutable('01-01-2020'),
                'to' => new \DateTimeImmutable('01-12-2020'),
                'expectedResult' => 'Heel 2020',
            ],
            'Spanning two months by end day and first day' => [
                'from' => new \DateTimeImmutable('30-01-2020'),
                'to' => new \DateTimeImmutable('01-02-2020'),
                'expectedResult' => 'Januari - februari 2020',
            ],
            'Spanning two months by first days' => [
                'from' => new \DateTimeImmutable('01-04-2020'),
                'to' => new \DateTimeImmutable('01-08-2020'),
                'expectedResult' => 'April - augustus 2020',
            ],
            'Spanning specific months over two years' => [
                'from' => new \DateTimeImmutable('01-04-2020'),
                'to' => new \DateTimeImmutable('01-08-2021'),
                'expectedResult' => 'April 2020 - augustus 2021',
            ],
            'All up to a specific year and month' => [
                'from' => null,
                'to' => new \DateTimeImmutable('01-08-2021'),
                'expectedResult' => 'Tot augustus 2021',
            ],
            'All up to a specific year and month by January first' => [
                'from' => null,
                'to' => new \DateTimeImmutable('01-01-2020'),
                'expectedResult' => 'Tot januari 2020',
            ],
            'All from January 2020' => [
                'from' => new \DateTimeImmutable('01-01-2020'),
                'to' => null,
                'expectedResult' => 'Vanaf januari 2020',
            ],
            'All from December 2020' => [
                'from' => new \DateTimeImmutable('31-12-2020'),
                'to' => null,
                'expectedResult' => 'Vanaf december 2020',
            ],
            'Any date' => [
                'from' => null,
                'to' => null,
                'expectedResult' => 'Alles',
            ],
        ];
    }

    /**
     * @dataProvider dateRangeProvider
     */
    public function testConverter(?\DateTimeImmutable $from, ?\DateTimeImmutable $to, string $result): void
    {
        $this->assertEquals($result, DateRangeConverter::convertToString($from, $to));
    }
}
