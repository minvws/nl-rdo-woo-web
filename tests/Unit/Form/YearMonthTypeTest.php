<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\YearMonthType;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class YearMonthTypeTest extends TestCase
{
    private YearMonthType $yearMonthType;

    public function setUp(): void
    {
        Carbon::setTestNow('2024-04-30 09:42:11');

        $this->yearMonthType = new YearMonthType(null, null);

        parent::setUp();
    }

    public function testGetChoicesModeFrom(): void
    {
        $choices = $this->yearMonthType->getChoices(YearMonthType::MODE_FROM, 1, 1, false);

        $this->assertEquals(
            [
                2025 => [
                    'January 2025' => '2025-01-01T00:00:00+00:00',
                    'February 2025' => '2025-02-01T00:00:00+00:00',
                    'March 2025' => '2025-03-01T00:00:00+00:00',
                ],
                2024 => [
                    'January 2024' => '2024-01-01T00:00:00+00:00',
                    'February 2024' => '2024-02-01T00:00:00+00:00',
                    'March 2024' => '2024-03-01T00:00:00+00:00',
                    'April 2024' => '2024-04-01T00:00:00+00:00',
                    'May 2024' => '2024-05-01T00:00:00+00:00',
                    'June 2024' => '2024-06-01T00:00:00+00:00',
                    'July 2024' => '2024-07-01T00:00:00+00:00',
                    'August 2024' => '2024-08-01T00:00:00+00:00',
                    'September 2024' => '2024-09-01T00:00:00+00:00',
                    'October 2024' => '2024-10-01T00:00:00+00:00',
                    'November 2024' => '2024-11-01T00:00:00+00:00',
                    'December 2024' => '2024-12-01T00:00:00+00:00',
                ],
                2023 => [
                    'May 2023' => '2023-05-01T00:00:00+00:00',
                    'June 2023' => '2023-06-01T00:00:00+00:00',
                    'July 2023' => '2023-07-01T00:00:00+00:00',
                    'August 2023' => '2023-08-01T00:00:00+00:00',
                    'September 2023' => '2023-09-01T00:00:00+00:00',
                    'October 2023' => '2023-10-01T00:00:00+00:00',
                    'November 2023' => '2023-11-01T00:00:00+00:00',
                    'December 2023' => '2023-12-01T00:00:00+00:00',
                ],
            ],
            $choices,
        );
    }

    public function testGetChoicesModeFromReversed(): void
    {
        $choices = $this->yearMonthType->getChoices(YearMonthType::MODE_FROM, 1, 1, true);

        $this->assertEquals(
            [
                2023 => [
                    'May 2023' => '2023-05-01T00:00:00+00:00',
                    'June 2023' => '2023-06-01T00:00:00+00:00',
                    'July 2023' => '2023-07-01T00:00:00+00:00',
                    'August 2023' => '2023-08-01T00:00:00+00:00',
                    'September 2023' => '2023-09-01T00:00:00+00:00',
                    'October 2023' => '2023-10-01T00:00:00+00:00',
                    'November 2023' => '2023-11-01T00:00:00+00:00',
                    'December 2023' => '2023-12-01T00:00:00+00:00',
                ],
                2024 => [
                    'January 2024' => '2024-01-01T00:00:00+00:00',
                    'February 2024' => '2024-02-01T00:00:00+00:00',
                    'March 2024' => '2024-03-01T00:00:00+00:00',
                    'April 2024' => '2024-04-01T00:00:00+00:00',
                    'May 2024' => '2024-05-01T00:00:00+00:00',
                    'June 2024' => '2024-06-01T00:00:00+00:00',
                    'July 2024' => '2024-07-01T00:00:00+00:00',
                    'August 2024' => '2024-08-01T00:00:00+00:00',
                    'September 2024' => '2024-09-01T00:00:00+00:00',
                    'October 2024' => '2024-10-01T00:00:00+00:00',
                    'November 2024' => '2024-11-01T00:00:00+00:00',
                    'December 2024' => '2024-12-01T00:00:00+00:00',
                ],
                2025 => [
                    'January 2025' => '2025-01-01T00:00:00+00:00',
                    'February 2025' => '2025-02-01T00:00:00+00:00',
                    'March 2025' => '2025-03-01T00:00:00+00:00',
                ],
            ],
            $choices,
        );
    }

    public function testGetChoicesModeTo(): void
    {
        $choices = $this->yearMonthType->getChoices(YearMonthType::MODE_TO, 1, 1, false);

        $this->assertEquals(
            [
                2025 => [
                    'January 2025' => '2025-01-31T00:00:00+00:00',
                    'February 2025' => '2025-02-28T00:00:00+00:00',
                    'March 2025' => '2025-03-31T00:00:00+00:00',
                ],
                2024 => [
                    'January 2024' => '2024-01-31T00:00:00+00:00',
                    'February 2024' => '2024-02-29T00:00:00+00:00',
                    'March 2024' => '2024-03-31T00:00:00+00:00',
                    'April 2024' => '2024-04-30T00:00:00+00:00',
                    'May 2024' => '2024-05-31T00:00:00+00:00',
                    'June 2024' => '2024-06-30T00:00:00+00:00',
                    'July 2024' => '2024-07-31T00:00:00+00:00',
                    'August 2024' => '2024-08-31T00:00:00+00:00',
                    'September 2024' => '2024-09-30T00:00:00+00:00',
                    'October 2024' => '2024-10-31T00:00:00+00:00',
                    'November 2024' => '2024-11-30T00:00:00+00:00',
                    'December 2024' => '2024-12-31T00:00:00+00:00',
                ],
                2023 => [
                    'May 2023' => '2023-05-31T00:00:00+00:00',
                    'June 2023' => '2023-06-30T00:00:00+00:00',
                    'July 2023' => '2023-07-31T00:00:00+00:00',
                    'August 2023' => '2023-08-31T00:00:00+00:00',
                    'September 2023' => '2023-09-30T00:00:00+00:00',
                    'October 2023' => '2023-10-31T00:00:00+00:00',
                    'November 2023' => '2023-11-30T00:00:00+00:00',
                    'December 2023' => '2023-12-31T00:00:00+00:00',
                ],
            ],
            $choices,
        );
    }
}
