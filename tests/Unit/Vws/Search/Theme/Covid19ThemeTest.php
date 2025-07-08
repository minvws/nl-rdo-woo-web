<?php

declare(strict_types=1);

namespace App\Tests\Unit\Vws\Search\Theme;

use App\Tests\Unit\UnitTestCase;
use App\Vws\Search\Theme\Covid19QueryConditionBuilder;
use App\Vws\Search\Theme\Covid19Theme;

class Covid19ThemeTest extends UnitTestCase
{
    public function testGetBaseQueryConditions(): void
    {
        $theme = new Covid19Theme(
            $queryConditions = \Mockery::mock(Covid19QueryConditionBuilder::class),
        );

        self::assertSame($queryConditions, $theme->getBaseQueryConditionBuilder());
    }
}
