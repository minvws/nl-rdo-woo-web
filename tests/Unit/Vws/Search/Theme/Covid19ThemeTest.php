<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Vws\Search\Theme;

use Mockery;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Vws\Search\Theme\Covid19QueryConditionBuilder;
use Shared\Vws\Search\Theme\Covid19Theme;

class Covid19ThemeTest extends UnitTestCase
{
    public function testGetBaseQueryConditions(): void
    {
        $theme = new Covid19Theme(
            $queryConditions = Mockery::mock(Covid19QueryConditionBuilder::class),
        );

        self::assertSame($queryConditions, $theme->getBaseQueryConditionBuilder());
    }
}
