<?php

declare(strict_types=1);

namespace Shared\Twig\Extension;

use Generator;
use Override;
use Shared\Domain\Search\Theme\ThemeManager;
use Shared\Domain\Search\Theme\ViewModel\Theme;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeExtension extends AbstractExtension
{
    public function __construct(
        private readonly ThemeManager $themeManager,
    ) {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('all_themes', $this->getAllThemes(...)),
        ];
    }

    /**
     * @return Generator<Theme>
     */
    public function getAllThemes(): Generator
    {
        return $this->themeManager->getViewsForAllThemes();
    }
}
