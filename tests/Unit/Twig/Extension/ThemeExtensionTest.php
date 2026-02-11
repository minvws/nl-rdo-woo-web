<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Extension;

use Mockery;
use Shared\Domain\Search\Theme\ThemeManager;
use Shared\Domain\Search\Theme\ViewModel\Theme;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Extension\ThemeExtension;

use function iterator_to_array;

class ThemeExtensionTest extends UnitTestCase
{
    use IterableToGenerator;

    public function testGetAllThemes(): void
    {
        $views = [
            Mockery::mock(Theme::class),
            Mockery::mock(Theme::class),
        ];

        $themeManager = Mockery::mock(ThemeManager::class);
        $themeManager->expects('getViewsForAllThemes')->andReturn($this->iterableToGenerator($views));

        $extension = new ThemeExtension($themeManager);

        self::assertEquals(
            $views,
            iterator_to_array($extension->getAllThemes(), false),
        );
    }
}
