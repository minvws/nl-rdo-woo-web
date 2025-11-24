<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Rollover\MainTypeCount;
use Shared\Domain\Search\Index\Rollover\SubtypeCount;
use Shared\Tests\Unit\UnitTestCase;

class MainTypeCountsTest extends UnitTestCase
{
    public function testGetValues(): void
    {
        $count = new MainTypeCount(
            $type = ElasticDocumentType::COVENANT,
            $expected = 20,
            $actual = 10,
            $subCounts = [
                new SubtypeCount(
                    ElasticDocumentType::COVENANT_MAIN_DOCUMENT,
                    1,
                    1,
                    3,
                    3,
                ),
            ],
        );

        self::assertEquals($type, $count->type);
        self::assertEquals($expected, $count->expected);
        self::assertEquals($actual, $count->actual);
        self::assertEquals($subCounts, $count->subCounts);
        self::assertEquals(50, $count->getPercentage());
    }

    public function testGetPercentageReturns100PercentWhenTheExpectedCountIsZero(): void
    {
        $count = new MainTypeCount(
            ElasticDocumentType::COVENANT,
            0,
            0,
        );

        self::assertEquals(100, $count->getPercentage());
    }
}
