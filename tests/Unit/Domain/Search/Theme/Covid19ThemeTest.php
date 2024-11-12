<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Theme;

use App\Domain\Search\Theme\Covid19QueryConditions;
use App\Domain\Search\Theme\Covid19Theme;
use App\Tests\Unit\UnitTestCase;

class Covid19ThemeTest extends UnitTestCase
{
    public function testGetBaseQueryConditions(): void
    {
        $theme = new Covid19Theme(
            $queryConditions = \Mockery::mock(Covid19QueryConditions::class),
        );

        self::assertSame($queryConditions, $theme->getBaseQueryConditions());
    }
}
