<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shared\Service\DateRangeConverter;
use Shared\ValueObject\PlainDate;

class DateRangeConverterTest extends TestCase
{
    /**
     * @return array<string, array{from: string|null, to: string|null, expectedResult: string}>
     */
    public static function dateRangeProvider(): array
    {
        return [
            // 01-01-2021 can be affected by ISO-8601 week-numbering year difference
            'Single month by first day' => [
                'from' => '01-01-2021',
                'to' => '01-01-2021',
                'expectedResult' => 'Januari 2021',
            ],
            'Spanning a single year' => [
                'from' => '01-01-2020',
                'to' => '31-12-2020',
                'expectedResult' => 'Januari t/m december 2020',
            ],
            'Spanning a single year by first of December' => [
                'from' => '01-01-2020',
                'to' => '01-12-2020',
                'expectedResult' => 'Januari t/m december 2020',
            ],
            'Spanning two months by end day and first day' => [
                'from' => '30-01-2020',
                'to' => '01-02-2020',
                'expectedResult' => 'Januari t/m februari 2020',
            ],
            'Spanning two months by first days' => [
                'from' => '01-04-2020',
                'to' => '01-08-2020',
                'expectedResult' => 'April t/m augustus 2020',
            ],
            'Spanning specific months over two years' => [
                'from' => '01-04-2020',
                'to' => '01-08-2021',
                'expectedResult' => 'April 2020 t/m augustus 2021',
            ],
            'All up to a specific year and month' => [
                'from' => null,
                'to' => '01-08-2021',
                'expectedResult' => 'Tot augustus 2021',
            ],
            'All up to a specific year and month by January first' => [
                'from' => null,
                'to' => '01-01-2020',
                'expectedResult' => 'Tot januari 2020',
            ],
            'All from January 2020' => [
                'from' => '01-01-2020',
                'to' => null,
                'expectedResult' => 'Vanaf januari 2020',
            ],
            'All from December 2020' => [
                'from' => '31-12-2020',
                'to' => null,
                'expectedResult' => 'Vanaf december 2020',
            ],
        ];
    }

    public function testConverterWithNullValues(): void
    {
        self::assertEquals('Alles', DateRangeConverter::convertToString(null, null));
    }

    #[DataProvider('dateRangeProvider')]
    public function testConverterWithDateTimeImmutable(?string $from, ?string $to, string $expectedResult): void
    {
        $from = $from ? new DateTimeImmutable($from) : null;
        $to = $to ? new DateTimeImmutable($to) : null;

        self::assertEquals($expectedResult, DateRangeConverter::convertToString($from, $to));
    }

    #[DataProvider('dateRangeProvider')]
    public function testConverterWithPlainDate(?string $from, ?string $to, string $expectedResult): void
    {
        $from = $from ? PlainDate::createFromFormat('d-m-Y', $from) : null;
        $to = $to ? PlainDate::createFromFormat('d-m-Y', $to) : null;

        self::assertEquals($expectedResult, DateRangeConverter::convertToString($from, $to));
    }
}
