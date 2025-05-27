<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Extension;

use App\Domain\Search\Theme\ThemeManager;
use App\Domain\Search\Theme\ViewModel\Theme;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Twig\Extension\ThemeExtension;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ThemeExtensionTest extends MockeryTestCase
{
    use IterableToGenerator;

    public function testGetAllThemes(): void
    {
        $views = [
            \Mockery::mock(Theme::class),
            \Mockery::mock(Theme::class),
        ];

        $themeManager = \Mockery::mock(ThemeManager::class);
        $themeManager->expects('getViewsForAllThemes')->andReturn($this->iterableToGenerator($views));

        $extension = new ThemeExtension($themeManager);

        self::assertEquals(
            $views,
            iterator_to_array($extension->getAllThemes(), false),
        );
    }
}
