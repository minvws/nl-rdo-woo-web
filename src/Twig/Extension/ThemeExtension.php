<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Domain\Search\Theme\ThemeManager;
use App\Domain\Search\Theme\ViewModel\Theme;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeExtension extends AbstractExtension
{
    public function __construct(
        private readonly ThemeManager $themeManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('all_themes', [$this, 'getAllThemes']),
        ];
    }

    /**
     * @return \Generator<Theme>
     */
    public function getAllThemes(): \Generator
    {
        return $this->themeManager->getViewsForAllThemes();
    }
}
