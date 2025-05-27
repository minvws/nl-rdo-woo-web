<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Theme;

use App\Domain\Search\Theme\ThemeInterface;
use App\Domain\Search\Theme\ThemeManager;
use App\Domain\Search\Theme\ViewModel\Theme;
use App\Domain\Search\Theme\ViewModel\ThemeViewFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class ThemeManagerTest extends UnitTestCase
{
    private ThemeViewFactory&MockInterface $themeViewFactory;
    private ThemeInterface&MockInterface $themeA;
    private ThemeInterface&MockInterface $themeB;
    private ThemeManager $themeManager;

    public function setUp(): void
    {
        $this->themeViewFactory = \Mockery::mock(ThemeViewFactory::class);

        $this->themeA = \Mockery::mock(ThemeInterface::class);
        $this->themeA->shouldReceive('getUrlName')->andReturn('a');

        $this->themeB = \Mockery::mock(ThemeInterface::class);
        $this->themeB->shouldReceive('getUrlName')->andReturn('b');

        $this->themeManager = new ThemeManager(
            $this->themeViewFactory,
            [$this->themeA, $this->themeB]
        );

        parent::setUp();
    }

    public function testGetThemeUrlByName(): void
    {
        self::assertSame($this->themeA, $this->themeManager->getThemeByUrlName('a'));
        self::assertSame($this->themeB, $this->themeManager->getThemeByUrlName('b'));
        self::assertNull($this->themeManager->getThemeByUrlName('c'));
    }

    public function testGetView(): void
    {
        $this->themeViewFactory->expects('make')->with($this->themeB)->andReturn($view = \Mockery::mock(Theme::class));

        self::assertSame($view, $this->themeManager->getView($this->themeB));
    }

    public function testGetViewsForAllThemes(): void
    {
        $this->themeViewFactory->expects('make')->with($this->themeA)->andReturn($viewA = \Mockery::mock(Theme::class));
        $this->themeViewFactory->expects('make')->with($this->themeB)->andReturn($viewB = \Mockery::mock(Theme::class));

        self::assertEquals(
            [$viewA, $viewB],
            iterator_to_array($this->themeManager->getViewsForAllThemes(), false)
        );
    }
}
