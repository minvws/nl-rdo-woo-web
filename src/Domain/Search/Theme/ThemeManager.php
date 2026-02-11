<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Theme;

use Generator;
use Shared\Domain\Search\Theme\ViewModel\Theme;
use Shared\Domain\Search\Theme\ViewModel\ThemeViewFactory;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ThemeManager
{
    /**
     * @var array<array-key, ThemeInterface>
     */
    private array $themes;

    /**
     * @param iterable<array-key,ThemeInterface> $themes
     */
    public function __construct(
        private readonly ThemeViewFactory $viewFactory,
        #[AutowireIterator('woo_platform.search.theme')]
        iterable $themes,
    ) {
        foreach ($themes as $theme) {
            $this->themes[$theme->getUrlName()] = $theme;
        }
    }

    public function getThemeByUrlName(string $name): ?ThemeInterface
    {
        return $this->themes[$name] ?? null;
    }

    public function getView(ThemeInterface $theme): Theme
    {
        return $this->viewFactory->make($theme);
    }

    /**
     * @return Generator<Theme>
     */
    public function getViewsForAllThemes(): Generator
    {
        foreach ($this->themes as $theme) {
            yield $this->viewFactory->make($theme);
        }
    }
}
