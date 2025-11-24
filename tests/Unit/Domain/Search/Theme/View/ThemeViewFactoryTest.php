<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Theme\View;

use Shared\Domain\Search\Theme\ViewModel\ThemeViewFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Vws\Search\Theme\Covid19QueryConditionBuilder;
use Shared\Vws\Search\Theme\Covid19Theme;

class ThemeViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $theme = new Covid19Theme(
            \Mockery::mock(Covid19QueryConditionBuilder::class),
        );

        $factory = new ThemeViewFactory();

        $this->assertMatchesObjectSnapshot(
            $factory->make($theme)
        );
    }
}
