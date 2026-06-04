<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form;

use PHPUnit\Framework\TestCase;
use Shared\Form\YearType;
use Shared\ValueObject\PlainDate;

final class YearTypeTest extends TestCase
{
    public function testGetChoices(): void
    {
        $plainDate = PlainDate::create('2024-04-30');
        $yearType = new YearType(null, null);

        $choices = $yearType->getChoices(3, 1, false, $plainDate);

        self::assertSame(
            [
                2021 => '2021-01-01',
                2022 => '2022-01-01',
                2023 => '2023-01-01',
                2024 => '2024-01-01',
                2025 => '2025-01-01',
            ],
            $choices,
        );
    }

    public function testGetChoicesReversed(): void
    {
        $plainDate = PlainDate::create('2024-04-30');
        $yearType = new YearType(null, null);

        $choices = $yearType->getChoices(3, 1, true, $plainDate);

        self::assertSame(
            [
                2025 => '2025-01-01',
                2024 => '2024-01-01',
                2023 => '2023-01-01',
                2022 => '2022-01-01',
                2021 => '2021-01-01',
            ],
            $choices,
        );
    }

    public function testGetChoicesWithNoPlusYears(): void
    {
        $plainDate = PlainDate::create('2024-04-30');
        $yearType = new YearType(null, null);

        $choices = $yearType->getChoices(2, 0, false, $plainDate);

        self::assertSame(
            [
                2022 => '2022-01-01',
                2023 => '2023-01-01',
                2024 => '2024-01-01',
            ],
            $choices,
        );
    }
}
