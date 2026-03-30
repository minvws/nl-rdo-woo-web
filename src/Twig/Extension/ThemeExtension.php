<?php

declare(strict_types=1);

namespace Shared\Twig\Extension;

use Generator;
use Shared\Domain\Search\Theme\ThemeManager;
use Shared\Domain\Search\Theme\ViewModel\Theme;
use Twig\Attribute\AsTwigFunction;

class ThemeExtension
{
    public function __construct(
        private readonly ThemeManager $themeManager,
    ) {
    }

    /**
     * @return Generator<Theme>
     */
    #[AsTwigFunction(name: 'all_themes')]
    public function getAllThemes(): Generator
    {
        return $this->themeManager->getViewsForAllThemes();
    }
}
