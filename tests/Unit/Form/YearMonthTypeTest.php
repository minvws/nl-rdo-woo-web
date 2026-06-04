<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shared\Form\YearMonthType;
use Shared\ValueObject\PlainDate;

use function is_string;

final class YearMonthTypeTest extends TestCase
{
    public function testGetChoicesModeFrom(): void
    {
        $now = PlainDate::create('2024-04-30');
        $yearMonthType = new YearMonthType(null, null);
        $choices = $yearMonthType->getChoices(YearMonthType::MODE_FROM, $now->subYears(1), 1, false, $now);

        $this->assertEquals(
            [
                2025 => [
                    'January 2025' => '2025-01-01',
                    'February 2025' => '2025-02-01',
                    'March 2025' => '2025-03-01',
                ],
                2024 => [
                    'January 2024' => '2024-01-01',
                    'February 2024' => '2024-02-01',
                    'March 2024' => '2024-03-01',
                    'April 2024' => '2024-04-01',
                    'May 2024' => '2024-05-01',
                    'June 2024' => '2024-06-01',
                    'July 2024' => '2024-07-01',
                    'August 2024' => '2024-08-01',
                    'September 2024' => '2024-09-01',
                    'October 2024' => '2024-10-01',
                    'November 2024' => '2024-11-01',
                    'December 2024' => '2024-12-01',
                ],
                2023 => [
                    'May 2023' => '2023-05-01',
                    'June 2023' => '2023-06-01',
                    'July 2023' => '2023-07-01',
                    'August 2023' => '2023-08-01',
                    'September 2023' => '2023-09-01',
                    'October 2023' => '2023-10-01',
                    'November 2023' => '2023-11-01',
                    'December 2023' => '2023-12-01',
                ],
            ],
            $choices,
        );
    }

    public function testGetChoicesModeFromReversed(): void
    {
        $now = PlainDate::create('2024-04-30');
        $yearMonthType = new YearMonthType(null, null);
        $choices = $yearMonthType->getChoices(YearMonthType::MODE_FROM, $now->subYears(1), 1, true, $now);

        $this->assertEquals(
            [
                2023 => [
                    'May 2023' => '2023-05-01',
                    'June 2023' => '2023-06-01',
                    'July 2023' => '2023-07-01',
                    'August 2023' => '2023-08-01',
                    'September 2023' => '2023-09-01',
                    'October 2023' => '2023-10-01',
                    'November 2023' => '2023-11-01',
                    'December 2023' => '2023-12-01',
                ],
                2024 => [
                    'January 2024' => '2024-01-01',
                    'February 2024' => '2024-02-01',
                    'March 2024' => '2024-03-01',
                    'April 2024' => '2024-04-01',
                    'May 2024' => '2024-05-01',
                    'June 2024' => '2024-06-01',
                    'July 2024' => '2024-07-01',
                    'August 2024' => '2024-08-01',
                    'September 2024' => '2024-09-01',
                    'October 2024' => '2024-10-01',
                    'November 2024' => '2024-11-01',
                    'December 2024' => '2024-12-01',
                ],
                2025 => [
                    'January 2025' => '2025-01-01',
                    'February 2025' => '2025-02-01',
                    'March 2025' => '2025-03-01',
                ],
            ],
            $choices,
        );
    }

    public function testGetChoicesModeTo(): void
    {
        $now = PlainDate::create('2024-04-30');
        $yearMonthType = new YearMonthType(null, null);
        $choices = $yearMonthType->getChoices(YearMonthType::MODE_TO, $now->subYears(1), 1, false, $now);

        $this->assertEquals(
            [
                2025 => [
                    'January 2025' => '2025-01-31',
                    'February 2025' => '2025-02-28',
                    'March 2025' => '2025-03-31',
                ],
                2024 => [
                    'January 2024' => '2024-01-31',
                    'February 2024' => '2024-02-29',
                    'March 2024' => '2024-03-31',
                    'April 2024' => '2024-04-30',
                    'May 2024' => '2024-05-31',
                    'June 2024' => '2024-06-30',
                    'July 2024' => '2024-07-31',
                    'August 2024' => '2024-08-31',
                    'September 2024' => '2024-09-30',
                    'October 2024' => '2024-10-31',
                    'November 2024' => '2024-11-30',
                    'December 2024' => '2024-12-31',
                ],
                2023 => [
                    'May 2023' => '2023-05-31',
                    'June 2023' => '2023-06-30',
                    'July 2023' => '2023-07-31',
                    'August 2023' => '2023-08-31',
                    'September 2023' => '2023-09-30',
                    'October 2023' => '2023-10-31',
                    'November 2023' => '2023-11-30',
                    'December 2023' => '2023-12-31',
                ],
            ],
            $choices,
        );
    }

    #[DataProvider('transformToStringProvider')]
    public function testTransformPlainDateToString(?PlainDate $date, string $expected): void
    {
        $result = $date ? $date->toString() : '';

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: ?PlainDate, 1: string}>
     */
    public static function transformToStringProvider(): array
    {
        return [
            'first of month' => [PlainDate::create('2024-01-01'), '2024-01-01'],
            'last of month' => [PlainDate::create('2024-01-31'), '2024-01-31'],
            'null date' => [null, ''],
        ];
    }

    #[DataProvider('transformFromStringProvider')]
    public function testTransformStringToPlainDate(?string $value, ?PlainDate $expected): void
    {
        $result = is_string($value) ? PlainDate::create($value) : null;

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{0: ?string, 1: ?PlainDate}>
     */
    public static function transformFromStringProvider(): array
    {
        return [
            'first of month' => ['2024-01-01', PlainDate::create('2024-01-01')],
            'last of month' => ['2024-01-31', PlainDate::create('2024-01-31')],
            'null value' => [null, null],
        ];
    }
}
