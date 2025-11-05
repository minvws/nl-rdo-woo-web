<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\DateRangeConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateRangeConverterTest extends TestCase
{
    /**
     * @return array<string, array{from: \DateTimeImmutable|null, to: \DateTimeImmutable|null, expectedResult: string}>
     */
    public static function dateRangeProvider(): array
    {
        return [
            // 01-01-2021 can be affected by ISO-8601 week-numbering year difference
            'Single month by first day' => [
                'from' => new \DateTimeImmutable('01-01-2021'),
                'to' => new \DateTimeImmutable('01-01-2021'),
                'expectedResult' => 'Januari 2021',
            ],
            'Spanning a single year' => [
                'from' => new \DateTimeImmutable('01-01-2020'),
                'to' => new \DateTimeImmutable('31-12-2020'),
                'expectedResult' => 'Januari t/m december 2020',
            ],
            'Spanning a single year by first of December' => [
                'from' => new \DateTimeImmutable('01-01-2020'),
                'to' => new \DateTimeImmutable('01-12-2020'),
                'expectedResult' => 'Januari t/m december 2020',
            ],
            'Spanning two months by end day and first day' => [
                'from' => new \DateTimeImmutable('30-01-2020'),
                'to' => new \DateTimeImmutable('01-02-2020'),
                'expectedResult' => 'Januari t/m februari 2020',
            ],
            'Spanning two months by first days' => [
                'from' => new \DateTimeImmutable('01-04-2020'),
                'to' => new \DateTimeImmutable('01-08-2020'),
                'expectedResult' => 'April t/m augustus 2020',
            ],
            'Spanning specific months over two years' => [
                'from' => new \DateTimeImmutable('01-04-2020'),
                'to' => new \DateTimeImmutable('01-08-2021'),
                'expectedResult' => 'April 2020 t/m augustus 2021',
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

    #[DataProvider('dateRangeProvider')]
    public function testConverter(?\DateTimeImmutable $from, ?\DateTimeImmutable $to, string $expectedResult): void
    {
        self::assertEquals($expectedResult, DateRangeConverter::convertToString($from, $to));
    }
}
