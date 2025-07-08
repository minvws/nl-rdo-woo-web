<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Theme\View;

use App\Domain\Search\Theme\ViewModel\ThemeViewFactory;
use App\Tests\Unit\UnitTestCase;
use App\Vws\Search\Theme\Covid19QueryConditionBuilder;
use App\Vws\Search\Theme\Covid19Theme;

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
