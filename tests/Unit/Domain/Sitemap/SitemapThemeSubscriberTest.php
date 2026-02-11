<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Sitemap;

use Mockery;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Shared\Domain\Search\Theme\ThemeManager;
use Shared\Domain\Search\Theme\ViewModel\Theme;
use Shared\Domain\Sitemap\SitemapThemeSubscriber;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapThemeSubscriberTest extends UnitTestCase
{
    use IterableToGenerator;

    private ThemeManager&MockInterface $themeManager;
    private SitemapThemeSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->themeManager = Mockery::mock(ThemeManager::class);

        $this->subscriber = new SitemapThemeSubscriber(
            $this->themeManager,
        );
    }

    public function testPopulate(): void
    {
        $themeView = new Theme($slug = 'foobar', '', '', '');

        $urlContainer = Mockery::mock(UrlContainerInterface::class);

        $this->themeManager
            ->expects('getViewsForAllThemes')
            ->once()
            ->andReturn($this->iterableToGenerator([$themeView]));

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')->with(
            'app_theme',
            [
                'name' => $slug,
            ],
            0,
        )->andReturn($themeUrl = '/theme/foobar');

        $urlContainer->expects('addUrl')->with(
            Mockery::on(
                static function (UrlConcrete $urlConcrete) use ($themeUrl): bool {
                    self::assertEquals($themeUrl, $urlConcrete->getLoc());

                    return true;
                }
            ),
            'themes',
        );

        $event = new SitemapPopulateEvent(
            $urlContainer,
            $urlGenerator,
        );

        $this->subscriber->populate($event);
    }
}
