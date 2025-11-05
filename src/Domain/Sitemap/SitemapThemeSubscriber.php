<?php

declare(strict_types=1);

namespace App\Domain\Sitemap;

use App\Domain\Search\Theme\ThemeManager;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class SitemapThemeSubscriber
{
    public function __construct(private ThemeManager $themeManager)
    {
    }

    #[AsEventListener(event: SitemapPopulateEvent::class)]
    public function populate(SitemapPopulateEvent $event): void
    {
        foreach ($this->themeManager->getViewsForAllThemes() as $themeView) {
            $event->getUrlContainer()->addUrl(
                new UrlConcrete(
                    $event->getUrlGenerator()->generate(
                        'app_theme',
                        [
                            'name' => $themeView->urlName,
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ),
                    null,
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.8
                ),
                'themes',
            );
        }
    }
}
