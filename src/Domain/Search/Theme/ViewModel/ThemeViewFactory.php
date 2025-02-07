<?php

declare(strict_types=1);

namespace App\Domain\Search\Theme\ViewModel;

use App\Domain\Search\Theme\ThemeInterface;

readonly class ThemeViewFactory
{
    public function make(ThemeInterface $theme): Theme
    {
        return new Theme(
            $theme->getUrlName(),
            $theme->getMenuNameTranslationKey(),
            $theme->getPageTitleTranslationKey(),
            $theme->getPageTextTranslationKey(),
        );
    }
}
