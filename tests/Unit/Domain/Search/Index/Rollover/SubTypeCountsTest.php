<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Rollover\SubtypeCount;
use Shared\Tests\Unit\UnitTestCase;

class SubTypeCountsTest extends UnitTestCase
{
    public function testGetValues(): void
    {
        $count = new SubtypeCount(
            $type = ElasticDocumentType::COVENANT_MAIN_DOCUMENT,
            $expected = 10,
            $actual = 3,
            $expectedPages = 123,
            $actualPages = 57,
        );

        self::assertEquals($type, $count->type);
        self::assertEquals($expected, $count->expected);
        self::assertEquals($actual, $count->actual);
        self::assertEquals($expectedPages, $count->expectedPages);
        self::assertEquals($actualPages, $count->actualPages);
        self::assertEquals(30, $count->getPercentage());
        self::assertEquals(46.34, $count->getPagesPercentage());
    }

    public function testGetPercentageReturns100PercentWhenTheExpectedCountIsZero(): void
    {
        $count = new SubtypeCount(
            ElasticDocumentType::COVENANT_MAIN_DOCUMENT,
            0,
            0,
            0,
            0,
        );

        self::assertEquals(100, $count->getPercentage());
        self::assertEquals(100, $count->getPagesPercentage());
    }
}
