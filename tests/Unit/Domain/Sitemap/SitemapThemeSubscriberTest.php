<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Sitemap;

use App\Domain\Search\Theme\ThemeManager;
use App\Domain\Search\Theme\ViewModel\Theme;
use App\Domain\Sitemap\SitemapThemeSubscriber;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapThemeSubscriberTest extends UnitTestCase
{
    use IterableToGenerator;

    private ThemeManager&MockInterface $themeManager;
    private SitemapThemeSubscriber $subscriber;

    public function setUp(): void
    {
        $this->themeManager = \Mockery::mock(ThemeManager::class);

        $this->subscriber = new SitemapThemeSubscriber(
            $this->themeManager,
        );
    }

    public function testPopulate(): void
    {
        $themeView = new Theme($slug = 'foobar', '', '', '');

        $urlContainer = \Mockery::mock(UrlContainerInterface::class);

        $this->themeManager
            ->expects('getViewsForAllThemes')
            ->once()
            ->andReturn($this->iterableToGenerator([$themeView]));

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator->expects('generate')->with(
            'app_theme',
            [
                'name' => $slug,
            ],
            0,
        )->andReturn($themeUrl = '/theme/foobar');

        $urlContainer->expects('addUrl')->with(
            \Mockery::on(
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
