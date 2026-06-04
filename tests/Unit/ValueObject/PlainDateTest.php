<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PlainDateException;

use function random_int;
use function sprintf;

class PlainDateTest extends TestCase
{
    public function testCreateFromFormat(): void
    {
        $dateTime = new DateTimeImmutable($this->randomDate());

        $PlainDate = PlainDate::createFromFormat('Y-m-d', $dateTime->format('Y-m-d'));

        $this->assertEquals($dateTime->format('Y-m-d'), $PlainDate->format('Y-m-d'));
    }

    public function testCastToString(): void
    {
        $dateTime = new DateTimeImmutable($this->randomDate());

        $PlainDate = PlainDate::create($dateTime->format('Y-m-d'));

        $this->assertEquals($dateTime->format('Y-m-d'), (string) $PlainDate);
    }

    public function testAddDays(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-01-01');

        $this->assertEquals('2000-01-08', $PlainDate->addDays(7)->format('Y-m-d'));
    }

    public function testSubDays(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-01-08');

        $this->assertEquals('2000-01-01', $PlainDate->subDays(7)->format('Y-m-d'));
    }

    public function testAddMonths(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-01-01');
        $monthsToAdd = random_int(1, 11);

        $newPlainDate = $PlainDate->addMonths($monthsToAdd);

        $this->assertEquals(
            $PlainDate->addMonths($monthsToAdd)->format('Y-m-d'),
            $newPlainDate->format('Y-m-d'),
        );
    }

    public function testSubMonths(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-06-01');

        $this->assertEquals('2000-03-01', $PlainDate->subMonths(3)->format('Y-m-d'));
    }

    public function testAddYears(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-01-01');

        $this->assertEquals('2003-01-01', $PlainDate->addYears(3)->format('Y-m-d'));
    }

    public function testSubYears(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-01-01');

        $this->assertEquals('1997-01-01', $PlainDate->subYears(3)->format('Y-m-d'));
    }

    public function testFirstOfMonth(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-03-11');

        $this->assertEquals('2000-03-01', $PlainDate->firstOfMonth()->format('Y-m-d'));
    }

    public function testLastOfMonth(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-03-11');

        $this->assertEquals('2000-03-31', $PlainDate->lastOfMonth()->format('Y-m-d'));
    }

    public function testFirstOfYear(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-03-11');

        $this->assertEquals('2000-01-01', $PlainDate->firstOfYear()->format('Y-m-d'));
    }

    public function testLastOfYear(): void
    {
        $PlainDate = PlainDate::createFromFormat('Y-m-d', '2000-03-11');

        $this->assertEquals('2000-12-31', $PlainDate->lastOfYear()->format('Y-m-d'));
    }

    public function testEqualDates(): void
    {
        $time = $this->randomDate();

        $date1 = PlainDate::createFromFormat('Y-m-d', $time);
        $date2 = PlainDate::createFromFormat('Y-m-d', $time);

        $this->assertTrue($date1->equalTo($date2));
    }

    public function testUnequalDates(): void
    {
        $date1 = PlainDate::createFromFormat('Y-m-d', '2000-01-01');
        $date2 = PlainDate::createFromFormat('Y-m-d', '2000-01-02');

        $this->assertFalse($date1->equalTo($date2));
    }

    public function testIsBefore(): void
    {
        $date1 = PlainDate::createFromFormat('Y-m-d', '2000-01-01');
        $date2 = PlainDate::createFromFormat('Y-m-d', '2000-01-02');

        $this->assertTrue($date1->isBefore($date2));
        $this->assertFalse($date2->isBefore($date1));
    }

    public function testIsAfter(): void
    {
        $date1 = PlainDate::createFromFormat('Y-m-d', '2000-01-02');
        $date2 = PlainDate::createFromFormat('Y-m-d', '2000-01-01');

        $this->assertTrue($date1->isAfter($date2));
        $this->assertFalse($date2->isAfter($date1));
    }

    public function testIsBeforeReturnsFalseForEqualDates(): void
    {
        $date1 = PlainDate::createFromFormat('Y-m-d', '2000-01-01');
        $date2 = PlainDate::createFromFormat('Y-m-d', '2000-01-01');

        $this->assertFalse($date1->isBefore($date2));
    }

    public function testIsAfterReturnsFalseForEqualDates(): void
    {
        $date1 = PlainDate::createFromFormat('Y-m-d', '2000-01-01');
        $date2 = PlainDate::createFromFormat('Y-m-d', '2000-01-01');

        $this->assertFalse($date1->isAfter($date2));
    }

    #[DataProvider('formatProvider')]
    public function testFormat(string $format): void
    {
        $date = $this->randomDate();
        $PlainDate = PlainDate::createFromFormat('Y-m-d', $date);

        $this->assertEquals(
            new DateTimeImmutable($date)->format($format),
            $PlainDate->format($format),
        );
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function formatProvider(): array
    {
        return [
            ['Y-m-d'],
            ['d-m-Y'],
            ['d/m/Y'],
        ];
    }

    public function testIsPastForHistoricalDate(): void
    {
        $this->assertTrue(
            PlainDate::createFromFormat('Y-m-d', '2000-01-01')->isPast(),
        );
    }

    public function testIsPastReturnsFalseForToday(): void
    {
        $this->assertFalse(PlainDate::today()->isPast());
    }

    public function testIsPastReturnsFalseForFutureDate(): void
    {
        $futureDate = PlainDate::create(new DateTimeImmutable(sprintf('+%s days', random_int(1, 365)))->format('Y-m-d'));

        $this->assertFalse($futureDate->isPast());
    }

    public function testIsFuture(): void
    {
        $futureDate = PlainDate::create(new DateTimeImmutable(sprintf('+%s days', random_int(1, 365)))->format('Y-m-d'));

        $this->assertTrue($futureDate->isFuture());
    }

    public function testIsFutureReturnsFalseForToday(): void
    {
        $this->assertFalse(PlainDate::today()->isFuture());
    }

    public function testIsFutureReturnsFalseForHistoricalDate(): void
    {
        $this->assertFalse(
            PlainDate::createFromFormat('Y-m-d', '2000-01-01')->isFuture(),
        );
    }

    public function testIsToday(): void
    {
        $this->assertTrue(PlainDate::today()->isToday());
    }

    public function testIsTodayReturnsFalseForOtherDate(): void
    {
        $this->assertFalse(
            PlainDate::createFromFormat('Y-m-d', '2000-01-01')->isToday(),
        );
    }

    public function testToday(): void
    {
        $this->assertEquals(
            new DateTimeImmutable('today')->format('Y-m-d'),
            PlainDate::today()->format('Y-m-d'),
        );
    }

    public function testCreate(): void
    {
        $date = $this->randomDate();

        $this->assertEquals($date, PlainDate::create($date)->format('Y-m-d'));
    }

    public function testToString(): void
    {
        $date = $this->randomDate();

        $this->assertEquals($date, PlainDate::create($date)->toString());
    }

    public function testParseRelativeDate(): void
    {
        self::expectException(PlainDateException::class);

        PlainDate::create('today');
    }

    private function randomDate(): string
    {
        return new DateTimeImmutable()
            ->setDate(random_int(1970, 2020), random_int(1, 12), random_int(1, 28))
            ->format('Y-m-d');
    }
}
